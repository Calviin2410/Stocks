<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json',
            ])
                ->timeout(20)
                ->get('https://nfs.faireconomy.media/ff_calendar_thisweek.json');

            if ($response->failed()) {
                throw new \Exception(
                    'Forex Factory request failed. Status: ' . $response->status()
                );
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
}