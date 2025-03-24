<?php
// Start session
session_start();

include 'config_db.php';


if (isset($_POST['music_id'])) {
    $music_id = $_POST['music_id'];
    
    // Increment play count
    $sql = "UPDATE music SET plays = plays + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $music_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No music ID provided']);
}

// Close database connection
mysqli_close($conn);
?>