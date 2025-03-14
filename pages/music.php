<?php
// Include database connection
include '../includes/config_db.php'; // Make sure this file exists with DB connection

// Get filters from URL parameters
$album_filter = isset($_GET['album']) ? $_GET['album'] : '';
$artist_filter = isset($_GET['artist']) ? $_GET['artist'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$language_filter = isset($_GET['language']) ? $_GET['language'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Base query to fetch music with all necessary joins
$sql = "SELECT m.id, m.title, m.file_path, m.duration, m.release_year, m.plays, m.likes, 
               m.is_new, m.is_featured,
               a.name AS artist_name, a.image AS artist_image,
               al.title AS album_title, al.cover_image,
               g.name AS genre_name,
               l.name AS language_name
        FROM music m
        INNER JOIN artists a ON m.artist_id = a.id
        LEFT JOIN albums al ON m.album_id = al.id
        LEFT JOIN genres g ON m.genre_id = g.id
        LEFT JOIN languages l ON m.language_id = l.id
        WHERE m.deleted_at IS NULL";

// Add filters if provided
$params = array();

if (!empty($album_filter)) {
    $sql .= " AND al.title LIKE ?";
    $params[] = "%$album_filter%";
}

if (!empty($artist_filter)) {
    $sql .= " AND a.name LIKE ?";
    $params[] = "%$artist_filter%";
}

if (!empty($year_filter)) {
    $sql .= " AND m.release_year = ?";
    $params[] = $year_filter;
}

if (!empty($genre_filter)) {
    $sql .= " AND g.name LIKE ?";
    $params[] = "%$genre_filter%";
}

if (!empty($language_filter)) {
    $sql .= " AND l.name LIKE ?";
    $params[] = "%$language_filter%";
}

if (!empty($search_query)) {
    $sql .= " AND (m.title LIKE ? OR a.name LIKE ? OR al.title LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Add order by clause
$sql .= " ORDER BY m.created_at DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

// Bind parameters if any
if (!empty($params)) {
    $types = str_repeat('s', count($params)); // Assuming all params are strings
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$music_items = $result->fetch_all(MYSQLI_ASSOC);

// Get all albums for the filter dropdowns
$sql_albums = "SELECT DISTINCT a.title, a.id FROM albums a WHERE a.deleted_at IS NULL ORDER BY a.title";
$result_albums = $conn->query($sql_albums);
$albums = $result_albums->fetch_all(MYSQLI_ASSOC);

// Get all artists
$sql_artists = "SELECT DISTINCT a.name, a.id FROM artists a WHERE a.deleted_at IS NULL ORDER BY a.name";
$result_artists = $conn->query($sql_artists);
$artists = $result_artists->fetch_all(MYSQLI_ASSOC);

// Get all genres
$sql_genres = "SELECT id, name FROM genres ORDER BY name";
$result_genres = $conn->query($sql_genres);
$genres = $result_genres->fetch_all(MYSQLI_ASSOC);

// Get all languages
$sql_languages = "SELECT id, name FROM languages ORDER BY name";
$result_languages = $conn->query($sql_languages);
$languages = $result_languages->fetch_all(MYSQLI_ASSOC);

// Get all years
$sql_years = "SELECT DISTINCT release_year FROM music WHERE deleted_at IS NULL ORDER BY release_year DESC";
$result_years = $conn->query($sql_years);
$years = $result_years->fetch_all(MYSQLI_ASSOC);

// If no music found, insert demo data if database is empty
if (count($music_items) == 0 && !isset($_GET['noinsert'])) {
    // Insert demo artists if they don't exist

    // Get artist IDs
    $stmt = $conn->prepare("SELECT id, name FROM artists WHERE name IN (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $demo_artists[0][0], $demo_artists[1][0], $demo_artists[2][0], $demo_artists[3][0], $demo_artists[4][0]);
    $stmt->execute();
    $result = $stmt->get_result();
    $artist_ids = [];
    while ($row = $result->fetch_assoc()) {
        $artist_ids[$row['name']] = $row['id'];
    }
    
    // Get genre IDs
    $rock_id = $conn->query("SELECT id FROM genres WHERE name = 'Rock'")->fetch_assoc()['id'];
    $rnb_id = $conn->query("SELECT id FROM genres WHERE name = 'R&B'")->fetch_assoc()['id'];
    $pop_id = $conn->query("SELECT id FROM genres WHERE name = 'Pop'")->fetch_assoc()['id'];
    
    // Get language IDs
    $english_id = $conn->query("SELECT id FROM languages WHERE name = 'English'")->fetch_assoc()['id'];
    $hindi_id = $conn->query("SELECT id FROM languages WHERE name = 'Hindi'")->fetch_assoc()['id'];
    
    
    // Get album IDs
    $stmt = $conn->prepare("SELECT id, title FROM albums WHERE title IN (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $demo_albums[0][0], $demo_albums[1][0], $demo_albums[2][0], $demo_albums[3][0], $demo_albums[4][0], $demo_albums[5][0], $demo_albums[6][0]);
    $stmt->execute();
    $result = $stmt->get_result();
    $album_ids = [];
    while ($row = $result->fetch_assoc()) {
        $album_ids[$row['title']] = $row['id'];
    }
    
    // Refresh the page to load the new data
    header("Location: ?noinsert=1");
    exit;
}
include '../layout/header.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SONIC ARCHIVE - Modern Music Hub</title>
    <!-- Bootstrap CSS (v5) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/music.css">
</head>

<body>
    <!-- Floating Particle Background -->
    <div class="particles"></div>
    <main class="container mt-5">
        <header class="stellar-header">
            <h1>SONIC ARCHIVE</h1>
            <p>Explore the Universe of Sound</p>
        </header>
        
        <!-- Desktop Filter Section -->
        <section class="hologram-filter d-none d-md-block">
            <div class="filter-section">
                <div class="filter-options">
                    <select id="album-filter">
                        <option value="">Album</option>
                        <?php foreach($albums as $album): ?>
                            <option value="<?php echo htmlspecialchars($album['title']); ?>" <?php echo ($album_filter == $album['title'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($album['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="artist-filter">
                        <option value="">Artist</option>
                        <?php foreach($artists as $artist): ?>
                            <option value="<?php echo htmlspecialchars($artist['name']); ?>" <?php echo ($artist_filter == $artist['name'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($artist['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="year-filter">
                        <option value="">Year</option>
                        <?php foreach($years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year['release_year']); ?>" <?php echo ($year_filter == $year['release_year'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($year['release_year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="genre-filter">
                        <option value="">Genre</option>
                        <?php foreach($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>" <?php echo ($genre_filter == $genre['name'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="language-filter">
                        <option value="">Language</option>
                        <?php foreach($languages as $language): ?>
                            <option value="<?php echo htmlspecialchars($language['name']); ?>" <?php echo ($language_filter == $language['name'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($language['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-bar">
                    <input type="text" id="search-input" placeholder="Search for music..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button id="search-btn">Search</button>
                </div>
            </div>
        </section>
        
        <!-- Offcanvas Filter Section for Mobile -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileFilter" aria-labelledby="mobileFilterLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileFilterLabel">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"
                    style="filter: invert(1);"></button>
            </div>
            <div class="offcanvas-body">
                <div class="filter-options">
                    <select id="album-filter-mobile">
                        <option value="">Album</option>
                        <?php foreach($albums as $album): ?>
                            <option value="<?php echo htmlspecialchars($album['title']); ?>" <?php echo ($album_filter == $album['title'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($album['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="artist-filter-mobile">
                        <option value="">Artist</option>
                        <?php foreach($artists as $artist): ?>
                            <option value="<?php echo htmlspecialchars($artist['name']); ?>" <?php echo ($artist_filter == $artist['name'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($artist['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="year-filter-mobile">
                        <option value="">Year</option>
                        <?php foreach($years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year['release_year']); ?>" <?php echo ($year_filter == $year['release_year'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($year['release_year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="genre-filter-mobile">
                        <option value="">Genre</option>
                        <?php foreach($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>" <?php echo ($genre_filter == $genre['name'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="language-filter-mobile">
                        <option value="">Language</option>
                        <?php foreach($languages as $language): ?>
                            <option value="<?php echo htmlspecialchars($language['name']); ?>" <?php echo ($language_filter == $language['name'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($language['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-bar">
                    <input type="text" id="search-input-mobile" placeholder="Search for music..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button id="search-btn-mobile">Search</button>
                </div>
            </div>
        </div>
        
        <!-- Album Grid using Bootstrap's grid system -->
        <section class="album-grid">
            <div class="row g-4" id="music-grid">
                <!-- PHP Will populate music cards here -->
                <?php 
                $count = 0;
                foreach($music_items as $item): 
                    $count++;
                    $hidden_class = ($count > 12) ? 'hidden-card' : '';
                ?>
                <div class="col-12 col-md-6 col-lg-3 music-item <?php echo $hidden_class; ?>"
                     data-id="<?php echo $item['id']; ?>"
                     data-title="<?php echo htmlspecialchars($item['title']); ?>"
                     data-artist="<?php echo htmlspecialchars($item['artist_name']); ?>"
                     data-album="<?php echo htmlspecialchars($item['album_title']); ?>"
                     data-year="<?php echo $item['release_year']; ?>"
                     data-genre="<?php echo htmlspecialchars($item['genre_name']); ?>"
                     data-language="<?php echo htmlspecialchars($item['language_name']); ?>"
                     data-file="<?php echo htmlspecialchars($item['file_path']); ?>"
                     data-duration="<?php echo $item['duration']; ?>">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <?php if($item['is_new']): ?>
                            <div class="card-tag">New</div>
                            <?php endif; ?>
                            <div class="morph-play"><i class="fas fa-play"></i></div>
                            <img src="<?php echo !empty($item['cover_image']) ? '../uploads/' . htmlspecialchars($item['cover_image']) : '../assets/img/default-album.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" class="card-image">
                        </div>
                        <div class="card-info">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="card-artist"><?php echo htmlspecialchars($item['artist_name']); ?></div>
                            <div class="card-meta">
                                <span><?php echo $item['release_year']; ?> 
                                <span class="genre-tag"><?php echo htmlspecialchars($item['genre_name']); ?></span></span>
                                <span class="card-rating">
                                    <?php 
                                    // Simple algorithm to generate a rating based on plays and likes
                                    $rating = min(5, ceil(($item['plays'] + $item['likes'] * 2) / 500));
                                    for($i = 1; $i <= 5; $i++) {
                                        echo ($i <= $rating) ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(count($music_items) == 0): ?>
                <div class="col-12 text-center">
                    <div class="no-results-message">
                        <i class="fas fa-music fa-3x"></i>
                        <h3>No music tracks found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Load More Button -->
        <?php if(count($music_items) > 12): ?>
        <button id="loadMoreBtn">Load More</button>
        <?php endif; ?>

        <!-- Mobile Filter Toggle Button in Top Right -->
        <button class="d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileFilter"
            aria-controls="mobileFilter">
            <div class="filter-icon">
                <div class="pulse"></div>
                <svg viewBox="0 0 24 24">
                    <path d="M10 18h4v-2h-4v2zm-7-10v2h18v-2h-18zm3 6h12v-2h-12v2z" />
                </svg>
            </div>
        </button>
        
        <!-- Fixed Audio Player Section -->
        <div id="audioPlayerSection" class="audio-player-section">
            <div class="audio-player-container">
                <div class="audio-player-info">
                    <div class="song-thumbnail">
                        <img id="audioPlayerImage" src="../assets/img/default-album.jpg" alt="Album art">
                    </div>
                    <div class="song-details">
                        <h3 id="audioPlayerSongTitle">Song Title</h3>
                        <p id="audioPlayerArtist">Artist Name</p>
                    </div>
                </div>

                <div class="audio-controls">
                    <div class="player-controls">
                        <button class="control-btn" id="prevBtn">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button class="control-btn play-btn" id="playBtn">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="control-btn" id="nextBtn">
                            <i class="fas fa-step-forward"></i>
                        </button>
                    </div>

                    <div class="progress-bar-container">
                        <div class="progress-container">
                            <div class="progress-bar" id="progressBar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                        </div>

                        <div class="time-display">
                            <span id="currentTime">0:00</span>
                            <span id="duration">0:00</span>
                        </div>
                    </div>
                </div>

                <div class="player-options">
                    <div class="volume-control">
                        <div class="volume-icon" id="volumeIcon">
                            <i class="fas fa-volume-up"></i>
                        </div>

                        <div class="volume-slider-container" id="volumeSliderContainer">
                            <div class="volume-slider" id="volumeSlider">
                                <div class="volume-fill" id="volumeFill"></div>
                            </div>
                            <span class="volume-percentage" id="volumePercentage">70%</span>
                        </div>
                    </div>
                </div>

                <button id="closePlayerBtn" class="close-player-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Hidden audio element for actual playback -->
        <audio id="audioElement"></audio>

    </main>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        
        // Filter Functionality
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
            searchInput: document.getElementById('search-input'),
            searchButton: document.getElementById('search-btn'),
            searchInputMobile: document.getElementById('search-input-mobile'),
            searchButtonMobile: document.getElementById('search-btn-mobile')
        };
        
        // Apply filters function
        function applyFilters() {
            let url = new URL(window.location.href);
            
            // Get values from desktop or mobile filters based on viewport
            const isMobile = window.innerWidth < 768;
            
            const albumValue = isMobile ? filterElements.albumMobile.value : filterElements.album.value;
            const artistValue = isMobile ? filterElements.artistMobile.value : filterElements.artist.value;
            const yearValue = isMobile ? filterElements.yearMobile.value : filterElements.year.value;
            const genreValue = isMobile ? filterElements.genreMobile.value : filterElements.genre.value;
            const languageValue = isMobile ? filterElements.languageMobile.value : filterElements.language.value;
            const searchValue = isMobile ? filterElements.searchInputMobile.value : filterElements.searchInput.value;
            
            // Clear existing parameters
            url.search = '';
            
// Add new parameters
if (albumValue) url.searchParams.set('album', albumValue);
if (artistValue) url.searchParams.set('artist', artistValue);
if (yearValue) url.searchParams.set('year', yearValue);
if (genreValue) url.searchParams.set('genre', genreValue);
if (languageValue) url.searchParams.set('language', languageValue);
if (searchValue) url.searchParams.set('search', searchValue);

// Navigate to new URL
window.location.href = url.toString();
}

// Add event listeners to filter elements
filterElements.album.addEventListener('change', applyFilters);
filterElements.artist.addEventListener('change', applyFilters);
filterElements.year.addEventListener('change', applyFilters);
filterElements.genre.addEventListener('change', applyFilters);
filterElements.language.addEventListener('change', applyFilters);
filterElements.albumMobile.addEventListener('change', applyFilters);
filterElements.artistMobile.addEventListener('change', applyFilters);
filterElements.yearMobile.addEventListener('change', applyFilters);
filterElements.genreMobile.addEventListener('change', applyFilters);
filterElements.languageMobile.addEventListener('change', applyFilters);
filterElements.searchButton.addEventListener('click', applyFilters);
filterElements.searchButtonMobile.addEventListener('click', applyFilters);

// Search on Enter key
filterElements.searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});
filterElements.searchInputMobile.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Sync mobile and desktop filters
function syncFilters(source, target) {
    source.addEventListener('change', function() {
        target.value = source.value;
    });
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
syncFilters(filterElements.searchInput, filterElements.searchInputMobile);
syncFilters(filterElements.searchInputMobile, filterElements.searchInput);

// Audio Player Logic
const audioPlayer = {
    element: document.getElementById('audioElement'),
    container: document.getElementById('audioPlayerSection'),
    image: document.getElementById('audioPlayerImage'),
    songTitle: document.getElementById('audioPlayerSongTitle'),
    artist: document.getElementById('audioPlayerArtist'),
    playBtn: document.getElementById('playBtn'),
    prevBtn: document.getElementById('prevBtn'),
    nextBtn: document.getElementById('nextBtn'),
    progressFill: document.getElementById('progressFill'),
    progressBar: document.getElementById('progressBar'),
    currentTimeDisplay: document.getElementById('currentTime'),
    durationDisplay: document.getElementById('duration'),
    volumeIcon: document.getElementById('volumeIcon'),
    volumeSlider: document.getElementById('volumeSlider'),
    volumeFill: document.getElementById('volumeFill'),
    volumePercentage: document.getElementById('volumePercentage'),
    closePlayerBtn: document.getElementById('closePlayerBtn'),
    
    currentIndex: 0,
    playlist: [],
    isPlaying: false,
    volume: 0.7, // Default volume (70%)
    
    init: function() {
        this.loadPlaylist();
        this.attachEventListeners();
        this.updateVolumeUI();
    },
    
    loadPlaylist: function() {
        const musicItems = document.querySelectorAll('.music-item');
        this.playlist = Array.from(musicItems).map(item => ({
            id: item.dataset.id,
            title: item.dataset.title,
            artist: item.dataset.artist,
            album: item.dataset.album,
            file: item.dataset.file,
            duration: item.dataset.duration,
            image: item.querySelector('img').src
        }));
    },
    
    attachEventListeners: function() {
        // Play/pause button
        this.playBtn.addEventListener('click', () => {
            this.togglePlay();
        });
        
        // Previous button
        this.prevBtn.addEventListener('click', () => {
            this.playPrev();
        });
        
        // Next button
        this.nextBtn.addEventListener('click', () => {
            this.playNext();
        });
        
        // Progress bar click
        this.progressBar.addEventListener('click', (e) => {
            const percent = e.offsetX / this.progressBar.offsetWidth;
            this.element.currentTime = percent * this.element.duration;
            this.updateProgressBar();
        });
        
        // Volume controls
        this.volumeIcon.addEventListener('click', () => {
            this.toggleMute();
        });
        
        this.volumeSlider.addEventListener('click', (e) => {
            const percent = e.offsetX / this.volumeSlider.offsetWidth;
            this.setVolume(percent);
        });
        
        // Time update
        this.element.addEventListener('timeupdate', () => {
            this.updateProgressBar();
        });
        
        // Audio ended
        this.element.addEventListener('ended', () => {
            this.playNext();
        });
        
        // Close player
        this.closePlayerBtn.addEventListener('click', () => {
            this.pause();
            this.container.classList.remove('active');
        });
        
        // Load metadata
        this.element.addEventListener('loadedmetadata', () => {
            this.updateDurationDisplay();
        });
        
        // Click on music items
        document.querySelectorAll('.music-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.currentIndex = index;
                this.loadAndPlay();
            });
        });
    },
    
    loadAndPlay: function() {
        if (this.playlist.length === 0) return;
        
        const current = this.playlist[this.currentIndex];
        this.element.src = '../' + current.file; // Adjust path as needed
        this.songTitle.textContent = current.title;
        this.artist.textContent = current.artist;
        this.image.src = current.image;
        
        this.container.classList.add('active');
        this.play();
    },
    
    togglePlay: function() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    },
    
    play: function() {
        this.element.play();
        this.isPlaying = true;
        this.playBtn.innerHTML = '<i class="fas fa-pause"></i>';
    },
    
    pause: function() {
        this.element.pause();
        this.isPlaying = false;
        this.playBtn.innerHTML = '<i class="fas fa-play"></i>';
    },
    
    playNext: function() {
        this.currentIndex = (this.currentIndex + 1) % this.playlist.length;
        this.loadAndPlay();
    },
    
    playPrev: function() {
        this.currentIndex = (this.currentIndex - 1 + this.playlist.length) % this.playlist.length;
        this.loadAndPlay();
    },
    
    updateProgressBar: function() {
        const percent = (this.element.currentTime / this.element.duration) * 100 || 0;
        this.progressFill.style.width = `${percent}%`;
        this.updateTimeDisplay();
    },
    
    updateTimeDisplay: function() {
        this.currentTimeDisplay.textContent = this.formatTime(this.element.currentTime);
    },
    
    updateDurationDisplay: function() {
        this.durationDisplay.textContent = this.formatTime(this.element.duration);
    },
    
    formatTime: function(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
    },
    
    toggleMute: function() {
        if (this.element.volume > 0) {
            this.lastVolume = this.volume;
            this.setVolume(0);
        } else {
            this.setVolume(this.lastVolume || 0.7);
        }
    },
    
    setVolume: function(volumeLevel) {
        this.volume = Math.max(0, Math.min(1, volumeLevel));
        this.element.volume = this.volume;
        this.updateVolumeUI();
    },
    
    updateVolumeUI: function() {
        this.volumeFill.style.width = `${this.volume * 100}%`;
        this.volumePercentage.textContent = `${Math.round(this.volume * 100)}%`;
        
        // Update icon based on volume level
        if (this.volume === 0) {
            this.volumeIcon.innerHTML = '<i class="fas fa-volume-mute"></i>';
        } else if (this.volume < 0.5) {
            this.volumeIcon.innerHTML = '<i class="fas fa-volume-down"></i>';
        } else {
            this.volumeIcon.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    }
};

// Initialize audio player
audioPlayer.init();

// Load More Functionality
const loadMoreBtn = document.getElementById('loadMoreBtn');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function() {
        const hiddenCards = document.querySelectorAll('.hidden-card');
        const cardsToShow = Array.from(hiddenCards).slice(0, 12);
        
        cardsToShow.forEach(card => {
            card.classList.remove('hidden-card');
        });
        
        if (document.querySelectorAll('.hidden-card').length === 0) {
            loadMoreBtn.style.display = 'none';
        }
    });
}
});
</script>

<?php
include '../layout/footer.php';
?>