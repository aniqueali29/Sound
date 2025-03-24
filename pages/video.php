<?php
include '../includes/config_db.php'; 
require_once '../layout/header.php';


// Function to get average rating for a video
function getAverageRating($conn, $videoId) {
    $sql = "SELECT AVG(rating) as avg_rating FROM ratings WHERE item_id = ? AND item_type = 'video'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['avg_rating'] ? number_format($row['avg_rating'], 1) : '0.0';
}

// Function to format duration
function formatDuration($time) {
    $timeObj = new DateTime($time);
    return $timeObj->format('i:s');
}

// Function to format views
function formatViews($views) {
    if ($views >= 1000000) {
        return round($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return round($views / 1000, 1) . 'K';
    } else {
        return $views;
    }
}

// Get filters
$albumFilter = isset($_GET['album']) ? $_GET['album'] : '';
$artistFilter = isset($_GET['artist']) ? $_GET['artist'] : '';
$yearFilter = isset($_GET['year']) ? $_GET['year'] : '';
$genreFilter = isset($_GET['genre']) ? $_GET['genre'] : '';
$languageFilter = isset($_GET['language']) ? $_GET['language'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch all filter options for dropdown population
$albumsQuery = "SELECT DISTINCT a.title, a.id FROM albums a WHERE a.deleted_at IS NULL ORDER BY a.title";
$artistsQuery = "SELECT id, name FROM artists ORDER BY name";
$yearsQuery = "SELECT DISTINCT release_year FROM videos ORDER BY release_year DESC";
$genresQuery = "SELECT id, name FROM genres ORDER BY name";
$languagesQuery = "SELECT id, name FROM languages ORDER BY name";
$albumsResult = $conn->query($albumsQuery);
$artistsResult = $conn->query($artistsQuery);
$yearsResult = $conn->query($yearsQuery);
$genresResult = $conn->query($genresQuery);
$languagesResult = $conn->query($languagesQuery);

// Fetch all genre categories for category chips
$categoriesQuery = "SELECT id, name FROM genres ORDER BY name ";
$categoriesResult = $conn->query($categoriesQuery);

// Build query for videos based on filters
$sql = "SELECT v.*, a.name as artist_name, g.name as genre_name 
        FROM videos v 
        LEFT JOIN artists a ON v.artist_id = a.id 
        LEFT JOIN genres g ON v.genre_id = g.id 
        LEFT JOIN albums al ON v.album_id = al.id 
        LEFT JOIN languages l ON v.language_id = l.id 
        WHERE v.deleted_at IS NULL";

$params = [];
$types = "";

if (!empty($albumFilter)) {
    $sql .= " AND v.album_id = ?";
    $params[] = $albumFilter;
    $types .= "i";
}

if (!empty($artistFilter)) {
    $sql .= " AND v.artist_id = ?";
    $params[] = $artistFilter;
    $types .= "i";
}

if (!empty($yearFilter)) {
    $sql .= " AND v.release_year = ?";
    $params[] = $yearFilter;
    $types .= "i";
}

if (!empty($genreFilter)) {
    $sql .= " AND v.genre_id = ?";
    $params[] = $genreFilter;
    $types .= "i";
}

if (!empty($languageFilter)) {
    $sql .= " AND v.language_id = ?";
    $params[] = $languageFilter;
    $types .= "i";
}

if (!empty($searchTerm)) {
    $sql .= " AND (v.title LIKE ? OR a.name LIKE ? OR g.name LIKE ?)";
    $searchParam = "%" . $searchTerm . "%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($categoryFilter) && $categoryFilter != 'All') {
    $sql .= " AND g.name = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

// Add ordering
$sql .= " ORDER BY v.is_featured DESC, v.is_new DESC, v.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$videosResult = $stmt->get_result();

// Get featured videos
$featuredVideosQuery = "SELECT v.*, a.name as artist_name, g.name as genre_name 
                      FROM videos v 
                      LEFT JOIN artists a ON v.artist_id = a.id 
                      LEFT JOIN genres g ON v.genre_id = g.id 
                      WHERE v.is_featured = 1 AND v.deleted_at IS NULL 
                      ORDER BY v.created_at DESC 
                      LIMIT 6";
$featuredVideosResult = $conn->query($featuredVideosQuery);

// Get new releases videos
$newVideosQuery = "SELECT v.*, a.name as artist_name, g.name as genre_name 
                 FROM videos v 
                 LEFT JOIN artists a ON v.artist_id = a.id 
                 LEFT JOIN genres g ON v.genre_id = g.id 
                 WHERE v.is_new = 1 AND v.deleted_at IS NULL 
                 ORDER BY v.created_at DESC 
                 LIMIT 6";
$newVideosResult = $conn->query($newVideosQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUND Group - Video Archive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/video.css">
</head>

<body>
        <!-- Second Navbar -->
        <nav class="navbarstwo">
        <div class="nav-container">
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="../index.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="music.php">
                        <i class="fa-solid fa-music"></i>
                        <span>Music</span>
                    </a>
                </li>
                <li class="nav-item  active">
                    <a href="video.php">
                        <i class="fa-solid fa-video"></i>
                        <span>Video</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="albums.php">
                        <i class="fa-solid fa-record-vinyl"></i>
                        <span>Albums</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="particles"></div>

    <div class="container" style="margin-top:100px;">
        <h1 class="main-title">VIDEO ARCHIVE</h1>
        <p class="subtitle">Explore the Universe of Moving Pictures</p>

        <div class="search-container">
            <form method="GET" action="" id="filter-form">
                <div class="filter-row">
                    <select class="filter-dropdown" name="album" id="album-filter">
                        <option value="" selected>Album</option>
                        <?php while ($album = $albumsResult->fetch_assoc()): ?>
                        <option value="<?= $album['id'] ?>" <?= ($albumFilter == $album['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($album['title']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <select class="filter-dropdown" name="artist" id="artist-filter">
                        <option value="" selected>Artist</option>
                        <?php while ($artist = $artistsResult->fetch_assoc()): ?>
                        <option value="<?= $artist['id'] ?>" <?= ($artistFilter == $artist['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($artist['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <select class="filter-dropdown" name="year" id="year-filter">
                        <option value="" selected>Year</option>
                        <?php while ($year = $yearsResult->fetch_assoc()): ?>
                        <option value="<?= $year['release_year'] ?>"
                            <?= ($yearFilter == $year['release_year']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($year['release_year']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <select class="filter-dropdown" name="genre" id="genre-filter">
                        <option value="" selected>Genre</option>
                        <?php while ($genre = $genresResult->fetch_assoc()): ?>
                        <option value="<?= $genre['id'] ?>" <?= ($genreFilter == $genre['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($genre['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <select class="filter-dropdown" name="language" id="language-filter">
                        <option value="" selected>Language</option>
                        <?php while ($language = $languagesResult->fetch_assoc()): ?>
                        <option value="<?= $language['id'] ?>"
                            <?= ($languageFilter == $language['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($language['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-group">
                    <input type="text" class="form-control search-input" name="search"
                        placeholder="Search for videos..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="search-button" type="submit">Search</button>
                </div>
            </form>
        </div>

        <!-- Mobile Filter Toggle Button -->
        <button class="d-md-none mobile-filter-toggle" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#mobileFilter" aria-controls="mobileFilter">
            <div class="filter-icon">
                <div class="pulse"></div>
                <svg viewBox="0 0 24 24">
                    <path d="M10 18h4v-2h-4v2zm-7-10v2h18v-2h-18zm3 6h12v-2h-12v2z" />
                </svg>
            </div>
        </button>

        <!-- Offcanvas Filter Section -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileFilter" aria-labelledby="mobileFilterLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileFilterLabel">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"
                    style="filter: invert(1);"></button>
            </div>
            <div class="offcanvas-body">
                <form method="GET" action="" id="mobile-filter-form">
                    <div class="filter-options">
                        <select id="album-filter-mobile" name="album" class="filter-dropdown">
                            <option value="">Album</option>
                            <?php 
                            $albumsResult->data_seek(0);
                            while ($album = $albumsResult->fetch_assoc()): 
                            ?>
                            <option value="<?= $album['id'] ?>" <?= ($albumFilter == $album['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($album['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <select id="artist-filter-mobile" name="artist" class="filter-dropdown">
                            <option value="">Artist</option>
                            <?php 
                            $artistsResult->data_seek(0);
                            while ($artist = $artistsResult->fetch_assoc()): 
                            ?>
                            <option value="<?= $artist['id'] ?>"
                                <?= ($artistFilter == $artist['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($artist['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <select id="year-filter-mobile" name="year" class="filter-dropdown">
                            <option value="">Year</option>
                            <?php 
                            $yearsResult->data_seek(0);
                            while ($year = $yearsResult->fetch_assoc()): 
                            ?>
                            <option value="<?= $year['release_year'] ?>"
                                <?= ($yearFilter == $year['release_year']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['release_year']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <select id="genre-filter-mobile" name="genre" class="filter-dropdown">
                            <option value="">Genre</option>
                            <?php 
                            $genresResult->data_seek(0);
                            while ($genre = $genresResult->fetch_assoc()): 
                            ?>
                            <option value="<?= $genre['id'] ?>" <?= ($genreFilter == $genre['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($genre['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <select id="language-filter-mobile" name="language" class="filter-dropdown">
                            <option value="">Language</option>
                            <?php 
                            $languagesResult->data_seek(0);
                            while ($language = $languagesResult->fetch_assoc()): 
                            ?>
                            <option value="<?= $language['id'] ?>"
                                <?= ($languageFilter == $language['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($language['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="search-bar">
                        <input type="text" id="search-input-mobile" name="search" class="search-input"
                            placeholder="Search for videos..." value="<?= htmlspecialchars($searchTerm) ?>">
                        <button id="search-btn-mobile" class="search-button" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="category-chips">
            <a href="?category=All"
                class="category-chip <?= empty($categoryFilter) || $categoryFilter == 'All' ? 'active' : '' ?>">All</a>
            <?php 
            $categoriesResult->data_seek(0);
            while ($category = $categoriesResult->fetch_assoc()): 
            ?>
            <a href="?category=<?= urlencode($category['name']) ?>"
                class="category-chip <?= $categoryFilter == $category['name'] ? 'active' : '' ?>">
                <?= htmlspecialchars($category['name']) ?>
            </a>
            <?php endwhile; ?>
        </div>

                <!-- All Videos Section (Based on Filters) -->
                <div class="featured-section">
            <h2 class="section-title">All Videos<?= !empty($searchTerm) ? ' - Search Results' : '' ?></h2>
            <div class="video-grid">
                <?php 
                if ($videosResult->num_rows > 0):
                    while ($video = $videosResult->fetch_assoc()): 
                        $rating = getAverageRating($conn, $video['id']);
                        $formattedViews = formatViews($video['views']);
                        $formattedDuration = formatDuration($video['duration']);
                ?>
                <div class="video-card" onclick="openVideoPage(<?= $video['id'] ?>)">
                    <div class="video-thumbnail-container">
                        <img src="<?= htmlspecialchars(str_replace(["../../../", "../../"], "../",$video['thumbnail'])) ?>"
                            alt="<?= htmlspecialchars($video['title']) ?>" class="video-thumbnail">
                        <div class="video-duration"><?= htmlspecialchars($formattedDuration) ?></div>
                        <div class="video-play-button"></div>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                        <div class="video-artist"><?= htmlspecialchars($video['artist_name']) ?></div>
                        <div class="video-year-genre"><?= htmlspecialchars($video['release_year']) ?> •
                            <?= htmlspecialchars($video['genre_name']) ?></div>
                        <div class="video-views"><?= htmlspecialchars($formattedViews) ?> views • <?= htmlspecialchars($video['likes']) ?> likes</div>
                        <div class="rating">
                            <?= htmlspecialchars($rating) ?> <br>
                            <span style="font-size: 14px; color: gray;">RATING</span>
                        </div>
                        <div class="video-menu" data-video-id="<?= $video['id'] ?>">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                <div class="no-results">
                    <p>No videos found matching your criteria.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Releases Section -->
        <div class="featured-section">
            <h2 class="section-title">New Releases</h2>
            <div class="video-grid">
                <?php while ($video = $newVideosResult->fetch_assoc()): 
                        $rating = getAverageRating($conn, $video['id']);
                        $formattedViews = formatViews($video['views']);
                        $formattedDuration = formatDuration($video['duration']);
                    ?>
                <div class="video-card" onclick="openVideoPage(<?= $video['id'] ?>)">
                    <div class="video-thumbnail-container">
                        <img src="<?= htmlspecialchars(str_replace(["../../../", "../../"], "../",$video['thumbnail'])) ?>"
                            alt="<?= htmlspecialchars($video['title']) ?>" class="video-thumbnail">
                        <div class="video-duration"><?= htmlspecialchars($formattedDuration) ?></div>
                        <div class="video-play-button"></div>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                        <div class="video-artist"><?= htmlspecialchars($video['artist_name']) ?></div>
                        <div class="video-year-genre"><?= htmlspecialchars($video['release_year']) ?> •
                            <?= htmlspecialchars($video['genre_name']) ?></div>
                            <div class="video-views"><?= htmlspecialchars($formattedViews) ?> views • <?= htmlspecialchars($video['likes']) ?> likes</div>
                        <div class="rating">
                            <?= htmlspecialchars($rating) ?> <br>
                            <span style="font-size: 14px; color: gray;">RATING</span>
                        </div>
                        <div class="video-menu" data-video-id="<?= $video['id'] ?>">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Featured Videos Section -->
        <div class="featured-section">
            <h2 class="section-title">Featured Videos</h2>
            <div class="video-grid">
                <?php while ($video = $featuredVideosResult->fetch_assoc()): 
                    $rating = getAverageRating($conn, $video['id']);
                    $formattedViews = formatViews($video['views']);
                    $formattedDuration = formatDuration($video['duration']);
                ?>
                <div class="video-card" onclick="openVideoPage(<?= $video['id'] ?>)">
                    <div class="video-thumbnail-container">
                        <img src="<?= htmlspecialchars(str_replace(["../../../", "../../"], "../",$video['thumbnail'])) ?>"
                            alt="<?= htmlspecialchars($video['title']) ?>" class="video-thumbnail">
                        <div class="video-duration"><?= htmlspecialchars($formattedDuration) ?></div>
                        <div class="video-play-button"></div>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                        <div class="video-artist"><?= htmlspecialchars($video['artist_name']) ?></div>
                        <div class="video-year-genre"><?= htmlspecialchars($video['release_year']) ?> •
                            <?= htmlspecialchars($video['genre_name']) ?></div>
                            <div class="video-views"><?= htmlspecialchars($formattedViews) ?> views • <?= htmlspecialchars($video['likes']) ?> likes</div>
                        <div class="rating">
                            <?= htmlspecialchars($rating) ?> <br>
                            <span style="font-size: 14px; color: gray;">RATING</span>
                        </div>
                        <div class="video-menu" data-video-id="<?= $video['id'] ?>">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>



        <!-- Video Popup for Description -->
        <div class="video-popup">
            <div class="video-popup-content">
                <button class="video-popup-close">&times;</button>
                <h3 class="video-title"></h3>
                <div class="video-description"></div>
            </div>
        </div>
    </div>

    <?php
require_once '../layout/footer.php';

    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Filter Sync and Handling
        const filterElements = {
            album: document.getElementById('album-filter'),
            artist: document.getElementById('artist-filter'),
            year: document.getElementById('year-filter'),
            genre: document.getElementById('genre-filter'),
            language: document.getElementById('language-filter'),
            albumMobile: document.getElementById('album-filter-mobile'),
            artistMobile: document.getElementById('artist-filter-mobile'),
            yearMobile: document.getElementById('year-filter-mobile'),
            genreMobile: document.getElementById('genre-filter-mobile'),
            languageMobile: document.getElementById('language-filter-mobile'),
            searchInput: document.querySelector('.search-input'),
            searchInputMobile: document.getElementById('search-input-mobile'),
            searchButton: document.querySelector('.search-button'),
            searchButtonMobile: document.getElementById('search-btn-mobile')
        };

        // Auto-submit form on filter change
        [...document.querySelectorAll('#filter-form select, #mobile-filter-form select')].forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Sync desktop/mobile filters
        function syncFilters(source, target) {
            if (source && target) {
                source.addEventListener('change', () => {
                    target.value = source.value;
                });
            }
        }

        syncFilters(filterElements.album, filterElements.albumMobile);
        syncFilters(filterElements.albumMobile, filterElements.album);
        syncFilters(filterElements.artist, filterElements.artistMobile);
        syncFilters(filterElements.artistMobile, filterElements.artist);
        syncFilters(filterElements.year, filterElements.yearMobile);
        syncFilters(filterElements.yearMobile, filterElements.year);
        syncFilters(filterElements.genre, filterElements.genreMobile);
        syncFilters(filterElements.genreMobile, filterElements.genre);
        syncFilters(filterElements.language, filterElements.languageMobile);
        syncFilters(filterElements.languageMobile, filterElements.language);

        // Dynamic Particle Effect
        function createParticles() {
            const container = document.querySelector('.particles');
            for (let i = 0; i < 100; i++) {
                const particle = document.createElement('div');
                particle.style.cssText = `
                        position: absolute;
                        width: 2px;
                        height: 2px;
                        background: var(--neon-green);
                        border-radius: 50%;
                        top: ${Math.random() * 100}vh;
                        left: ${Math.random() * 100}vw;
                        animation: particle-float ${5 + Math.random() * 10}s infinite;
                    `;
                container.appendChild(particle);
            }
        }
        createParticles();

        // CSS keyframes for particle animation
        const styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.innerText = `
                @keyframes particle-float {
                    0% { transform: translateY(0); opacity: 1; }
                    100% { transform: translateY(-100px); opacity: 0; }
                }
            `;
        document.head.appendChild(styleSheet);

        // Video menu handling
        const videoMenus = document.querySelectorAll('.video-menu');
        const videoPopup = document.querySelector('.video-popup');
        const closeButton = document.querySelector('.video-popup-close');
        const popupTitle = videoPopup.querySelector('.video-title');
        const popupDescription = videoPopup.querySelector('.video-description');

        // Add click event to all video menu buttons
        videoMenus.forEach(menu => {
            menu.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent card click event

                const videoId = this.getAttribute('data-video-id');

                // Fetch video details via AJAX
                fetch(`get_video_details.php?id=${videoId}`)
                    .then(response => response.json())
                    .then(data => {
                        popupTitle.textContent = data.title;
                        popupDescription.innerHTML = data.description;
                        videoPopup.classList.add('active');
                    })
                    .catch(error => console.error('Error fetching video details:', error));
            });
        });

        // Close popup when clicking close button
        closeButton.addEventListener('click', function() {
            videoPopup.classList.remove('active');
        });

        // Close popup when clicking outside content
        videoPopup.addEventListener('click', function(e) {
            if (e.target === videoPopup) {
                videoPopup.classList.remove('active');
            }
        });

        // Video thumbnail hover effect
        const videoCards = document.querySelectorAll('.video-card');

        videoCards.forEach(card => {
            const thumbnailContainer = card.querySelector('.video-thumbnail-container');
            const thumbnail = thumbnailContainer.querySelector('.video-thumbnail');

            card.addEventListener('mouseenter', () => {
                if (thumbnail.tagName === 'IMG' && thumbnail.dataset.videoPath) {
                    const videoElement = document.createElement('video');
                    videoElement.className = 'video-thumbnail';
                    videoElement.src = thumbnail.dataset.videoPath;
                    videoElement.muted = true;
                    videoElement.loop = true;
                    videoElement.preload = 'metadata';
                    thumbnailContainer.replaceChild(videoElement, thumbnail);
                    videoElement.play();
                } else if (thumbnail.tagName === 'VIDEO') {
                    thumbnail.play();
                }
            });

            card.addEventListener('mouseleave', () => {
                const videoElement = thumbnailContainer.querySelector('video');
                if (videoElement) {
                    videoElement.pause();
                    videoElement.currentTime = 0;
                }
            });
        });
    });

    function openVideoPage(videoId) {
        // Increment view count via AJAX
        fetch('increment_view.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `video_id=${encodeURIComponent(videoId)}`
        });

        window.location.href = `video_details.php?id=${encodeURIComponent(videoId)}`;
    }
    </script>
</body>

</html>
<?php
$conn->close();
?>