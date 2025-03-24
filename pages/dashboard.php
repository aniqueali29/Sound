<?php
ob_start();
require '../includes/config_db.php';
require_once '../layout/header.php';

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    
    // Update user information
    $update_sql = "UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $name, $username, $email, $user_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}

// Handle password update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $password_sql = "SELECT password FROM users WHERE id = ?";
    $password_stmt = $conn->prepare($password_sql);
    $password_stmt->bind_param("i", $user_id);
    $password_stmt->execute();
    $password_result = $password_stmt->get_result();
    $user_data = $password_result->fetch_assoc();
    
    if (password_verify($current_password, $user_data['password'])) {
        if ($new_password == $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_password_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_password_stmt = $conn->prepare($update_password_sql);
            $update_password_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_password_stmt->execute()) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Error updating password: " . $conn->error;
            }
        } else {
            $error_message = "New passwords do not match!";
        }
    } else {
        $error_message = "Current password is incorrect!";
    }
}

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
        $upload_dir = '../uploads/profile_pictures/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $user_id . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            // Update profile picture in database
            $update_pic_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $update_pic_stmt = $conn->prepare($update_pic_sql);
            $update_pic_stmt->bind_param("si", $upload_path, $user_id);
            
            if ($update_pic_stmt->execute()) {
                $success_message = "Profile picture updated successfully!";
                // Update user data
                $user['profile_picture'] = $upload_path;
            } else {
                $error_message = "Error updating profile picture in database: " . $conn->error;
            }
        } else {
            $error_message = "Error uploading profile picture!";
        }
    } else {
        $error_message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
    }
}

function getFavorites($conn, $user_id, $type) {
    $favorites = [];
    $sql = "";
    
    if ($type === 'music') {
        $sql = "SELECT m.*, a.name as artist_name, a.image as artist_image, f.id as favorite_id, 
                CASE 
                    WHEN al.cover_image IS NOT NULL AND m.album_id IS NOT NULL THEN al.cover_image 
                    ELSE m.thumbnail_path 
                END as image_path,
                CASE 
                    WHEN al.cover_image IS NOT NULL AND m.album_id IS NOT NULL THEN 'album' 
                    ELSE 'music' 
                END as image_type
                FROM favorites f 
                JOIN music m ON f.music_id = m.id 
                JOIN artists a ON m.artist_id = a.id 
                LEFT JOIN albums al ON m.album_id = al.id 
                WHERE f.user_id = ? AND f.music_id IS NOT NULL";
    } elseif ($type === 'albums') {
        $sql = "SELECT al.*, a.name as artist_name, a.image as artist_image, f.id as favorite_id, al.cover_image as image_path 
                FROM favorites f 
                JOIN albums al ON f.album_id = al.id 
                JOIN artists a ON al.artist_id = a.id 
                WHERE f.user_id = ? AND f.album_id IS NOT NULL";
    }
    
    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }
    }
    
    return $favorites;
}

// Function to correct image path
function correctImagePath($path) {
    if (empty($path)) return '../assets/images/default-image.jpg';
    
    // Remove any "../" from the beginning of the path
    $path = preg_replace('/^(\.\.\/)+/', '', $path);
    
    // Add "../" at the beginning to ensure correct relative path from current directory
    if (substr($path, 0, 1) !== '/') {
        return "../" . $path;
    }
    
    return $path;
}

// Get favorites data
$music_favorites = getFavorites($conn, $user_id, 'music');
$album_favorites = getFavorites($conn, $user_id, 'albums');

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Beats | User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- <link rel="stylesheet" href="../css/test.css"> -->
    <style>
    :root {
        --neon-green: #0ff47a;
        --deep-space: #0a0a14;
        --stellar-purple: #6c43f5;
        --cosmic-pink: #ff3b8d;
        --holographic-gradient: linear-gradient(45deg, var(--neon-green), var(--stellar-purple));

        /* Additional dark theme variables */
        --background-primary: #060b19;
        --background-secondary: #0d1529;
        --background-tertiary: #162040;
        --text-primary: #ffffff;
        --text-secondary: #b8c3e6;
        --border-color: #1e2b4d;
        --card-background: rgba(18, 26, 51, 0.8);
        --input-background: rgba(9, 14, 31, 0.7);
        --hover-color: rgba(108, 67, 245, 0.3);
        --success-color: #2ecc71;
        --danger-color: #e74c3c;
    }

    /* Base styling */
    body {
        background-color: var(--background-primary);
        background-image:
            radial-gradient(circle at 10% 20%, rgba(91, 2, 154, 0.2) 0%, rgba(0, 0, 0, 0) 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 65, 108, 0.2) 0%, rgba(0, 0, 0, 0) 40%);
        color: var(--text-primary);
        font-family: 'Quicksand', sans-serif;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    .container {
        width: 100%;
        max-width: 2400px;
        border: none;
        margin: 0 auto;
        /* padding: 20px; */
    }

    /* Dashboard Layout */
    .dashboard {
        display: flex;
        background-color: var(--background-secondary);
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        margin-top: 30px;
        position: relative;
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .sidebar {
        width: 250px;
        background-color: var(--background-tertiary);
        padding: 25px 0;
        border-right: 1px solid var(--border-color);
        flex-shrink: 0;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li {
        margin-bottom: 5px;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 12px 25px;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border-left: 4px solid transparent;
    }

    .sidebar-menu a i {
        margin-right: 12px;
        font-size: 18px;
    }

    .sidebar-menu a:hover {
        background-color: var(--hover-color);
        color: var(--text-primary);
        border-left-color: var(--neon-green);
    }

    .sidebar-menu a.active {
        background-color: var(--hover-color);
        color: var(--neon-green);
        border-left-color: var(--neon-green);
    }

    .main-content {
        flex: 1;
        padding: 30px;
        position: relative;
        overflow-y: auto;
        /* max-height: 85vh; */
    }

    /* Alert messages */
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: 500;
        animation: fadeIn 0.5s ease-out;
    }

    .alert-success {
        background-color: rgba(46, 204, 113, 0.2);
        border-left: 4px solid var(--success-color);
        color: var(--success-color);
    }

    .alert-danger {
        background-color: rgba(231, 76, 60, 0.2);
        border-left: 4px solid var(--danger-color);
        color: var(--danger-color);
    }

    /* Tab Content */
    .tab-content {
        display: none;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.4s ease;
    }

    .tab-content.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    /* Profile Section */
    .profile-section {
        background-color: var(--card-background);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
    }

    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .profile-picture-container {
        position: relative;
        margin-right: 30px;
    }

    .profile-picture {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--stellar-purple);
        box-shadow: 0 0 15px rgba(108, 67, 245, 0.5);
    }

    .edit-profile-picture {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background-color: var(--cosmic-pink);
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .edit-profile-picture:hover {
        transform: scale(1.1);
    }

    .edit-profile-picture i {
        color: white;
        font-size: 16px;
    }

    .file-upload {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .profile-info h2 {
        color: var(--text-primary);
        font-size: 24px;
        margin-bottom: 10px;
    }

    .profile-info p {
        color: var(--text-secondary);
        margin: 8px 0;
        font-size: 15px;
    }

    .profile-info p i {
        width: 20px;
        margin-right: 10px;
        color: var(--neon-green);
    }

    .section-title {
        color: var(--text-primary);
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 20px;
        position: relative;
        padding-bottom: 10px;
    }

    .section-title:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 3px;
        background: var(--holographic-gradient);
        border-radius: 2px;
    }

    /* Forms */
    .form-row {
        display: flex;
        margin: 0 -10px 20px;
    }

    .form-group {
        flex: 1;
        margin: 0 10px 20px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        background-color: var(--input-background);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        font-family: 'Quicksand', sans-serif;
        font-size: 15px;
        transition: all 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--stellar-purple);
        box-shadow: 0 0 0 3px rgba(108, 67, 245, 0.2);
    }

    .form-control:disabled,
    .form-control[readonly] {
        background-color: rgba(26, 32, 53, 0.7);
        color: var(--text-secondary);
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-family: 'Quicksand', sans-serif;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn i {
        margin-right: 8px;
    }

    .btn-primary {
        background: var(--holographic-gradient);
        color: white;
        box-shadow: 0 4px 10px rgba(108, 67, 245, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(108, 67, 245, 0.4);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .password-section {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .password-section h3 {
        color: var(--text-primary);
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 20px;
    }

    /* Favorites */
    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }

    .favorite-item {
        background-color: var(--card-background);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        height: 100%;
    }

    .favorite-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        border-color: var(--cosmic-pink);
    }

    .favorite-thumb {
        position: relative;
        height: 180px;
        overflow: hidden;
    }

    .favorite-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .favorite-item:hover .favorite-thumb img {
        transform: scale(1.05);
    }

    .favorite-type {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: rgba(10, 10, 20, 0.7);
        color: var(--neon-green);
        font-size: 11px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(15, 244, 122, 0.3);
        letter-spacing: 1px;
    }

    .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        width: 50px;
        height: 50px;
        background-color: var(--cosmic-pink);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(255, 59, 141, 0.4);
    }

    .favorite-item:hover .play-button {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }

    .play-button i {
        color: white;
        font-size: 20px;
    }

    .favorite-info {
        padding: 15px;
    }

    .favorite-info h4 {
        margin: 0 0 8px;
        font-size: 16px;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .favorite-info p {
        color: var(--text-secondary);
        margin: 0 0 15px;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .control-buttons {
        display: flex;
        justify-content: space-between;
    }

    .control-btn {
        background-color: transparent;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        width: 32%;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.3s;
    }

    .control-btn:hover {
        background-color: var(--hover-color);
        color: var(--text-primary);
        border-color: var(--stellar-purple);
    }

    .control-btn i {
        font-size: 14px;
    }

    .no-items-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px 0;
        color: var(--text-secondary);
        font-size: 16px;
        background-color: var(--card-background);
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-in {
        animation: fadeIn 0.5s ease-out forwards;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .dashboard {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 0;
        }

        .sidebar-menu {
            display: flex;
            overflow-x: auto;
            padding: 0 15px;
        }

        .sidebar-menu li {
            margin-bottom: 0;
            margin-right: 10px;
        }

        .sidebar-menu a {
            padding: 10px 15px;
            white-space: nowrap;
            border-left: none;
            border-bottom: 3px solid transparent;
        }

        .sidebar-menu a.active,
        .sidebar-menu a:hover {
            border-left-color: transparent;
            border-bottom-color: var(--neon-green);
        }

        .main-content {
            max-height: none;
        }

        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .profile-picture-container {
            margin-right: 0;
            margin-bottom: 20px;
        }

        .form-row {
            flex-direction: column;
            margin: 0;
        }

        .form-group {
            margin: 0 0 20px;
        }
    }

    @media (max-width: 768px) {
        .favorites-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }

    @media (max-width: 576px) {
        .favorites-grid {
            grid-template-columns: 1fr;
        }

        .main-content {
            padding: 20px 15px;
        }

        .container {
            padding: 10px;
        }
    }

    /* Audio Player Styles (for player.php) */
    .audio-player-container {
        background-color: var(--card-background);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }

    .audio-player-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at top right,
                rgba(108, 67, 245, 0.1),
                transparent 70%),
            radial-gradient(circle at bottom left,
                rgba(255, 59, 141, 0.1),
                transparent 70%);
        pointer-events: none;
    }

    .audio-player {
        width: 100%;
        margin-top: 20px;
    }

    audio::-webkit-media-controls-panel {
        background-color: var(--background-tertiary);
    }

    audio::-webkit-media-controls-play-button,
    audio::-webkit-media-controls-mute-button {
        background-color: var(--cosmic-pink);
        border-radius: 50%;
    }

    audio::-webkit-media-controls-current-time-display,
    audio::-webkit-media-controls-time-remaining-display {
        color: var(--text-primary);
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-track {
        background: var(--background-tertiary);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--stellar-purple);
        border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--cosmic-pink);
    }

    .profile-picture-container {
        position: relative;
        margin-right: 30px;
    }

    .profile-picture {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--stellar-purple);
        box-shadow: 0 0 15px rgba(108, 67, 245, 0.5);
    }

    .profile-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--cosmic-pink);
        color: white;
        font-size: 48px;
        font-weight: bold;
        text-transform: uppercase;
        border: 3px solid var(--stellar-purple);
        box-shadow: 0 0 15px rgba(108, 67, 245, 0.5);
    }

    .edit-profile-picture {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background-color: var(--cosmic-pink);
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .edit-profile-picture:hover {
        transform: scale(1.1);
    }

    .edit-profile-picture i {
        color: white;
        font-size: 16px;
    }

    .file-upload {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
/* 
    .music_img {
        width: 100%;
        height: 360px !important;
        object-fit: cover;
        object-position: top;
        /* display: block; */
    } */
    </style>
</head>

<body>
    <!-- Second Navbar -->
    <nav class="navbarstwo">
        <div class="nav-container">
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="../index.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="music.php">
                        <i class="fa-solid fa-music"></i>
                        <span>Music</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="video.php">
                        <i class="fa-solid fa-video"></i>
                        <span>Video</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="albums.php">
                        <i class="fa-solid fa-record-vinyl"></i>
                        <span>Albums</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container" style="margin-top:130px;">
        <div class="dashboard">
            <div class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="#" class="active" data-tab="profile"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="#" data-tab="music-favorites"><i class="fas fa-music"></i> Music Favorites</a></li>
                    <li><a href="#" data-tab="album-favorites"><i class="fas fa-compact-disc"></i> Album Favorites</a>
                    </li>
                    <li><a href="./logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>

            <div class="main-content">
                <!-- Display success/error messages -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Profile Tab -->
                <div id="profile" class="tab-content active">
                    <div class="profile-section">
                        <div class="profile-header">
                            <div class="profile-picture-container">
                                <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?php echo correctImagePath($user['profile_picture']); ?>"
                                    alt="Profile Picture" class="profile-picture">
                                <?php else: ?>
                                <div class="profile-placeholder">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>

                                <div class="edit-profile-picture">
                                    <i class="fas fa-camera"></i>
                                    <form id="profile-pic-form" method="POST" enctype="multipart/form-data">
                                        <input type="file" name="profile_picture" id="profile-pic-upload"
                                            class="file-upload" onchange="this.form.submit()">
                                    </form>
                                </div>
                            </div>

                            <div class="profile-info">
                                <h2><?php echo $user['name']; ?></h2>
                                <p><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></p>
                                <p><i class="fas fa-user"></i> @<?php echo $user['username']; ?></p>
                                <p><i class="fas fa-calendar-alt"></i> Member since
                                    <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>

                        <form id="profile-form" method="POST">
                            <h3 class="section-title">Personal Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        value="<?php echo $user['name']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" name="username" class="form-control"
                                        value="<?php echo $user['username']; ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?php echo $user['email']; ?>" readonly>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </form>

                        <form id="password-form" method="POST">
                            <div class="password-section">
                                <h3>Change Password</h3>
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password"
                                        class="form-control">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password"
                                            class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="update_password" class="btn btn-primary">Update
                                Password</button>
                        </form>
                    </div>
                </div>
                <!-- Music Favorites Tab -->
                <div id="music-favorites" class="tab-content">
                    <h2>Music Favorites</h2>
                    <div class="favorites-grid animate-in">
                        <?php if (count($music_favorites) > 0): ?>
                        <?php foreach ($music_favorites as $music): ?>
                        <div class="favorite-item">
                            <div class="favorite-thumb">
                                <img src="<?php 
                                if (!empty($music['image_path'])) {
                                    if ($music['image_type'] === 'album') {
                                        echo correctImagePath('../uploads/albums/covers/' . $music['image_path']);
                                    } else {
                                        echo correctImagePath($music['image_path']);
                                    }
                                } else {
                                    echo '../assets/images/default-thumbnail.jpg';
                                }
                                ?>" class="music_img"
                                    alt="<?php echo htmlspecialchars($music['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="favorite-type">TRACK</div>
                                <div class="play-button" data-music-id="<?php echo $music['id']; ?>">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="favorite-info">
                                <h4><?php echo $music['title']; ?></h4>
                                <p><?php echo $music['artist_name']; ?></p>
                                <div class="control-buttons">
                                    <button class="control-btn play-music" data-music-id="<?php echo $music['id']; ?>">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button class="control-btn add-to-playlist"
                                        data-music-id="<?php echo $music['id']; ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="control-btn remove-favorite"
                                        data-favorite-id="<?php echo $music['favorite_id']; ?>">
                                        <i class="fas fa-heart" style="color: var(--cosmic-pink);"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <p class="no-items-message">No music favorites found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Album Favorites Tab -->
                <div id="album-favorites" class="tab-content">
                    <h2>Album Favorites</h2>
                    <div class="favorites-grid animate-in">
                        <?php if (count($album_favorites) > 0): ?>
                        <?php foreach ($album_favorites as $album): ?>
                        <div class="favorite-item">
                            <div class="favorite-thumb">
                                <img src="<?php echo correctImagePath('../uploads/albums/covers/' . $album['image_path']); ?>"
                                    alt="<?php echo $album['title']; ?>">
                                <div class="favorite-type">ALBUM</div>
                                <div class="play-button" data-album-id="<?php echo $album['id']; ?>">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="favorite-info">
                                <h4><?php echo $album['title']; ?></h4>
                                <p><?php echo $album['artist_name']; ?> • <?php echo $album['release_year']; ?></p>
                                <div class="control-buttons">
                                    <button class="control-btn view-album" data-album-id="<?php echo $album['id']; ?>">
                                        <i class="fas fa-compact-disc"></i>
                                    </button>
                                    <button class="control-btn add-to-collection"
                                        data-album-id="<?php echo $album['id']; ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="control-btn remove-favorite"
                                        data-favorite-id="<?php echo $album['favorite_id']; ?>">
                                        <i class="fas fa-heart" style="color: var(--cosmic-pink);"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <p class="no-items-message">No album favorites found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
require_once '../layout/footer.php';

        ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
        $(document).ready(function() {
            // Tab switching functionality
            $('.sidebar-menu a').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');

                // Update active menu item
                $('.sidebar-menu a').removeClass('active');
                $(this).addClass('active');

                // Show appropriate tab content
                $('.tab-content').removeClass('active');
                $('#' + tab).addClass('active');
            });

            // AJAX for removing favorites
            $('.remove-favorite').on('click', function() {
                const favoriteId = $(this).data('favorite-id');
                const item = $(this).closest('.favorite-item');
                const tabId = $('.tab-content.active').attr('id');

                $.ajax({
                    url: '../includes/remove_favorite.php',
                    type: 'POST',
                    data: {
                        favorite_id: favoriteId
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                item.fadeOut(300, function() {
                                    $(this).remove();

                                    // Check if this was the last item
                                    if ($('#' + tabId + ' .favorite-item')
                                        .length === 0) {
                                        $('#' + tabId + ' .favorites-grid').append(
                                            '<p class="no-items-message">No ' +
                                            tabId.replace('-favorites', '') +
                                            ' favorites found.</p>'
                                        );
                                    }
                                });
                            } else {
                                alert('Error removing favorite: ' + data.message);
                            }
                        } catch (e) {
                            console.error('Error parsing JSON response:', e);
                            alert('Error processing response from server');
                        }
                    },
                    error: function() {
                        alert('Error processing request');
                    }
                });
            });

            // Play music functionality
            $('.play-music, .favorite-thumb .play-button[data-music-id]').on('click', function() {
                const musicId = $(this).data('music-id');
                // Send AJAX request to increment play count
                $.ajax({
                    url: '../includes/play_music.php',
                    type: 'POST',
                    data: {
                        music_id: musicId
                    },
                    success: function(response) {
                        // Redirect to player page
                        window.location.href = 'music.php?id=' + musicId;
                    },
                    error: function() {
                        console.error('Error sending play request');
                        // Still redirect even if AJAX fails
                        window.location.href = 'music.php?id=' + musicId;
                    }
                });
            });

            // View album functionality
            $('.view-album, .favorite-thumb .play-button[data-album-id]').on('click', function() {
                const albumId = $(this).data('album-id');
                window.location.href = 'album.php?id=' + albumId;
            });

            // Add to playlist functionality
            $('.add-to-playlist').on('click', function() {
                const musicId = $(this).data('music-id');
                // Open modal with playlists
                alert('Added to playlist');
            });

            // Add to collection functionality
            $('.add-to-collection').on('click', function() {
                const albumId = $(this).data('album-id');
                // Implement collection functionality here
                alert('Added to collection');
            });
        });
        </script>
    </div>
</body>

</html>
<?php
// Close database connection
mysqli_close($conn);
?>