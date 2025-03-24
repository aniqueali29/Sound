<?php
require 'db_connection.php'; // Include database connection

session_start();
$user_id = $_SESSION['user_id'] ?? null; // Get logged-in user ID

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to rate.']);
    exit;
}

$video_id = $_POST['video_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$video_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Fetch current like/dislike status
$stmt = $conn->prepare("SELECT rating FROM video_ratings WHERE user_id = ? AND video_id = ?");
$stmt->bind_param("ii", $user_id, $video_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$current_rating = $row['rating'] ?? 0; // 1 = Liked, -1 = Disliked, 0 = No rating

$new_rating = $current_rating; // Default to current rating

if ($action === 'like') {
    if ($current_rating == 1) {
        // Already liked → Remove like, keep dislike as it is
        $new_rating = 0;
    } else {
        // Set like, keep dislike unchanged
        $new_rating = 1;
    }
} elseif ($action === 'dislike') {
    if ($current_rating == -1) {
        // Already disliked → Remove dislike, keep like as it is
        $new_rating = 0;
    } else {
        // Set dislike, keep like unchanged
        $new_rating = -1;
    }
}

// Insert or update rating
if ($row) {
    $stmt = $conn->prepare("UPDATE video_ratings SET rating = ? WHERE user_id = ? AND video_id = ?");
    $stmt->bind_param("iii", $new_rating, $user_id, $video_id);
} else {
    $stmt = $conn->prepare("INSERT INTO video_ratings (user_id, video_id, rating) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $video_id, $new_rating);
}

$stmt->execute();

// Get updated like and dislike counts
$like_count = getRatingCount($conn, $video_id, 1);
$dislike_count = getRatingCount($conn, $video_id, -1);

// Return JSON response
echo json_encode([
    'success' => true,
    'message' => ($new_rating == 1) ? 'You liked this video' : (($new_rating == -1) ? 'You disliked this video' : 'Rating removed'),
    'likes' => $like_count,
    'dislikes' => $dislike_count,
    'user_rating' => $new_rating
]);

function getRatingCount($conn, $video_id, $rating_value) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM video_ratings WHERE video_id = ? AND rating = ?");
    $stmt->bind_param("ii", $video_id, $rating_value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    return $count;
}
?>
