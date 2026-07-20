<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MarketLens')</title>

    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>
    <div class="navbar">
        <a href="/dashboard" class="navbar-title">
            MarketLens
        </a>

        <div class="navbar-links">
            <a href="/dashboard">Market</a>
            <a href="/news">News</a>
            <a href="/watchlist">Watchlist</a>
            <a href="/mbti">MBTI Quiz</a>

            @if (auth()->user()->profile?->hasActiveSubscription())
                <a href="{{ route('tutorial.index') }}">
                    Tutorial
                </a>
            @endif

            @auth
                @if (auth()->user()->profile?->hasActiveSubscription())
                    <a
                        href="{{ route('subscription.show') }}"
                        class="premium-nav-badge"
                    >
                        Premium
                    </a>
                @else
                    <a
                        href="{{ route('subscription.show') }}"
                        class="subscribe-nav-btn"
                    >
                        Subscribe
                    </a>
                @endif

                <a href="{{ route('profile.edit') }}" class="navbar-user-link">
                    {{ auth()->user()->username }}

                    @if (auth()->user()->profile?->mbti_type)
                        | {{ auth()->user()->profile->mbti_type }}
                    @endif
                </a>

                <form method="POST" action="/logout" class="logout-form">
                    @csrf

                    <button type="submit" class="logout-btn">
                        Logout
                    </button>
                </form>
            @endauth
        </div>
    </div>

    @yield('content')
    @if (session('show_investment_notice'))
        <div id="investmentNoticeModal" class="investment-notice-overlay">
            <div class="investment-notice-box">
                <h2>Investment Risk Reminder</h2>

                <p>
                    Investment involves risk. Please make your own decision carefully.
                </p>

                <p class="investment-notice-small">
                    The information in this dashboard is for educational and analysis purposes only.
                </p>

                <button id="investmentNoticeCloseBtn" class="investment-notice-btn">
                    I Understand
                </button>
            </div>
        </div>
    @endif

    <script>
        const investmentNoticeModal = document.getElementById('investmentNoticeModal');
        const investmentNoticeCloseBtn = document.getElementById('investmentNoticeCloseBtn');

        if (investmentNoticeModal && investmentNoticeCloseBtn) {
            investmentNoticeCloseBtn.addEventListener('click', function () {
                investmentNoticeModal.style.display = 'none';
            });
        }
    </script>
    @stack('scripts')
</body>
</html>