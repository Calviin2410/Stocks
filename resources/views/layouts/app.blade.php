<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stock Dashboard')</title>

    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>
    <div class="navbar">
        <a href="/dashboard" class="navbar-title">
            Stock Dashboard
        </a>

        <div class="navbar-links">
            <a href="/dashboard">Market</a>
            <a href="/news">News</a>
            <a href="/watchlist">Watchlist</a>
            <a href="/mbti">MBTI Quiz</a>

            @auth
                <span class="navbar-user">
                    {{ Auth::user()->username }}
                </span>

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