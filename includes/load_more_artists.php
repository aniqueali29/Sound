<?php
include './config_db.php';

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 6; // Same as in the main file
$offset = ($page - 1) * $limit;

// Process filters
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$language_filter = isset($_GET['language']) ? $_GET['language'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';
$albums_filter = isset($_GET['albums']) ? $_GET['albums'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT a.id, a.name, a.bio, a.created_at,
        COUNT(DISTINCT m.id) AS total_songs,
        COUNT(DISTINCT al.id) AS total_albums,
        COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM artists a
        LEFT JOIN music m ON a.id = m.artist_id
        LEFT JOIN albums al ON a.id = al.artist_id
        LEFT JOIN ratings r ON m.id = r.item_id
        LEFT JOIN genres g ON m.genre_id = g.id  
        WHERE 1=1";

if (!empty($genre_filter)) {
    $sql .= " AND g.name = '$genre_filter'";
}

if (!empty($genre_filter)) {
    $sql .= " AND m.genre_id = '$genre_filter'";
}

if (!empty($year_filter)) {
    $sql .= " AND al.release_year = $year_filter";
}

if (!empty($search_term)) {
    $sql .= " AND a.name LIKE '%$search_term%'";
}

$sql .= " GROUP BY a.id, a.name, a.bio";

if (!empty($rating_filter)) {
    $rating_value = str_replace('+', '', $rating_filter);
    $sql .= " HAVING avg_rating >= $rating_value";
}

if (!empty($albums_filter)) {
    $albums_value = intval(str_replace('+', '', str_replace(' Albums', '', $albums_filter)));
    $sql .= " HAVING total_albums >= $albums_value";
}

$sql .= " ORDER BY a.name LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

// Fetch languages (for language badge)
$languages_query = "SELECT DISTINCT name AS language FROM genres WHERE name IS NOT NULL";
$languages_result = $conn->query($languages_query);
$languages = [];
if ($languages_result->num_rows > 0) {
    while($row = $languages_result->fetch_assoc()) {
        $languages[] = $row['language'];
    }
}

// Output artists HTML
if ($result->num_rows > 0) {
    while($artist = $result->fetch_assoc()) {
        // Check if this is a new artist
        $is_new = false;
        if(strtotime($artist['created_at'] ?? '') > strtotime('-30 days')) {
            $is_new = true;
        }
        
        // Determine language badge (simplified example - adjust based on your data)
        $language_badge = in_array($artist['genre'] ?? '', $languages) ? $artist['genre'] : 'English';
        
        // Output artist card HTML
        ?>
        <div class="col">
            <a href="artist.php?id=<?php echo $artist['id']; ?>" class="artist-link">
                <div class="artist-card h-100">
                    <div class="artist-image-container">
                        <?php if($is_new): ?>
                        <span class="new-badge">NEW</span>
                        <?php endif; ?>
                        <span class="language-badge"><?php echo htmlspecialchars($language_badge); ?></span>
                        <img src="/api/placeholder/400/400" alt="<?php echo htmlspecialchars($artist['name']); ?>" class="artist-image">
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
    }
}

// Close the database connection
$conn->close();
?>