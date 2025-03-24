<?php
// Include database connection
include '../../includes/config_db.php';

require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];


// Initialize variables
$success_msg = $error_msg = '';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Create new music track
        if ($action === 'create') {
            // Get form fields
            $title = $_POST['title'] ?? '';
            $artist_id = $_POST['artist_id'] ?? '';
            $album_id = $_POST['album_id'] ?? '';
            $genre_id = $_POST['genre_id'] ?? '';
            $language_id = $_POST['language_id'] ?? '';
            $release_year = $_POST['release_year'] ?? '';
            $duration = $_POST['duration'] ?? '';
            $is_new = isset($_POST['is_new']) ? 1 : 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle music file upload
            $file_path = '';
            if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === 0) {
                $upload_dir = '../../uploads/music/';
                
                // Create directory if it doesn't exist
                if (!file_exists('../../' . $upload_dir)) {
                    mkdir('../../' . $upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['music_file']['name']);
                $file_path = '../../uploads/music/' . $file_name;  // Changed to ../uploads/music/
                
                if (move_uploaded_file($_FILES['music_file']['tmp_name'], $upload_dir . $file_name)) {
                    // File uploaded successfully
                } else {
                    $error_msg = "Failed to upload music file.";
                }
            } else {
                $error_msg = "No music file uploaded or upload error.";
            }
            
            // Handle thumbnail image upload
            $thumbnail_path = '';
            if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === 0) {
                $upload_dir = '../../uploads/music/thumbnails/';
                
                // Create directory if it doesn't exist
                if (!file_exists('../' . $upload_dir)) {
                    mkdir('../../' . $upload_dir, 0777, true);
                }
                
                $thumbnail_name = time() . '_' . basename($_FILES['thumbnail_image']['name']);
                $thumbnail_path = '../../uploads/music/thumbnails/' . $thumbnail_name;  // Changed to ../uploads/music/thumbnails/
                
                if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $upload_dir . $thumbnail_name)) {
                    // Thumbnail uploaded successfully
                } else {
                    $error_msg = "Failed to upload thumbnail image.";
                }
            }
            
            if (empty($error_msg)) {
                // Insert new music track
                $insert_query = "INSERT INTO music (title, artist_id, album_id, genre_id, language_id, 
                                               release_year, duration, file_path, thumbnail_path, is_new, is_featured, is_active,
                                               created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("siiiiisssiiii", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                $release_year, $duration, $file_path, $thumbnail_path, $is_new, $is_featured, $is_active);
                
                if ($stmt->execute()) {
                    $success_msg = "Music track added successfully!";
                } else {
                    $error_msg = "Error adding music track: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        // Delete music track (either single or batch)
        elseif ($action === 'delete') {
            if (isset($_POST['selected_tracks']) && !empty($_POST['selected_tracks'])) {
                // Batch delete
                $tracks = json_decode($_POST['selected_tracks']);
                $deleted_count = 0;
                
                foreach ($tracks as $track_id) {
                    $delete_query = "UPDATE music SET deleted_at = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($delete_query);
                    $stmt->bind_param("i", $track_id);
                    
                    if ($stmt->execute()) {
                        $deleted_count++;
                    }
                    
                    $stmt->close();
                }
                
                if ($deleted_count > 0) {
                    $success_msg = "$deleted_count music track(s) deleted successfully!";
                } else {
                    $error_msg = "No tracks were deleted.";
                }
            } else if (isset($_POST['id'])) {
                // Single delete
                $id = $_POST['id'];
                
                // Soft delete by setting deleted_at timestamp
                $delete_query = "UPDATE music SET deleted_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_msg = "Music track deleted successfully!";
                } else {
                    $error_msg = "Error deleting music track: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        // Toggle track active status
        elseif ($action === 'toggle_active') {
            if (isset($_POST['id'])) {
                $id = $_POST['id'];
                $new_status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
                
                // Update the active status
                $update_query = "UPDATE music SET is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $new_status, $id);
                
                if ($stmt->execute()) {
                    $status_text = $new_status ? "activated" : "deactivated";
                    $success_msg = "Music track $status_text successfully!";
                } else {
                    $error_msg = "Error updating track status: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        // Batch toggle active status
        elseif ($action === 'batch_toggle_active') {
            if (isset($_POST['selected_tracks']) && !empty($_POST['selected_tracks'])) {
                $tracks = json_decode($_POST['selected_tracks']);
                $new_status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
                $updated_count = 0;
                
                foreach ($tracks as $track_id) {
                    $update_query = "UPDATE music SET is_active = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ii", $new_status, $track_id);
                    
                    if ($stmt->execute()) {
                        $updated_count++;
                    }
                    
                    $stmt->close();
                }
                
                if ($updated_count > 0) {
                    $status_text = $new_status ? "activated" : "deactivated";
                    $success_msg = "$updated_count music track(s) $status_text successfully!";
                } else {
                    $error_msg = "No tracks were updated.";
                }
            }
        }
        // Export tracks
        elseif ($action === 'export') {
            if (isset($_POST['selected_tracks']) && !empty($_POST['selected_tracks'])) {
                $tracks = json_decode($_POST['selected_tracks']);
                // Here you would implement your export functionality
                $success_msg = "Export functionality would be implemented here!";
            } else {
                $error_msg = "No tracks selected for export.";
            }
        }
    }
}

// Get filter values if set
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// <!-- In the SQL query, add album cover_image to selection -->
$music_query = "SELECT m.id, m.title, m.file_path, m.thumbnail_path, m.duration, m.release_year, 
                     m.is_new, m.is_featured, m.is_active, m.created_at,
                     a.name AS artist_name, al.title AS album_title, al.cover_image AS album_cover,
                     g.name AS genre_name, l.name AS language_name
              FROM music m
              LEFT JOIN artists a ON m.artist_id = a.id
              LEFT JOIN albums al ON m.album_id = al.id
              LEFT JOIN genres g ON m.genre_id = g.id
              LEFT JOIN languages l ON m.language_id = l.id
              WHERE m.deleted_at IS NULL";
// Add status filter
if ($status_filter === 'new') {
    $music_query .= " AND m.is_new = 1";
} else if ($status_filter === 'featured') {
    $music_query .= " AND m.is_featured = 1";
} else if ($status_filter === 'active') {
    $music_query .= " AND m.is_active = 1";
} else if ($status_filter === 'inactive') {
    $music_query .= " AND m.is_active = 0";
}

// Add genre filter
if ($genre_filter !== 'all') {
    $music_query .= " AND m.genre_id = '" . $conn->real_escape_string($genre_filter) . "'";
}

// Add search filter
if (!empty($search_query)) {
    $music_query .= " AND (m.title LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                      OR a.name LIKE '%" . $conn->real_escape_string($search_query) . "%'
                      OR al.title LIKE '%" . $conn->real_escape_string($search_query) . "%')";
}

$music_query .= " ORDER BY m.created_at DESC";

$music_result = $conn->query($music_query);
$music_tracks = $music_result->fetch_all(MYSQLI_ASSOC);

// Set page title
$page_title = 'Manage Music Tracks';

// Query to count the number of tracks in the music table
$query = "SELECT COUNT(id) AS track_count FROM music";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    $track_count = $row['track_count']; // Fetch the count
} else {
    $track_count = 0; // Fallback in case of an error
}
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
    <link rel="stylesheet" href="../assets/css/tracks.css">
    <style>
    /* Enhanced styles for active status toggle - smaller version */
    .status-badge {
        display: inline-block;
        padding: 0.15rem 0.35rem;
        border-radius: 12px;
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        transition: all 0.3s ease;
    }

    .status-badge.active {
        background-color: #28a745;
        color: white;
        box-shadow: 0 1px 3px rgba(40, 167, 69, 0.3);
    }

    .status-badge.inactive {
        background-color: #dc3545;
        color: white;
        box-shadow: 0 1px 3px rgba(220, 53, 69, 0.3);
    }

    .toggle-active {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .toggle-active:hover {
        opacity: 0.8;
        transform: scale(1.03);
    }

    /* Redesigned toggle switch - smaller version */
    .switch {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 18px;
        margin: 0 4px;
        vertical-align: middle;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #e0e0e0;
        transition: .3s ease-in-out;
        border-radius: 18px;
        box-shadow: inset 0 0 3px rgba(0, 0, 0, 0.2);
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: .3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    input:checked+.slider {
        background-color: #2196F3;
        background-image: linear-gradient(to right, #2196F3, #4CAF50);
    }

    input:focus+.slider {
        box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.3);
    }

    input:checked+.slider:before {
        transform: translateX(18px);
    }

    /* Active/inactive status icons - smaller */
    .slider:after {
        content: '✕';
        color: rgba(255, 255, 255, 0.6);
        display: block;
        position: absolute;
        transform: translate(-50%, -50%);
        top: 50%;
        left: 70%;
        font-size: 8px;
        font-weight: bold;
    }

    input:checked+.slider:after {
        content: '✓';
        left: 30%;
    }

    /* Add animation for status change */
    @keyframes statusPulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    .status-badge.active,
    .status-badge.inactive {
        animation: statusPulse 0.4s ease;
    }


    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .action-icon {
        cursor: pointer;
        padding: 10px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .action-icon:hover {
        transform: scale(1);
    }
    label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
    font-size: 14px;
    color: #a2a3b7 !important;
}
    </style>
</head>

<body>


    <div class="page-header">
        <h1>Music Management</h1>
    </div>
    <div class="admin-container">
        <div class="row">
            <div class="col-12">
                <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="admin-card">
                    <form id="tracks-filter-form" method="get">
                        <div class="filters-section">
                            <div class="filter-wrapper">
                                <label for="status-filter">Status:</label>
                                <select id="status-filter" name="status" class="form-select form-select-sm">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All
                                        Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>
                                        Active</option>
                                    <option value="inactive"
                                        <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>
                                        Inactive</option>
                                    <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New
                                    </option>
                                    <option value="featured"
                                        <?php echo $status_filter === 'featured' ? 'selected' : ''; ?>>
                                        Featured</option>
                                </select>
                            </div>

                            <div class="filter-wrapper">
                                <label for="genre-filter">Genre:</label>
                                <select id="genre-filter" name="genre" class="form-select form-select-sm">
                                    <option value="all">All Genres</option>
                                    <?php foreach ($genres as $genre): ?>
                                    <option value="<?php echo $genre['id']; ?>"
                                        <?php echo $genre_filter == $genre['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($genre['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="search-wrapper">
                                <input type="text" name="search" class="form-control" placeholder="Search tracks..."
                                    value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <div class="action-buttons">
                                <a href="add_edit_track.php" class="action-icon text-primary" title="Add Track">
                                    <i class="fas fa-plus fa-lg"></i>
                                </a>
                                <a href="#" id="activate-selected" class="action-icon text-success disabled"
                                    title="Activate">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </a>
                                <a href="#" id="deactivate-selected" class="action-icon text-warning disabled"
                                    title="Deactivate">
                                    <i class="fas fa-ban fa-lg"></i>
                                </a>
                                <a href="#" id="delete-selected" class="action-icon text-danger disabled"
                                    title="Delete">
                                    <i class="fas fa-trash fa-lg"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="admin-table" style="background-color: #1E1E2D !important;">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="form-check-input" id="select-all">
                                    </th>
                                    <th>Track</th>
                                    <th>Waveform</th>
                                    <th>Genre</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($music_tracks) > 0): ?>
                                <?php foreach ($music_tracks as $track): ?>
                                <tr class="track-row" data-id="<?php echo $track['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input track-checkbox"
                                            data-id="<?php echo $track['id']; ?>">
                                    </td>
                                    <!-- In the table where you display tracks -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($track['album_cover'])): ?>
                                            <img src="<?php echo htmlspecialchars('../../uploads/albums/covers/' . $track['album_cover']); ?>"
                                                class="track-thumbnail me-3"
                                                alt="<?php echo htmlspecialchars($track['album_title']); ?>">
                                            <?php elseif (!empty($track['thumbnail_path'])): ?>
                                            <img src="<?php echo htmlspecialchars( $track['thumbnail_path']); ?>"
                                                class="track-thumbnail me-3"
                                                alt="<?php echo htmlspecialchars($track['title']); ?>">
                                            <?php else: ?>
                                            <img src="../assets/images/default-track.png" class="track-thumbnail me-3"
                                                alt="Default thumbnail">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold">
                                                    <?php echo htmlspecialchars($track['title']); ?>
                                                </div>
                                                <div class="small" style="color:#A2A3B7;">
                                                    <?php echo htmlspecialchars($track['artist_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="waveform-container" data-track-id="<?php echo $track['id']; ?>">
                                            <div class="waveform-controls">

                                            </div>
                                            <div id="waveform-<?php echo $track['id']; ?>" class="waveform">
                                            </div>
                                            <!-- Hidden audio element -->
                                            <audio id="audio-<?php echo $track['id']; ?>" style="display: none;">
                                                <?php if (!empty( $track['file_path'])): ?>
                                                <source
                                                    src="<?php echo htmlspecialchars( $track['file_path']); ?>"
                                                    type="audio/mpeg">
                                                <?php else: ?>
                                                <source src="../assets/demo/demo-song.mp3" type="audio/mpeg">
                                                <?php endif; ?>
                                                Your browser does not support the audio element.
                                            </audio>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($track['genre_name']); ?></td>
                                    <td>
                                        <div class="d-flex flex-column align-items-start">
                                            <?php if ($track['is_new']): ?>
                                            <span class="status-badge new">New</span>
                                            <?php endif; ?>

                                            <?php if ($track['is_featured']): ?>
                                            <span class="status-badge featured">Featured</span>
                                            <?php endif; ?>

                                            <!-- Active status toggle switch -->
                                            <label class="switch mt-1"
                                                title="<?php echo $track['is_active'] ? 'Active' : 'Inactive'; ?>">
                                                <input type="checkbox" class="active-toggle"
                                                    data-id="<?php echo $track['id']; ?>"
                                                    <?php echo $track['is_active'] ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="add_edit_track.php?edit=<?php echo $track['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-track"
                                                data-id="<?php echo $track['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($track['title']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No music tracks found. Add your
                                        first track
                                        using the "Add Track" button.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
    </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the selected track(s)?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="delete-form" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-track-id" value="">
                        <input type="hidden" name="selected_tracks" id="selected-tracks-input" value="">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Status Change Form (Hidden) -->
    <form id="active-status-form" method="post" style="display: none;">
        <input type="hidden" name="action" value="toggle_active">
        <input type="hidden" name="id" id="status-track-id" value="">
        <input type="hidden" name="status" id="status-value" value="">
    </form>

    <!-- Batch Status Change Form (Hidden) -->
    <form id="batch-status-form" method="post" style="display: none;">
        <input type="hidden" name="action" value="batch_toggle_active">
        <input type="hidden" name="selected_tracks" id="status-tracks-input" value="">
        <input type="hidden" name="status" id="batch-status-value" value="">
    </form>

    <!-- Export Form (Hidden) -->
    <form id="export-form" method="post" style="display: none;">
        <input type="hidden" name="action" value="export">
        <input type="hidden" name="selected_tracks" id="export-tracks-input" value="">
    </form>

    <script src="../assets/js/app.js"></script>
            
    <script src="../assets/js/tracks.js"></script>
    <!-- <script src="../assets/js/app.js"></script> -->

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/6.6.3/wavesurfer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>


</body>

</html>