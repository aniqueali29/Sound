<?php
// Include database connection
include '../../includes/config_db.php';

require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];

// Initialize variables
$id = $title = $artist_id = $album_id = $genre_id = $language_id = $release_year = $duration = '';
$is_new = $is_featured = 0;
$file_path = $thumbnail_path = '';
$success_msg = $error_msg = '';
$edit_mode = false;

// Get all artists for dropdown
$artists_query = "SELECT id, name FROM artists WHERE deleted_at IS NULL ORDER BY name";
$artists_result = $conn->query($artists_query);
$artists = $artists_result->fetch_all(MYSQLI_ASSOC);

// Get all albums for dropdown
$albums_query = "SELECT id, title FROM albums WHERE deleted_at IS NULL ORDER BY title";
$albums_result = $conn->query($albums_query);
$albums = $albums_result->fetch_all(MYSQLI_ASSOC);

// Get all genres for dropdown
$genres_query = "SELECT id, name FROM genres ORDER BY name";
$genres_result = $conn->query($genres_query);
$genres = $genres_result->fetch_all(MYSQLI_ASSOC);

// Get all languages for dropdown
$languages_query = "SELECT id, name FROM languages ORDER BY name";
$languages_result = $conn->query($languages_query);
$languages = $languages_result->fetch_all(MYSQLI_ASSOC);

// Edit functionality - Get music data for editing
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM music WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($edit_query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $music_data = $result->fetch_assoc();
        $id = $music_data['id'];
        $title = $music_data['title'];
        $artist_id = $music_data['artist_id'];
        $album_id = $music_data['album_id'];
        $genre_id = $music_data['genre_id'];
        $language_id = $music_data['language_id'];
        $release_year = $music_data['release_year'];
        $duration = $music_data['duration'];
        $is_new = $music_data['is_new'];
        $is_featured = $music_data['is_featured'];
        $file_path = $music_data['file_path'];
        $thumbnail_path = $music_data['thumbnail_path'] ?? '';
        $edit_mode = true;
    }
    
    $stmt->close();
}

// Function to associate track with album
function associateTrackWithAlbum($conn, $music_id, $album_id) {
    // First, get album details to ensure we're working with a valid album
    $album_query = "SELECT * FROM albums WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($album_query);
    $stmt->bind_param("i", $album_id);
    $stmt->execute();
    $album_result = $stmt->get_result();
    
    if ($album_result->num_rows > 0) {
        // Album exists, now update the music-album relationship
        // You might need to create a separate table for album_tracks if not exists
        // For now, we'll just ensure the album_id is set in the music table
        return true;
    }
    
    return false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Get common form fields
        $title = $_POST['title'] ?? '';
        $artist_id = $_POST['artist_id'] ?? '';
        
        // Check if album is selected
        if (!empty($_POST['album_id'])) {
            $album_id = $_POST['album_id'];
            // If album is selected, set thumbnail_path to NULL (will use album cover)
            $thumbnail_path = NULL;
        } else {
            $album_id = null;  // Set to NULL if no album is selected
        }
        
        $genre_id = $_POST['genre_id'] ?? '';
        $language_id = $_POST['language_id'] ?? '';
        $release_year = $_POST['release_year'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Create new music track
        if ($action === 'create') {
            // Start transaction for multiple operations
            $conn->begin_transaction();
            
            // Handle music file upload
            $file_path = '';
            if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === 0) {
                $upload_dir = '../../uploads/music/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['music_file']['name']);
                $file_path = '../../uploads/music/' . $file_name;
                
                if (move_uploaded_file($_FILES['music_file']['tmp_name'], $upload_dir . $file_name)) {
                    // File uploaded successfully
                } else {
                    $error_msg = "Failed to upload music file.";
                }
            } else {
                $error_msg = "No music file uploaded or upload error.";
            }
            
            // Handle thumbnail image upload only if no album is selected
            $thumbnail_path = '';
            if (empty($album_id) && isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === 0) {
                $upload_dir = '../../uploads/music/thumbnails/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $thumbnail_name = time() . '_' . basename($_FILES['thumbnail_image']['name']);
                $thumbnail_path = '../../uploads/music/thumbnails/' . $thumbnail_name;
                
                if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $upload_dir . $thumbnail_name)) {
                    // Thumbnail uploaded successfully
                } else {
                    $error_msg = "Failed to upload thumbnail image.";
                }
            }
            
            if (empty($error_msg)) {
                try {
                    // Insert new music track
                    $insert_query = "INSERT INTO music (title, artist_id, album_id, genre_id, language_id, 
                                                     release_year, duration, file_path, thumbnail_path, is_new, is_featured, 
                                                     created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("siiiidsssii", $title, $artist_id, $album_id, $genre_id, $language_id, 
                      $release_year, $duration, $file_path, $thumbnail_path, $is_new, $is_featured);
    
                    if ($stmt->execute()) {
                        // Get the ID of the newly inserted music track
                        $music_id = $conn->insert_id;
                        
                        // If album is selected, update the album's track count
                        if (!empty($album_id)) {
                            // Update album track count
                            $update_album_query = "UPDATE albums SET updated_at = NOW() WHERE id = ?";
                            $album_stmt = $conn->prepare($update_album_query);
                            $album_stmt->bind_param("i", $album_id);
                            $album_stmt->execute();
                            $album_stmt->close();
                        }
                        
                        // Commit the transaction
                        $conn->commit();
                        
                        $success_msg = "Music track added successfully!";
                        // Redirect to the main music management page
                        header("Location: tracks.php?success=created");
                        exit;
                    } else {
                        throw new Exception("Error adding music track: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    // Rollback the transaction on error
                    $conn->rollback();
                    $error_msg = $e->getMessage();
                } finally {
                    $stmt->close();
                }
            }
        } 
        // Update existing music track
        elseif ($action === 'update') {
            $id = $_POST['id'] ?? '';
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get current track data before update
                $current_track_query = "SELECT album_id FROM music WHERE id = ?";
                $current_track_stmt = $conn->prepare($current_track_query);
                $current_track_stmt->bind_param("i", $id);
                $current_track_stmt->execute();
                $current_result = $current_track_stmt->get_result();
                $current_track = $current_result->fetch_assoc();
                $previous_album_id = $current_track['album_id'];
                $current_track_stmt->close();
                
                // Check if music file was uploaded
                $file_update = '';
                if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === 0) {
                    $upload_dir = '../../uploads/music/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_name = time() . '_' . basename($_FILES['music_file']['name']);
                    $file_path = '../../uploads/music/' . $file_name;
                    
                    if (move_uploaded_file($_FILES['music_file']['tmp_name'], $upload_dir . $file_name)) {
                        $file_update = ", file_path = ?";
                    } else {
                        throw new Exception("Failed to upload music file.");
                    }
                }
                
                // Check if thumbnail image was uploaded and no album is selected
                $thumbnail_update = '';
                if (!empty($album_id)) {
                    // If album is selected, set thumbnail_path to NULL
                    $thumbnail_update = ", thumbnail_path = NULL";
                } else if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === 0) {
                    $upload_dir = '../../uploads/music/thumbnails/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $thumbnail_name = time() . '_' . basename($_FILES['thumbnail_image']['name']);
                    $thumbnail_path = '../../uploads/music/thumbnails/' . $thumbnail_name;
                    
                    if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $upload_dir . $thumbnail_name)) {
                        $thumbnail_update = ", thumbnail_path = ?";
                    } else {
                        throw new Exception("Failed to upload thumbnail image.");
                    }
                }
                
                // Update music track
                $update_query = "UPDATE music SET title = ?, artist_id = ?, album_id = ?, 
                                              genre_id = ?, language_id = ?, release_year = ?, 
                                              duration = ?, is_new = ?, is_featured = ?, 
                                              updated_at = NOW()";
                
                // Add file path and thumbnail updates if needed
                if (!empty($file_update)) {
                    $update_query .= $file_update;
                }
                
                if (!empty($thumbnail_update)) {
                    $update_query .= $thumbnail_update;
                }
                
                $update_query .= " WHERE id = ?";
                
                $stmt = $conn->prepare($update_query);
                
                if (!empty($file_update) && !empty($thumbnail_update) && $thumbnail_update !== ", thumbnail_path = NULL") {
                    $stmt->bind_param("siiiidsiissi", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $file_path, $thumbnail_path, $id);
                } elseif (!empty($file_update) && !empty($thumbnail_update) && $thumbnail_update === ", thumbnail_path = NULL") {
                    $stmt->bind_param("siiiidsiisi", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $file_path, $id);
                } elseif (!empty($file_update)) {
                    $stmt->bind_param("siiiidsiisi", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $file_path, $id);
                } elseif (!empty($thumbnail_update) && $thumbnail_update !== ", thumbnail_path = NULL") {
                    $stmt->bind_param("siiiidsiisi", $title, $artist_id, $album_id, $genre_id, $language_id, 
                    $release_year, $duration, $is_new, $is_featured, $thumbnail_path, $id);
                } elseif (!empty($thumbnail_update) && $thumbnail_update === ", thumbnail_path = NULL") {
                    $stmt->bind_param("siiiidsiii", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $id);
                } else {
                    $stmt->bind_param("siiiidsiii", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $id);
                }
                
                if ($stmt->execute()) {
                    // If album association has changed
                    if ($previous_album_id != $album_id) {
                        // If new album assigned, update that album
                        if (!empty($album_id)) {
                            $update_new_album = "UPDATE albums SET updated_at = NOW() WHERE id = ?";
                            $new_album_stmt = $conn->prepare($update_new_album);
                            $new_album_stmt->bind_param("i", $album_id);
                            $new_album_stmt->execute();
                            $new_album_stmt->close();
                        }
                        
                        // If track was previously in an album, update that album too
                        if (!empty($previous_album_id)) {
                            $update_old_album = "UPDATE albums SET updated_at = NOW() WHERE id = ?";
                            $old_album_stmt = $conn->prepare($update_old_album);
                            $old_album_stmt->bind_param("i", $previous_album_id);
                            $old_album_stmt->execute();
                            $old_album_stmt->close();
                        }
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    $success_msg = "Music track updated successfully!";
                    // Redirect to the main music management page
                    header("Location: tracks.php?success=updated");
                    exit;
                } else {
                    throw new Exception("Error updating music track: " . $stmt->error);
                }
            } catch (Exception $e) {
                // Rollback the transaction on error
                $conn->rollback();
                $error_msg = $e->getMessage();
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
    }
}

// Query to count the number of tracks in the music table
$query = "SELECT COUNT(id) AS track_count FROM music WHERE deleted_at IS NULL";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    $track_count = $row['track_count']; // Fetch the count
} else {
    $track_count = 0; // Fallback in case of an error
}

// Set page title
$page_title = $edit_mode ? 'Edit Music Track' : 'Add New Music Track';
$page_btn = $edit_mode ? '<a href="./tracks.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Music List</a>' : '';

// Include header (adjust path as needed)
// include '../layout/admin_header.php';
include './header.php';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sound Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --admin-primary: #00c6ff;
            --admin-secondary: #6e42c1;
            --admin-success: #00e676;
            --admin-danger: #ff3d71;
            --admin-dark: #1e1e2d;
            --admin-light: #a2a3b7;
            --admin-background: #0f0f1a;
            --admin-card-bg: #1e1e2d;
            --admin-hover: rgba(255, 255, 255, 0.05);
            --admin-border: rgba(255, 255, 255, 0.05);
            --admin-border-radius: 12px;
            --admin-box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        body {
            background-color: var(--admin-background);
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--admin-light);
            margin: 0;
            padding: 0;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-card {
            background: var(--admin-card-bg);
            border-radius: var(--admin-border-radius);
            box-shadow: var(--admin-box-shadow);
            margin-bottom: 30px;
            padding: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--admin-border);
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            background-color: var(--admin-dark);
        }

        .admin-card h2 {
            color: #ffffff;
            font-weight: 700;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--admin-border);
            padding-bottom: 12px;
            font-size: 1.8rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #ffffff;
            display: block;
        }

        .form-control,
        /* Style for the select dropdown */
        .form-select {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--admin-border);
            color: #ffffff;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s;
            width: 100%;
            appearance: none;
        }

        /* Style for options inside select */
        .form-control:focus,
        .form-select option {
            background-color: #1e1e2d !important;
            /* Dark background */
            color: #ffffff !important;
            /* White text */
            padding: 10px;
        }

        /* Fixes the issue on Firefox */
        .form-select optgroup {
            background-color: #1e1e2d !important;
            color: #ffffff !important;
        }

        /* Ensure the dropdown background is also dark */
        .form-select:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 198, 255, 0.25);
            outline: none;
        }

        /* WebKit (Chrome, Edge, Safari) fix */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #1e1e2d;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 198, 255, 0.25);
            outline: none;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23a2a3b7' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
            appearance: none;
        }

        /* File Input Specific Style */
        input[type="file"].form-control {
            padding: 8px;
        }

        input[type="file"].form-control::file-selector-button {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            margin-right: 16px;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        input[type="file"].form-control::file-selector-button:hover {
            background-color: var(--admin-primary);
        }

        /* Checkbox Style */
        .form-check {
            margin-bottom: 1rem;
            display: inline-block;
            align-items: center;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--admin-primary);
            border-color: var(--admin-primary);
        }

        .form-check-label {
            color: #ffffff;
            font-weight: 500;
            cursor: pointer;
            margin-left: 4px;
        }

        /* Thumbnail Preview */
        .thumbnail-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--admin-border);
        }

        .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
            font-size: 0.85rem;
        }

        /* Buttons */
        .btn-admin-primary {
            background-color: var(--admin-primary);
            border: none;
            color: var(--admin-dark);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-block;
        }

        .btn-admin-primary:hover {
            background-color: var(--admin-secondary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 198, 255, 0.3);
        }

        .btn-outline-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid var(--admin-light);
            color: var(--admin-light);
            transition: all 0.3s;
            background-color: transparent;
            font-weight: 600;
        }

        .btn-outline-secondary:hover {
            background-color: var(--admin-hover);
            color: white;
            border-color: white;
        }

        .ms-2 {
            margin-left: 0.5rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }

            .admin-card {
                padding: 20px;
            }

            .row {
                margin-right: -10px;
                margin-left: -10px;
            }

            .col-md-6,
            .col-12 {
                padding-right: 10px;
                padding-left: 10px;
            }

            .btn-admin-primary,
            .btn-outline-secondary {
                width: 100%;
                margin: 5px 0;
                text-align: center;
            }

            .ms-2 {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
            <!-- Main Content -->
            <main>
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1>
                            <?php echo $page_title; ?>
                        </h1>
                        <?php echo $page_btn; ?>
                    </div>
                </div>
                <div class="admin-container">
                    <div class="row">
                        <div class="col-12">


                            <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="admin-card">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="action"
                                        value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
                                    <?php if ($edit_mode): ?>
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Title</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    value="<?php echo htmlspecialchars($title); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="artist_id" class="form-label">Artist</label>
                                                <select class="form-select" id="artist_id" name="artist_id" required>
                                                    <option value="">Select Artist</option>
                                                    <?php foreach ($artists as $artist): ?>
                                                    <option value="<?php echo $artist['id']; ?>" <?php echo
                                                        ($artist_id==$artist['id']) ? 'selected' : '' ; ?>>
                                                        <?php echo htmlspecialchars($artist['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="album_id" class="form-label">Album</label>
                                                <select class="form-select" id="album_id" name="album_id">
                                                    <option value="">Select Album (Optional)</option>
                                                    <?php foreach ($albums as $album): ?>
                                                    <option value="<?php echo $album['id']; ?>" <?php echo
                                                        ($album_id==$album['id']) ? 'selected' : '' ; ?>>
                                                        <?php echo htmlspecialchars($album['title']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>


                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="language_id" class="form-label">Language</label>
                                                <select class="form-select" id="language_id" name="language_id"
                                                    required>
                                                    <option value="">Select Language</option>
                                                    <?php foreach ($languages as $language): ?>
                                                    <option value="<?php echo $language['id']; ?>" <?php echo
                                                        ($language_id==$language['id']) ? 'selected' : '' ; ?>>
                                                        <?php echo htmlspecialchars($language['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="release_year" class="form-label">Release Year</label>
                                                <input type="number" class="form-control" id="release_year"
                                                    name="release_year" min="1900" max="<?php echo date('Y'); ?>"
                                                    value="<?php echo $release_year; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="genre_id" class="form-label">Genre</label>
                                                <select class="form-select" id="genre_id" name="genre_id" required>
                                                    <option value="">Select Genre</option>
                                                    <?php foreach ($genres as $genre): ?>
                                                    <option value="<?php echo $genre['id']; ?>" <?php echo
                                                        ($genre_id==$genre['id']) ? 'selected' : '' ; ?>>
                                                        <?php echo htmlspecialchars($genre['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-4">

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="thumbnail_image" class="form-label">Thumbnail Image
                                                    <?php echo $edit_mode ? '(Leave empty to keep current image)' : ''; ?>
                                                </label>
                                                <input type="file" class="form-control" id="thumbnail_image"
                                                    name="thumbnail_image" accept=".jpg, .jpeg, .png, .gif">
                                                <?php if (!empty($thumbnail_path)): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Current thumbnail:</small>
                                                    <img src="<?php echo htmlspecialchars($thumbnail_path); ?>"
                                                        class="thumbnail-preview d-block" alt="Current thumbnail">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="music_file" class="form-label">Music File
                                                    <?php echo $edit_mode ? '(Leave empty to keep current file)' : ''; ?>
                                                </label>
                                                <input type="file" class="form-control" id="music_file"
                                                    name="music_file" accept=".mp3, .wav, .ogg, .flac" <?php echo
                                                    $edit_mode ? '' : 'required' ; ?>>
                                                <?php if (!empty($file_path)): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Current file:
                                                        <?php echo htmlspecialchars($file_path); ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>


                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3 form-check mt-4">
                                                <input type="checkbox" class="form-check-input" id="is_new"
                                                    name="is_new" value="1" <?php echo $is_new ? 'checked' : '' ; ?>>
                                                <label class="form-check-label" for="is_new">Mark as New</label>
                                                <br>
                                                <input type="checkbox" class="form-check-input" id="is_featured"
                                                    name="is_featured" value="1" <?php echo $is_featured ? 'checked'
                                                    : '' ; ?>>
                                                <label class="form-check-label" for="is_featured">Mark as
                                                    Featured</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-admin-primary">
                                            <?php echo $edit_mode ? 'Update Music Track' : 'Add Music Track'; ?>
                                        </button>
                                        <a href="tracks.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
        </script>

    <!-- Preview thumbnail image script -->
    <script>
        document.getElementById('thumbnail_image').addEventListener('change', function (e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const previewContainer = document.querySelector('.thumbnail-preview');

                    if (previewContainer) {
                        // Update existing preview
                        previewContainer.src = e.target.result;
                    } else {
                        // Create new preview
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'thumbnail-preview d-block';
                        img.alt = 'Thumbnail preview';
                        document.getElementById('thumbnail_image').parentNode.appendChild(img);
                    }
                };

                reader.readAsDataURL(file);
            }
        });


        document.addEventListener('DOMContentLoaded', function () {
            const albumSelect = document.getElementById('album_id');
            const thumbnailInput = document.getElementById('thumbnail_image');
            const previewContainer = document.querySelector('.thumbnail-preview');

            function toggleThumbnailState() {
                const inputContainer = thumbnailInput.parentNode;
                let existingMessage = inputContainer.querySelector('.album-selected-message');

                if (albumSelect.value !== '') {
                    // Clear the thumbnail input
                    thumbnailInput.value = '';

                    // Prevent selecting a file without affecting styles
                    thumbnailInput.setAttribute('readonly', true);
                    thumbnailInput.style.pointerEvents = 'none';
                    thumbnailInput.style.opacity = '0.6';

                    // Hide preview if it exists
                    if (previewContainer) previewContainer.style.display = 'none';

                    // Show message if not already present
                    if (!existingMessage) {
                        existingMessage = document.createElement('div');
                        existingMessage.className = 'text-muted mt-2 album-selected-message';
                        existingMessage.innerHTML =
                            'Thumbnail disabled: Currently using album’s own thumbnail instead';
                        inputContainer.appendChild(existingMessage);
                    }
                } else {
                    // Re-enable file input without affecting styles
                    thumbnailInput.removeAttribute('readonly');
                    thumbnailInput.style.pointerEvents = 'auto';
                    thumbnailInput.style.opacity = '1';

                    // Remove message
                    if (existingMessage) existingMessage.remove();

                    // Show preview again if it exists
                    if (previewContainer) previewContainer.style.display = 'block';
                }
            }

            // Event listener for album selection change
            albumSelect.addEventListener('change', toggleThumbnailState);

            // Run check on page load
            toggleThumbnailState();
        });
    </script>

</body>

</html>