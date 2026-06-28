<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class StockDashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
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
    - Do not tell the user to buy, sell, or hold any stock.
    - Do not provide financial advice.
    - If the answer depends on recent information, use Google Search grounding.
    - Keep the answer short and clear.

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
}