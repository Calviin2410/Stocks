<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class StockDashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function watchlistPage()
    {
        return view('watchlist');
    }

    public function newsPage()
    {
        return view('news');
    }

    public function mbtiPage()
    {
        return view('mbti');
    }

    public function summary(): JsonResponse
    {
        try {
            $stocks = [
                [
                    'name' => 'Nvidia',
                    'symbol' => 'NVDA',
                ],
                [
                    'name' => 'Apple',
                    'symbol' => 'AAPL',
                ],
                [
                    'name' => 'Tesla',
                    'symbol' => 'TSLA',
                ],
                [
                    'name' => 'S&P 500 ETF',
                    'symbol' => 'SPY',
                ],
            ];

            $summary = collect($stocks)
                ->map(function ($stock) {
                    $response = Http::timeout(15)
                        ->acceptJson()
                        ->get(config('services.finnhub.base_url') . '/quote', [
                            'symbol' => $stock['symbol'],
                            'token' => config('services.finnhub.key'),
                        ]);

                    if ($response->failed()) {
                        throw new \Exception('Finnhub quote API failed. Status: ' . $response->status());
                    }

                    $data = $response->json();

                    $currentPrice = (float) ($data['c'] ?? 0);
                    $changePercent = (float) ($data['dp'] ?? 0);

                    return [
                        'name' => $stock['name'],
                        'symbol' => $stock['symbol'],
                        'price' => '$' . number_format($currentPrice, 2),
                        'change' => ($changePercent >= 0 ? '+' : '') . number_format($changePercent, 2) . '%',
                        'trend' => $changePercent >= 0 ? 'up' : 'down',
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'source' => 'finnhub',
                'data' => $summary,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'source' => 'finnhub',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function news(Request $request): JsonResponse
    {
        try {
            $category = strtolower($request->query('category', 'all'));
            $search = strtolower($request->query('search', ''));

            $symbolMap = [
                'nvidia' => 'NVDA',
                'apple' => 'AAPL',
                'tesla' => 'TSLA',
            ];

            $from = now()->subDays(30)->toDateString();
            $to = now()->toDateString();

            $symbols = $category === 'all' || $category === 'market'
                ? ['NVDA', 'AAPL', 'TSLA']
                : [$symbolMap[$category] ?? 'NVDA'];

            $articles = collect();

            foreach ($symbols as $symbol) {
                $response = Http::timeout(15)
                    ->acceptJson()
                    ->get(config('services.finnhub.base_url') . '/company-news', [
                        'symbol' => $symbol,
                        'from' => $from,
                        'to' => $to,
                        'token' => config('services.finnhub.key'),
                    ]);

                if ($response->failed()) {
                    throw new \Exception('Finnhub company news API failed. Status: ' . $response->status());
                }

                $data = collect($response->json())
                    ->map(function ($article) use ($symbol) {
                        return [
                            'title' => $article['headline'] ?? 'No title',
                            'source_name' => $article['source'] ?? 'Unknown source',
                            'posted_at' => isset($article['datetime'])
                                ? \Carbon\Carbon::createFromTimestamp($article['datetime'])->toDateTimeString()
                                : now()->toDateTimeString(),
                            'image' => $article['image'] ?: 'https://images.unsplash.com/photo-1640340434855-6084b1f4901c',
                            'url' => $article['url'] ?? '#',
                            'category' => $this->symbolToCategory($symbol),
                            'description' => $article['summary'] ?? 'No description available for this news.',
                        ];
                    });

                $articles = $articles->merge($data);
            }

            $articles = $articles
                ->when($search !== '', function ($collection) use ($search) {
                    return $collection->filter(function ($article) use ($search) {
                        return str_contains(strtolower($article['title']), $search)
                            || str_contains(strtolower($article['source_name']), $search)
                            || str_contains(strtolower($article['category']), $search)
                            || str_contains(strtolower($article['description']), $search);
                    });
                })
                ->sortByDesc('posted_at')
                ->take(45)
                ->values();

            return response()->json([
                'success' => true,
                'source' => 'finnhub_company_news',
                'data' => $articles,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'source' => 'finnhub_company_news',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function economicCalendar(): JsonResponse
    {
        $cacheKey = 'forex_factory_economic_calendar';

        try {
            $cachedEvents = Cache::get($cacheKey);

            if ($cachedEvents) {
                return response()->json([
                    'success' => true,
                    'source' => 'forex_factory_cache',
                    'data' => $cachedEvents,
                ]);
            }

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json',
            ])
                ->timeout(20)
                ->get('https://nfs.faireconomy.media/ff_calendar_thisweek.json');

            if ($response->failed()) {
                throw new \Exception('Failed to fetch Forex Factory calendar.');
            }

            $events = collect($response->json())
                ->map(function ($event) {
                    return [
                        'title' => $event['title'] ?? 'Untitled event',
                        'country' => $event['country'] ?? '-',
                        'date' => $event['date'] ?? now()->toDateTimeString(),
                        'impact' => $event['impact'] ?? 'Low',
                        'forecast' => $event['forecast'] ?? '',
                        'previous' => $event['previous'] ?? '',
                        'source_name' => 'Forex Factory',
                        'category' => 'calendar',
                    ];
                })
                ->values();

            Cache::put($cacheKey, $events, now()->addMinutes(30));

            return response()->json([
                'success' => true,
                'source' => 'forex_factory',
                'data' => $events,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'source' => 'forex_factory',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    private function symbolToCategory(string $symbol): string
    {
        return match (strtoupper($symbol)) {
            'NVDA' => 'nvidia',
            'AAPL' => 'apple',
            'TSLA' => 'tesla',
            default => 'market',
        };
    }

    public function chart(Request $request): JsonResponse
    {
        try {
            $symbol = strtoupper($request->query('symbol', 'NVDA'));

            $allowedSymbols = ['NVDA', 'AAPL', 'TSLA', 'SPY'];

            if (!in_array($symbol, $allowedSymbols)) {
                $symbol = 'NVDA';
            }

            $cacheKey = 'stock_chart_' . $symbol;

            $cachedChart = Cache::get($cacheKey);

            if ($cachedChart) {
                return response()->json([
                    'success' => true,
                    'source' => 'alpha_vantage_cache',
                    'symbol' => $symbol,
                    'data' => $cachedChart,
                ]);
            }

            $response = Http::timeout(20)
                ->acceptJson()
                ->get(config('services.alpha_vantage.base_url'), [
                    'function' => 'TIME_SERIES_DAILY',
                    'symbol' => $symbol,
                    'outputsize' => 'compact',
                    'apikey' => config('services.alpha_vantage.key'),
                ]);

            if ($response->failed()) {
                throw new \Exception('Alpha Vantage chart API failed. Status: ' . $response->status());
            }

            $json = $response->json();

            if (isset($json['Error Message'])) {
                throw new \Exception($json['Error Message']);
            }

            if (isset($json['Note'])) {
                throw new \Exception($json['Note']);
            }

            $timeSeries = $json['Time Series (Daily)'] ?? null;

            if (!$timeSeries || !is_array($timeSeries)) {
                throw new \Exception('No chart data available from Alpha Vantage.');
            }

            $chartData = collect($timeSeries)
                ->map(function ($item, $date) {
                    return [
                        'date' => $date,
                        'close' => (float) ($item['4. close'] ?? 0),
                    ];
                })
                ->sortBy('date')
                ->take(-30)
                ->values();

            Cache::put($cacheKey, $chartData, now()->addMinutes(30));

            return response()->json([
                'success' => true,
                'source' => 'alpha_vantage',
                'symbol' => $symbol,
                'data' => $chartData,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'source' => 'alpha_vantage',
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function chatbot(Request $request): JsonResponse
    {
        try {
            $question = trim($request->input('message', ''));

            $user = Auth::user();
            $mbtiType = $user?->profile?->mbti_type;
            $mbtiAdviceStyle = $this->getMbtiAdviceStyle($mbtiType);

            if ($question === '') {
                return response()->json([
                    'success' => false,
                    'source' => 'gemini',
                    'message' => 'Message is required.',
                ]);
            }

            $newsResponse = $this->news(new Request([
                'category' => 'all',
                'search' => '',
            ]));

            $newsData = $newsResponse->getData(true)['data'] ?? [];

            $newsContext = collect($newsData)
                ->take(8)
                ->map(function ($article) {
                    return "- {$article['title']} ({$article['source_name']}, {$article['posted_at']}): {$article['description']}";
                })
                ->implode("\n");

            if ($newsContext === '') {
                $newsContext = 'No stock news is currently available from the dashboard.';
            }

            $prompt = "
    You are a stock market dashboard assistant.

    Rules:
    - Answer in simple English.
    - You may use the dashboard news context and Google Search when needed.
    - You may provide news-based analysis, sentiment, risks, and possible market impact.
    - You may give a simple educational signal: Buy, Hold, Wait, Avoid, or Sell.
    - Use Buy when the news sentiment looks clearly positive.
    - Use Hold when the news sentiment is mixed or uncertain.
    - Use Wait when there is not enough clear positive signal yet.
    - Use Avoid when the stock looks too risky to enter based on the news.
    - Use Sell when the news sentiment looks clearly negative or there are strong downside risks.
    - Do not say 'you must buy' or 'you must sell'.
    - Keep the answer short and clear.
    User MBTI profile:
    - MBTI Type: " . ($mbtiType ?? 'Unknown') . "
    - Advice style: {$mbtiAdviceStyle}

    When answering, personalize the explanation based on the user's MBTI type.
    Do not make investment decisions only based on MBTI.
    Use MBTI only to adjust the advice style, risk reminder, and explanation tone.

    Answer format:
    Signal: Buy / Hold / Wait / Avoid / Sell
    Reason: One to three simple sentences.
    Risk: One short risk.
    MBTI Tip: One short sentence based on the user's MBTI type.
    Note: Investment involves risk. Please make your own decision carefully.

    Dashboard stock news context:
    {$newsContext}

    User question:
    {$question}
    ";

            $response = Http::withHeaders([
                'x-goog-api-key' => config('services.gemini.key'),
            ])
                ->timeout(30)
                ->acceptJson()
                ->post(
                    config('services.gemini.base_url') . '/interactions',
                    [
                        'model' => 'gemini-2.5-flash',
                        'input' => $prompt,
                        'tools' => [
                            [
                                'type' => 'google_search',
                            ],
                        ],
                    ]
                );

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'source' => 'gemini',
                    'message' => 'Gemini chatbot request failed. Status: ' . $response->status(),
                    'debug' => $response->json(),
                ]);
            }

            $data = $response->json();

            $reply = collect($data['steps'] ?? [])
                ->where('type', 'model_output')
                ->flatMap(function ($step) {
                    return $step['content'] ?? [];
                })
                ->where('type', 'text')
                ->pluck('text')
                ->implode("\n\n");

            if ($reply === '') {
                $reply = $data['output_text'] ?? 'No reply generated.';
            }

            return response()->json([
                'success' => true,
                'source' => 'gemini',
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'source' => 'gemini',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function createDevilAiTest()
    {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->get(rtrim(config('services.devil_ai.base_url'), '/') . '/new_test', [
                    'api_key' => trim(config('services.devil_ai.key')),
                    'return_url' => url('/mbti'),
                    'company_name' => 'Stock Dashboard',
                    'completed_message' => 'Your MBTI test is completed. Please return to the dashboard to check your result.',
                    'ask_gender' => '0',
                    'ask_age' => '0',
                    'lang' => 'en',
                ]);

            $data = $response->json();
            $meta = $data['meta'] ?? [];

            if (!$response->successful() || ($meta['success'] ?? false) !== true) {
                return response()->json([
                    'success' => false,
                    'message' => $meta['message'] ?? 'Failed to create MBTI test.',
                    'debug' => $data,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Devil.ai API connection error.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkDevilAiTest(Request $request)
    {
        $validated = $request->validate([
            'test_id' => ['required', 'string'],
        ]);

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->get(rtrim(config('services.devil_ai.base_url'), '/') . '/check_test', [
                    'api_key' => trim(config('services.devil_ai.key')),
                    'test_id' => $validated['test_id'],
                ]);

            $data = $response->json();
            $meta = $data['meta'] ?? [];

            if (!$response->successful() || ($meta['success'] ?? false) !== true) {
                return response()->json([
                    'success' => false,
                    'message' => $meta['message'] ?? 'Failed to check MBTI result.',
                    'debug' => $data,
                ], 500);
            }

            if (empty($data['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your test is not completed yet, or this test ID does not match the completed test.',
                    'debug' => $data,
                ], 200);
            }

            $mbtiData = $data['data'];
            $mbtiType = $mbtiData['prediction'] ?? null;

            if ($mbtiType && Auth::check()) {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'mbti_type' => $mbtiType,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'data' => $mbtiData,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Devil.ai result API error.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    private function getMbtiAdviceStyle(?string $mbtiType): string
    {
        if (!$mbtiType || strlen($mbtiType) !== 4) {
            return 'No MBTI type is available yet. Give balanced and general risk-aware advice.';
        }

        $type = strtoupper($mbtiType);
        $tips = [];

        $tips[] = match ($type[0]) {
            'I' => 'The user may prefer clear, quiet, detailed analysis before making decisions.',
            'E' => 'The user may respond well to market sentiment, news flow, and social discussion.',
            default => '',
        };

        $tips[] = match ($type[1]) {
            'S' => 'Focus more on concrete data, price movement, facts, and recent news.',
            'N' => 'Focus more on big-picture trends, future possibilities, and long-term themes.',
            default => '',
        };

        $tips[] = match ($type[2]) {
            'T' => 'Use logical reasoning, risk-reward thinking, and objective comparison.',
            'F' => 'Include emotional discipline reminders and avoid panic or hype-based decisions.',
            default => '',
        };

        $tips[] = match ($type[3]) {
            'J' => 'Suggest a structured plan, clear entry/exit thinking, and risk control.',
            'P' => 'Suggest flexibility, waiting for confirmation, and avoiding impulsive trades.',
            default => '',
        };

        return implode(' ', array_filter($tips));
    }
}