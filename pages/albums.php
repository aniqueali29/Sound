<?php
require_once '../includes/config_db.php';
require_once '../layout/header.php';

// Get filter parameters
$genre = isset($_GET['genre']) ? (int)$_GET['genre'] : 0;
$language = isset($_GET['language']) ? (int)$_GET['language'] : 0;
$year = isset($_GET['year']) ? $_GET['year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT a.*, ar.name as artist_name, g.name as genre_name, l.name as language_name, 
            (SELECT COUNT(*) FROM playlist_music WHERE album_id = a.id) as track_count,
            (SELECT COUNT(*) FROM favorites WHERE album_id = a.id) as listen_count 
          FROM albums a 
          LEFT JOIN artists ar ON a.artist_id = ar.id 
          LEFT JOIN genres g ON a.genre_id = g.id 
          LEFT JOIN languages l ON a.language_id = l.id 
          WHERE a.deleted_at IS NULL";

// Apply filters
if ($genre > 0) {
    $query .= " AND a.genre_id = $genre";
}

if ($language > 0) {
    $query .= " AND a.language_id = $language";
}

if (!empty($year)) {
    if (strpos($year, '-') !== false) {
        // Year range
        $year_range = explode('-', $year);
        $query .= " AND a.release_year BETWEEN {$year_range[0]} AND {$year_range[1]}";
    } else {
        // Specific year
        $query .= " AND a.release_year = $year";
    }
}

// Apply search
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (a.title LIKE '%$search%' OR ar.name LIKE '%$search%')";
}

// Default sorting
$query .= " ORDER BY a.release_year DESC, a.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

$count_query = "SELECT COUNT(*) as total FROM ($query) as subquery";
$total_result = mysqli_query($conn, $count_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_albums = $total_row['total'];
$total_pages = ceil($total_albums / $items_per_page);

$query .= " LIMIT $offset, $items_per_page";
$result = mysqli_query($conn, $query);

// Get all genres and languages for filters
$genres_query = "SELECT * FROM genres ORDER BY name ASC";
$genres_result = mysqli_query($conn, $genres_query);

$languages_query = "SELECT * FROM languages ORDER BY name ASC";
$languages_result = mysqli_query($conn, $languages_query);

// Get all available years from the database
$years_query = "SELECT DISTINCT release_year FROM albums WHERE deleted_at IS NULL ORDER BY release_year DESC";
$years_result = mysqli_query($conn, $years_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albums | SOUND</title>
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

        /* Page Header */
        .page-header {
            padding: 40px 0;
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(15, 244, 122, 0.5);
            background: linear-gradient(to right, #8c9eff, #0ff47a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            opacity: 0.8;
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }


        /* Album Cards (keeping the same as original) */
        .album-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            height: 100%;
        }

        .album-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(15, 244, 122, 0.3);
            box-shadow: 0 10px 20px rgba(15, 244, 122, 0.2);
        }

        .album-img-container {
            position: relative;
            margin-bottom: 15px;
            overflow: hidden;
            border-radius: 10px;
        }

        .album-img {
            width: 100%;
            height: 280px;
            /* object-fit: contain; */
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.5s;
            object-position: top !important;
        }

        .album-img-container:hover .album-img {
            transform: scale(1.05);
        }

        .album-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(0deg, rgba(6, 11, 25, 0.7) 0%, rgba(6, 11, 25, 0) 50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .album-img-container:hover .album-overlay {
            opacity: 1;
        }

        .album-actions {
            position: absolute;
            bottom: 15px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
        }

        .album-img-container:hover .album-actions {
            opacity: 1;
            transform: translateY(0);
        }

        .album-actions a {
            text-decoration: none;
        }

        .album-action-btn {
            background: rgba(15, 244, 122, 0.8);
            color: #060b19;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .album-action-btn:hover {
            transform: scale(1.1);
            background: var(--neon-green);
        }

        .album-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .album-artist {
            color: #8c9eff;
            margin-bottom: 10px;
        }

        .album-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .album-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }

        .album-badge {
            background: rgba(255, 255, 108, 0.2);
            color: #fff;
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .genre-badge {
            background: rgba(108, 255, 255, 0.2);
            color: #fff;
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .language-badge {
            background: rgba(255, 108, 255, 0.2);
            color: #fff;
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .album-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .album-rating {
            color: gold;
            font-size: 1rem;
        }

        .album-tracks {
            font-size: 0.9rem;
            opacity: 0.8;
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
            z-index: 2;
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

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin: 40px 0;
        }

        .pagination .page-link {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(15, 244, 122, 0.3);
            color: #fff;
            margin: 0 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .pagination .page-link:hover,
        .pagination .page-item.active .page-link {
            background: var(--neon-green);
            color: #060b19;
            border-color: var(--neon-green);
        }

        .pagination .page-item.active .page-link {
            font-weight: bold;
        }




        /*----------------------------------------Filter CODE------------------------------------------ */

        .search-container {
            background: rgba(31, 35, 61, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin: auto;
            max-width: 100%;
            box-shadow: 0 0 20px rgba(79, 109, 197, 0.3);
            border: 1px solid rgba(79, 109, 197, 0.5);
            position: relative;
            overflow: hidden;
        }

        .search-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, 0.05) 50%,
                    rgba(255, 255, 255, 0) 100%);
            transform: rotate(45deg);
            animation: shimmer 6s infinite linear;
            z-index: 0;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        /* .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    } */

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }



        .filter-dropdown {
            background-color: rgba(25, 28, 49, 0.9);
            color: white;
            border: 1px solid #383d6e;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            min-width: 160px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-dropdown:hover {
            border-color: #8c9eff;
            box-shadow: 0 0 10px rgba(140, 158, 255, 0.3);
        }

        .input-group {
            display: flex;
            width: 100%;
        }

        #search-form {
            display: flex;
            width: 100%;
        }


        .search-input {
            background-color: rgba(25, 28, 49, 0.9);
            color: white;
            border: 1px solid #383d6e;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            position: relative;
            flex-grow: 1;
            margin-right: 10px;
            margin-top: 15px;
        }

        .search-input:focus {
            border-color: #ff7b54;
            box-shadow: 0 0 10px rgba(255, 123, 84, 0.3);
            outline: none;
        }

        .search-input::placeholder {
            color: #9da0b8;
        }

        .search-input {
            flex: 1;
            width: 100%;
            margin-right: 10px;
        }

        .search-button {
            background: linear-gradient(45deg, #8c9eff, #7a8ce0);
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 2rem;
            font-weight: bold;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            z-index: 1;
            /* height: ; */
            margin-top: 15px;
            text-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 109, 197, 0.4);
        }

        .search-button:active {
            transform: translateY(0);
        }

        .main-title {
            color: #ff7b54;
            text-align: center;
            font-size: 4rem;
            margin-top: 7rem;
            text-shadow: 0 0 15px rgba(255, 123, 84, 0.7);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 4px;
            position: relative;
        }

        .main-title::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #ff7b54, transparent);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .subtitle {
            text-align: center;
            color: #e0e0e0;
            margin-bottom: 2rem;
            font-weight: 300;
            letter-spacing: 1px;
        }

        @media screen and (max-width: 768px) {

            .main-title {
                font-size: 2.5rem;
            }
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

    <main class="container py-4 mt-">

        <h1 class="main-title">Albums</h1>
        <p class="subtitle">Explore albums across genres, artists, and languages</p>

        <!-- Filtering Section -->
        <div class="search-container mb-5 d-none d-md-block">
            <div class="filter-row">
                <select id="genre-select" name="genre" class="filter-dropdown">
                    <option value="0">All Genres</option>
                    <?php while ($genre_row = mysqli_fetch_assoc($genres_result)): ?>
                    <option value="<?= $genre_row['id'] ?>" <?=$genre==$genre_row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($genre_row['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>


                <select id="language-select" name="language" class="filter-dropdown">
                    <option value="0">All Languages</option>
                    <?php while ($language_row = mysqli_fetch_assoc($languages_result)): ?>
                    <option value="<?= $language_row['id'] ?>" <?=$language==$language_row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($language_row['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <select id="year-select" name="year" class="filter-dropdown">
                    <option value="">All Years</option>
                    <?php while ($year_row = mysqli_fetch_assoc($years_result)): ?>
                    <option value="<?= $year_row['release_year'] ?>" <?=$year==$year_row['release_year'] ? 'selected'
                        : '' ?>>
                        <?= $year_row['release_year'] ?>
                    </option>
                    <?php endwhile; ?>
                    <option value="2010-2020" <?=$year=='2010-2020' ? 'selected' : '' ?>>2010-2020</option>
                    <option value="2000-2010" <?=$year=='2000-2010' ? 'selected' : '' ?>>2000-2010</option>
                    <option value="1900-2000" <?=$year=='1900-2000' ? 'selected' : '' ?>>Before 2000</option>
                </select>
            </div>

            <div class="input-group">
                <form id="search-form" method="GET" action="">
                    <input type="hidden" id="genre-input" name="genre" value="<?= $genre ?>">
                    <input type="hidden" id="language-input" name="language" value="<?= $language ?>">
                    <input type="hidden" id="year-input" name="year" value="<?= $year ?>">
                    <input type="text" name="search" class="search-input" placeholder="Search for music..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-button">Search</button>
                </form>
            </div>
        </div>

        <!-- Albums Grid -->
        <div class="row g-4">
            <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($album = mysqli_fetch_assoc($result)): ?>
            <div class="col-lg-4 col-md-6">
                <div class="album-card">
                    <div class="album-img-container">
                        <img src="<?= htmlspecialchars(str_replace([" ../../../", "../../" ], "../"
                            , '../uploads/albums/covers/' . $album['cover_image'])) ?>"
                        alt="
                        <?= htmlspecialchars($album['title']) ?>" class="album-img">
                        <div class="album-overlay"></div>
                        <?php if ($album['is_new']): ?>
                        <div class="new-badge">NEW</div>
                        <?php endif; ?>
                        <div class="album-actions">
                            <a href="album_details.php?id=<?= $album['id'] ?>" class="album-action-btn">
                                <i class="fas fa-play"></i>
                            </a>
                        </div>
                    </div>
                    <h3 class="album-title">
                        <?= htmlspecialchars($album['title']) ?>
                    </h3>
                    <p class="album-artist">
                        <?= htmlspecialchars($album['artist_name']) ?>
                    </p>
                    <div class="album-meta">
                        <span><i class="fas fa-calendar-alt me-1"></i>
                            <?= $album['release_year'] ?>
                        </span>
                        <span><i class="fas fa-headphones me-1"></i>
                            <?= number_format($album['listen_count']) ?>
                        </span>
                    </div>
                    <div class="album-badges">
                        <?php if (!empty($album['genre_name'])): ?>
                        <span class="genre-badge">
                            <?= htmlspecialchars($album['genre_name']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($album['language_name'])): ?>
                        <span class="language-badge">
                            <?= htmlspecialchars($album['language_name']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="album-footer">
                        <div class="album-rating">
                            <?php
                                        $rating_query = "SELECT AVG(rating) as avg_rating FROM ratings WHERE item_id = " . $album['id'] . " AND item_type = 'music'";
                                        $rating_result = mysqli_query($conn, $rating_query);
                                        $rating_row = mysqli_fetch_assoc($rating_result);
                                        $avg_rating = round($rating_row['avg_rating'] ?? 0, 1);
                                    ?>
                            <i class="fas fa-star"></i>
                            <?= $avg_rating ?>
                        </div>
                        <div class="album-tracks">
                            <i class="fas fa-music me-1"></i>
                            <?= $album['track_count'] ?> Tracks
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No albums found matching your criteria.</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link"
                            href="?page=<?= $page - 1 ?>&genre=<?= $genre ?>&language=<?= $language ?>&year=<?= $year ?>&search=<?= urlencode($search) ?>"
                            aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link"
                            href="?page=<?= $i ?>&genre=<?= $genre ?>&language=<?= $language ?>&year=<?= $year ?>&search=<?= urlencode($search) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link"
                            href="?page=<?= $page + 1 ?>&genre=<?= $genre ?>&language=<?= $language ?>&year=<?= $year ?>&search=<?= urlencode($search) ?>"
                            aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </main>

    <?php require_once '../layout/footer.php'; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <script>
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

        // Filters immediate application
        document.getElementById('genre-select').addEventListener('change', function () {
            document.getElementById('genre-input').value = this.value;
            document.getElementById('search-form').submit();
        });

        document.getElementById('language-select').addEventListener('change', function () {
            document.getElementById('language-input').value = this.value;
            document.getElementById('search-form').submit();
        });

        document.getElementById('year-select').addEventListener('change', function () {
            document.getElementById('year-input').value = this.value;
            document.getElementById('search-form').submit();
        });
    </script>
</body>

</html>