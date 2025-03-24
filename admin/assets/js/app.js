document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar dropdown functionality
    const submenuItems = document.querySelectorAll('.has-submenu');

    submenuItems.forEach(item => {
        item.addEventListener('click', function (e) {
            // Only toggle if clicking on the main menu item, not submenu links
            if (e.target.closest('a') === this.querySelector('a')) {
                e.preventDefault();
                this.classList.toggle('active');
                const submenu = this.querySelector('.submenu');
                submenu.classList.toggle('show');
            }
        });
    });

    // Dynamic search functionality
    const searchInput = document.getElementById('search-input');
    const searchResults = document.querySelector('.search-results');

    // Add debounce to avoid excessive database queries
    function debounce(func, timeout = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    // Search function
    async function performSearch() {
        const query = searchInput.value.toLowerCase().trim();

        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }

        try {
            // Fetch results from server-side API
            // const response = await fetch(`./api_search.php?query=${encodeURIComponent(query)}`);
            const response = await fetch(`/admin/search.php?query=${encodeURIComponent(query)}`);

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            displayResults(data);
        } catch (error) {
            console.error('Error fetching search results:', error);
            searchResults.innerHTML = '<div class="result-item">An error occurred. Please try again.</div>';
            searchResults.classList.add('show');
        }
    }

    // Display search results
    function displayResults(data) {
        // Clear previous results
        searchResults.innerHTML = '';

        let resultsFound = false;

        // Process tracks (music)
        if (data.tracks && data.tracks.length) {
            resultsFound = true;
            data.tracks.forEach(track => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.innerHTML = `
                <img src="${track.thumbnail_path || '/placeholder/40/40'}" alt="${track.title}">
                <div class="result-details">
                    <h4>${track.title}</h4>
                    <p>${track.artist_name} · ${formatTime(track.duration)}</p>
                </div>
                <span class="result-category">Track</span>
            `;
                resultItem.addEventListener('click', () => navigateTo(`/music/${track.id}`));
                searchResults.appendChild(resultItem);
            });
        }

        // Process artists
        if (data.artists && data.artists.length) {
            resultsFound = true;
            data.artists.forEach(artist => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.innerHTML = `
                <img src="${artist.image || '/placeholder/40/40'}" alt="${artist.name}">
                <div class="result-details">
                    <h4>${artist.name}</h4>
                    <p>${artist.track_count || 0} tracks</p>
                </div>
                <span class="result-category">Artist</span>
            `;
                resultItem.addEventListener('click', () => navigateTo(`/artists/${artist.id}`));
                searchResults.appendChild(resultItem);
            });
        }

        // Process albums
        if (data.albums && data.albums.length) {
            resultsFound = true;
            data.albums.forEach(album => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.innerHTML = `
                <img src="${album.cover_image || '/placeholder/40/40'}" alt="${album.title}">
                <div class="result-details">
                    <h4>${album.title}</h4>
                    <p>${album.artist_name} · ${album.release_year}</p>
                </div>
                <span class="result-category">Album</span>
            `;
                resultItem.addEventListener('click', () => navigateTo(`/albums/${album.id}`));
                searchResults.appendChild(resultItem);
            });
        }

        // Process videos
        if (data.videos && data.videos.length) {
            resultsFound = true;
            data.videos.forEach(video => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.innerHTML = `
                <img src="${video.thumbnail || '/placeholder/40/40'}" alt="${video.title}">
                <div class="result-details">
                    <h4>${video.title}</h4>
                    <p>${video.artist_name} · ${formatTime(video.duration)}</p>
                </div>
                <span class="result-category">Video</span>
            `;
                resultItem.addEventListener('click', () => navigateTo(`/videos/${video.id}`));
                searchResults.appendChild(resultItem);
            });
        }

        // Process users
        if (data.users && data.users.length) {
            resultsFound = true;
            data.users.forEach(user => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.innerHTML = `
                <img src="${user.profile_picture || '/placeholder/40/40'}" alt="${user.name}">
                <div class="result-details">
                    <h4>${user.name}</h4>
                    <p>${user.username}</p>
                </div>
                <span class="result-category">User</span>
            `;
                resultItem.addEventListener('click', () => navigateTo(`/users/${user.id}`));
                searchResults.appendChild(resultItem);
            });
        }

        if (resultsFound) {
            searchResults.classList.add('show');
        } else {
            searchResults.innerHTML = '<div class="result-item">No results found</div>';
            searchResults.classList.add('show');
        }
    }

    // Helper function to format time (duration)
    function formatTime(timeString) {
        // Convert MySQL time format (HH:MM:SS) to minutes:seconds
        const timeParts = timeString.split(':');
        const hours = parseInt(timeParts[0]);
        const minutes = parseInt(timeParts[1]);
        const seconds = parseInt(timeParts[2]);

        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        } else {
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    // Navigation function
    function navigateTo(url) {
        window.location.href = url;
    }

    // Setup event listeners
    const debouncedSearch = debounce(performSearch, 300);
    searchInput.addEventListener('input', debouncedSearch);

    // Close search results when clicking outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.search-container')) {
            searchResults.classList.remove('show');
        }
    });
    
    // 3. Dynamic Music Play Analytics Chart
    const playAnalyticsCtx = document.getElementById('playAnalyticsChart').getContext('2d');

    // Mock data that would come from SQL in a real application
    const playAnalyticsData = {
        today: {
            labels: ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
            datasets: [{
                label: 'Plays',
                data: [42, 15, 8, 29, 85, 120, 180, 135],
                borderColor: '#00c6ff',
                backgroundColor: 'rgba(0, 198, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Likes',
                data: [18, 5, 2, 14, 35, 55, 75, 50],
                borderColor: '#ff00aa',
                backgroundColor: 'rgba(255, 0, 170, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        week: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Plays',
                data: [580, 620, 540, 680, 720, 850, 780],
                borderColor: '#00c6ff',
                backgroundColor: 'rgba(0, 198, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Likes',
                data: [220, 240, 200, 250, 310, 380, 340],
                borderColor: '#ff00aa',
                backgroundColor: 'rgba(255, 0, 170, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        month: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Plays',
                data: [4200, 4800, 5100, 5400],
                borderColor: '#00c6ff',
                backgroundColor: 'rgba(0, 198, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Likes',
                data: [1800, 2100, 2300, 2400],
                borderColor: '#ff00aa',
                backgroundColor: 'rgba(255, 0, 170, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        year: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Plays',
                data: [18500, 19200, 21000, 22400, 24100, 25800, 27200, 28500, 29700, 31000, 32300, 33600],
                borderColor: '#00c6ff',
                backgroundColor: 'rgba(0, 198, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Likes',
                data: [8200, 8500, 9100, 9800, 10500, 11200, 12000, 12800, 13500, 14200, 14800, 15400],
                borderColor: '#ff00aa',
                backgroundColor: 'rgba(255, 0, 170, 0.1)',
                tension: 0.4,
                fill: true
            }]
        }
    };

    // Initialize with monthly data
    let playAnalyticsChart = new Chart(playAnalyticsCtx, {
        type: 'line',
        data: playAnalyticsData.month,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)',
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    });

    // Handle time period selection for Play Analytics
    document.querySelectorAll('.dropdown-content a[data-period]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const period = this.getAttribute('data-period');
            const dropdownBtn = this.closest('.dropdown').querySelector('.dropbtn');

            // Update button text
            dropdownBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';

            // Update chart data
            playAnalyticsChart.data = playAnalyticsData[period];
            playAnalyticsChart.update();
        });
    });

    // 4. Dynamic Genre Chart with SQL data integration
    const genreChartCtx = document.getElementById('genreChart').getContext('2d');

    // This simulates data that would be fetched from SQL in a real application
    // SQL query would be something like:
    // SELECT genre, COUNT(*) as track_count, SUM(play_count) as total_plays
    // FROM tracks
    // GROUP BY genre
    // ORDER BY total_plays DESC
    // LIMIT 6;
    const genreData = {
        today: {
            labels: ['Electronic', 'Hip Hop', 'Pop', 'Rock', 'R&B', 'Jazz'],
            datasets: [{
                label: 'Plays by Genre',
                data: [285, 210, 180, 120, 90, 60],
                backgroundColor: [
                    '#00c6ff', '#ff00aa', '#8a2be2', '#ff9900', '#00ffaa', '#ff5500'
                ],
                borderWidth: 0
            }]
        },
        week: {
            labels: ['Electronic', 'Hip Hop', 'Pop', 'Rock', 'R&B', 'Jazz'],
            datasets: [{
                label: 'Plays by Genre',
                data: [1850, 1420, 1150, 820, 640, 420],
                backgroundColor: [
                    '#00c6ff', '#ff00aa', '#8a2be2', '#ff9900', '#00ffaa', '#ff5500'
                ],
                borderWidth: 0
            }]
        },
        month: {
            labels: ['Electronic', 'Hip Hop', 'Pop', 'Rock', 'R&B', 'Jazz'],
            datasets: [{
                label: 'Plays by Genre',
                data: [7500, 5800, 4300, 3200, 2500, 1800],
                backgroundColor: [
                    '#00c6ff', '#ff00aa', '#8a2be2', '#ff9900', '#00ffaa', '#ff5500'
                ],
                borderWidth: 0
            }]
        },
        year: {
            labels: ['Electronic', 'Hip Hop', 'Pop', 'Rock', 'R&B', 'Jazz'],
            datasets: [{
                label: 'Plays by Genre',
                data: [82500, 65400, 52300, 41200, 35500, 22800],
                backgroundColor: [
                    '#00c6ff', '#ff00aa', '#8a2be2', '#ff9900', '#00ffaa', '#ff5500'
                ],
                borderWidth: 0
            }]
        }
    };

    // Initialize with monthly data
    let genreChart = new Chart(genreChartCtx, {
        type: 'doughnut',
        data: genreData.month,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)',
                        font: {
                            size: 12
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value.toLocaleString()} plays (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '0%'
        }
    });

    // Handle time period selection for Genre Chart
    const genreDropdowns = document.querySelectorAll('.card-header:nth-of-type(2) .dropdown-content a[data-period]');
    genreDropdowns.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const period = this.getAttribute('data-period');
            const dropdownBtn = this.closest('.dropdown').querySelector('.dropbtn');

            // Update button text
            dropdownBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';

            // Update chart data
            genreChart.data = genreData[period];
            genreChart.update();
        });
    });

    // 5. Sidebar toggle functionality
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    toggleSidebar.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });

    // 6. User dropdown toggle
    const navUser = document.querySelector('.nav-user');
    const userDropdown = document.querySelector('.user-dropdown');

    navUser.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('show');
    });

    // Close user dropdown when clicking outside
    document.addEventListener('click', function () {
        userDropdown.classList.remove('show');
    });

    // 7. Implement SQL-based search suggestion functionality
    // In a real application, this would use AJAX to query a SQL database
    // SQL example:
    /*
    SELECT 'track' as type, name, artist as subtitle, image 
    FROM tracks 
    WHERE name LIKE '%?%' OR artist LIKE '%?%'
    UNION
    SELECT 'artist' as type, name, CONCAT(track_count, ' tracks') as subtitle, image 
    FROM artists 
    WHERE name LIKE '%?%'
    UNION
    SELECT 'album' as type, name, artist as subtitle, image 
    FROM albums 
    WHERE name LIKE '%?%' OR artist LIKE '%?%'
    UNION
    SELECT 'user' as type, name, email as subtitle, image 
    FROM users 
    WHERE name LIKE '%?%' OR email LIKE '%?%'
    LIMIT 10;
    */

    // 8. Result item click handlers (navigation)
    document.addEventListener('click', function (e) {
        const resultItem = e.target.closest('.result-item');
        if (resultItem) {
            // In a real app, this would navigate to the corresponding entity page
            // For demonstration, just log the click
            console.log('Clicked on result item:', resultItem.querySelector('h4').textContent);
            searchResults.classList.remove('show');
            searchInput.value = '';
        }
    });
});