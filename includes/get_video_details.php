<?php
include '../includes/config_db.php';

// Check if video ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid video ID']);
    exit;
}

$videoId = (int)$_GET['id'];

// Prepare the SQL query to get video details
$sql = "SELECT v.*, a.name as artist_name, g.name as genre_name, l.name as language_name, al.title as album_title 
        FROM videos v 
        LEFT JOIN artists a ON v.artist_id = a.id 
        LEFT JOIN genres g ON v.genre_id = g.id 
        LEFT JOIN languages l ON v.language_id = l.id 
        LEFT JOIN albums al ON v.album_id = al.id 
        WHERE v.id = ? AND v.deleted_at IS NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $videoId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Video not found']);
    exit;
}

$video = $result->fetch_assoc();

// Format views
function formatViews($views) {
    if ($views >= 1000000) {
        return round($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return round($views / 1000, 1) . 'K';
    } else {
        return $views;
    }
}

// Get average rating
$ratingQuery = "SELECT AVG(rating) as avg_rating FROM video_ratings WHERE video_id = ?";
$ratingStmt = $conn->prepare($ratingQuery);
$ratingStmt->bind_param("i", $videoId);
$ratingStmt->execute();
$ratingResult = $ratingStmt->get_result();
$ratingRow = $ratingResult->fetch_assoc();
$rating = $ratingRow['avg_rating'] ? number_format($ratingRow['avg_rating'], 1) : '0.0';

// Format the description with HTML
$formattedDescription = "<div class='video-details'>
    <div class='detail-row'><span class='detail-label'>Artist:</span> <span class='detail-value'>{$video['artist_name']}</span></div>
    <div class='detail-row'><span class='detail-label'>Album:</span> <span class='detail-value'>{$video['album_title']}</span></div>
    <div class='detail-row'><span class='detail-label'>Genre:</span> <span class='detail-value'>{$video['genre_name']}</span></div>
    <div class='detail-row'><span class='detail-label'>Language:</span> <span class='detail-value'>{$video['language_name']}</span></div>
    <div class='detail-row'><span class='detail-label'>Year:</span> <span class='detail-value'>{$video['release_year']}</span></div>
    <div class='detail-row'><span class='detail-label'>Views:</span> <span class='detail-value'>" . formatViews($video['views']) . "</span></div>
    <div class='detail-row'><span class='detail-label'>Rating:</span> <span class='detail-value'>{$rating}</span></div>
</div>
<div class='video-description-text'>
    <h4>Description</h4>
    <p>{$video['description']}</p>
</div>";

// Prepare the response
$response = [
    'id' => $video['id'],
    'title' => $video['title'],
    'description' => $formattedDescription,
    'file_path' => $video['file_path'],
    'thumbnail' => $video['thumbnail'],
    'artist' => $video['artist_name'],
    'genre' => $video['genre_name'],
    'year' => $video['release_year'],
    'views' => formatViews($video['views']),
    'rating' => $rating
];

// Return the data as JSON
echo json_encode($response);

// Close the database connection
$stmt->close();
$ratingStmt->close();
$conn->close();
?>