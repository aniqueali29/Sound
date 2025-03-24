<?php
// Start session
session_start();

include 'config_db.php';


if (isset($_POST['video_id'])) {
    $video_id = $_POST['video_id'];
    
    // Increment view count
    $sql = "UPDATE videos SET views = views + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No video ID provided']);
}

// Close database connection
mysqli_close($conn);
?>