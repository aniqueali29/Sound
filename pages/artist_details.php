<?php

require_once '../includes/config_db.php';



// Get artist ID from URL parameter
$artist_id = isset($_GET['id']) ? intval($_GET['id']) : 1; // Default to ID 1 if not provided

// Fetch artist details
$stmt = $conn->prepare("SELECT * FROM artists WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$artist = $result->fetch_assoc();

// If artist not found, redirect to artists list
if (!$artist) {
    header("Location: artists.php");
    exit;
}

// Get track count
$stmt = $conn->prepare("SELECT COUNT(*) as track_count FROM music WHERE artist_id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$trackCountRow = $result->fetch_assoc();
$trackCount = $trackCountRow['track_count'];

// Get album count
$stmt = $conn->prepare("SELECT COUNT(*) as album_count FROM albums WHERE artist_id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$albumCountRow = $result->fetch_assoc();
$albumCount = $albumCountRow['album_count'];

// Get total plays from music
$stmt = $conn->prepare("SELECT SUM(plays) as total_plays FROM music WHERE artist_id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$playsRow = $result->fetch_assoc();
$musicPlays = $playsRow['total_plays'] ?: 0;

// Get total views from videos
$stmt = $conn->prepare("SELECT SUM(views) as total_views FROM videos WHERE artist_id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$viewsRow = $result->fetch_assoc();
$videoViews = $viewsRow['total_views'] ?: 0;

// Total plays (music plays + video views)
$totalPlays = $musicPlays + $videoViews;

// Get artist music with genre and language names
$stmt = $conn->prepare("
    SELECT m.*, g.name as genre_name, l.name as language_name, a.title as album_title, a.cover_image as album_cover 
    FROM music m
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN languages l ON m.language_id = l.id
    LEFT JOIN albums a ON m.album_id = a.id
    WHERE m.artist_id = ? AND m.deleted_at IS NULL AND m.is_active = 1
    ORDER BY m.is_new DESC, m.created_at DESC
    LIMIT 6
");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$artistMusic = [];
while ($row = $result->fetch_assoc()) {
    $artistMusic[] = $row;
}

// Get artist videos with genre and language names
$stmt = $conn->prepare("
    SELECT v.*, g.name as genre_name, l.name as language_name, a.title as album_title, a.cover_image as album_cover
    FROM videos v
    LEFT JOIN genres g ON v.genre_id = g.id
    LEFT JOIN languages l ON v.language_id = l.id
    LEFT JOIN albums a ON v.album_id = a.id
    WHERE v.artist_id = ? AND v.deleted_at IS NULL AND v.is_active = 1
    ORDER BY v.is_new DESC, v.created_at DESC
    LIMIT 6
");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$artistVideos = [];
while ($row = $result->fetch_assoc()) {
    $artistVideos[] = $row;
}

// Get artist albums with track count
$stmt = $conn->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM music WHERE album_id = a.id AND deleted_at IS NULL) as track_count
    FROM albums a
    WHERE a.artist_id = ? AND a.deleted_at IS NULL
    ORDER BY a.release_year DESC
");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$artistAlbums = [];
while ($row = $result->fetch_assoc()) {
    $artistAlbums[] = $row;
}

// Get similar albums (based on genre)
$stmt = $conn->prepare("
    SELECT DISTINCT a.*
    FROM albums a
    JOIN albums artist_albums ON a.genre_id = artist_albums.genre_id
    WHERE artist_albums.artist_id = ?
    AND a.artist_id != ?
    AND a.deleted_at IS NULL
    ORDER BY RAND()
    LIMIT 3
");
$stmt->bind_param("ii", $artist_id, $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$similarAlbums = [];
while ($row = $result->fetch_assoc()) {
    $similarAlbums[] = $row;
}

// Get artist genres
$stmt = $conn->prepare("
    SELECT DISTINCT g.name 
    FROM (
        SELECT genre_id FROM music WHERE artist_id = ? AND genre_id IS NOT NULL AND deleted_at IS NULL
        UNION
        SELECT genre_id FROM videos WHERE artist_id = ? AND genre_id IS NOT NULL AND deleted_at IS NULL
        UNION
        SELECT genre_id FROM albums WHERE artist_id = ? AND genre_id IS NOT NULL AND deleted_at IS NULL
    ) AS genre_ids
    JOIN genres g ON genre_ids.genre_id = g.id
");
$stmt->bind_param("iii", $artist_id, $artist_id, $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$artistGenres = [];
while ($row = $result->fetch_assoc()) {
    $artistGenres[] = $row['name'];
}

// Get artist languages
$stmt = $conn->prepare("
    SELECT DISTINCT l.name 
    FROM (
        SELECT language_id FROM music WHERE artist_id = ? AND language_id IS NOT NULL AND deleted_at IS NULL
        UNION
        SELECT language_id FROM videos WHERE artist_id = ? AND language_id IS NOT NULL AND deleted_at IS NULL
        UNION
        SELECT language_id FROM albums WHERE artist_id = ? AND language_id IS NOT NULL AND deleted_at IS NULL
    ) AS lang_ids
    JOIN languages l ON lang_ids.language_id = l.id
");
$stmt->bind_param("iii", $artist_id, $artist_id, $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$artistLanguages = [];
while ($row = $result->fetch_assoc()) {
    $artistLanguages[] = $row['name'];
}

// Get similar artists based on genre
$stmt = $conn->prepare("
    SELECT DISTINCT a.id, a.name, a.image 
    FROM artists a
    JOIN music m1 ON a.id = m1.artist_id
    JOIN music m2 ON m1.genre_id = m2.genre_id
    WHERE m2.artist_id = ?
    AND a.id != ?
    AND a.deleted_at IS NULL
    ORDER BY RAND()
    LIMIT 3
");
$stmt->bind_param("ii", $artist_id, $artist_id);
$stmt->execute();
$result = $stmt->get_result();
$similarArtists = [];
while ($row = $result->fetch_assoc()) {
    $similarArtists[] = $row;
}

// If there aren't enough similar artists, get random ones
// if (count($similarArtists) < 3) {
//     $stmt = $conn->prepare("
//         SELECT id, name, image 
//         FROM artists 
//         WHERE id != ? AND deleted_at IS NULL
//         AND id NOT IN (" . implode(',', array_map(function($artist) { return $artist['id']; }, $similarArtists)) . ")
//         ORDER BY RAND()
//         LIMIT ?
//     ");
//     $limit = 3 - count($similarArtists);
//     $stmt->bind_param("ii", $artist_id, $limit);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     while ($row = $result->fetch_assoc()) {
//         $similarArtists[] = $row;
//     }
// }

// // If still not enough, use placeholder data
// while (count($similarArtists) < 3) {
//     $similarArtists[] = [
//         'id' => 0,
//         'name' => count($similarArtists) == 0 ? 'Maya Ren' : (count($similarArtists) == 1 ? 'Zephyr' : 'Lunar Echo'),
//         'image' => null
//     ];
// }
require_once '../layout/header.php';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile | SOUND</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Quicksand:wght@300;400;500;600&display=swap');

    :root {
        --neon-green: #0ff47a;
    }

    body {
        background-color: #060b19;
        background-image:
            radial-gradient(circle at 10% 20%, rgba(91, 2, 154, 0.2) 0%, rgba(0, 0, 0, 0) 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 65, 108, 0.2) 0%, rgba(0, 0, 0, 0) 40%);
        color: #fff;
        font-family: 'Quicksand', sans-serif;
        overflow-x: hidden;
    }

    .particles {
        position: fixed;
        width: 100vw;
        height: 100vh;
        z-index: -999;
        top: 0;
        left: 0;
    }

    /* Custom Styles */
    .artist-header {
        position: relative;
        height: 350px;
        overflow: hidden;
        border-radius: 0 0 20px 20px;
        margin-bottom: 30px;
    }

    .artist-header-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(0deg, rgba(6, 11, 25, 1) 0%, rgba(6, 11, 25, 0.7) 50%, rgba(6, 11, 25, 0.4) 100%);
        z-index: 1;
    }

    .artist-header img {
        /* width: 100%;
            height: 100%; */
        object-fit: cover;
        opacity: 0.7;
    }

    .artist-info {
        position: absolute;
        bottom: 30px;
        left: 50px;
        z-index: 2;
    }

    .artist-name {
        font-family: 'Orbitron', sans-serif;
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 0;
        text-shadow: 0 0 10px rgba(15, 244, 122, 0.5);
    }

    .artist-meta {
        opacity: 0.8;
        font-size: 1.1rem;
    }

    .artist-profile {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 3px solid var(--neon-green);
        object-fit: cover;
        object-position: top;
        box-shadow: 0 0 15px rgba(15, 244, 122, 0.5);
    }

    .section-title {
        font-family: 'Orbitron', sans-serif;
        margin-bottom: 25px;
        font-weight: 600;
        color: #8c9eff;
        border-bottom: 1px solid #8c9eff;
        padding-bottom: 10px;
    }

    .music-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .music-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(15, 244, 122, 0.3);
        box-shadow: 0 5px 15px rgba(15, 244, 122, 0.2);
    }

    .music-title {
        font-family: 'Orbitron', sans-serif;
        font-weight: 500;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }

    .music-meta {
        font-size: 0.9rem;
        opacity: 0.7;
    }

    .music-thumbnail {
        width: 100%;
        height: 280px;
        object-fit: cover;
        object-position: top;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .music-rating {
        color: gold;
        font-size: 1.2rem;
        margin-right: 10px;
    }

    .music-plays {
        font-size: 0.9rem;
        opacity: 0.7;
    }

    .action-btn {
        /* background: transparent; */
        color: white;
        border: 1px solid rgba(15, 244, 122, 0.5);
        border-radius: 20px;
        padding: 5px 15px;
        padding-left: 20px;
        font-size: .9rem;
        transition: all 0.3s;
    }

    .action-btn:hover {
        background: var(--neon-green);
        color: #060b19;
    }

    .play-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(15, 244, 122, 0.8);
        color: #060b19;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s;
    }

    .thumbnail-container {
        position: relative;
    }

    .thumbnail-container:hover .play-btn {
        opacity: 1;
    }

    .new-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--neon-green);
        color: #060b19;
        font-size: 0.8rem;
        padding: 3px 10px;
        border-radius: 10px;
        font-weight: 600;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(15, 244, 122, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(15, 244, 122, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(15, 244, 122, 0);
        }
    }

    .bio-section {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .review-section {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .review-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .review-user {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .review-time {
        font-size: 0.8rem;
        opacity: 0.7;
    }

    .review-text {
        margin-top: 10px;
        font-size: 0.95rem;
    }

    .star-rating {
        color: #ccc;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .star-rating .star:hover~.star,
    .star-rating .star:hover {
        color: gold;
    }

    .star-filled {
        color: gold;
    }

    .album-badge {
        background: rgba(255, 255, 108, 0.2);
        color: #fff;
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 10px;
        margin-right: 5px;
    }

    .genre-badge {
        background: rgba(108, 255, 255, 0.2);
        color: #fff;
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 10px;
        margin-right: 5px;
    }

    .language-badge {
        background: rgba(255, 108, 255, 0.2);
        color: #fff;
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 10px;
    }

    .stats-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        text-align: center;
    }

    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #fff;
        font-family: 'Orbitron', sans-serif;
    }

    .stats-label {
        font-size: 0.9rem;
        opacity: 0.7;
    }

    .similar-artists .artist-item {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 10px;
        text-align: center;
        transition: all 0.3s;
    }

    .similar-artists .artist-item:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-5px);
    }

    .similar-artists img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 10px;
        object-fit: cover;
        object-position: top;

    }

    .similar-artists .artist-name-small {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .bg-img {
        width: 100%;
        height: auto;
        object-fit: contain;
        object-position: bottom;
        display: block;
    }

    .social-link {
        transition: 0.2s ease-in-out;
        text-decoration: none;
        padding: 10px 15px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
    }

    .social-link:hover {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 0.2);
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

    <main class="container py-4" style="margin-top:80px;">
        <!-- Artist Hero Section -->
        <div class="artist-header">
            <img src="../uploads/image.png" alt="Artist Background" class="bg-img">
            <div class="artist-header-overlay"></div>
            <div class="artist-info d-flex align-items-end">
                <img src="<?php echo str_replace(["../../../", "../../"], "../", $artist['image']); ?>"
                    alt="<?php echo $artist['name']; ?>" class="artist-profile me-4" width: 50px; height: 50px;>
                <div>
                    <h1 class="artist-name"><?php echo $artist['name']; ?></h1>
                    <p class="artist-meta">
                        <span class="me-3"><i class="fas fa-music me-2"></i> <?php echo $trackCount; ?>
                            Tracks</span><br>
                        <span><i class="fas fa-headphones me-2"></i> <?php echo $totalPlays; ?> Plays</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Bio Section -->
                <div class="bio-section mb-4">
                    <h3 class="section-title">Biography</h3>
                    <p><?php echo $artist['bio']; ?></p>
                </div>

                <!-- Music Section -->
                <div class="mb-4">
                    <h3 class="section-title">Latest Music</h3>
                    <div class="row">
                        <?php foreach($artistMusic as $music): ?>
                        <!-- Music Item -->
                        <div class="col-md-6">
                            <div class="music-card">
                                <div class="thumbnail-container">
                                    <?php
                                    // Use album cover if available, otherwise use music thumbnail
                                    $thumbnailPath = !empty($music['album_cover']) ? 
                                        str_replace(["../../../", "../../"], "../", '../uploads/albums/covers/' . $music['album_cover']) : 
                                        (!empty($music['thumbnail_path']) ? 
                                            str_replace(["../../../", "../../"], "../",  $music['thumbnail_path']) : 
                                            '/api/placeholder/300/200');
                                    ?>
                                    <img src="<?php echo htmlspecialchars($thumbnailPath, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars($music['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                        class="music-thumbnail">

                                    <div class="play-btn" onclick="playMusic(<?php echo $music['id']; ?>)">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <?php if($music['is_new']): ?>
                                    <div class="new-badge">NEW</div>
                                    <?php endif; ?>
                                </div>
                                <h4 class="music-title"><?php echo $music['title']; ?></h4>
                                <p class="music-meta mb-2">
                                    <span
                                        class="album-badge"><?php echo $music['album_title'] ? $music['album_title'] : 'Single'; ?></span>
                                    <span class="genre-badge"><?php echo $music['genre_name']; ?></span>
                                    <span class="language-badge"><?php echo $music['language_name']; ?></span>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="music-rating">
                                            <i class="fas fa-star"></i> <?php echo $music['rating'] ?: '4.8'; ?>
                                        </span>
                                        <span class="music-plays">
                                            <i class="fas fa-headphones me-1"></i> <?php echo $music['plays']; ?>
                                        </span>
                                    </div>
                                    <span class="music-meta"><?php echo $music['release_year']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button class="action-btn" onclick="window.location.href='./music';">
                                        <i class="fas fa-play me-1"></i>
                                    </button>

                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Videos Section -->
                <div class="mb-4">
                    <h3 class="section-title">Latest Videos</h3>
                    <div class="row">
                        <?php if (!empty($artistVideos)): ?>
                        <?php foreach ($artistVideos as $video): ?>
                        <!-- Video Item -->
                        <div class="col-md-6">
                            <div class="music-card">
                                <div class="thumbnail-container">
                                    <?php
                            // Determine the thumbnail to use (album cover, video thumbnail, or placeholder)
                            $thumbnailPath = !empty($video['album_cover']) ? 
                                str_replace(["../../../", "../../"], "../", $video['album_cover']) : 
                                (!empty($video['thumbnail']) ? 
                                    str_replace(["../../../", "../../"], "../", $video['thumbnail']) : 
                                    '/api/placeholder/300/200');
                            ?>
                                    <img src="<?php echo htmlspecialchars($thumbnailPath, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                        class="music-thumbnail">
                                    <div class="play-btn" onclick="playVideo(<?php echo $video['id']; ?>)">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <?php if ($video['is_new']): ?>
                                    <div class="new-badge">NEW</div>
                                    <?php endif; ?>
                                </div>

                                <h4 class="music-title"><?php echo $video['title']; ?></h4>
                                <p class="music-meta mb-2">
                                    <span class="album-badge">
                                        <?php echo !empty($video['album_title']) ? $video['album_title'] : 'Single'; ?>
                                    </span>
                                    <span class="genre-badge"><?php echo $video['genre_name']; ?></span>
                                    <span class="language-badge"><?php echo $video['language_name']; ?></span>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="music-plays">
                                            <i class="fas fa-eye me-1"></i> <?php echo $video['views']; ?>
                                        </span>
                                        <span class="music-likes">
                                            <i class="fas fa-thumbs-up me-1"></i> <?php echo $video['likes']; ?>
                                        </span>
                                    </div>
                                    <span class="music-meta"><?php echo $video['release_year']; ?></span>
                                </div>

                                <?php if (!empty($video['video_url'])): ?>
                                <!-- If video is available -->
                                <div class="d-flex justify-content-between">
                                    <button class="action-btn" onclick="playVideo(<?php echo $video['id']; ?>)">
                                        <i class="fas fa-play me-1"></i> Watch
                                    </button>
                                    <button class="action-btn"
                                        onclick="addToFavorites(<?php echo $video['id']; ?>, 'video')">
                                        <i class="fas fa-heart me-1"></i> Like
                                    </button>
                                </div>
                                <?php else: ?>
                                <!-- If video is NOT available -->
                                <!-- <p class="text-muted text-center">Video not available</p> -->
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <p class="">No videos available of this artist.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Artist Stats -->
                <div class="mb-4">
                    <h3 class="section-title">Artist Stats</h3>
                    <div class="row">
                        <div class="col-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $trackCount; ?></div>
                                <div class="stats-label">Tracks</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $albumCount; ?></div>
                                <div class="stats-label">Albums</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalPlays; ?></div>
                                <div class="stats-label">Plays</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Genre & Language -->
                <div class="mb-4">
                    <h3 class="section-title">Music Style</h3>
                    <div class="music-card">
                        <h5 class="mb-3">Genres</h5>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php foreach($artistGenres as $genre): ?>
                            <span class="genre-badge"><?php echo $genre; ?></span>
                            <?php endforeach; ?>
                            <?php if(empty($artistGenres)): ?>
                            <span class="genre-badge">No genres found</span>
                            <?php endif; ?>
                        </div>

                        <h5 class="mb-3">Languages</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($artistLanguages as $language): ?>
                            <span class="language-badge"><?php echo $language; ?></span>
                            <?php endforeach; ?>
                            <?php if(empty($artistLanguages)): ?>
                            <span class="language-badge">No languages found</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Albums -->
                <div class="mb-4">
                    <h3 class="section-title">Albums</h3>
                    <?php if(count($artistAlbums) > 0): ?>
                    <?php foreach($artistAlbums as $album): ?>
                    <div class="music-card">
                        <div class="d-flex align-items-center mb-3">
                            <?php
                                $albumCover = !empty($album['cover_image']) ? 
                                    str_replace(["../../../", "../../"], "../", '../uploads/albums/covers/' . $album['cover_image']) : 
                                    '/api/placeholder/80/80';
                                ?>
                            <img src="<?php echo htmlspecialchars($albumCover, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8'); ?>" class="me-3"
                                style="width: 80px; height: 80px; border-radius: 10px;">
                            <div>
                                <h5 class="mb-1"><?php echo $album['title']; ?></h5>
                                <p class="mb-0 music-meta"><?php echo $album['release_year']; ?> •
                                    <?php echo $album['track_count']; ?> tracks</p>
                            </div>
                        </div>
                        <button class="action-btn w-100" onclick="playAlbum(<?php echo $album['id']; ?>)">
                            <i class="fas fa-play me-1"></i> Play Album
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="music-card">
                        <p class="text-center">No albums found for this artist.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Similar Albums -->
                <?php if(count($similarAlbums) > 0): ?>
                <div class="mb-4">
                    <h3 class="section-title">Similar Albums You Might Like</h3>
                    <?php foreach($similarAlbums as $album): ?>
                    <div class="music-card">
                        <div class="d-flex align-items-center mb-3">
                            <?php
                            $albumCover = !empty($album['cover_image']) ? 
                                str_replace(["../../../", "../../"], "../", $album['cover_image']) : 
                                '/api/placeholder/80/80';
                            ?>
                            <img src="<?php echo htmlspecialchars($albumCover, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8'); ?>" class="me-3"
                                style="width: 80px; height: 80px; border-radius: 10px;">
                            <div>
                                <h5 class="mb-1"><?php echo $album['title']; ?></h5>
                                <p class="mb-0 music-meta"><?php echo $album['release_year']; ?></p>
                            </div>
                        </div>
                        <button class="action-btn w-100" onclick="playAlbum(<?php echo $album['id']; ?>)">
                            <i class="fas fa-play me-1"></i> Explore Album
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>



                <!-- Social Share -->
                <div class="mb-4">
                    <h3 class="section-title">Share Artist</h3>
                    <div class="music-card d-flex justify-content-center">
                        <?php $artistUrl = "localhost/sound/pages/artist_details?id=" . $artist['id']; ?>

                        <!-- Copy Link -->
                        <a href="#" class="text-white fs-4 social-link"
                            onclick="copyArtistLink('<?php echo $artistUrl; ?>')">
                            <i class="fas fa-link"></i>
                        </a>
                    </div>
                </div>

                <!-- JavaScript for Copy Link -->
                <script>
                function copyArtistLink(link) {
                    navigator.clipboard.writeText(link).then(() => {
                        alert("Artist link copied!");
                    });
                }
                </script>



            </div>
        </div>
    </main>
    <?php
require_once '../layout/footer.php';

    ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function playVideo(videoId) {
        // Redirect to video player page
        window.location.href = 'video_details.php?id=' + videoId;
    }

    function playAlbum(albumId) {
        // Redirect to album page or player
        window.location.href = 'album_details.php?id=' + albumId;
    }

    function addToFavorites(itemId, type) {
        // AJAX call to add item to favorites
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'add_favorite.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                alert(response.message); // Show success/error message
            }
        };
        xhr.send('item_id=' + itemId + '&type=' + type);
    }

    function shareArtist(platform, artistId) {
        // Function to share artist on different platforms
        let shareUrl = window.location.href;

        switch (platform) {
            case 'facebook':
                window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl), '_blank');
                break;
            case 'twitter':
                window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl), '_blank');
                break;
            case 'instagram':
                alert('Copy this link to share on Instagram: ' + shareUrl);
                break;
            case 'youtube':
                alert('Copy this link to share: ' + shareUrl);
                break;
            case 'share':
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        url: shareUrl
                    });
                } else {
                    alert('Copy this link to share: ' + shareUrl);
                }
                break;
        }
    }

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
    </script>
</body>

</html>