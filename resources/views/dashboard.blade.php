@extends('layouts.app')

@section('title', 'Stock News Dashboard')

@section('content')
    <div class="container">
        <div class="header">
            <h1>MarketLens Dashboard</h1>
            <p>Track stock market updates and latest financial news.</p>
        </div>

        <div id="summary" class="summary-grid"></div>

        <div class="calendar-section">
            <div class="calendar-header">
                <div>
                    <h2>Economic Calendar</h2>
                    <p>Upcoming economic events from Forex Factory</p>
                </div>
            </div>

            <div id="economicCalendar" class="calendar-box">
                Loading economic calendar...
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-header">
                <h2>Stock Price Trend</h2>

                <select id="chartSymbol" class="chart-select">
                    <option value="NVDA">Nvidia</option>
                    <option value="AAPL">Apple</option>
                    <option value="TSLA">Tesla</option>
                    <option value="SPY">S&P 500 ETF</option>
                </select>
            </div>

            <div class="chart-box">
                <canvas id="stockChart"></canvas>
                <div id="chartMessage" class="chart-message"></div>
            </div>
        </div>

        <div class="toolbar">
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all">All</button>
                <button class="filter-btn" data-category="nvidia">Nvidia</button>
                <button class="filter-btn" data-category="apple">Apple</button>
                <button class="filter-btn" data-category="tesla">Tesla</button>
                <button class="filter-btn" data-category="market">Market</button>
            </div>

            <div class="toolbar-right">
                <input
                    id="searchInput"
                    class="search-input"
                    type="text"
                    placeholder="Search stock news..."
                >

                <select id="sortSelect" class="sort-select">
                    <option value="latest">Latest</option>
                    <option value="oldest">Oldest</option>
                </select>
            </div>
        </div>

        <div id="news"></div>
        <div id="pagination" class="pagination"></div>
    </div>

    <div id="newsModal" class="modal-overlay">
        <div class="modal-box">
            <img id="modalImage" class="modal-image" src="" alt="News image">

            <div class="modal-content">
                <div id="modalCategory" class="modal-category"></div>
                <div id="modalTitle" class="modal-title"></div>
                <div id="modalMeta" class="modal-meta"></div>
                <div id="modalDescription" class="modal-description"></div>

                <div class="modal-actions">
                    <button id="modalCloseBtn" class="modal-close-btn">Close</button>
                    <a id="modalReadBtn" class="modal-read-btn" href="#" target="_blank">
                        Open Article
                    </a>
                </div>
            </div>
        </div>
    </div>

    <button id="chatbotOpenBtn" class="chatbot-open-btn show">
        Stock Assistant
    </button>

    <div id="chatbotBox" class="chatbot-box hide">
        <div class="chatbot-header">
            <span>Stock Assistant</span>
            <button id="chatbotCloseBtn" class="chatbot-close-btn">×</button>
        </div>

        <div id="chatbotMessages" class="chatbot-messages">
            <div class="bot-message">
                Hi, ask me about the latest stock news.
            </div>
        </div>

        <div class="chatbot-input-row">
            <input
                id="chatbotInput"
                class="chatbot-input"
                type="text"
                placeholder="Ask about Nvidia, Apple, Tesla..."
            >

            <button id="chatbotSendBtn" class="chatbot-send-btn">
                Send
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush