<?php
session_start();
include 'config_db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get video ID and duration from the request
$video_id = isset($_POST['video_id']) ? intval($_POST['video_id']) : 0;
$duration = isset($_POST['duration']) ? $_POST['duration'] : '';

// Validate input
if ($video_id <= 0 || empty($duration)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Save duration in the database
$update_sql = "UPDATE videos SET duration = ? WHERE id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("si", $duration, $video_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Duration saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save duration']);
}

$stmt->close();
$conn->close();
?>
