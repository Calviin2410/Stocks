<!DOCTYPE html>
<html>
<head>
    <title>Stock News Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="navbar">
        <div class="navbar-title">Stock Dashboard</div>
        <div class="navbar-links">
            <span onclick="showMarket()">Market</span>
            <span onclick="showNews()">News</span>
            <span onclick="showWatchlist()">Watchlist</span>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Stock News Dashboard</h1>
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
</body>
</html>