<?php
// Include database connection
include '../../includes/config_db.php';
require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];


// Functions for album operations
function getAlbums($conn) {
    $sql = "SELECT a.*, ar.name as artist_name, g.name as genre_name, l.name as language_name 
            FROM albums a 
            LEFT JOIN artists ar ON a.artist_id = ar.id 
            LEFT JOIN genres g ON a.genre_id = g.id 
            LEFT JOIN languages l ON a.language_id = l.id 
            WHERE a.deleted_at IS NULL 
            ORDER BY a.created_at DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getAlbumById($conn, $id) {
    $id = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM albums WHERE id = '$id' AND deleted_at IS NULL";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

function getArtists($conn) {
    $sql = "SELECT id, name FROM artists WHERE deleted_at IS NULL ORDER BY name";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getGenres($conn) {
    $sql = "SELECT id, name FROM genres ORDER BY name";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getLanguages($conn) {
    $sql = "SELECT id, name FROM languages ORDER BY name";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Handle form submissions
$error = '';
$success = '';

// Add new album
if(isset($_POST['add_album'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $artist_id = mysqli_real_escape_string($conn, $_POST['artist_id']);
    $release_year = mysqli_real_escape_string($conn, $_POST['release_year']);
    $genre_id = isset($_POST['genre_id']) ? mysqli_real_escape_string($conn, $_POST['genre_id']) : "NULL";
    $language_id = isset($_POST['language_id']) ? mysqli_real_escape_string($conn, $_POST['language_id']) : "NULL";
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : "NULL";
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    // Handle cover image upload
    $cover_image = NULL;
    if(isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../../uploads/albums/covers/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES['cover_image']['name']);
        $target_file = $target_dir . $file_name;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['cover_image']['tmp_name']);
        if($check !== false) {
            if(move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                $cover_image = $file_name;
            } else {
                $error = "Error uploading cover image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    // Handle featured image upload
    $featured_image = NULL;
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $target_dir = "../../uploads/albums/featured/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES['featured_image']['name']);
        $target_file = $target_dir . $file_name;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['featured_image']['tmp_name']);
        if($check !== false) {
            if(move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                $featured_image = $file_name;
            } else {
                $error = "Error uploading featured image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if(empty($error)) {
        $sql = "INSERT INTO albums (title, artist_id, release_year, genre_id, language_id, 
                cover_image, featured_image, description, is_featured, is_new) 
                VALUES ('$title', '$artist_id', '$release_year', 
                " . ($genre_id == "NULL" ? "NULL" : "'$genre_id'") . ", 
                " . ($language_id == "NULL" ? "NULL" : "'$language_id'") . ", 
                " . ($cover_image ? "'$cover_image'" : "NULL") . ", 
                " . ($featured_image ? "'$featured_image'" : "NULL") . ", 
                " . ($description == "NULL" ? "NULL" : "'$description'") . ", 
                '$is_featured', '$is_new')";
        
        if(mysqli_query($conn, $sql)) {
            $success = "Album added successfully.";
            header("Location: albums.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Update album
if(isset($_POST['update_album'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $artist_id = mysqli_real_escape_string($conn, $_POST['artist_id']);
    $release_year = mysqli_real_escape_string($conn, $_POST['release_year']);
    $genre_id = isset($_POST['genre_id']) ? mysqli_real_escape_string($conn, $_POST['genre_id']) : "NULL";
    $language_id = isset($_POST['language_id']) ? mysqli_real_escape_string($conn, $_POST['language_id']) : "NULL";
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : "NULL";
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    // Get current album data
    $current_album = getAlbumById($conn, $id);
    
    // Handle cover image upload
    $cover_image = $current_album['cover_image'];
    if(isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../../uploads/albums/covers/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES['cover_image']['name']);
        $target_file = $target_dir . $file_name;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['cover_image']['tmp_name']);
        if($check !== false) {
            if(move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                // Delete old image if exists
                if($current_album['cover_image'] && file_exists($target_dir . $current_album['cover_image'])) {
                    unlink($target_dir . $current_album['cover_image']);
                }
                $cover_image = $file_name;
            } else {
                $error = "Error uploading cover image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    // Handle featured image upload
    $featured_image = $current_album['featured_image'];
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $target_dir = "../../uploads/albums/featured/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES['featured_image']['name']);
        $target_file = $target_dir . $file_name;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['featured_image']['tmp_name']);
        if($check !== false) {
            if(move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                // Delete old image if exists
                if($current_album['featured_image'] && file_exists($target_dir . $current_album['featured_image'])) {
                    unlink($target_dir . $current_album['featured_image']);
                }
                $featured_image = $file_name;
            } else {
                $error = "Error uploading featured image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if(empty($error)) {
        $sql = "UPDATE albums SET 
                title = '$title', 
                artist_id = '$artist_id', 
                release_year = '$release_year', 
                genre_id = " . ($genre_id == "NULL" ? "NULL" : "'$genre_id'") . ", 
                language_id = " . ($language_id == "NULL" ? "NULL" : "'$language_id'") . ", 
                cover_image = " . ($cover_image ? "'$cover_image'" : "NULL") . ", 
                featured_image = " . ($featured_image ? "'$featured_image'" : "NULL") . ", 
                description = " . ($description == "NULL" ? "NULL" : "'$description'") . ", 
                is_featured = '$is_featured', 
                is_new = '$is_new' 
                WHERE id = '$id'";
        
        if(mysqli_query($conn, $sql)) {
            $success = "Album updated successfully.";
            header("Location: albums.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Delete album (soft delete)
if(isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $sql = "UPDATE albums SET deleted_at = CURRENT_TIMESTAMP WHERE id = '$id'";
    
    if(mysqli_query($conn, $sql)) {
        $success = "Album deleted successfully.";
        header("Location: albums.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Get all records for display
$albums = getAlbums($conn);
$artists = getArtists($conn);
$genres = getGenres($conn);
$languages = getLanguages($conn);

// Check if we're editing
$edit = false;
$album = null;
if(isset($_GET['edit'])) {
    $edit = true;
    $album = getAlbumById($conn, $_GET['edit']);
}
include './header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albums Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
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
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--admin-background);
        color: #fff;
    }

    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 30px;
    }

    .header h1 {
        color: #fff;
        font-size: 24px;
    }

    .btn {
        background-color: var(--admin-primary);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn:hover {
        opacity: 0.9;
    }

    .btn-danger {
        background-color: var(--admin-danger);
    }

    .card {
        background-color: var(--admin-card-bg);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-weight: 500;
        font-size: 14px;
        color: var(--admin-light);

    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--admin-light);
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border-radius: 5px;
        border: 1px solid var(--admin-border);
        background-color: var(--admin-dark);
        color: #fff;
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--admin-primary);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .form-check {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .form-check input {
        margin-right: 10px;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .alert-danger {
        background-color: rgba(255, 61, 113, 0.2);
        border: 1px solid var(--admin-danger);
        color: var(--admin-danger);
    }

    .alert-success {
        background-color: rgba(0, 230, 118, 0.2);
        border: 1px solid var(--admin-success);
        color: var(--admin-success);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table th,
    table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--admin-border);
    }

    table th {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--admin-light);
        font-weight: 500;
    }

    table tr:hover {
        background-color: var(--admin-hover);
    }

    .action-btn {
        padding: 6px 10px;
        font-size: 12px;
        margin-right: 5px;
    }

    .cover-preview {
        max-width: 100px;
        max-height: 100px;
        border-radius: 5px;
        display: block;
        margin-top: 10px;
    }

    .file-upload {
        position: relative;
        display: inline-block;
        cursor: pointer;
        margin-top: 10px;
    }

    .file-upload-label {
        display: inline-block;
        padding: 8px 15px;
        background-color: var(--admin-primary);
        color: white;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .file-upload-label:hover {
        opacity: 0.9;
    }

    .file-upload input[type="file"] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .file-name {
        display: inline-block;
        margin-left: 10px;
        font-size: 14px;
        color: var(--admin-light);
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination a {
        padding: 8px 12px;
        margin: 0 5px;
        background-color: var(--admin-dark);
        color: #fff;
        border-radius: 5px;
        text-decoration: none;
    }

    .pagination a.active {
        background-color: var(--admin-primary);
    }
    </style>
</head>

<body>
    <div class="page-header">
        <h1><?php echo $edit ? 'Edit Album' : 'Albums Management'; ?></h1>
    </div>
    <div class="container">
        <div class="header">
            <?php if(!$edit): ?>
            <a href="albums.php?add=true" class="btn"><i class="fas fa-plus"></i> Add Album</a>
            <?php else: ?>
            <a href="albums.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Albums</a>
            <?php endif; ?>
        </div>

        <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if($edit || isset($_GET['add'])): ?>
        <!-- Album Form (Add/Edit) -->
        <div class="card">
            <form action="albums.php" method="post" enctype="multipart/form-data">
                <?php if($edit): ?>
                <input type="hidden" name="id" value="<?php echo $album['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Album Title</label>
                    <input type="text" id="title" name="title" class="form-control" required
                        value="<?php echo $edit ? htmlspecialchars($album['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="artist_id">Artist</label>
                    <select id="artist_id" name="artist_id" class="form-control" required>
                        <option value="">Select Artist</option>
                        <?php foreach($artists as $artist): ?>
                        <option value="<?php echo $artist['id']; ?>"
                            <?php echo $edit && $album['artist_id'] == $artist['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($artist['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="release_year">Release Year</label>
                    <select id="release_year" name="release_year" class="form-control" required>
                        <?php for($year = date('Y'); $year >= 1900; $year--): ?>
                        <option value="<?php echo $year; ?>"
                            <?php echo $edit && $album['release_year'] == $year ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="genre_id">Genre</label>
                    <select id="genre_id" name="genre_id" class="form-control">
                        <option value="">Select Genre</option>
                        <?php foreach($genres as $genre): ?>
                        <option value="<?php echo $genre['id']; ?>"
                            <?php echo $edit && $album['genre_id'] == $genre['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="language_id">Language</label>
                    <select id="language_id" name="language_id" class="form-control">
                        <option value="">Select Language</option>
                        <?php foreach($languages as $language): ?>
                        <option value="<?php echo $language['id']; ?>"
                            <?php echo $edit && $album['language_id'] == $language['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <?php if($edit && $album['cover_image']): ?>
                    <img src="../../uploads/albums/covers/<?php echo $album['cover_image']; ?>" class="cover-preview"
                        alt="Cover Image">
                    <?php endif; ?>
                    <div class="file-upload">
                        <label for="cover_image" class="file-upload-label">Choose File</label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        <span class="file-name" id="cover-file-name">No file chosen</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="featured_image">Featured Image</label>
                    <?php if($edit && $album['featured_image']): ?>
                    <img src="../../uploads/albums/featured/<?php echo $album['featured_image']; ?>"
                        class="cover-preview" alt="Featured Image">
                    <?php endif; ?>
                    <div class="file-upload">
                        <label for="featured_image" class="file-upload-label">Choose File</label>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*">
                        <span class="file-name" id="featured-file-name">No file chosen</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"
                        class="form-control"><?php echo $edit ? htmlspecialchars($album['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="is_featured" name="is_featured"
                            <?php echo $edit && $album['is_featured'] ? 'checked' : ''; ?>>
                        <label for="is_featured">Featured Album</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="is_new" name="is_new"
                            <?php echo $edit ? ($album['is_new'] ? 'checked' : '') : 'checked'; ?>>
                        <label for="is_new">New Release</label>
                    </div>
                </div>

                <button type="submit" name="<?php echo $edit ? 'update_album' : 'add_album'; ?>" class="btn">
                    <?php echo $edit ? 'Update Album' : 'Add Album'; ?>
                </button>
            </form>
        </div>
        <?php else: ?>
        <!-- Albums List -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Artist</th>
                        <th>Release Year</th>
                        <th>Genre</th>
                        <th>Language</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($albums) > 0): ?>
                    <?php foreach($albums as $album): ?>
                    <tr>
                        <td><?php echo $album['id']; ?></td>
                        <td>
                            <?php if($album['cover_image']): ?>
                            <img src="../../uploads/albums/covers/<?php echo $album['cover_image']; ?>" alt="Cover"
                                width="50">
                            <?php else: ?>
                            <span>No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($album['title']); ?></td>
                        <td><?php echo htmlspecialchars($album['artist_name']); ?></td>
                        <td><?php echo $album['release_year']; ?></td>
                        <td><?php echo $album['genre_name'] ? htmlspecialchars($album['genre_name']) : '-'; ?></td>
                        <td><?php echo $album['language_name'] ? htmlspecialchars($album['language_name']) : '-'; ?>
                        </td>
                        <td>
                            <?php if($album['is_featured']): ?>
                            <span class="badge"
                                style="background-color: var(--admin-primary); padding: 3px 8px; border-radius: 3px; font-size: 12px;">Featured</span>
                            <?php endif; ?>
                            <?php if($album['is_new']): ?>
                            <span class="badge"
                                style="background-color: var(--admin-success); padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-left: 5px;">New</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="albums.php?edit=<?php echo $album['id']; ?>" class="btn action-btn">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $album['id']; ?>)"
                                class="btn btn-danger action-btn">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No albums found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <script src="../assets/js/app.js"></script>

    <script>
    // Handle file input display
    document.getElementById('cover_image').addEventListener('change', function(e) {
        document.getElementById('cover-file-name').textContent = e.target.files[0].name;
    });

    document.getElementById('featured_image').addEventListener('change', function(e) {
        document.getElementById('featured-file-name').textContent = e.target.files[0].name;
    });

    // Confirm delete
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this album?')) {
            window.location.href = 'albums.php?delete=' + id;
        }
    }
    </script>
</body>

</html>