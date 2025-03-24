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
    // Query to get total ratings and average rating
    $query = "
    SELECT 
        COUNT(*) as total_ratings,
        AVG(rating) as average_rating
    FROM 
        ratings
    WHERE 
        item_id = ? AND item_type = 'music'
";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $music_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating_data = $result->fetch_assoc();
    
    $total_ratings = intval($rating_data['total_ratings']);
    $average_rating = floatval($rating_data['average_rating']);
    
    // If there are no ratings yet
    if ($total_ratings == 0) {
        echo json_encode([
            'success' => true,
            'distribution' => [
                5 => 0,
                4 => 0,
                3 => 0,
                2 => 0,
                1 => 0
            ],
            'average' => 0,
            'total' => 0
        ]);
        exit;
    }
    
    // Query to get distribution of ratings
    $query = "
    SELECT 
        rating,
        COUNT(*) as count
    FROM 
        ratings
    WHERE 
        item_id = ? AND item_type = 'music'
    GROUP BY 
        rating
    ORDER BY 
        rating DESC
";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $music_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $distribution = [
        5 => 0,
        4 => 0,
        3 => 0,
        2 => 0,
        1 => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $rating = intval($row['rating']);
        $count = intval($row['count']);
        $percentage = ($count / $total_ratings) * 100;
        $distribution[$rating] = round($percentage);
    }
    
    echo json_encode([
        'success' => true,
        'distribution' => $distribution,
        'average' => $average_rating,
        'total' => $total_ratings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching rating distribution: ' . $e->getMessage()
    ]);
}
?>