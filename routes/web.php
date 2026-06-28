<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockDashboardController;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [StockDashboardController::class, 'index']);
Route::get('/watchlist', [StockDashboardController::class, 'index']);
Route::get('/stock-summary', [StockDashboardController::class, 'summary']);
Route::get('/stock-news', [StockDashboardController::class, 'news']);
Route::get('/economic-calendar', [StockDashboardController::class, 'economicCalendar']);
Route::get('/stock-chart', [StockDashboardController::class, 'chart']);
Route::post('/chatbot', [StockDashboardController::class, 'chatbot']);