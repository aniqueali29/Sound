<?php
// Database connection
include '../../includes/config_db.php';

require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];


// Get video ID from URL if editing
$editing = false;
$video_id = null;
$video = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $video_id = (int)$_GET['id'];
    $editing = true;
    
    // Fetch the video details
    $videoQuery = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $videoQuery->bind_param("i", $video_id);
    $videoQuery->execute();
    $result = $videoQuery->get_result();
    
    if ($result->num_rows > 0) {
        $video = $result->fetch_assoc();
    } else {
        // Video not found
        header("Location: video_management.php");
        exit();
    }
}

// Get all artists for dropdown
$artistsResult = $conn->query("SELECT id, name FROM artists ORDER BY name");
$artists = [];
while ($artist = $artistsResult->fetch_assoc()) {
    $artists[] = $artist;
}

// Get all albums for dropdown
$albumsResult = $conn->query("SELECT id, title FROM albums ORDER BY title");
$albums = [];
while ($album = $albumsResult->fetch_assoc()) {
    $albums[] = $album;
}

// Get all genres for dropdown
$genresResult = $conn->query("SELECT id, name FROM genres ORDER BY name");
$genres = [];
while ($genre = $genresResult->fetch_assoc()) {
    $genres[] = $genre;
}

// Get all languages for dropdown
$languagesResult = $conn->query("SELECT id, name FROM languages ORDER BY name");
$languages = [];
while ($language = $languagesResult->fetch_assoc()) {
    $languages[] = $language;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $artist_id = $_POST['artist_id'] ?? '';
    $album_id = !empty($_POST['album_id']) ? $_POST['album_id'] : null;
    $genre_id = !empty($_POST['genre_id']) ? $_POST['genre_id'] : null;
    $language_id = !empty($_POST['language_id']) ? $_POST['language_id'] : null;
    $release_year = $_POST['release_year'] ?? date('Y');
    // $description = $_POST['description'] ?? '';
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = 1;
    
    // Initialize variables for file paths
    $thumbnail = '';
    $file_path = '';
    
    // If editing, get current values
    if ($editing && $video) {
        $thumbnail = $video['thumbnail'];
        $file_path = $video['file_path'];
        $duration = $video['duration'];
    } else {
        $duration = "00:00:00"; // Default duration for new videos
    }
    
    // Handle thumbnail upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $target_dir = "../../uploads/video/thumbnails/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_file)) {
            // If updating and there's an old thumbnail, delete it
            if ($editing && !empty($thumbnail) && file_exists($thumbnail)) {
                unlink($thumbnail);
            }
            $thumbnail = $target_file;
        }
    }
    
    // Handle video file upload
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
        $target_dir = "../../uploads/video/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_file)) {
            // If updating and there's an old video file, delete it
            if ($editing && !empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
            $file_path = $target_file;
        }
    }
    
    // If editing, update the existing video
    if ($editing) {
        $stmt = $conn->prepare("UPDATE videos SET title = ?, artist_id = ?, album_id = ?, genre_id = ?, language_id = ?, release_year = ?, thumbnail = ?, file_path = ?, is_new = ?, is_featured = ?, is_active = ? WHERE id = ?");
        // $stmt->bind_param("siiiisssiii", $title, $artist_id, $album_id, $genre_id, $language_id, $release_year, $thumbnail, $file_path, $is_new, $is_featured, $is_active, $video_id);
           $stmt->bind_param("siiiisssiiii", $title, $artist_id, $album_id, $genre_id, $language_id, $release_year, $thumbnail, $file_path, $is_new, $is_featured, $is_active, $video_id);
    } else {
        // Insert new video into database
        $stmt = $conn->prepare("INSERT INTO videos (title, artist_id, album_id, genre_id, language_id, release_year, duration, thumbnail, file_path, is_new, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiissssiii", $title, $artist_id, $album_id, $genre_id, $language_id, $release_year, $duration, $thumbnail, $file_path, $is_new, $is_featured, $is_active);
    }

    if ($stmt->execute()) {
        header("Location: video.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}
include './header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editing ? 'Edit Video' : 'Add New Video'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --bs-body-bg: #141420;
        --bs-body-color: #ffffff;
        --bs-primary: #00c2ff;
        --bs-primary-rgb: 0, 194, 255;
        --bs-secondary: #33334d;
        --bs-secondary-rgb: 51, 51, 77;
        --bs-danger: #ff4d4d;
        --bs-success: #00cc88;
        --panel-bg: #1c1c2b;
    }

    body {
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    h1 {
        color: var(--bs-primary);
    }

    .card {
        background-color: var(--panel-bg);
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .form-control,
    .form-select {
        background-color: var(--bs-secondary);
        border: none;
        color: var(--bs-body-color);
    }

    .form-control:focus,
    .form-select:focus {
        background-color: var(--bs-secondary);
        color: var(--bs-body-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }

    .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .btn-primary {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .btn-secondary {
        background-color: var(--bs-secondary);
        border-color: var(--bs-secondary);
    }

    .file-input-label {
        display: block;
        padding: 0.375rem 0.75rem;
        background-color: var(--bs-secondary);
        border-radius: 0.25rem;
        cursor: pointer;
        text-align: center;
    }

    .file-name {
        margin-top: 0.5rem;
        font-size: 0.875em;
        color: rgba(255, 255, 255, 0.7);
    }

    .current-files {
        margin-top: 0.5rem;
        font-size: 0.875em;
        color: rgba(255, 255, 255, 0.7);
    }

    .current-files a {
        color: var(--bs-primary);
        text-decoration: none;
    }

    .current-files a:hover {
        text-decoration: underline;
    }

    .error {
        color: var(--bs-danger);
    }

    /* Custom styling for file input button */
    .custom-file-input::file-selector-button {
        background-color: #00c2ff;
        /* Your preferred button color */
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        margin-right: 1rem;
        border-radius: 0.25rem;
        cursor: pointer;
    }

    .custom-file-input::file-selector-button:hover {
        background-color: #00a9df;
        /* Slightly darker on hover */
    }

    /* Additional styling for the input itself */
    .custom-file-input {
        color: white;
    }
    </style>
</head>

<body>
    <div class="container py-4">
        <h1 class="mb-4"><?php echo $editing ? 'Edit Video Track' : 'Add New Video Track'; ?></h1>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="add_edit_video.php<?php echo $editing ? '?id=' . $video_id : ''; ?>" method="post"
            enctype="multipart/form-data">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?php echo $editing ? htmlspecialchars($video['title']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="language_id" class="form-label">Language</label>
                                <select class="form-select" id="language_id" name="language_id">
                                    <option value="">Select Language</option>
                                    <?php foreach ($languages as $language): ?>
                                    <option value="<?php echo $language['id']; ?>"
                                        <?php echo $editing && $video['language_id'] == $language['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($language['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="artist_id" class="form-label">Artist</label>
                                <select class="form-select" id="artist_id" name="artist_id" required>
                                    <option value="">Select Artist</option>
                                    <?php foreach ($artists as $artist): ?>
                                    <option value="<?php echo $artist['id']; ?>"
                                        <?php echo $editing && $video['artist_id'] == $artist['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($artist['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="release_year" class="form-label">Release Year</label>
                                <input type="number" class="form-control" id="release_year" name="release_year"
                                    min="1900" max="<?php echo date('Y'); ?>"
                                    value="<?php echo $editing ? $video['release_year'] : date('Y'); ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="album_id" class="form-label">Album (Optional)</label>
                                <select class="form-select" id="album_id" name="album_id">
                                    <option value="">Select Album</option>
                                    <?php foreach ($albums as $album): ?>
                                    <option value="<?php echo $album['id']; ?>"
                                        <?php echo $editing && $video['album_id'] == $album['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($album['title']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="genre_id" class="form-label">Genre</label>
                                <select class="form-select" id="genre_id" name="genre_id">
                                    <option value="">Select Genre</option>
                                    <?php foreach ($genres as $genre): ?>
                                    <option value="<?php echo $genre['id']; ?>"
                                        <?php echo $editing && $video['genre_id'] == $genre['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($genre['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="thumbnail" class="form-label">Thumbnail Image</label>
                                <div class="input-group">
                                    <input type="file" class="form-control custom-file-input" id="thumbnail" name="thumbnail"
                                        accept="image/*">
                                </div>
                                <div class="file-name" id="thumbnail-file-name">
                                    <?php echo ($editing && !empty($video['thumbnail'])) ? 'Current: ' . basename($video['thumbnail']) : 'No file chosen'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="video_file" class="form-label">Video File</label>
                                <div class="input-group">
                                    <input type="file" class="form-control custom-file-input" id="video_file" name="video_file"
                                        accept="video/*" <?php echo $editing ? '' : 'required'; ?>>
                                </div>
                                <div class="file-name" id="video-file-name">
                                    <?php echo ($editing && !empty($video['file_path'])) ? 'Current: ' . basename($video['file_path']) : 'No file chosen'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_new" name="is_new"
                                    <?php echo (!$editing || ($editing && $video['is_new'] == 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_new">Mark as New</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured"
                                    <?php echo ($editing && $video['is_featured'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">Mark as Featured</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary"
                            onclick="window.location.href='video.php'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editing ? 'Update Video Track' : 'Add Video Track'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Update file input display
    document.getElementById('thumbnail').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        document.getElementById('thumbnail-file-name').textContent = fileName;
    });

    document.getElementById('video_file').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        document.getElementById('video-file-name').textContent = fileName;
    });
    </script>
</body>

</html>