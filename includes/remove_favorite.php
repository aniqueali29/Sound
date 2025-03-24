<?php
// Start session
session_start();

include 'config_db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if favorite_id is provided
if (!isset($_POST['favorite_id'])) {
    echo json_encode(['success' => false, 'message' => 'No favorite ID provided']);
    exit();
}

$favorite_id = $_POST['favorite_id'];
$user_id = $_SESSION['user_id'];

// Delete the favorite
$sql = "DELETE FROM favorites WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $favorite_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting favorite']);
}

// Close database connection
mysqli_close($conn);
?>