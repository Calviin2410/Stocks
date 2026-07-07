<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockDashboardController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard')->with('show_investment_notice', true);
    }

    return redirect('/login');
});

Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout']);


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [StockDashboardController::class, 'index']);
    Route::get('/news', [StockDashboardController::class, 'newsPage']);
    Route::get('/watchlist', [StockDashboardController::class, 'watchlistPage']);
    Route::get('/mbti', [StockDashboardController::class, 'mbtiPage']);

    Route::get('/stock-summary', [StockDashboardController::class, 'summary']);
    Route::get('/stock-news', [StockDashboardController::class, 'news']);
    Route::get('/economic-calendar', [StockDashboardController::class, 'economicCalendar']);
    Route::get('/stock-chart', [StockDashboardController::class, 'chart']);

    Route::post('/chatbot', [StockDashboardController::class, 'chatbot']);

    Route::post('/mbti/new-test', [StockDashboardController::class, 'createDevilAiTest']);
    Route::get('/mbti/check-test', [StockDashboardController::class, 'checkDevilAiTest']);
});