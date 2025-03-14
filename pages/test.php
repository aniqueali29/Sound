<?php
// Include database connection
include '../includes/config_db.php';

// Check if admin is logged in (you'll need to implement proper authentication)
session_start();
// if (!isset($_SESSION['admin_id'])) {
//     // Redirect to login page if not logged in
//     header("Location: admin_login.php");
//     exit;
// }

// Initialize variables
$id = $title = $artist_id = $album_id = $genre_id = $language_id = $release_year = $duration = '';
$is_new = $is_featured = 0;
$file_path = '';
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
        
        // Get common form fields
        $title = $_POST['title'] ?? '';
        $artist_id = $_POST['artist_id'] ?? '';
        $album_id = $_POST['album_id'] ?? '';
        $genre_id = $_POST['genre_id'] ?? '';
        $language_id = $_POST['language_id'] ?? '';
        $release_year = $_POST['release_year'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Create new music track
        if ($action === 'create') {
            // Handle file upload
            $file_path = '';
            if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === 0) {
                $upload_dir = '../uploads/music/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['music_file']['name']);
                $file_path = 'uploads/music/' . $file_name;
                
                if (move_uploaded_file($_FILES['music_file']['tmp_name'], $upload_dir . $file_name)) {
                    // File uploaded successfully
                } else {
                    $error_msg = "Failed to upload file.";
                }
            } else {
                $error_msg = "No music file uploaded or upload error.";
            }
            
            if (empty($error_msg)) {
                // Insert new music track
                $insert_query = "INSERT INTO music (title, artist_id, album_id, genre_id, language_id, 
                                                 release_year, duration, file_path, is_new, is_featured, 
                                                 created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("siiiidssii", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                $release_year, $duration, $file_path, $is_new, $is_featured);
                
                if ($stmt->execute()) {
                    $success_msg = "Music track added successfully!";
                    // Clear form fields after successful submission
                    $title = $artist_id = $album_id = $genre_id = $language_id = $release_year = $duration = '';
                    $is_new = $is_featured = 0;
                } else {
                    $error_msg = "Error adding music track: " . $stmt->error;
                }
                
                $stmt->close();
            }
        } 
        // Update existing music track
        elseif ($action === 'update') {
            $id = $_POST['id'] ?? '';
            
            // Check if file was uploaded
            $file_update = '';
            if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === 0) {
                $upload_dir = '../uploads/music/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['music_file']['name']);
                $file_path = 'uploads/music/' . $file_name;
                
                if (move_uploaded_file($_FILES['music_file']['tmp_name'], $upload_dir . $file_name)) {
                    $file_update = ", file_path = ?";
                } else {
                    $error_msg = "Failed to upload file.";
                }
            }
            
            if (empty($error_msg)) {
                // Update music track
                $update_query = "UPDATE music SET title = ?, artist_id = ?, album_id = ?, 
                                              genre_id = ?, language_id = ?, release_year = ?, 
                                              duration = ?, is_new = ?, is_featured = ?, 
                                              updated_at = NOW()";
                
                // Add file path update if needed
                if (!empty($file_update)) {
                    $update_query .= $file_update;
                }
                
                $update_query .= " WHERE id = ?";
                
                $stmt = $conn->prepare($update_query);
                
                if (!empty($file_update)) {
                    $stmt->bind_param("siiiidsiisi", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $file_path, $id);
                } else {
                    $stmt->bind_param("siiiidsiii", $title, $artist_id, $album_id, $genre_id, $language_id, 
                                    $release_year, $duration, $is_new, $is_featured, $id);
                }
                
                if ($stmt->execute()) {
                    $success_msg = "Music track updated successfully!";
                } else {
                    $error_msg = "Error updating music track: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        // Delete music track
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            
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
}

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
    }
    
    $stmt->close();
}

// Fetch all music tracks for the table
$music_query = "SELECT m.id, m.title, m.file_path, m.duration, m.release_year, 
                     m.is_new, m.is_featured, m.created_at, 
                     a.name AS artist_name, al.title AS album_title,
                     g.name AS genre_name, l.name AS language_name
              FROM music m
              LEFT JOIN artists a ON m.artist_id = a.id
              LEFT JOIN albums al ON m.album_id = al.id
              LEFT JOIN genres g ON m.genre_id = g.id
              LEFT JOIN languages l ON m.language_id = l.id
              WHERE m.deleted_at IS NULL
              ORDER BY m.created_at DESC";

$music_result = $conn->query($music_query);
$music_tracks = $music_result->fetch_all(MYSQLI_ASSOC);

// Set page title
$page_title = 'Manage Music Tracks';

// Include header (adjust path as needed)
// include '../layout/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Music</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --admin-primary: #4a6cf7;
            --admin-secondary: #6e42c1;
            --admin-success: #28a745;
            --admin-danger: #dc3545;
            --admin-dark: #212529;
            --admin-light: #f8f9fa;
        }
        
        body {
            background-color: #f5f8fb;
            font-family: 'Inter', sans-serif;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            padding: 25px;
        }
        
        .admin-card h2 {
            color: var(--admin-dark);
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .btn-admin-primary {
            background-color: var(--admin-primary);
            border-color: var(--admin-primary);
            color: white;
        }
        
        .btn-admin-primary:hover {
            background-color: var(--admin-secondary);
            border-color: var(--admin-secondary);
            color: white;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .admin-table th, .admin-table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .admin-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .admin-table tbody tr:hover {
            background-color: rgba(74, 108, 247, 0.05);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.new {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .status-badge.featured {
            background-color: rgba(74, 108, 247, 0.15);
            color: #4a6cf7;
        }
        
        .form-check-input:checked {
            background-color: var(--admin-primary);
            border-color: var(--admin-primary);
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Music Management</h1>
                
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
            <!-- Music Form Card -->
            <div class="col-md-4">
                <div class="admin-card">
                    <h2><?php echo !empty($id) ? 'Edit Music Track' : 'Add New Music Track'; ?></h2>
                    
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo !empty($id) ? 'update' : 'create'; ?>">
                        <?php if (!empty($id)): ?>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="artist_id" class="form-label">Artist</label>
                            <select class="form-select" id="artist_id" name="artist_id" required>
                                <option value="">Select Artist</option>
                                <?php foreach ($artists as $artist): ?>
                                <option value="<?php echo $artist['id']; ?>" <?php echo ($artist_id == $artist['id']) ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $album['id']; ?>" <?php echo ($album_id == $album['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($album['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="genre_id" class="form-label">Genre</label>
                            <select class="form-select" id="genre_id" name="genre_id" required>
                                <option value="">Select Genre</option>
                                <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo $genre['id']; ?>" <?php echo ($genre_id == $genre['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="language_id" class="form-label">Language</label>
                            <select class="form-select" id="language_id" name="language_id" required>
                                <option value="">Select Language</option>
                                <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['id']; ?>" <?php echo ($language_id == $language['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="release_year" class="form-label">Release Year</label>
                            <input type="number" class="form-control" id="release_year" name="release_year" 
                                   min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $release_year; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (in seconds)</label>
                            <input type="number" class="form-control" id="duration" name="duration" 
                                   min="1" step="0.01" value="<?php echo $duration; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="music_file" class="form-label">Music File <?php echo !empty($id) ? '(Leave empty to keep current file)' : ''; ?></label>
                            <input type="file" class="form-control" id="music_file" name="music_file" 
                                   accept=".mp3, .wav, .ogg, .flac" <?php echo empty($id) ? 'required' : ''; ?>>
                            <?php if (!empty($file_path)): ?>
                            <div class="mt-2">
                                <small class="text-muted">Current file: <?php echo htmlspecialchars($file_path); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_new" name="is_new" value="1" <?php echo $is_new ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_new">Mark as New</label>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" <?php echo $is_featured ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Mark as Featured</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-admin-primary">
                                <?php echo !empty($id) ? 'Update Music Track' : 'Add Music Track'; ?>
                            </button>
                            
                            <?php if (!empty($id)): ?>
                            <a href="admin_music.php" class="btn btn-outline-secondary">Cancel Editing</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Music Table Card -->
            <div class="col-md-8">
                <div class="admin-card">
                    <h2>Music Tracks List</h2>
                    
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Artist</th>
                                    <th>Album</th>
                                    <th>Genre</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($music_tracks) > 0): ?>
                                    <?php foreach ($music_tracks as $track): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($track['title']); ?></td>
                                        <td><?php echo htmlspecialchars($track['artist_name']); ?></td>
                                        <td><?php echo htmlspecialchars($track['album_title'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($track['genre_name']); ?></td>
                                        <td><?php echo $track['release_year']; ?></td>
                                        <td>
                                            <?php if ($track['is_new']): ?>
                                            <span class="status-badge new">New</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($track['is_featured']): ?>
                                            <span class="status-badge featured">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?edit=<?php echo $track['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $track['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $track['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $track['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $track['id']; ?>">Confirm Deletion</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete the music track: <strong><?php echo htmlspecialchars($track['title']); ?></strong>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="id" value="<?php echo $track['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No music tracks found. Add your first track using the form.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Include footer (adjust path as needed)
include '../layout/admin_footer.php';
?>