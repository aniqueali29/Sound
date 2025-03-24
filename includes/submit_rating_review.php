<?php
// Include database connection
include 'config_db.php';
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Debug information
error_log("POST data: " . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to submit a review'
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get form data - Check all possible field names
$music_id = 0;
if (isset($_POST['music_id'])) {
    $music_id = intval($_POST['music_id']);
} elseif (isset($_POST['musicId'])) {
    $music_id = intval($_POST['musicId']);
}

$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

// Debug logging
error_log("Processing review - Music ID: $music_id, Rating: $rating, User ID: $user_id");

// Validate input
if ($music_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid music ID: ' . $music_id . '. Check form field name.'
    ]);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Rating must be between 1 and 5'
    ]);
    exit;
}

if (empty($review)) {
    echo json_encode([
        'success' => false,
        'message' => 'Review text cannot be empty'
    ]);
    exit;
}

// Check if music exists
$sql = "SELECT id FROM music WHERE id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $music_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Music track not found in database with ID: ' . $music_id
    ]);
    $stmt->close();
    exit;
}

// Check if user has already reviewed this music
$sql = "SELECT id FROM ratings WHERE user_id = ? AND item_id = ? AND item_type = 'music'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $music_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing review
    $sql = "UPDATE ratings SET rating = ?, review = ? WHERE user_id = ? AND item_id = ? AND item_type = 'music'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsii", $rating, $review, $user_id, $music_id);
    
    if ($stmt->execute()) {
        // Update the average rating in music table
        updateMusicAverageRating($conn, $music_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your review has been updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update review: ' . $conn->error
        ]);
    }
} else {
    // Insert new review
    $sql = "INSERT INTO ratings (user_id, item_id, item_type, rating, review, created_at) 
            VALUES (?, ?, 'music', ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iids", $user_id, $music_id, $rating, $review);
    
    if ($stmt->execute()) {
        // Update the average rating in music table
        updateMusicAverageRating($conn, $music_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your review has been submitted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit review: ' . $conn->error . ' SQL: ' . $sql
        ]);
    }
}

// Close connection
$stmt->close();
$conn->close();

/**
 * Update the average rating for a music track
 */
function updateMusicAverageRating($conn, $music_id) {
    // Calculate the new average rating from the ratings table
    $sql = "SELECT AVG(rating) as avg_rating FROM ratings WHERE item_id = ? AND item_type = 'music'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $music_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $avg_rating = $row['avg_rating'];
    
    // Update the music table with the new average rating
    $update_sql = "UPDATE music SET rating = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("di", $avg_rating, $music_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $stmt->close();
}

error_log("Received music_id: " . $music_id);
error_log("Received rating: " . $rating);
error_log("Received review: " . $review);
?>