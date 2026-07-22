@extends('layouts.app')

@section('title', 'Stock News')

@section('content')
    <div class="container">
        <div class="news-page-header" style="display: block;">
            <h1>Latest Stock News</h1>
            <p>Search, filter and save the latest market news.</p>
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
                    class="search-input news-search-input"
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
        MarketLens Assistant
    </button>

    <div id="chatbotBox" class="chatbot-box hide">
        <div class="chatbot-header">
            <span>Stock Assistant</span>
            <button id="chatbotCloseBtn" class="chatbot-close-btn">×</button>
        </div>

        <div id="chatbotMessages" class="chatbot-messages">
            <div class="bot-message">Hi, I’m your MarketLens assistant. Ask me about market news, stock strategy, or your personal investment profile.</div>
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