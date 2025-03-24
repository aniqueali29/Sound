<?php
ob_start(); 
require_once '../includes/config_db.php';
require_once '../layout/header.php';


// Check if album ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$album_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0;
$is_logged_in = isset($_SESSION['user_id']);

// Get album details
$album_query = "SELECT a.*, ar.name as artist_name, ar.id as artist_id, ar.bio as artist_bio, ar.image as artist_image, 
                g.name as genre_name, l.name as language_name,
                (SELECT COUNT(*) FROM favorites WHERE album_id = a.id) as favorite_count,
                (SELECT AVG(rating) FROM ratings WHERE item_id = a.id AND item_type = 'music') as avg_rating,
                (SELECT COUNT(*) FROM ratings WHERE item_id = a.id AND item_type = 'music') as rating_count,
                (SELECT COUNT(*) FROM favorites WHERE album_id = a.id AND user_id = ?) as is_favorite
                FROM albums a 
                LEFT JOIN artists ar ON a.artist_id = ar.id 
                LEFT JOIN genres g ON a.genre_id = g.id 
                LEFT JOIN languages l ON a.language_id = l.id 
                WHERE a.id = ? AND a.deleted_at IS NULL";

$stmt = mysqli_prepare($conn, $album_query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $album_id);
mysqli_stmt_execute($stmt);
$album_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($album_result) == 0) {
    header('Location: index.php');
    exit;
}

$album = mysqli_fetch_assoc($album_result);

// Get album tracks
$tracks_query = "SELECT m.*, 
                (SELECT COUNT(*) FROM favorites WHERE music_id = m.id) as favorite_count,
                (SELECT COUNT(*) FROM favorites WHERE music_id = m.id AND user_id = ?) as is_favorite
                FROM music m
                WHERE m.album_id = ? AND m.is_active = 1 AND m.deleted_at IS NULL
                ORDER BY m.id ASC";

$stmt = mysqli_prepare($conn, $tracks_query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $album_id);
mysqli_stmt_execute($stmt);
$tracks_result = mysqli_stmt_get_result($stmt);

// Get similar albums (same genre or artist)
$similar_query = "SELECT a.*, ar.name as artist_name
                FROM albums a
                LEFT JOIN artists ar ON a.artist_id = ar.id
                WHERE a.id != ? AND a.deleted_at IS NULL AND
                (a.genre_id = ? OR a.artist_id = ?)
                ORDER BY RAND()
                LIMIT 6";

$stmt = mysqli_prepare($conn, $similar_query);
mysqli_stmt_bind_param($stmt, "iii", $album_id, $album['genre_id'], $album['artist_id']);
mysqli_stmt_execute($stmt);
$similar_result = mysqli_stmt_get_result($stmt);

// Get user's rating if logged in
$user_rating = 0;
$user_review = '';
if ($is_logged_in) {
    $rating_query = "SELECT rating, review FROM ratings 
                    WHERE user_id = ? AND item_id = ? AND item_type = 'music'";
    $stmt = mysqli_prepare($conn, $rating_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $album_id);
    mysqli_stmt_execute($stmt);
    $rating_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($rating_result) > 0) {
        $rating_row = mysqli_fetch_assoc($rating_result);
        $user_rating = $rating_row['rating'];
        $user_review = $rating_row['review'];
    }
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating_submit']) && $is_logged_in) {
    $rating = (float)$_POST['rating'];
    $review = trim($_POST['review'] ?? '');
    
    // Validate rating
    if ($rating >= 1 && $rating <= 5) {
        // Check if user already rated this album
        $check_query = "SELECT id FROM ratings WHERE user_id = ? AND item_id = ? AND item_type = 'music'";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $album_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing rating
            $rating_id = mysqli_fetch_assoc($check_result)['id'];
            $update_query = "UPDATE ratings SET rating = ?, review = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "dsi", $rating, $review, $rating_id);
            mysqli_stmt_execute($stmt);
        } else {
            // Insert new rating
            $insert_query = "INSERT INTO ratings (user_id, item_id, item_type, rating, review) 
                            VALUES (?, ?, 'music', ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "iids", $user_id, $album_id, $rating, $review);
            mysqli_stmt_execute($stmt);
        }
        
        header("Location: album_details.php?id=$album_id&rated=1");
        die();         
    }
}

// Get all ratings and reviews
$reviews_query = "SELECT r.*, u.username, u.profile_picture 
                FROM ratings r
                JOIN users u ON r.user_id = u.id
                WHERE r.item_id = ? AND r.item_type = 'music' AND r.review IS NOT NULL AND r.review != ''
                ORDER BY r.created_at DESC
                LIMIT 10";
$stmt = mysqli_prepare($conn, $reviews_query);
mysqli_stmt_bind_param($stmt, "i", $album_id);
mysqli_stmt_execute($stmt);
$reviews_result = mysqli_stmt_get_result($stmt);

// Update view count
$update_view = "UPDATE albums SET views = views + 1 WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_view);
mysqli_stmt_bind_param($stmt, "i", $album_id);
mysqli_stmt_execute($stmt);

// Format album cover image path
$album_cover = str_replace(["../../../", "../../"], "../", '../uploads/albums/covers/' . $album['cover_image']);
$featured_image = str_replace(["../../../", "../../"], "../", '../uploads/albums/featured/' . $album['featured_image']);
$artist_image = str_replace(["../../../", "../../"], "../", $album['artist_image']);

// Calculate total duration of all tracks
$total_duration = 0;
$total_tracks = mysqli_num_rows($tracks_result);
ob_end_flush(); // Send output to browser

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album['title']) ?> by <?= htmlspecialchars($album['artist_name']) ?> | SOUND</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Quicksand:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/album_details.css">
    <style>
    /* Add these styles to your album_details.css file */

    /* Animation for player sliding up */
    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Enhanced player styling */
    .audio-player {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(20, 20, 30, 0.95);
        border-top: 2px solid #0ff47a;
        z-index: 1000;
        display: none;
        box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.5);
        transition: all 0.3s ease;
    }

    .audio-player.active {
        display: block;
    }

    /* Track highlighting for currently playing track */
    .track-item.now-playing {
        background: rgba(15, 244, 122, 0.1);
        border-left: 4px solid #0ff47a;
    }

    .track-item.now-playing .track-title {
        color: #0ff47a;
        font-weight: bold;
    }

    /* Improved Play All button with feedback */
    #play-all-btn {
        transition: all 0.3s ease;
    }

    #play-all-btn:active {
        transform: scale(0.95);
    }

    /* Player controls enhancement */
    .player-controls .player-btn {
        transition: all 0.2s ease;
    }

    .player-controls .player-btn:hover {
        transform: scale(1.1);
        color: #0ff47a;
    }

    .player-controls .player-btn.active {
        color: #0ff47a;
    }

    /* Progress bar improvement */
    #progress-container {
        cursor: pointer;
        background: rgba(255, 255, 255, 0.1);
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }

    #progress-bar {
        background: linear-gradient(90deg, #0ff47a, #00c4ff);
        height: 100%;
        border-radius: 4px;
        transition: width 0.1s linear;
    }

    /* Volume control enhancement */
    .volume-slider {
        cursor: pointer;
        background: rgba(255, 255, 255, 0.1);
        height: 6px;
        border-radius: 3px;
        width: 80px;
        overflow: hidden;
    }

    #volume-bar {
        background: #0ff47a;
        height: 100%;
        border-radius: 3px;
    }

    .reviews-container {
        margin-top: 15px;
    }

    .section-title {
        display: flex;
        align-items: center;
        font-size: 1.8rem;
        margin-bottom: 25px;
        font-weight: 600;
        color: #9babff;
        border-bottom: 1px solid #2a2f3d;
        padding-bottom: 10px;
    }

    .section-title i {
        color: #1DB954;
        margin-right: 15px;
        font-size: 1.6rem;
    }

    .track-item {
        background-color: #1a1f2e;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        transition: transform 0.2s, background-color 0.2s;
    }

    .track-item:hover {
        transform: translateY(-2px);
        background-color: #232838;
    }

    .track-title {
        font-weight: 600;
        font-size: 1.1rem;
        color: #fff;
    }

    .fa-star {
        color: #1DB954;
    }

    .far.fa-star {
        color: #3e4451;
    }

    .alert-info {
        background-color: #1a2638;
        color: #8ebbff;
        border: none;
        padding: 15px;
        border-radius: 8px;
    }

    .user-image {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #1DB954;
    }

    small {
        color: #9ba0ae;
    }

    /* New styling for the header with icon */
    .reviews-header {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid #2a2f3d;
        padding-bottom: 10px;
    }

    .reviews-header i {
        font-size: 24px;
        margin-right: 15px;
        color: #1DB954;
    }

    .reviews-header h2 {
        font-size: 1.8rem;
        font-weight: 600;
        color: #9babff;
        margin: 0;
    }

    /* Date posted styling */
    .date-posted {
        color: #9ba0ae;
        font-size: 0.85rem;
    }

    /* Review text */
    .review-text {
        margin-top: 10px;
        line-height: 1.5;
    }



    /* NEW Badge - Subtle Neon Glow */
    .badge-item {
        display: inline-block;
        background: rgba(0, 255, 170, 0.2);
        /* Soft neon green with transparency */
        color: #00ffaa;
        /* Bright Neon Aqua */
        font-size: 0.85rem;
        font-weight: bold;
        padding: 6px 14px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 1px solid #00ffaa;
        /* Neon Aqua Border */
        box-shadow: 0 0 10px rgba(0, 255, 170, 0.2);
        /* Soft Neon Glow */
        transition: all 0.3s ease-in-out;
    }

    /* Glowing effect on hover */
    .badge-item:hover {
        background: rgba(0, 255, 170, 0.3);
        box-shadow: 0 0 15px rgba(0, 255, 170, 1);
        transform: scale(1.05);
    }

    /* Positioning */
    .language-badges {
        position: absolute;
        top: 10px;
        left: 10px;
    }
    .artist-header a{
        text-decoration: none;
    }
    </style>

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
                <li class="nav-item">
                    <a href="video.php">
                        <i class="fa-solid fa-video"></i>
                        <span>Video</span>
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="albums.php">
                        <i class="fa-solid fa-record-vinyl"></i>
                        <span>Albums</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="particles"></div>

    <!-- FIRST: Album Header Section -->
    <div class="album-header">
        <div class="bg"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-5 mb-4 mb-md-0 text-center text-md-start">
                    <img src="<?= $album_cover ?>" alt="<?= htmlspecialchars($album['title']) ?>" class="album-cover">
                    <?php if ($album['is_new']): ?>
                    <div class="badge-item language-badges mt-2">NEW</div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-8 col-md-7">
                    <h1 class="album-title"><?= htmlspecialchars($album['title']) ?></h1>
                    <h2 class="album-artist">
                        <a
                            href="artist_details.php?id=<?= $album['artist_id'] ?>"><?= htmlspecialchars($album['artist_name']) ?></a>
                    </h2>

                    <div class="album-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt meta-icon"></i>
                            <span><?= $album['release_year'] ?></span>
                        </div>
                        <?php if (!empty($album['genre_name'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-guitar meta-icon"></i>
                            <span><?= htmlspecialchars($album['genre_name']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($album['language_name'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-globe meta-icon"></i>
                            <span><?= htmlspecialchars($album['language_name']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="fas fa-music meta-icon"></i>
                            <span><?= $total_tracks ?> Tracks</span>
                        </div>
                    </div>

                    <div class="album-badges">
                        <?php if (!empty($album['genre_name'])): ?>
                        <div class="badge-item genre-badge"><?= htmlspecialchars($album['genre_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($album['language_name'])): ?>
                        <div class="badge-item language-badge"><?= htmlspecialchars($album['language_name']) ?></div>
                        <?php endif; ?>
                        <div class="badge-item year-badge"><?= $album['release_year'] ?></div>
                    </div>

                    <div class="album-rating-container mb-3">
                        <div class="rating-stars">
                            <?php 
                            $avg_rating = round($album['avg_rating'] ?? 0, 1);
                            for ($i = 1; $i <= 5; $i++) {
                                $star_class = 'far fa-star'; // empty star
                                if ($i <= floor($avg_rating)) {
                                    $star_class = 'fas fa-star'; // filled star
                                } else if ($i - 0.5 <= $avg_rating) {
                                    $star_class = 'fas fa-star-half-alt'; // half star
                                }
                                echo "<i class=\"$star_class\"></i>";
                            }
                            ?>
                            <span class="ms-2"><?= $avg_rating ?> (<?= number_format($album['rating_count'] ?? 0) ?>
                                ratings)</span>
                        </div>
                    </div>

                    <div class="album-actions">
                        <!-- Play All Button -->
                        <button id="play-all-btn" class="btn-main">
                            <i class="fas fa-play"></i> Play All
                        </button>

                        <!-- Add to Favorites Button -->
                        <button id="favorite-btn" class="btn-outline" data-album-id="<?= $album_id ?>"
                            data-is-favorite="<?= $album['is_favorite'] ? '1' : '0' ?>">
                            <i class="<?= $album['is_favorite'] ? 'fas' : 'far' ?> fa-heart"></i>
                            <span><?= $album['is_favorite'] ? 'Remove Favorite' : 'Add Favorite' ?></span>
                        </button>

                        <!-- Share Button -->
                        <button id="share-btn" class="btn-outline" data-bs-toggle="modal" data-bs-target="#shareModal">
                            <i class="fas fa-share-alt"></i> Share
                        </button>

                        <!-- Rate Button (NEW) -->
                        <button id="rate-btn" class="btn-outline" data-bs-toggle="modal" data-bs-target="#rateModal">
                            <i class="fas fa-star"></i> Rate
                        </button>
                    </div>

                    <?php if (!empty($album['description'])): ?>
                    <div class="album-description">
                        <p><?= nl2br(htmlspecialchars($album['description'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- SECOND: Two column layout for Tracks and Similar Albums -->
        <div class="row">
            <!-- Tracks Column -->
            <div class="col-lg-8">
                <div class="tracklist">
                    <h2 class="section-title">
                        <i class="fas fa-music"></i> Tracks
                    </h2>

                    <?php
                    if (mysqli_num_rows($tracks_result) > 0):
                        $track_num = 1;
                        mysqli_data_seek($tracks_result, 0); // Reset result pointer
                    ?>
                    <div class="tracks-container">
                        <?php while ($track = mysqli_fetch_assoc($tracks_result)): 
                            // Format track file path
                            $track_path = str_replace(["../../../", "../../"], "../", $track['file_path']);
                            $track_thumbnail = empty($track['thumbnail_path']) ? $album_cover : str_replace(["../../../", "../../"], "../", $track['thumbnail_path']);
                            
                            // Convert duration to readable format
                            $duration = $track['duration'];
                            $total_duration += strtotime("1970-01-01 $duration UTC");
                        ?>
                        <div class="track-item" data-track-id="<?= $track['id'] ?>" data-track-path="<?= $track_path ?>"
                            data-track-num="<?= $track_num ?>"
                            data-track-title="<?= htmlspecialchars($track['title']) ?>"
                            data-track-thumbnail="<?= $track_thumbnail ?>">

                            <div class="track-number"><?= $track_num++ ?></div>

                            <div class="track-info">
                                <div class="track-title"><?= htmlspecialchars($track['title']) ?>
                                    <?php if ($track['is_new']): ?>
                                    <span class="badge-item language-badge ms-2"
                                        style="font-size: 0.7rem; padding: 2px 8px;">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <div class="track-artist"><?= htmlspecialchars($album['artist_name']) ?></div>
                            </div>

                            <div class="track-meta">
                                <span><?= $duration ?></span>
                            </div>

                            <div class="track-actions">
                                <button class="track-btn track-play-btn"><i class="fas fa-play"></i></button>
                                <button class="track-btn track-fav-btn <?= $track['is_favorite'] ? 'active' : '' ?>"
                                    data-music-id="<?= $track['id'] ?>">
                                    <i class="<?= $track['is_favorite'] ? 'fas' : 'far' ?> fa-heart"></i>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No tracks available for this album.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Similar Albums Column -->
            <div class="col-lg-4">
                <div class="similar-albums">
                    <h2 class="section-title">
                        <i class="fas fa-compact-disc"></i> Similar Albums
                    </h2>

                    <?php if (mysqli_num_rows($similar_result) > 0): ?>
                    <div class="row">
                        <?php while ($similar = mysqli_fetch_assoc($similar_result)): 
                            $similar_cover = str_replace(["../../../", "../../"], "../", $similar['cover_image']);
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="similar-album-card">
                                <img src="<?= $similar_cover ?>" alt="<?= htmlspecialchars($similar['title']) ?>"
                                    class="similar-album-img">
                                <h3 class="similar-album-title mt-3"><?= htmlspecialchars($similar['title']) ?></h3>
                                <p class="similar-album-artist"><?= htmlspecialchars($similar['artist_name']) ?></p>
                                <div class="d-flex gap-2 mt-2">
                                    <a href="album_details.php?id=<?= $similar['id'] ?>" class="btn-outline btn-sm">View
                                        Album</a>
                                    <button class="btn-outline btn-sm add-to-favorites"
                                        data-album-id="<?= $similar['id'] ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No similar albums found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- THIRD: User Reviews Section (Display Only) -->
        <div class="mt-5">
            <h2 class="section-title">
                <i class="fas fa-comment"></i> User Reviews
            </h2>

            <?php if (mysqli_num_rows($reviews_result) > 0): ?>
            <div class="reviews-container">
                <?php while ($review = mysqli_fetch_assoc($reviews_result)): 
            $profile_pic = !empty($review['profile_picture']) ? str_replace(["../../../", "../../"], "../", $review['profile_picture']) : null;
            $username = htmlspecialchars($review['username']);
            $firstLetter = strtoupper(substr($username, 0, 1));
        ?>
                <div class="track-item" style="display: block;">
                    <div class="d-flex align-items-center mb-2">
                        <?php if ($profile_pic): ?>
                        <img src="<?= $profile_pic ?>" alt="<?= $username ?>"
                            style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                        <?php else: ?>
                        <div class="profile-placeholder" style="width: 40px; height: 40px; border-radius: 50%; 
                        background-color: #6c757d; color: white; display: flex; align-items: center; 
                        justify-content: center; font-weight: bold; font-size: 16px; margin-right: 10px;">
                            <?= $firstLetter ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div class="track-title"><?= $username ?></div>
                            <div>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                <?php endfor; ?>
                                <small class="ms-2">Posted on
                                    <?= date('F j, Y', strtotime($review['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">No reviews available for this album yet.</div>
            <?php endif; ?>
        </div>


        <!-- FOURTH: About the Artist -->
        <div class="artist-info mt-5">
            <h2 class="section-title">
                <i class="fas fa-user"></i> About the Artist
            </h2>

            <div class="artist-header">
                <img src="<?= $artist_image ?>" alt="<?= htmlspecialchars($album['artist_name']) ?>"
                    class="artist-image">
                <div>
                    <h3 class="artist-name"><?= htmlspecialchars($album['artist_name']) ?></h3>
                    <a href="artist_details.php?id=<?= $album['artist_id'] ?>" class="btn-outline mt-2">View Artist
                        Profile</a>
                </div>
            </div>

            <?php if (!empty($album['artist_bio'])): ?>
            <div class="artist-bio">
                <p><?= nl2br(htmlspecialchars($album['artist_bio'])) ?></p>
            </div>
            <?php else: ?>
            <div class="artist-bio">
                <p>No biography available for this artist.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audio Player -->
    <div class="audio-player" id="audio-player-container">
        <div class="container">
            <div class="player-content">
                <div class="player-left">
                    <img src="<?= $album_cover ?>" alt="Currently playing" class="player-thumbnail"
                        id="player-thumbnail">
                    <div class="player-info">
                        <div class="player-title" id="player-track-title">Select a track</div>
                        <div class="player-artist"><?= htmlspecialchars($album['artist_name']) ?></div>
                    </div>
                </div>

                <div class="player-center">
                    <div class="player-controls">
                        <button class="player-btn" id="shuffle-btn"><i class="fas fa-random"></i></button>
                        <button class="player-btn" id="prev-btn"><i class="fas fa-step-backward"></i></button>
                        <button class="player-btn main" id="play-btn"><i class="fas fa-play"></i></button>
                        <button class="player-btn" id="next-btn"><i class="fas fa-step-forward"></i></button>
                        <button class="player-btn" id="repeat-btn"><i class="fas fa-redo"></i></button>
                    </div>

                    <div class="player-progress" id="progress-container">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>

                    <div class="player-time">
                        <span id="current-time">0:00</span>
                        <span id="duration">0:00</span>
                    </div>
                </div>

                <div class="player-right">
                    <button class="player-btn" id="volume-btn"><i class="fas fa-volume-up"></i></button>
                    <div class="volume-control">
                        <div class="volume-slider">
                            <div class="volume-bar" id="volume-bar"></div>
                        </div>
                    </div>
                    <button class="player-btn" id="close-player-btn"><i class="fas fa-times"></i></button>
                </div>
            </div>
        </div>
        <audio id="audio-element" preload="metadata"></audio>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share Album</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Share this album with your friends:</p>
                    <div class="d-flex gap-2 mb-3">
                        <input type="text" class="form-control" id="share-link"
                            value="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" readonly>
                        <button id="copy-link-btn" class="btn-main">Copy</button>
                    </div>
                    <div class="d-flex gap-3 mt-4 justify-content-center">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                            target="_blank" class="track-btn" style="background: #1877f2; color: white;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode('Check out ' . $album['title'] . ' by ' . $album['artist_name']) ?>"
                            target="_blank" class="track-btn" style="background: #1da1f2; color: white;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode('Check out ' . $album['title'] . ' by ' . $album['artist_name'] . ': ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                            target="_blank" class="track-btn" style="background: #25d366; color: white;">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://telegram.me/share/url?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode('Check out ' . $album['title'] . ' by ' . $album['artist_name']) ?>"
                            target="_blank" class="track-btn" style="background: #0088cc; color: white;">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rate Album Modal (NEW) -->
    <div class="modal fade" id="rateModal" tabindex="-1" aria-labelledby="rateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="rateModalLabel">Rate This Album</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($is_logged_in): ?>
                    <div class="rating-form-container">
                        <form id="rating-form" method="POST" action="album_details.php?id=<?= $album_id ?>">
                            <div class="user-rating-stars mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="rating-star" data-rating="<?= $i ?>">
                                    <i class="<?= $i <= $user_rating ? 'fas' : 'far' ?> fa-star fa-lg"></i>
                                </span>
                                <?php endfor; ?>
                                <input type="hidden" name="rating" id="rating-input" value="<?= $user_rating ?>">
                            </div>

                            <div class="mb-3">
                                <textarea class="form-control" id="review" name="review" rows="3"
                                    placeholder="Share your thoughts about this album..."><?= htmlspecialchars($user_review) ?></textarea>
                            </div>

                            <button type="submit" name="rating_submit" class="btn-main">Submit Rating</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <p>You need to be logged in to rate this album. <a href="../login.php">Login</a> or <a
                                href="../register.php">Register</a></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <?php
require_once '../layout/footer.php';

?>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
    // Wait for the document to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Audio element and player container
        const audioElement = document.getElementById('audio-element');
        const audioPlayer = document.getElementById('audio-player-container');
        const playButton = document.getElementById('play-btn');
        const prevButton = document.getElementById('prev-btn');
        const nextButton = document.getElementById('next-btn');
        const progressBar = document.getElementById('progress-bar');
        const progressContainer = document.getElementById('progress-container');
        const currentTimeElement = document.getElementById('current-time');
        const durationElement = document.getElementById('duration');
        const volumeBar = document.getElementById('volume-bar');
        const volumeButton = document.getElementById('volume-btn');
        const shuffleButton = document.getElementById('shuffle-btn');
        const repeatButton = document.getElementById('repeat-btn');
        const closePlayerButton = document.getElementById('close-player-btn');
        const playerThumbnail = document.getElementById('player-thumbnail');
        const playerTrackTitle = document.getElementById('player-track-title');
        const playAllButton = document.getElementById('play-all-btn');

        // Track list management
        let tracks = [];
        let currentTrackIndex = 0;
        let isPlaying = false;
        let isShuffled = false;
        let repeatMode = 'none'; // 'none', 'all', 'one'

        // Collect all tracks
        const trackElements = document.querySelectorAll('.track-item');
        trackElements.forEach(track => {
            tracks.push({
                id: track.getAttribute('data-track-id'),
                path: track.getAttribute('data-track-path'),
                title: track.getAttribute('data-track-title'),
                number: track.getAttribute('data-track-num'),
                thumbnail: track.getAttribute('data-track-thumbnail')
            });

            // Individual track play button
            const playBtn = track.querySelector('.track-play-btn');
            if (playBtn) {
                playBtn.addEventListener('click', function() {
                    const trackIndex = tracks.findIndex(t => t.id === track.getAttribute(
                        'data-track-id'));
                    if (trackIndex !== -1) {
                        playTrack(trackIndex);
                    }
                });
            }
        });

        // "Play All" button click event
        if (playAllButton) {
            playAllButton.addEventListener('click', function() {
                if (tracks.length > 0) {
                    // Show the player and start playing from the first track
                    showPlayer();
                    playTrack(0);

                    // Add visual feedback
                    this.innerHTML = '<i class="fas fa-music"></i> Now Playing';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-play"></i> Play All';
                    }, 2000);
                }
            });
        }

        // Play/Pause button
        playButton.addEventListener('click', togglePlay);

        // Previous track button
        prevButton.addEventListener('click', playPrevious);

        // Next track button
        nextButton.addEventListener('click', playNext);

        // Close player button
        closePlayerButton.addEventListener('click', function() {
            audioElement.pause();
            isPlaying = false;
            updatePlayButton();
            audioPlayer.classList.remove('active');
        });

        // Shuffle button
        shuffleButton.addEventListener('click', function() {
            isShuffled = !isShuffled;
            this.classList.toggle('active', isShuffled);
        });

        // Repeat button
        repeatButton.addEventListener('click', function() {
            if (repeatMode === 'none') {
                repeatMode = 'all';
                this.classList.add('active');
                this.innerHTML = '<i class="fas fa-redo"></i>';
            } else if (repeatMode === 'all') {
                repeatMode = 'one';
                this.innerHTML = '<i class="fas fa-redo-alt"></i>';
            } else {
                repeatMode = 'none';
                this.classList.remove('active');
                this.innerHTML = '<i class="fas fa-redo"></i>';
            }
        });

        // Update progress bar when audio time updates
        audioElement.addEventListener('timeupdate', updateProgress);

        // Seek when clicking on progress bar
        progressContainer.addEventListener('click', setProgress);

        // When track ends
        audioElement.addEventListener('ended', function() {
            if (repeatMode === 'one') {
                // Repeat the same track
                audioElement.currentTime = 0;
                audioElement.play();
            } else if (repeatMode === 'all' || currentTrackIndex < tracks.length - 1) {
                // Go to next track or loop back to first if repeat all
                playNext();
            } else {
                // End of playlist with no repeat
                isPlaying = false;
                updatePlayButton();
            }
        });

        // Volume control
        volumeBar.parentElement.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const width = rect.width;
            const clickX = e.clientX - rect.left;
            const volume = clickX / width;
            setVolume(volume);
        });

        // Mute/unmute
        volumeButton.addEventListener('click', function() {
            if (audioElement.volume > 0) {
                audioElement.dataset.previousVolume = audioElement.volume;
                setVolume(0);
            } else {
                setVolume(audioElement.dataset.previousVolume || 0.7);
            }
        });

        // Update audio player when metadata is loaded
        audioElement.addEventListener('loadedmetadata', function() {
            durationElement.textContent = formatTime(audioElement.duration);
        });

        // Initial volume setting
        setVolume(0.7);

        // Functions
        function playTrack(index) {
            if (index >= 0 && index < tracks.length) {
                currentTrackIndex = index;
                audioElement.src = tracks[index].path;
                audioElement.load();

                // Highlight the currently playing track in the list
                document.querySelectorAll('.track-item').forEach((item, idx) => {
                    if (idx === currentTrackIndex) {
                        item.classList.add('now-playing');
                    } else {
                        item.classList.remove('now-playing');
                    }
                });

                audioElement.play().then(() => {
                    isPlaying = true;
                    updatePlayButton();
                    updateTrackInfo();
                    showPlayer();
                }).catch(error => {
                    console.error('Error playing audio:', error);
                });
            }
        }

        function togglePlay() {
            if (audioElement.src) {
                if (isPlaying) {
                    audioElement.pause();
                    isPlaying = false;
                } else {
                    audioElement.play();
                    isPlaying = true;
                }
                updatePlayButton();
            } else if (tracks.length > 0) {
                playTrack(0);
            }
        }

        function updatePlayButton() {
            if (isPlaying) {
                playButton.innerHTML = '<i class="fas fa-pause"></i>';
            } else {
                playButton.innerHTML = '<i class="fas fa-play"></i>';
            }
        }

        function playPrevious() {
            let index;
            if (isShuffled) {
                index = Math.floor(Math.random() * tracks.length);
            } else {
                index = (currentTrackIndex - 1 + tracks.length) % tracks.length;
            }
            playTrack(index);
        }

        function playNext() {
            let index;
            if (isShuffled) {
                index = Math.floor(Math.random() * tracks.length);
            } else if (repeatMode === 'all' && currentTrackIndex === tracks.length - 1) {
                index = 0; // Loop back to first track if repeat all
            } else {
                index = (currentTrackIndex + 1) % tracks.length;
            }
            playTrack(index);
        }

        function updateProgress() {
            const currentTime = audioElement.currentTime;
            const duration = audioElement.duration || 1;
            const progressPercent = (currentTime / duration) * 100;
            progressBar.style.width = `${progressPercent}%`;
            currentTimeElement.textContent = formatTime(currentTime);
        }

        function setProgress(e) {
            const width = this.clientWidth;
            const clickX = e.offsetX;
            const duration = audioElement.duration;
            audioElement.currentTime = (clickX / width) * duration;
        }

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
        }

        function setVolume(volume) {
            volume = Math.max(0, Math.min(1, volume));
            audioElement.volume = volume;
            volumeBar.style.width = `${volume * 100}%`;

            // Update volume icon
            if (volume === 0) {
                volumeButton.innerHTML = '<i class="fas fa-volume-mute"></i>';
            } else if (volume < 0.5) {
                volumeButton.innerHTML = '<i class="fas fa-volume-down"></i>';
            } else {
                volumeButton.innerHTML = '<i class="fas fa-volume-up"></i>';
            }
        }

        function updateTrackInfo() {
            const currentTrack = tracks[currentTrackIndex];
            playerTrackTitle.textContent = currentTrack.title;
            playerThumbnail.src = currentTrack.thumbnail;
        }

        function showPlayer() {
            audioPlayer.classList.add('active');

            // Animation effect when player appears
            audioPlayer.style.animation = 'slideUp 0.3s ease-out forwards';
        }

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

        // Album favorite functionality
        const favoriteButton = document.getElementById('favorite-btn');
        if (favoriteButton) {
            favoriteButton.addEventListener('click', function() {
                if (!this.classList.contains('loading')) {
                    this.classList.add('loading');
                    const albumId = this.getAttribute('data-album-id');
                    const isFavorite = this.getAttribute('data-is-favorite') === '1';
                    toggleFavorite(albumId, 'album', !isFavorite, this);
                }
            });
        }

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

        // Copy share link functionality
        const copyLinkBtn = document.getElementById('copy-link-btn');
        if (copyLinkBtn) {
            copyLinkBtn.addEventListener('click', function() {
                const shareLink = document.getElementById('share-link');
                shareLink.select();
                document.execCommand('copy');
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.textContent = 'Copy';
                }, 2000);
            });
        }

        // Rating stars functionality
        const ratingStars = document.querySelectorAll('.rating-star');
        const ratingInput = document.getElementById('rating-input');

        if (ratingStars.length > 0 && ratingInput) {
            ratingStars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingInput.value = rating;

                    // Update star appearance
                    ratingStars.forEach(s => {
                        const r = s.getAttribute('data-rating');
                        s.querySelector('i').className = r <= rating ?
                            'fas fa-star fa-lg' : 'far fa-star fa-lg';
                    });
                });

                // Hover effects
                star.addEventListener('mouseenter', function() {
                    const hoveredRating = this.getAttribute('data-rating');
                    ratingStars.forEach(s => {
                        const r = s.getAttribute('data-rating');
                        if (r <= hoveredRating) {
                            s.querySelector('i').className = 'fas fa-star fa-lg';
                        }
                    });
                });

                star.addEventListener('mouseleave', function() {
                    const currentRating = ratingInput.value;
                    ratingStars.forEach(s => {
                        const r = s.getAttribute('data-rating');
                        s.querySelector('i').className = r <= currentRating ?
                            'fas fa-star fa-lg' : 'far fa-star fa-lg';
                    });
                });
            });
        }
    });
    </script>
</body>

</html>