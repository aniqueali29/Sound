<?php
// Include database connection
require_once 'config_db.php';
session_start();

// Check if music_id is provided
if (!isset($_GET['music_id']) || empty($_GET['music_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Music ID is required'
    ]);
    exit;
}

$music_id = intval($_GET['music_id']);

try {
    // Query to get reviews with user information
// Replace the query for getting reviews with:
    $query = "
    SELECT 
        r.review, 
        r.rating, 
        r.created_at, 
        u.id as user_id, 
        u.username, 
        u.profile_picture as user_avatar
    FROM 
        ratings r
    JOIN 
        users u ON r.user_id = u.id
    WHERE 
        r.item_id = ? AND r.item_type = 'music' AND r.review IS NOT NULL
    ORDER BY 
        r.created_at DESC
";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $music_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format the user avatar URL
        if (!empty($row['user_avatar'])) {
            $row['user_avatar'] = '../uploads/profile/' . $row['user_avatar'];
        } else {
            $row['user_avatar'] = '../assets/img/default-avatar.jpg';
        }
        
        $reviews[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching reviews: ' . $e->getMessage()
    ]);
}
?>