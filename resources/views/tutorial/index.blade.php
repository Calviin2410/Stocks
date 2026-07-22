@extends('layouts.app')

@section('title', 'Stock Tutorial')

@section('content')
    <div class="container">
        <div class="tutorial-hero">
            <span class="tutorial-badge">Premium Tutorial</span>

            <h1>Stock Market Beginner Tutorial</h1>

            <p>
                Learn the basic meaning of stocks, how the stock market works,
                and what beginners should understand before investing.
            </p>
        </div>

        <div class="tutorial-grid">
            <div class="tutorial-video-card">
                <h2>Watch: What is a Stock?</h2>

                <p>
                    This video introduces the basic concept of stocks and how companies
                    raise money through the stock market.
                </p>

                <div class="tutorial-video-wrapper">
                    <iframe
                        src="https://www.youtube.com/embed/p7HKvqRI_Bo"
                        title="Stock Market Tutorial"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>

                <small>
                    You can replace this YouTube video ID with any stock tutorial video you prefer.
                </small>
            </div>

            <div class="tutorial-info-card">
                <h2>What is a Stock?</h2>

                <p>
                    A stock represents ownership in a company. When you buy a stock,
                    you own a small part of that company. If the company performs well,
                    the stock price may increase. If the company performs poorly,
                    the stock price may decrease.
                </p>

                <h3>Why do people buy stocks?</h3>

                <ul>
                    <li>To grow their money over time</li>
                    <li>To receive dividends from some companies</li>
                    <li>To invest in companies they believe will grow</li>
                </ul>

                <h3>Beginner Reminder</h3>

                <p>
                    Stock prices can move up and down. Do not invest only because of hype,
                    social media, or short-term news.
                </p>

                <a
                    href="https://www.google.com/search?q=what+is+a+stock"
                    target="_blank"
                    class="tutorial-google-btn"
                >
                    Read More on Google
                </a>
            </div>
        </div>

        <div class="tutorial-section">
            <h2>Simple Example</h2>

            <div class="tutorial-example-box">
                <p>
                    Example: If a company has 1,000 shares and you own 10 shares,
                    you own 1% of that company.
                </p>

                <p>
                    If the company becomes more valuable, your shares may become more valuable.
                    But if the company loses value, your shares may also drop.
                </p>
            </div>
        </div>

        <div class="tutorial-section">
            <h2>Important Risk Reminder</h2>

            <div class="tutorial-risk-box">
                Investment involves risk. Please make your own decision carefully.
                This tutorial is for learning purposes only and is not financial advice.
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