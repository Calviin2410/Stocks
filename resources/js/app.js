import './bootstrap';
import Chart from 'chart.js/auto';

        let selectedCategory = 'all';
        let searchText = '';
        let sortOrder = 'latest';
        let showingWatchlist = window.location.pathname === '/watchlist';
        let currentPage = 1;
        let stockChart = null;
        const itemsPerPage = 9;

        let watchlist = JSON.parse(localStorage.getItem('watchlist')) || [];

        const summaryContainer = document.getElementById('summary');
        const newsContainer = document.getElementById('news');
        const paginationContainer = document.getElementById('pagination');
        const economicCalendarContainer = document.getElementById('economicCalendar');
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const newsModal = document.getElementById('newsModal');
        const modalImage = document.getElementById('modalImage');
        const modalCategory = document.getElementById('modalCategory');
        const modalTitle = document.getElementById('modalTitle');
        const modalMeta = document.getElementById('modalMeta');
        const modalDescription = document.getElementById('modalDescription');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        const modalReadBtn = document.getElementById('modalReadBtn');
        const chartSymbol = document.getElementById('chartSymbol');
        const chartMessage = document.getElementById('chartMessage');
        const chatbotMessages = document.getElementById('chatbotMessages');
        const chatbotInput = document.getElementById('chatbotInput');
        const chatbotSendBtn = document.getElementById('chatbotSendBtn');
        const chatbotBox = document.getElementById('chatbotBox');
        const chatbotCloseBtn = document.getElementById('chatbotCloseBtn');
        const chatbotOpenBtn = document.getElementById('chatbotOpenBtn');

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char];
            });
        }

        function getPaginatedArticles(articles) {
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;

            return articles.slice(start, end);
        }

        function renderPagination(totalItems) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let buttons = '';

            buttons += `
                <button
                    class="pagination-btn"
                    ${currentPage === 1 ? 'disabled' : ''}
                    onclick="changePage(${currentPage - 1})"
                >
                    Previous
                </button>
            `;

            for (let page = 1; page <= totalPages; page++) {
                buttons += `
                    <button
                        class="pagination-btn ${page === currentPage ? 'active' : ''}"
                        onclick="changePage(${page})"
                    >
                        ${page}
                    </button>
                `;
            }

            buttons += `
                <button
                    class="pagination-btn"
                    ${currentPage === totalPages ? 'disabled' : ''}
                    onclick="changePage(${currentPage + 1})"
                >
                    Next
                </button>
            `;

            paginationContainer.innerHTML = buttons;
        }

        function changePage(page) {
            currentPage = page;
            loadNews();

            window.scrollTo({
                top: 520,
                behavior: 'smooth'
            });
        }

        function getDateTime(article) {
            return new Date(article.posted_at.replace(' ', 'T')).getTime();
        }

        function sortArticles(articles) {
            return [...articles].sort((a, b) => {
                const dateA = getDateTime(a);
                const dateB = getDateTime(b);

                if (sortOrder === 'oldest') {
                    return dateA - dateB;
                }

                return dateB - dateA;
            });
        }
        function openNewsModal(article) {
            modalImage.src = article.image;
            modalCategory.textContent = article.category;
            modalTitle.textContent = article.title;
            modalMeta.textContent = `${article.source_name} • ${article.posted_at}`;
            modalDescription.textContent = article.description || 'No description available for this news.';
            modalReadBtn.href = article.url || '#';

            newsModal.classList.add('show');
        }

        function closeNewsModal() {
            newsModal.classList.remove('show');
        }

        function saveWatchlist() {
            localStorage.setItem('watchlist', JSON.stringify(watchlist));
        }

        function toggleWatchlist(article) {
            const exists = watchlist.some(item => item.title === article.title);

            if (exists) {
                watchlist = watchlist.filter(item => item.title !== article.title);
            } else {
                watchlist.push(article);
            }

            saveWatchlist();
            loadNews();
        }

        function isInWatchlist(article) {
            return watchlist.some(item => item.title === article.title);
        }

        function addChatMessage(message, type) {
            if (!chatbotMessages) {
                return;
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'user' ? 'user-message' : 'bot-message';
            messageDiv.textContent = message;

            chatbotMessages.appendChild(messageDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        async function sendChatbotMessage() {
            if (!chatbotInput || !chatbotMessages) {
                return;
            }

            const message = chatbotInput.value.trim();

            if (!message) {
                return;
            }

            addChatMessage(message, 'user');
            chatbotInput.value = '';

            addChatMessage('Thinking...', 'bot');

            try {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content');

                const response = await fetch('/chatbot', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        message,
                    }),
                });

                const result = await response.json();

                chatbotMessages.lastChild.remove();

                if (!result.success) {
                    addChatMessage(result.message || 'Failed to get reply.', 'bot');
                    return;
                }

                addChatMessage(result.reply, 'bot');
            } catch (error) {
                chatbotMessages.lastChild.remove();
                addChatMessage('Something went wrong.', 'bot');
            }
        }

        if (chatbotSendBtn && chatbotInput) {
            chatbotSendBtn.addEventListener('click', sendChatbotMessage);

            chatbotInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    sendChatbotMessage();
                }
            });
        }

        if (chatbotCloseBtn && chatbotBox && chatbotOpenBtn) {
            chatbotCloseBtn.addEventListener('click', function () {
                chatbotBox.classList.add('hide');
                chatbotOpenBtn.classList.add('show');
            });

            chatbotOpenBtn.addEventListener('click', function () {
                chatbotBox.classList.remove('hide');
                chatbotOpenBtn.classList.remove('show');
            });
        }

        window.addEventListener('popstate', function () {
            showingWatchlist = window.location.pathname === '/watchlist';
            loadNews();
        });

        modalCloseBtn.addEventListener('click', closeNewsModal);

        newsModal.addEventListener('click', function (event) {
            if (event.target === newsModal) {
                closeNewsModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeNewsModal();
            }
        });
        async function loadSummary() {
             if (!economicCalendarContainer) {
                return;
            }

            try {
                const response = await fetch('/stock-summary');
                const result = await response.json();

                if (!result.success) {
                    summaryContainer.innerHTML = '';
                    return;
                }

                summaryContainer.innerHTML = result.data.map(item => `
                    <div class="summary-card">
                        <div class="summary-top">
                            <span>${escapeHtml(item.name)}</span>
                            <span>${escapeHtml(item.symbol)}</span>
                        </div>
                        <div class="summary-price">${escapeHtml(item.price)}</div>
                        <div class="${item.trend === 'up' ? 'change-up' : 'change-down'}">
                            ${escapeHtml(item.change)}
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                summaryContainer.innerHTML = '';
            }
        }
        async function loadEconomicCalendar() {
            if (!economicCalendarContainer) {
                return;
            }

            economicCalendarContainer.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Loading economic calendar...
                </div>
            `;

            try {
                const response = await fetch('/economic-calendar');
                const result = await response.json();

                if (!result.success) {
                    economicCalendarContainer.innerHTML = `
                        <div class="error-box">
                            Failed to load economic calendar.
                        </div>
                    `;
                    return;
                }

                const events = result.data
                    .filter(calendarEvent => ['High', 'Medium'].includes(calendarEvent.impact))
                    .slice(0, 8);

                if (events.length === 0) {
                    economicCalendarContainer.innerHTML = `
                        <div class="empty-box">
                            No economic events found.
                        </div>
                    `;
                    return;
                }

                economicCalendarContainer.innerHTML = `
                    <table class="calendar-table">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>Country</th>
                                <th>Event</th>
                                <th>Impact</th>
                                <th>Forecast</th>
                                <th>Previous</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${events.map(calendarEvent => {
                                const impactClass = calendarEvent.impact.toLowerCase();

                                return `
                                    <tr>
                                        <td>${new Date(calendarEvent.date).toLocaleString()}</td>
                                        <td>${escapeHtml(calendarEvent.country)}</td>
                                        <td>${escapeHtml(calendarEvent.title)}</td>
                                        <td>
                                            <span class="impact-badge impact-${impactClass}">
                                                ${escapeHtml(calendarEvent.impact)}
                                            </span>
                                        </td>
                                        <td>${escapeHtml(calendarEvent.forecast || '-')}</td>
                                        <td>${escapeHtml(calendarEvent.previous || '-')}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                `;
            } catch (error) {
                economicCalendarContainer.innerHTML = `
                    <div class="error-box">
                        Something went wrong while loading the economic calendar.
                    </div>
                `;
            }
        }

        async function loadChart(symbol = 'NVDA') {
            const chartCanvas = document.getElementById('stockChart');

            if (!chartCanvas || !chartMessage) {
                return;
            }

            chartMessage.innerHTML = '';

            try {
                const response = await fetch(`/stock-chart?symbol=${symbol}`);
                const result = await response.json();

                if (!result.success || !Array.isArray(result.data) || result.data.length === 0) {
                    if (stockChart) {
                        stockChart.destroy();
                        stockChart = null;
                    }

                    chartMessage.innerHTML = result.message || 'No chart data available.';
                    return;
                }

                const labels = result.data.map(item => item.date);
                const prices = result.data.map(item => item.close);

                const ctx = chartCanvas.getContext('2d');

                if (stockChart) {
                    stockChart.destroy();
                }

                stockChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: `${symbol} Closing Price`,
                                data: prices,
                                tension: 0.3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                    },
                });
            } catch (error) {
                chartMessage.innerHTML = 'Failed to load stock chart.';
                console.error('Chart loading failed:', error);
            }
        }

        async function loadNews() {
            newsContainer.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Loading stock news...
                </div>
            `;

            paginationContainer.innerHTML = '';

            try {
                let articles = [];

                if (showingWatchlist) {
                    articles = watchlist;
                } else {
                    const params = new URLSearchParams({
                        category: selectedCategory,
                        search: searchText,
                    });

                    const response = await fetch(`/stock-news?${params.toString()}`);
                    const result = await response.json();

                    if (!result.success) {
                        newsContainer.innerHTML = `
                            <div class="error-box">
                                Failed to load news.
                            </div>
                        `;
                        return;
                    }

                    articles = result.data;
                }

                if (searchText !== '') {
                    articles = articles.filter(article => {
                        return article.title.toLowerCase().includes(searchText.toLowerCase())
                            || article.source_name.toLowerCase().includes(searchText.toLowerCase())
                            || article.category.toLowerCase().includes(searchText.toLowerCase())
                            || (article.description || '').toLowerCase().includes(searchText.toLowerCase());
                    });
                }
                articles = sortArticles(articles);

                if (articles.length === 0) {
                    newsContainer.innerHTML = `
                        <div class="empty-box">
                            ${showingWatchlist ? 'No saved news in your watchlist.' : 'No stock news found.'}
                        </div>
                    `;
                    return;
                }

                const paginatedArticles = getPaginatedArticles(articles);

                newsContainer.innerHTML = `
                    <div class="news-grid">
                        ${paginatedArticles.map(article => `
                            <div class="card">
                                <img src="${escapeHtml(article.image)}" alt="Stock news image">
                                <div class="badge">${escapeHtml(article.category)}</div>
                                <div class="source">${escapeHtml(article.source_name)}</div>
                                <div class="title">${escapeHtml(article.title)}</div>
                                <div class="date">${escapeHtml(article.posted_at)}</div>

                                <button
                                    class="read-more-btn"
                                    onclick='openNewsModal(${JSON.stringify(article).replace(/'/g, "&apos;")})'
                                >
                                    Read more
                                </button>

                                <button
                                    class="watchlist-btn"
                                    onclick='toggleWatchlist(${JSON.stringify(article).replace(/'/g, "&apos;")})'
                                >
                                    ${isInWatchlist(article) ? 'Remove from Watchlist' : 'Add to Watchlist'}
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;

                renderPagination(articles.length);
            } catch (error) {
                newsContainer.innerHTML = `
                    <div class="error-box">
                        Something went wrong. Please try again later.
                    </div>
                `;
                paginationContainer.innerHTML = '';
            }
        }

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                selectedCategory = this.dataset.category;
                currentPage = 1;
                loadNews();
            });
        });

        if (chartSymbol) {
            chartSymbol.addEventListener('change', function () {
                loadChart(this.value);
            });
        }

        let searchTimer = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);

            searchTimer = setTimeout(() => {
                searchText = this.value.trim();
                currentPage = 1;
                loadNews();
            }, 300);
        });

        function showNews() {
            showingWatchlist = false;

            window.history.pushState({}, '', '/dashboard');

            loadNews();
        }

        function showWatchlist() {
            showingWatchlist = true;

            window.history.pushState({}, '', '/watchlist');

            loadNews();
        }

        function showMarket() {
            showingWatchlist = false;

            window.history.pushState({}, '', '/dashboard');

            loadNews();

            window.scrollTo({
                top: 180,
                behavior: 'smooth'
            });
        }
        sortSelect.addEventListener('change', function () {
            sortOrder = this.value;
            currentPage = 1;
            loadNews();
        });

        if (summaryContainer) {
            loadSummary();
        }

        if (economicCalendarContainer) {
            loadEconomicCalendar();
        }

        if (chartSymbol) {
            loadChart(chartSymbol.value || 'NVDA');
        }

        if (newsContainer) {
            loadNews();
        }

       setInterval(() => {
            if (summaryContainer) {
                loadSummary();
            }
        }, 60000);

        window.showMarket = showMarket;
        window.showNews = showNews;
        window.showWatchlist = showWatchlist;
        window.changePage = changePage;
        window.toggleWatchlist = toggleWatchlist;
        window.openNewsModal = openNewsModal;