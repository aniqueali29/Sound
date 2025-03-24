<?php
require_once './config_db.php';
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['itemId']) || !isset($_POST['itemType']) || !isset($_POST['addToFavorite'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$item_id = (int)$_POST['itemId'];
$item_type = $_POST['itemType']; // 'music', 'video', 'album'
$add_to_favorite = (int)$_POST['addToFavorite']; // 1 to add, 0 to remove

// Validate item type
if (!in_array($item_type, ['music', 'video', 'album'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item type'
    ]);
    exit;
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Check if item exists in the favorites already
    $check_query = "SELECT id FROM favorites WHERE user_id = ? AND ";
    
    if ($item_type == 'music') {
        $check_query .= "music_id = ?";
    } elseif ($item_type == 'video') {
        $check_query .= "video_id = ?";
    } elseif ($item_type == 'album') {
        $check_query .= "album_id = ?";
    }
    
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;
    
    // If add to favorite = 1 and item doesn't exist, add it
    if ($add_to_favorite == 1 && !$exists) {
        $insert_query = "INSERT INTO favorites (user_id, ";
        
        if ($item_type == 'music') {
            $insert_query .= "music_id) VALUES (?, ?)";
        } elseif ($item_type == 'video') {
            $insert_query .= "video_id) VALUES (?, ?)";
        } elseif ($item_type == 'album') {
            $insert_query .= "album_id) VALUES (?, ?)";
        }
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
        $success = mysqli_stmt_execute($stmt);
        
        if (!$success) {
            throw new Exception("Failed to add to favorites");
        }
        
        // If it's music or video, update likes count
        if ($item_type == 'music') {
            $update_query = "UPDATE music SET likes = likes + 1 WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);
        } elseif ($item_type == 'video') {
            $update_query = "UPDATE videos SET likes = likes + 1 WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);
        }
        
    // If remove from favorite = 0 and item exists, remove it
    } elseif ($add_to_favorite == 0 && $exists) {
        $favorite_id = mysqli_fetch_assoc($result)['id'];
        
        $delete_query = "DELETE FROM favorites WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $favorite_id);
        $success = mysqli_stmt_execute($stmt);
        
        if (!$success) {
            throw new Exception("Failed to remove from favorites");
        }
        
        // If it's music or video, update likes count
        if ($item_type == 'music') {
            $update_query = "UPDATE music SET likes = GREATEST(likes - 1, 0) WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);
        } elseif ($item_type == 'video') {
            $update_query = "UPDATE videos SET likes = GREATEST(likes - 1, 0) WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Get updated favorite count
    $count_query = "SELECT COUNT(*) as count FROM favorites WHERE ";
    
    if ($item_type == 'music') {
        $count_query .= "music_id = ?";
    } elseif ($item_type == 'video') {
        $count_query .= "video_id = ?";
    } elseif ($item_type == 'album') {
        $count_query .= "album_id = ?";
    }
    
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $favorite_count = mysqli_fetch_assoc($count_result)['count'];
    
    echo json_encode([
        'success' => true,
        'message' => ($add_to_favorite == 1) ? 'Added to favorites' : 'Removed from favorites',
        'favoriteCount' => $favorite_count
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close statement if it exists
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}
?>