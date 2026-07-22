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
                            'image' => !empty($article['image'])
                                ? $article['image']
                                : 'https://images.unsplash.com/photo-1640340434855-6084b1f4901c',
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
            $question = trim((string) $request->input('message', ''));

            if ($question === '') {
                return response()->json([
                    'success' => false,
                    'source' => 'gemini',
                    'message' => 'Message is required.',
                ]);
            }

            $lowerQuestion = mb_strtolower($question);

            /*
            * Casual greeting: do not force investment analysis.
            */
            $isGreetingOnly = preg_match('/^(hi|hello|hey|halo|hihi|yo|你好|嗨|哈喽)$/iu', trim($question));

            if ($isGreetingOnly) {
                return response()->json([
                    'success' => true,
                    'reply' => "Hi! I am MarketLens Stock Assistant. You can ask me about latest stock news, stock strategy, MBTI-based investment style, or Premium salary-based risk analysis.",
                    'used_google_search' => false,
                ]);
            }

            /** @var \App\Models\User|null $user */
            $user = Auth::user();

            $profile = $user?->profile;
            $mbtiType = $profile?->mbti_type;
            $salary = $profile?->salary;
            $isPremium = $profile?->hasActiveSubscription() ?? false;

            $mbtiAdviceStyle = $this->getMbtiAdviceStyle($mbtiType);
            $investmentProfile = $this->getMbtiInvestmentStrategy($mbtiType);

            $salaryRiskProfile = $isPremium
                ? $this->getSalaryRiskProfile($salary)
                : 'Salary-based risk analysis is only available for Premium users.';

            /*
            * Intent detection.
            */
            $personalKeywords = [
                'personal',
                'mbti',
                'strategy',
                'profile',
                'salary',
                'risk style',
                'investment style',
                'my information',
                'my info',
            ];

            $stockKeywords = [
                'buy',
                'sell',
                'hold',
                'wait',
                'avoid',
                'stock',
                'share',
                'market',
                'invest',
                'investment',
                'nvidia',
                'nvda',
                'apple',
                'aapl',
                'tesla',
                'tsla',
                'spy',
                's&p',
                'honda',
                'toyota',
                'microsoft',
                'google',
                'amazon',
                'meta',
                'bitcoin',
                'crypto',
            ];

            $onlineKeywords = [
                'latest',
                'today',
                'current',
                'recent',
                'online',
                'news',
                'breaking',
                'earnings',
                'announcement',
                'update',
            ];

            $casualKeywords = [
                'what can you do',
                'help',
                'how to use',
                'who are you',
                '功能',
                '可以做什么',
            ];

            $isPersonalQuestion = collect($personalKeywords)->contains(function ($keyword) use ($lowerQuestion) {
                return str_contains($lowerQuestion, $keyword);
            });

            $isStockQuestion = collect($stockKeywords)->contains(function ($keyword) use ($lowerQuestion) {
                return str_contains($lowerQuestion, $keyword);
            });

            $isOnlineQuestion = collect($onlineKeywords)->contains(function ($keyword) use ($lowerQuestion) {
                return str_contains($lowerQuestion, $keyword);
            });

            $isCasualQuestion = collect($casualKeywords)->contains(function ($keyword) use ($lowerQuestion) {
                return str_contains($lowerQuestion, $keyword);
            });

            /*
            * Local personal strategy.
            * This does not call Gemini, so it saves quota.
            */
            if ($isPersonalQuestion && !$isOnlineQuestion) {
                return response()->json([
                    'success' => true,
                    'reply' => "
    Profile Summary:
    Your MBTI risk style is {$investmentProfile['risk_style']}.

    Suggested Strategy:
    {$investmentProfile['strategy']}

    Risk Reminder:
    {$investmentProfile['warning']}

    MBTI Tip:
    {$mbtiAdviceStyle}

    Salary Risk:
    {$salaryRiskProfile}

    Sources:
    This answer is based on your saved profile information.

    Note:
    Investment involves risk. Please make your own decision carefully.
    ",
                    'used_google_search' => false,
                ]);
            }

            /*
            * Casual help question.
            */
            if ($isCasualQuestion && !$isStockQuestion && !$isOnlineQuestion) {
                return response()->json([
                    'success' => true,
                    'reply' => "I can help you with stock news, simple Buy/Hold/Wait/Sell signals, MBTI-based investment style, and Premium salary-based risk analysis. For example, you can ask: \"based on my MBTI, give me a strategy\" or \"latest Honda news\".",
                    'used_google_search' => false,
                ]);
            }

            /*
            * Normal news analysis.
            * This uses Google News RSS + Laravel rule-based signal.
            * It does NOT call Gemini, so it does not use Gemini token or quota.
            */
            if ($isOnlineQuestion) {
                $rssNews = $this->fetchGoogleNewsRss($question);

                if (!empty($rssNews)) {
                    $signalData = $this->getNewsBasedSignal($rssNews);

                    $newsList = collect($rssNews)
                        ->take(5)
                        ->map(function ($item, $index) {
                            $number = $index + 1;

                            $title = e($item['title']);
                            $source = e($item['source']);
                            $link = e($item['link']);

                            return "{$number}. {$title}\n"
                                . "Source: {$source}\n"
                                . "<a href=\"{$link}\" target=\"_blank\" rel=\"noopener noreferrer\">Open article</a>";
                        })
                        ->implode("\n\n");

                    return response()->json([
                        'success' => true,
                        'reply' => trim("
            Signal:
            {$signalData['signal']}

            Suggested Action:
            {$signalData['action']}

            Reason:
            {$signalData['reason']}

            Related News:
            {$newsList}

            Note:
            Investment involves risk. Please make your own decision carefully.
            "),
                        'used_google_search' => false,
                        'source' => 'google_news_rss_rule_based',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'reply' => trim("
            Signal:
            Wait

            Suggested Action:
            I could not find enough related news right now. Wait for clearer information before making a decision.

            Note:
            Investment involves risk. Please make your own decision carefully.
            "),
                    'used_google_search' => false,
                    'source' => 'google_news_rss_rule_based',
                ]);
            }
            /*
            * Only fetch dashboard news when the question is related to stocks/news.
            */
            $newsContext = 'No dashboard news context is needed for this question.';

            if ($isStockQuestion || $isOnlineQuestion) {
                $newsResponse = $this->news(new Request([
                    'category' => 'all',
                    'search' => '',
                ]));

                $newsData = $newsResponse->getData(true)['data'] ?? [];

                $newsContext = collect($newsData)
                    ->take(8)
                    ->map(function ($item) {
                        return "- Title: " . ($item['title'] ?? '-') . "\n"
                            . "  Source: " . ($item['source_name'] ?? '-') . "\n"
                            . "  Summary: " . ($item['description'] ?? '-') . "\n"
                            . "  Link: " . ($item['url'] ?? '-');
                    })
                    ->implode("\n\n");

                if ($newsContext === '') {
                    $newsContext = 'No stock news is currently available from the dashboard.';
                }
            }

            /*
            * Use Google Search only for latest/current/news questions.
            */
            $useGoogleSearch = false;

                    
        $answerInstruction = <<<'TEXT'
        Answer style:
        - If the user is chatting casually, reply naturally and briefly.
        - If the user asks about personal MBTI, salary, risk profile, or investment strategy, use the Personal Strategy Format.
        - If the user asks about a stock, company news, buy, sell, wait, hold, or market movement, use the Stock Analysis Format.
        - Do not force stock analysis for casual messages.

        Personal Strategy Format:
        Profile Summary: 1 short sentence.
        Suggested Strategy: 1 to 3 simple sentences.
        Risk Reminder: 1 short risk reminder.
        MBTI Tip: 1 short sentence.
        Salary Risk: 1 short sentence.
        Note: Investment involves risk. Please make your own decision carefully.

        Stock Analysis Format:
        Signal: Buy / Hold / Wait / Avoid / Sell
        Suggested Action: Give a simple educational action, such as consider buying slowly, hold existing position, wait for confirmation, or avoid for now.
        Reason: 1 to 3 simple sentences.
        Risk: 1 short risk.
        Position Reminder: If Premium user, suggest a cautious position size based on salary risk. Do not suggest using a large part of salary.
        MBTI Tip: 1 short sentence if relevant.
        Salary Risk: 1 short sentence if Premium user.
        Sources: Provide relevant links if available.
        Note: Investment involves risk. Please make your own decision carefully.
        TEXT;

            $prompt = "
    You are MarketLens, a stock market dashboard assistant.

    Main rules:
    - Answer in simple English.
    - Use dashboard news context when it is relevant.
    - If the user asks about latest, current, recent, online, or company-specific news that is not in the dashboard context, use Google Search if available.
    - Include relevant source links when online information is used.
    - You may give a simple educational signal: Buy, Hold, Wait, Avoid, or Sell.
    - Do not say the user must buy or must sell.
    - Do not promise profit.
    - Always include risks.
    - Keep the answer short and clear.

    Signal meaning:
    - Buy: news sentiment looks clearly positive.
    - Hold: news is mixed or uncertain.
    - Wait: not enough confirmation yet.
    - Avoid: risk looks too high for new entry.
    - Sell: news sentiment looks clearly negative or downside risk is strong.

    User profile:
    - MBTI Type: " . ($mbtiType ?? 'Unknown') . "
    - MBTI Advice Style: {$mbtiAdviceStyle}
    - Premium User: " . ($isPremium ? 'Yes' : 'No') . "
    - Monthly Salary: " . ($isPremium ? ('RM' . ($salary ?? 'Unknown')) : 'Hidden for non-premium user') . "
    - Salary Risk Profile: {$salaryRiskProfile}

    Investment profile based on MBTI:
    - Risk Style: {$investmentProfile['risk_style']}
    - Suggested Strategy: {$investmentProfile['strategy']}
    - Risk Reminder: {$investmentProfile['warning']}

    Personalization rules:
    - Use MBTI only to adjust explanation style and risk reminder.
    - If Premium User is Yes, include salary-based risk analysis.
    - If Premium User is No, mention that salary-based analysis is available for Premium users.
    - Do not make decisions only based on MBTI or salary.

    {$answerInstruction}

    Dashboard news context:
    {$newsContext}

    User question:
    {$question}
    ";

            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.5,
                    'maxOutputTokens' => 700,
                ],
            ];

            if ($useGoogleSearch) {
                $requestBody['tools'] = [
                    [
                        'google_search' => (object) [],
                    ],
                ];
            }

            $geminiModel = config('services.gemini.model', 'gemini-3.5-flash');

            $response = Http::timeout(40)
                ->acceptJson()
                ->withHeaders([
                    'x-goog-api-key' => config('services.gemini.key'),
                ])
                ->post(
                    rtrim(config('services.gemini.base_url'), '/') . "/models/{$geminiModel}:generateContent",
                    $requestBody
                );

        if ($response->status() === 404) {
            return response()->json([
                'success' => true,
                'reply' => trim("
        I’m unable to connect to the AI model right now.

        Please check the Gemini model setting in the system configuration.

        Note:
        Investment involves risk. Please make your own decision carefully.
        "),
                'used_google_search' => $useGoogleSearch,
            ]);
        }

        if ($response->status() === 429 || $response->status() === 503) {
            if ($isOnlineQuestion) {
                $rssNews = $this->fetchGoogleNewsRss($question);

                if (!empty($rssNews)) {
                    $signalData = $this->getNewsBasedSignal($rssNews);

                    $newsList = collect($rssNews)
                        ->take(5)
                        ->map(function ($item, $index) {
                            $number = $index + 1;

                            $title = e($item['title']);
                            $source = e($item['source']);
                            $link = e($item['link']);

                            return "{$number}. {$title}\n"
                                . "Source: {$source}\n"
                                . "<a href=\"{$link}\" target=\"_blank\" rel=\"noopener noreferrer\">Open article</a>";
                        })
                        ->implode("\n\n");

                    return response()->json([
                        'success' => true,
                        'reply' => trim("
                        Signal:
                        {$signalData['signal']}

                        Suggested Action:
                        {$signalData['action']}

                        Related News:
                        {$newsList}

                        Note:
                        Investment involves risk. Please make your own decision carefully.
                    "),
                        'used_google_search' => false,
                        'fallback_source' => 'google_news_rss',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'reply' => trim("
        I’m unable to retrieve online AI analysis right now.

        You can still ask me about your MBTI investment style or Premium salary-based risk profile.

        Note:
        Investment involves risk. Please make your own decision carefully.
        "),
                'used_google_search' => $useGoogleSearch,
            ]);
        }

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'source' => 'gemini',
                    'message' => 'Gemini chatbot request failed. Status: ' . $response->status(),
                    'debug' => $response->json(),
                ]);
            }

            $data = $response->json();

            $answer = $data['candidates'][0]['content']['parts'][0]['text']
                ?? 'Sorry, I cannot generate an answer right now.';

            $groundingChunks = $data['candidates'][0]['groundingMetadata']['groundingChunks'] ?? [];

            $sourceLinks = collect($groundingChunks)
                ->map(function ($chunk) {
                    $web = $chunk['web'] ?? null;

                    if (!$web || empty($web['uri'])) {
                        return null;
                    }

                    $title = $web['title'] ?? 'Source';
                    $uri = $web['uri'];

                    return "- {$title}: {$uri}";
                })
                ->filter()
                ->unique()
                ->take(5)
                ->values()
                ->implode("\n");

            if ($sourceLinks !== '' && !str_contains(strtolower($answer), 'sources:')) {
                $answer .= "\n\nSources:\n" . $sourceLinks;
            }

            return response()->json([
                'success' => true,
                'reply' => $answer,
                'used_google_search' => $useGoogleSearch,
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
                    'company_name' => 'MarketLens',
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


            $investmentProfile = $this->getMbtiInvestmentStrategy($mbtiType);

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
                'investment_profile' => $investmentProfile,
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

    private function getMbtiInvestmentStrategy(?string $mbtiType): array
    {
        if (!$mbtiType || strlen($mbtiType) !== 4) {
            return [
                'risk_style' => 'Unknown',
                'strategy' => 'Complete the MBTI test first to receive a personalized investment style.',
                'warning' => 'Investment involves risk. Please make your own decision carefully.',
            ];
        }

        $type = strtoupper($mbtiType);

        $riskScore = 0;
        $notes = [];

        if ($type[0] === 'E') {
            $riskScore += 1;
            $notes[] = 'You may react more to market sentiment and discussion.';
        } else {
            $notes[] = 'You may prefer to analyze quietly before making decisions.';
        }

        if ($type[1] === 'N') {
            $riskScore += 1;
            $notes[] = 'You may focus on future trends and growth stories.';
        } else {
            $riskScore -= 1;
            $notes[] = 'You may prefer concrete facts, stable data, and proven performance.';
        }

        if ($type[2] === 'T') {
            $riskScore += 1;
            $notes[] = 'You may make decisions using logic and comparison.';
        } else {
            $riskScore -= 1;
            $notes[] = 'You may be more affected by stress, fear, or market emotions.';
        }

        if ($type[3] === 'P') {
            $riskScore += 1;
            $notes[] = 'You may prefer flexibility, but should avoid impulsive entries.';
        } else {
            $notes[] = 'You may prefer structured plans and clear rules.';
        }

        if ($riskScore <= -1) {
            return [
                'risk_style' => 'Conservative',
                'strategy' => 'You may be more suitable for a cautious approach. Focus on stable assets, diversification, and avoid high-risk or hype-based trades.',
                'warning' => 'Avoid putting too much money into one stock. Consider learning with small amounts or paper trading first.',
                'notes' => $notes,
            ];
        }

        if ($riskScore <= 1) {
            return [
                'risk_style' => 'Balanced',
                'strategy' => 'You may be suitable for a balanced approach. Combine stable investments with limited exposure to growth stocks.',
                'warning' => 'Use clear risk limits and avoid making decisions based only on short-term news.',
                'notes' => $notes,
            ];
        }

        return [
            'risk_style' => 'Growth-Oriented',
            'strategy' => 'You may be comfortable with higher-growth opportunities, but you should still control position size and avoid overconfidence.',
            'warning' => 'High-growth stocks can be volatile. Do not rely only on positive news or hype.',
            'notes' => $notes,
        ];
    }

    private function getSalaryRiskProfile($salary): string
    {
        if (!$salary || $salary <= 0) {
            return 'Salary is not available. Give general risk-aware analysis only.';
        }

        if ($salary < 2500) {
            return 'The user may have lower income flexibility. Suggest a conservative approach, avoid high-risk concentration, and explain that small learning amounts or paper trading may be safer.';
        }

        if ($salary < 6000) {
            return 'The user may have moderate income flexibility. Suggest a balanced approach, diversification, and clear risk limits.';
        }

        if ($salary < 12000) {
            return 'The user may have stronger income flexibility. Suggest balanced-to-growth exposure, but still remind about position sizing and volatility.';
        }

        return 'The user may have higher income flexibility. Suggest more flexible growth-oriented strategies, but warn against overconfidence and large single-stock concentration.';
    }

    private function fetchGoogleNewsRss(string $question): array
    {
        try {
            $query = strtolower(trim($question));

            $query = str_replace([
                'give me',
                'any news about',
                'news about',
                'latest news',
                'latest',
                'current',
                'today',
                'recent',
                'online',
                'hi',
                'hello',
                'please',
            ], '', $query);

            $query = trim($query);

            if ($query === '') {
                $query = 'stock market news';
            }

            $rssUrl = 'https://news.google.com/rss/search?q='
                . urlencode($query . ' stock news')
                . '&hl=en-MY&gl=MY&ceid=MY:en';

            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                ])
                ->get($rssUrl);

            if ($response->failed()) {
                return [];
            }

            $xml = simplexml_load_string($response->body());

            if (!$xml || !isset($xml->channel->item)) {
                return [];
            }

            $items = [];

            foreach ($xml->channel->item as $item) {
                $items[] = [
                    'title' => html_entity_decode((string) $item->title),
                    'link' => (string) $item->link,
                    'source' => isset($item->source)
                        ? (string) $item->source
                        : 'Google News',
                    'published_at' => (string) $item->pubDate,
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getNewsBasedSignal(array $newsItems): array
    {
        $titles = collect($newsItems)
            ->pluck('title')
            ->implode(' ');

        $text = strtolower($titles);

        $negativeWords = [
            'falls',
            'fall',
            'drops',
            'drop',
            'dips',
            'dip',
            'loss',
            'losses',
            'weak',
            'slump',
            'lower',
            'down',
            'concern',
            'risk',
            'miss',
            'soft quarter',
            'retreat',
            'decline',
            'pressure',
            'cuts',
            'cut',
        ];

        $positiveWords = [
            'rises',
            'rise',
            'gains',
            'gain',
            'up',
            'higher',
            'profit',
            'growth',
            'strong',
            'beats',
            'record',
            'surge',
            'positive',
            'upgrade',
            'expands',
            'rebound',
            'improves',
            'boost',
        ];

        $negativeScore = collect($negativeWords)
            ->filter(function ($word) use ($text) {
                return str_contains($text, $word);
            })
            ->count();

        $positiveScore = collect($positiveWords)
            ->filter(function ($word) use ($text) {
                return str_contains($text, $word);
            })
            ->count();

        if ($negativeScore > $positiveScore) {
            return [
                'signal' => 'Wait',
                'action' => 'Do not buy immediately. Wait for clearer confirmation because the recent news tone looks cautious.',
                'reason' => 'The related headlines contain more negative words such as drop, dip, loss, weak, or concern.',
            ];
        }

        if ($positiveScore > $negativeScore) {
            return [
                'signal' => 'Hold',
                'action' => 'The news tone looks slightly positive, but check the company fundamentals before buying.',
                'reason' => 'The related headlines contain more positive words such as growth, profit, rise, strong, or upgrade.',
            ];
        }

        return [
            'signal' => 'Wait',
            'action' => 'The news tone is mixed or unclear. Wait for more confirmation before making a decision.',
            'reason' => 'The related headlines do not show a clearly positive or negative direction.',
        ];
    }
}