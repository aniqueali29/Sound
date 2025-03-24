<?php
ob_start();

include '../includes/config_db.php'; 
include '../layout/header.php';

$album_filter = isset($_GET['album']) ? $_GET['album'] : '';
$artist_filter = isset($_GET['artist']) ? $_GET['artist'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$language_filter = isset($_GET['language']) ? $_GET['language'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT m.id, m.title, m.file_path, m.duration, m.release_year, m.plays, m.likes, 
               m.is_new, m.is_featured, m.thumbnail_path, m.is_active,
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

function sanitizeFilePath($path) {
    return str_replace(["../../../", "../../"], "../", $path);
}

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

$sql .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);

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

if (count($music_items) == 0 && !isset($_GET['noinsert'])) {

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
    
    header("Location: ?noinsert=1");
    exit;
}

ob_end_flush();

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
                <li class="nav-item active">
                    <a href="music.php">
                        <i class="fa-solid fa-music"></i>
                        <span>Music</span>
                    </a>
                </li>
                <li class="nav-item">
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
    <main class="container mt-5">
        <h1 class="main-title">MUSIC ARCHIVE</h1>
        <p class="subtitle">Explore the Universe of Sound</p>
        <section class="search-container d-none d-md-block">
            <div class="filter-row">
                <select class="filter-dropdown" id="album-filter">
                    <option value="">Album</option>
                    <?php foreach($albums as $album): ?>
                    <option value="<?php echo htmlspecialchars($album['title']); ?>"
                        <?php echo ($album_filter == $album['title'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($album['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-dropdown" id="artist-filter">
                    <option value="">Artist</option>
                    <?php foreach($artists as $artist): ?>
                    <option value="<?php echo htmlspecialchars($artist['name']); ?>"
                        <?php echo ($artist_filter == $artist['name'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($artist['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-dropdown" id="year-filter">
                    <option value="">Year</option>
                    <?php foreach($years as $year): ?>
                    <option value="<?php echo htmlspecialchars($year['release_year']); ?>"
                        <?php echo ($year_filter == $year['release_year'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($year['release_year']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-dropdown" id="genre-filter">
                    <option value="">Genre</option>
                    <?php foreach($genres as $genre): ?>
                    <option value="<?php echo htmlspecialchars($genre['name']); ?>"
                        <?php echo ($genre_filter == $genre['name'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($genre['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-dropdown" id="language-filter">
                    <option value="">Language</option>
                    <?php foreach($languages as $language): ?>
                    <option value="<?php echo htmlspecialchars($language['name']); ?>"
                        <?php echo ($language_filter == $language['name'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($language['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <input type="text" class="form-control search-input" id="search-input" placeholder="Search for music..."
                    value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="search-button" id="search-btn" type="button">Search</button>
            </div>
        </section>

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
                        <option value="<?php echo htmlspecialchars($album['title']); ?>"
                            <?php echo ($album_filter == $album['title'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($album['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="artist-filter-mobile">
                        <option value="">Artist</option>
                        <?php foreach($artists as $artist): ?>
                        <option value="<?php echo htmlspecialchars($artist['name']); ?>"
                            <?php echo ($artist_filter == $artist['name'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($artist['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="year-filter-mobile">
                        <option value="">Year</option>
                        <?php foreach($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year['release_year']); ?>"
                            <?php echo ($year_filter == $year['release_year'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($year['release_year']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="genre-filter-mobile">
                        <option value="">Genre</option>
                        <?php foreach($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre['name']); ?>"
                            <?php echo ($genre_filter == $genre['name'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($genre['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="language-filter-mobile">
                        <option value="">Language</option>
                        <?php foreach($languages as $language): ?>
                        <option value="<?php echo htmlspecialchars($language['name']); ?>"
                            <?php echo ($language_filter == $language['name'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($language['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-bar">
                    <input type="text" id="search-input-mobile" placeholder="Search for music..."
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button id="search-btn-mobile">Search</button>
                </div>
            </div>
        </div>

        <section class="album-grid">
            <h2 class="section-title">New Releases</h2>
            <div class="row g-4" id="music-grid">
                <?php 
                    $count = 0;
                    foreach($music_items as $item): 
                        if(empty($item['is_active'])) continue;
                        
                        $count++;
                        $hidden_class = ($count > 12) ? 'hidden-card' : '';
                    ?>
                <div class="col-12 col-md-6 col-lg-3 music-item <?php echo $hidden_class; ?>"
                    data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['title']); ?>"
                    data-artist="<?php echo htmlspecialchars($item['artist_name']); ?>"
                    data-album="<?php echo htmlspecialchars($item['album_title']); ?>"
                    data-year="<?php echo $item['release_year']; ?>"
                    data-genre="<?php echo htmlspecialchars($item['genre_name']); ?>"
                    data-language="<?php echo htmlspecialchars($item['language_name']); ?>"
                    data-file="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $item['file_path'])); ?>"
                    data-duration="<?php echo $item['duration']; ?>">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <?php if($item['is_new']): ?>
                            <div class="card-tag">New</div>
                            <?php endif; ?>
                            <div class="morph-play"><i class="fas fa-play"></i></div>
                            <img src="<?php 
                                if(!empty($item['cover_image'])) {
                                    echo  htmlspecialchars(str_replace(["../../../", "../../"], "../", '../uploads/albums/covers/' . $item['cover_image']));
                                } elseif(!empty($item['thumbnail_path'])) {
                                    echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $item['thumbnail_path']));
                                } else {
                                    echo '../assets/img/default-album.jpg';
                                }
                            ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="card-image">
                            <!-- Add Favorite Button -->
                            <?php
                                // Check if user is logged in
                                $is_favorite = false;
                                if(isset($_SESSION['user_id'])) {
                                    $user_id = $_SESSION['user_id'];
                                    $music_id = $item['id'];
                                    
                                    // Check if this track is in user's favorites
                                    $fav_query = "SELECT id FROM favorites WHERE user_id = ? AND music_id = ?";
                                    $fav_stmt = $conn->prepare($fav_query);
                                    $fav_stmt->bind_param("ii", $user_id, $music_id);
                                    $fav_stmt->execute();
                                    $fav_result = $fav_stmt->get_result();
                                    $is_favorite = $fav_result->num_rows > 0;
                                }
                            ?>
                            <button class="track-btn track-fav-btn <?php echo $is_favorite ? 'active' : ''; ?>"
                                data-music-id="<?php echo $item['id']; ?>">
                                <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="card-artist"><?php echo htmlspecialchars($item['artist_name']); ?></div>
                            <div class="card-meta">
                                <span><?php echo $item['release_year']; ?>
                                    <span class="genre-tag"><?php echo htmlspecialchars($item['genre_name']); ?></span>
                                </span>
                                <span class="card-rating">
                                    <?php
                                        $music_id = $item['id']; 
                                        $rating_query = "SELECT AVG(rating) as average_rating FROM ratings WHERE item_id = ? AND item_type = 'music'";
                                        $stmt = $conn->prepare($rating_query);
                                        $stmt->bind_param("i", $music_id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $rating_data = $result->fetch_assoc();
                                        
                                        $average_rating = $rating_data['average_rating'] ?? 0;
                                        $average_rating = round($average_rating, 1); 

                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $average_rating) {
                                                echo '★';
                                            } elseif ($i - 0.5 <= $average_rating) {
                                                echo '½'; 
                                            } else {
                                                echo '☆'; 
                                            }
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="more-options">
                                <button class="more-btn" data-bs-toggle="modal" data-bs-target="#musicDetailsModal"
                                    data-id="<?php echo $item['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                    data-artist="<?php echo htmlspecialchars($item['artist_name']); ?>"
                                    data-album="<?php echo htmlspecialchars($item['album_title']); ?>" data-cover="<?php 
                                    if(!empty($item['cover_image'])) {
                                        echo  htmlspecialchars(str_replace(["../../../", "../../"], "../", '../uploads/albums/covers/' . $item['cover_image']));
                                    } elseif(!empty($item['thumbnail_path'])) {
                                        echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $item['thumbnail_path']));
                                    } else {
                                        echo '../assets/img/default-album.jpg';
                                    }
                                    ?>">


                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
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

        <!-- Mobile Filter Toggle Button -->
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


        <!-- Music Details Modal -->
        <div class="modal fade" id="musicDetailsModal" tabindex="-1" aria-labelledby="musicDetailsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="musicDetailsModalLabel">Music Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="music-details-container">
                            <div class="music-details-header">
                                <div class="music-details-image">
                                    <img id="modalCoverImage" src="../assets/img/default-album.jpg" alt="Album Cover">
                                </div>
                                <div class="music-details-info">
                                    <h3 id="modalSongTitle" class="modal-song-title">Song Title</h3>
                                    <p id="modalArtistName" class="modal-artist-name">Artist Name</p>
                                    <p id="modalAlbumTitle" class="modal-album-title">Album Name</p>
                                    <div class="modal-rating-summary">
                                        <div class="rating-average">
                                            <div class="rating-stars" id="modalRatingStars">
                                                <!-- Stars will be populated by JavaScript -->
                                            </div>
                                            <div class="rating-count" id="modalRatingCount">
                                                <!-- Rating count will be populated by JavaScript -->
                                            </div>
                                        </div>
                                        <button class="btn btn-primary" id="rateReviewBtn" data-music-id="">Rate &
                                            Review</button>
                                    </div>
                                </div>
                            </div>
                            <div class="reviews-section">
                                <h4>User Reviews</h4>
                                <div class="reviews-container" id="reviewsContainer">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rate & Review Modal -->
        <div class="modal fade" id="rateReviewModal" tabindex="-1" aria-labelledby="rateReviewModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rateReviewModalLabel">Rate & Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="rate-review-form">
                            <form id="rateReviewForm">
                                <input type="hidden" id="music_id" name="music_id">

                                <div class="form-group mb-4">
                                    <label for="ratingStars" class="form-label">Your Rating</label>
                                    <div class="star-rating">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" id="ratingValue" name="rating" value="0">
                                </div>

                                <div class="form-group mb-4">
                                    <label for="reviewText" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="reviewText" name="review" rows="4"
                                        placeholder="Share your thoughts about this song..."></textarea>
                                </div>

                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-primary submit-review-btn">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <?php require_once '../layout/footer.php';?>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Add track favorite functionality
    const trackFavButtons = document.querySelectorAll('.track-fav-btn');
    trackFavButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('loading')) {
                this.classList.add('loading');
                const musicId = this.getAttribute('data-music-id');
                const isActive = this.classList.contains('active');
                toggleFavorite(musicId, 'music', !isActive, this);
            }
        });
    });

    // Toggle favorite function
    function toggleFavorite(itemId, itemType, addToFavorite, buttonElement) {
        // Ensure user is logged in
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

        if (!isLoggedIn) {
            alert('Please log in to add to favorites.');
            buttonElement.classList.remove('loading');
            return;
        }

        fetch('../includes/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `itemId=${itemId}&itemType=${itemType}&addToFavorite=${addToFavorite ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                buttonElement.classList.remove('loading');

                if (data.success) {
                    if (itemType === 'album') {
                        const icon = buttonElement.querySelector('i');
                        const textSpan = buttonElement.querySelector('span');

                        if (addToFavorite) {
                            icon.className = 'fas fa-heart';
                            textSpan.textContent = 'Remove Favorite';
                            buttonElement.setAttribute('data-is-favorite', '1');
                        } else {
                            icon.className = 'far fa-heart';
                            textSpan.textContent = 'Add Favorite';
                            buttonElement.setAttribute('data-is-favorite', '0');
                        }
                    } else if (itemType === 'music') {
                        if (addToFavorite) {
                            buttonElement.classList.add('active');
                            buttonElement.innerHTML = '<i class="fas fa-heart"></i>';
                        } else {
                            buttonElement.classList.remove('active');
                            buttonElement.innerHTML = '<i class="far fa-heart"></i>';
                        }
                    }
                } else {
                    alert(data.message || 'Error updating favorite status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                buttonElement.classList.remove('loading');
                alert('An error occurred while updating favorites.');
            });
    }
    </script>
    <script src="../js/music.js"></script>
</body>

</html>