<?php
include '../includes/config_db.php';

// Check if video path is provided
if (!isset($_POST['video_path']) || empty($_POST['video_path'])) {
    echo json_encode(['error' => 'Video path not provided']);
    exit;
}

$videoPath = $_POST['video_path'];

// Prepare the SQL query to get the video ID
$getVideoQuery = "SELECT id, views FROM videos WHERE file_path = ? AND deleted_at IS NULL";
$getVideoStmt = $conn->prepare($getVideoQuery);
$getVideoStmt->bind_param("s", $videoPath);
$getVideoStmt->execute();
$result = $getVideoStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Video not found']);
    exit;
}

$video = $result->fetch_assoc();
$videoId = $video['id'];
$currentViews = $video['views'];

// Increment view count
$newViews = $currentViews + 1;
$updateQuery = "UPDATE videos SET views = ? WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ii", $newViews, $videoId);
$updateStmt->execute();

// Check if update was successful
if ($updateStmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'views' => $newViews]);
} else {
    echo json_encode(['error' => 'Failed to update view count']);
}

// Close the database connection
$getVideoStmt->close();
$updateStmt->close();
$conn->close();
?>