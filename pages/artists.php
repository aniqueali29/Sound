<?php
require '../includes/config_db.php'; // Database connection
require_once '../layout/header.php';

// Base query for all artists with their average ratings
$sql = "SELECT a.id, a.name, a.bio, a.image, a.created_at,
        COUNT(DISTINCT m.id) AS total_songs,
        COUNT(DISTINCT al.id) AS total_albums,
        COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM artists a
        LEFT JOIN music m ON a.id = m.artist_id
        LEFT JOIN albums al ON a.id = al.artist_id
        LEFT JOIN ratings r ON m.id = r.item_id AND r.item_type = 'music'
        WHERE a.deleted_at IS NULL
        GROUP BY a.id, a.name, a.bio, a.image, a.created_at
        ORDER BY a.name";

$result = $conn->query($sql);

// Process filters if submitted
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$language_filter = isset($_GET['language']) ? $_GET['language'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';
$albums_filter = isset($_GET['albums']) ? $_GET['albums'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($genre_filter) || !empty($language_filter) || !empty($year_filter) || 
    !empty($rating_filter) || !empty($albums_filter) || !empty($search_term)) {
    
    // Modified query with proper joins and WHERE conditions
    $sql = "SELECT a.id, a.name, a.bio, a.image, a.created_at,
            COUNT(DISTINCT m.id) AS total_songs,
            COUNT(DISTINCT al.id) AS total_albums,
            COALESCE(AVG(r.rating), 0) AS avg_rating
            FROM artists a
            LEFT JOIN music m ON a.id = m.artist_id
            LEFT JOIN albums al ON a.id = al.artist_id
            LEFT JOIN ratings r ON m.id = r.item_id AND r.item_type = 'music'";
    
    // Add the join for genres only when needed
    if (!empty($genre_filter)) {
        $sql .= " LEFT JOIN genres g ON m.genre_id = g.id";
    }
    
    // Start the WHERE clause
    $sql .= " WHERE a.deleted_at IS NULL";
    
    if (!empty($genre_filter)) {
        $sql .= " AND g.name = '" . $conn->real_escape_string($genre_filter) . "'";
    }
    
    if (!empty($language_filter)) {
        // Join to languages table to filter by language
        $sql .= " AND EXISTS (SELECT 1 FROM music m2 
                  JOIN languages l ON m2.language_id = l.id 
                  WHERE m2.artist_id = a.id AND l.name = '" . $conn->real_escape_string($language_filter) . "')";
    }
    
    if (!empty($year_filter)) {
        $sql .= " AND EXISTS (SELECT 1 FROM albums a2 
                  WHERE a2.artist_id = a.id AND a2.release_year = '" . $conn->real_escape_string($year_filter) . "')";
    }
    
    if (!empty($search_term)) {
        $sql .= " AND a.name LIKE '%" . $conn->real_escape_string($search_term) . "%'";
    }
    
    $sql .= " GROUP BY a.id, a.name, a.bio, a.image, a.created_at";
    
    $havingClauses = [];
    
    if (!empty($rating_filter)) {
        $rating_value = str_replace('+', '', $rating_filter);
        if (is_numeric($rating_value)) {
            $havingClauses[] = "avg_rating >= " . floatval($rating_value);
        }
    }
    
    if (!empty($albums_filter)) {
        $albums_value = intval(str_replace('+', '', str_replace(' Albums', '', $albums_filter)));
        if ($albums_value > 0) {
            $havingClauses[] = "total_albums >= " . $albums_value;
        }
    }
    
    if (!empty($havingClauses)) {
        $sql .= " HAVING " . implode(" AND ", $havingClauses);
    }
    
    $sql .= " ORDER BY a.name";
    
    $result = $conn->query($sql);
    

}




// Fetch languages (for demonstration - adjust based on your actual data structure)
$languages_query = "SELECT DISTINCT name AS language FROM genres WHERE name IS NOT NULL";
$languages_result = $conn->query($languages_query);
$languages = [];
if ($languages_result->num_rows > 0) {
    while($row = $languages_result->fetch_assoc()) {
        $languages[] = $row['language'];
    }
}

// Fetch genres
$genres_query = "SELECT DISTINCT name FROM genres WHERE name IS NOT NULL";
$genres_result = $conn->query($genres_query);
$genres = [];
if ($genres_result->num_rows > 0) {
    while($row = $genres_result->fetch_assoc()) {
        $genres[] = $row['name'];
    }
}

// Fetch years
$years_query = "SELECT DISTINCT release_year FROM albums WHERE release_year IS NOT NULL ORDER BY release_year DESC";
$years_result = $conn->query($years_query);
$years = [];
if ($years_result->num_rows > 0) {
    while($row = $years_result->fetch_assoc()) {
        $years[] = $row['release_year'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUND | Popular Artists</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/artist.css">
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

    <div class="container py-5 mt-4">
        <h1 class="page-title">Popular Artists</h1>

        <p class="section-description py-3">
            Discover, explore, and celebrate your favorite artists.
        </p>

        <!-- Search Section -->
        <form method="GET" action="">
            <div class="search-container">
                <div class="filter-row">
                    <select name="genre" class="filter-dropdown">
                        <option selected disabled>Genre</option>
                        <?php foreach($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>"
                            <?php echo ($genre_filter == $genre) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="language" class="filter-dropdown">
                        <option selected disabled>Language</option>
                        <?php foreach($languages as $language): ?>
                        <option value="<?php echo htmlspecialchars($language); ?>"
                            <?php echo ($language_filter == $language) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="year" class="filter-dropdown">
                        <option selected disabled>Year</option>
                        <?php foreach($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>"
                            <?php echo ($year_filter == $year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="rating" class="filter-dropdown">
                        <option selected disabled>Rating</option>
                        <option value="5" <?php echo ($rating_filter == '5') ? 'selected' : ''; ?>>5</option>
                        <option value="4+" <?php echo ($rating_filter == '4+') ? 'selected' : ''; ?>>4+</option>
                        <option value="3+" <?php echo ($rating_filter == '3+') ? 'selected' : ''; ?>>3+</option>
                        <option value="0" <?php echo ($rating_filter == '0') ? 'selected' : ''; ?>>Any Rating</option>
                    </select>

                    <select name="albums" class="filter-dropdown">
                        <option selected disabled>Albums</option>
                        <option value="1+ Albums" <?php echo ($albums_filter == '1+ Albums') ? 'selected' : ''; ?>>1+
                            Albums</option>
                        <option value="3+ Albums" <?php echo ($albums_filter == '3+ Albums') ? 'selected' : ''; ?>>3+
                            Albums</option>
                        <option value="5+ Albums" <?php echo ($albums_filter == '5+ Albums') ? 'selected' : ''; ?>>5+
                            Albums</option>
                        <option value="10+ Albums" <?php echo ($albums_filter == '10+ Albums') ? 'selected' : ''; ?>>10+
                            Albums</option>
                    </select>
                </div>

                <div class="input-group">
                    <input type="text" name="search" class="form-control search-input" placeholder="Search artists..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="search-button" type="submit">Search</button>
                </div>
            </div>
        </form>

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
                <form method="GET" action="">
                    <div class="filter-options">
                        <select id="album-filter-mobile" name="genre" class="filter-dropdown">
                            <option value="">Genre</option>
                            <?php foreach($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre); ?>"
                                <?php echo ($genre_filter == $genre) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genre); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="artist-filter-mobile" name="language" class="filter-dropdown">
                            <option value="">Language</option>
                            <?php foreach($languages as $language): ?>
                            <option value="<?php echo htmlspecialchars($language); ?>"
                                <?php echo ($language_filter == $language) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($language); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="year-filter-mobile" name="year" class="filter-dropdown">
                            <option value="">Year</option>
                            <?php foreach($years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>"
                                <?php echo ($year_filter == $year) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="genre-filter-mobile" name="rating" class="filter-dropdown">
                            <option value="">Rating</option>
                            <option value="5" <?php echo ($rating_filter == '5') ? 'selected' : ''; ?>>5</option>
                            <option value="4+" <?php echo ($rating_filter == '4+') ? 'selected' : ''; ?>>4+</option>
                            <option value="3+" <?php echo ($rating_filter == '3+') ? 'selected' : ''; ?>>3+</option>
                            <option value="0" <?php echo ($rating_filter == '0') ? 'selected' : ''; ?>>Any Rating
                            </option>
                        </select>

                        <select id="language-filter-mobile" name="albums" class="filter-dropdown">
                            <option value="">Albums</option>
                            <option value="1+ Albums" <?php echo ($albums_filter == '1+ Albums') ? 'selected' : ''; ?>>
                                1+ Albums</option>
                            <option value="3+ Albums" <?php echo ($albums_filter == '3+ Albums') ? 'selected' : ''; ?>>
                                3+ Albums</option>
                            <option value="5+ Albums" <?php echo ($albums_filter == '5+ Albums') ? 'selected' : ''; ?>>
                                5+ Albums</option>
                            <option value="10+ Albums"
                                <?php echo ($albums_filter == '10+ Albums') ? 'selected' : ''; ?>>10+ Albums</option>
                        </select>
                    </div>
                    <div class="search-bar">
                        <input type="text" name="search" id="search-input-mobile" class="search-input"
                            placeholder="Search for artists..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button id="search-btn-mobile" class="search-button" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Navigation -->
        <ul class="nav category-nav">
            <li class="nav-item">
                <a class="nav-link active" href="artists.php">All</a>
            </li>
            <?php 
            $genre_nav_limit = 4; // Show only 4 genres in nav
            $count = 0;
            foreach($genres as $genre): 
                if($count < $genre_nav_limit):
            ?>
            <li class="nav-item">
                <a class="nav-link"
                    href="artists.php?genre=<?php echo urlencode($genre); ?>"><?php echo htmlspecialchars($genre); ?></a>
            </li>
            <?php 
                $count++;
                endif;
            endforeach; 
            ?>
        </ul>

        <!-- Artists Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php 
            if ($result->num_rows > 0):
                while($artist = $result->fetch_assoc()): 
                    // Check if this is a new artist
                    $is_new = false;
                    if(strtotime($artist['created_at'] ?? '') > strtotime('-30 days')) {
                        $is_new = true;
                    }
                    
                    // Determine language badge (simplified example - adjust based on your data)
                    $language_badge = in_array($artist['genre'] ?? '', $languages) ? $artist['genre'] : 'English';
            ?>
            <!-- Artist Card -->
            <div class="col">
                <a href="artist_details.php?id=<?php echo $artist['id']; ?>" class="artist-link">
                    <div class="artist-card h-100">
                        <div class="artist-image-container">
                            <?php if($is_new): ?>
                            <span class="new-badge">NEW</span>
                            <?php endif; ?>
                            <span class="language-badge"><?php echo htmlspecialchars($language_badge); ?></span>
                            <img src="<?php 
                                $imagePath = !empty($artist['image']) 
                                    ? str_replace(["../../../", "../../"], "../", $artist['image']) 
                                    : '/api/placeholder/400/400';

                                echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); 
                            ?>" alt="<?php echo htmlspecialchars($artist['name'], ENT_QUOTES, 'UTF-8'); ?>" class="artist-image">
                            <div class="artist-overlay">
                                <h5 class="artist-name"><?php echo htmlspecialchars($artist['name']); ?></h5>
                            </div>
                        </div>
                        <div class="artist-bio">
                            <?php echo htmlspecialchars($artist['bio'] ?? 'No description available.'); ?>
                        </div>
                        <div class="artist-stats">
                            <div class="artist-stat">
                                <span class="stat-value"><?php echo intval($artist['total_songs']); ?></span>
                                <span class="stat-label">Songs</span>
                            </div>
                            <div class="artist-stat">
                                <span class="stat-value"><?php echo intval($artist['total_albums']); ?></span>
                                <span class="stat-label">Albums</span>
                            </div>
                            <div class="artist-stat">
                                <span class="stat-value"><?php echo number_format($artist['avg_rating'], 1); ?></span>
                                <span class="stat-label">Rating</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <div class="col-12 text-center py-5">
                <h3>No artists found</h3>
                <p>Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- View More Button - Only show if we have artists -->
        <?php if ($result->num_rows > 0): ?>
        <div class="text-center mt-4">
            <button class="btn btn-view-more" id="load-more-btn">View More Artists</button>
        </div>
        <?php endif; ?>
    </div>

    <?php
require_once '../layout/footer.php';

    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Function to get query parameters from URL
    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    // Pagination functionality for "View More" button
    let page = 1;
    const limit = 6; // Number of artists per page

    document.getElementById('load-more-btn').addEventListener('click', function() {
        page++;

        // Create a URL with all current filters
        let url = '../includes/load_more_artists.php?page=' + page;

        // Add all current filters to the AJAX request
        const filters = ['genre', 'language', 'year', 'rating', 'albums', 'search'];
        filters.forEach(filter => {
            const value = getQueryParam(filter);
            if (value) {
                url += '&' + filter + '=' + encodeURIComponent(value);
            }
        });

        // AJAX request to load more artists
        fetch(url)
            .then(response => response.text())
            .then(html => {
                const artistsGrid = document.querySelector('.row-cols-1');

                // Insert new artists before the "View More" button
                artistsGrid.insertAdjacentHTML('beforeend', html);

                // Hide "View More" button if no more artists
                if (html.trim() === '') {
                    document.getElementById('load-more-btn').style.display = 'none';
                }
            })
            .catch(error => console.error('Error loading more artists:', error));
    });

    // Create dynamic particle effect
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

<?php
// Close the database connection
$conn->close();
?>