<?php
// Database connection
include '../../includes/config_db.php';

require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];


// Handle delete video
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $videoId = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE videos SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
    header("Location: video.php");
    exit();
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_videos'])) {
    $action = $_POST['bulk_action'];
    $selectedVideos = $_POST['selected_videos'];
    
    if (!empty($selectedVideos)) {
        $idList = implode(',', array_map('intval', $selectedVideos));
        
        if ($action === 'activate') {
            $conn->query("UPDATE videos SET is_active = 1 WHERE id IN ($idList)");
        } elseif ($action === 'deactivate') {
            $conn->query("UPDATE videos SET is_active = 0 WHERE id IN ($idList)");
        } elseif ($action === 'delete') {
            $conn->query("UPDATE videos SET deleted_at = NOW(), is_active = 0 WHERE id IN ($idList)");
        }
    }
    
    header("Location: video.php");
    exit();
}

// Handle toggle status
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $videoId = $_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE videos SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $videoId);
    $stmt->execute();
    header("Location: video.php");
    exit();
}

// Handle filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$genreFilter = isset($_GET['genre']) ? $_GET['genre'] : 'all';

// Build query based on filters
$query = "SELECT v.*, g.name as genre_name FROM videos v 
          LEFT JOIN genres g ON v.genre_id = g.id 
          WHERE v.deleted_at IS NULL";

if ($statusFilter !== 'all') {
    if ($statusFilter === 'new') {
        $query .= " AND v.is_new = 1";
    } elseif ($statusFilter === 'featured') {
        $query .= " AND v.is_featured = 1";
    } elseif ($statusFilter === 'active') {
        $query .= " AND v.is_active = 1";
    } elseif ($statusFilter === 'inactive') {
        $query .= " AND v.is_active = 0";
    }
}

if ($genreFilter !== 'all') {
    $query .= " AND v.genre_id = " . $conn->real_escape_string($genreFilter);
}

$query .= " ORDER BY v.created_at DESC";

$result = $conn->query($query);
$videos = [];
while ($row = $result->fetch_assoc()) {
    $videos[] = $row;
}

// Get all genres for filter dropdown
$genresResult = $conn->query("SELECT id, name FROM genres ORDER BY name");
$genres = [];
while ($genre = $genresResult->fetch_assoc()) {
    $genres[] = $genre;
}

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
    <link rel="stylesheet" href="../assets/css/video.css">

</head>

<body>
    <div class="page-header">
        <h1>Video Management</h1>
    </div>
    <div class="container">
        <form id="videoForm" method="post">
            <div class="panel">
                <div class="panel-header">
                    <div class="filter-controls">
                        <div class="filter-item">
                            <span>Status:</span>
                            <select id="status-filter" onchange="applyFilters()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status
                                </option>
                                <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New
                                </option>
                                <option value="featured" <?php echo $statusFilter === 'featured' ? 'selected' : ''; ?>>
                                    Featured</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>
                                    Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>
                                    Inactive</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <span>Genre:</span>
                            <select id="genre-filter" onchange="applyFilters()">
                                <option value="all" <?php echo $genreFilter === 'all' ? 'selected' : ''; ?>>
                                    All Genres
                                </option>
                                <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo $genre['id']; ?>"
                                    <?php echo $genreFilter == $genre['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="search-container">
                            <span class="search-icon"><i class="fas fa-search"></i></span>
                            <input type="text" class="search-input" id="search-input" placeholder="Search videos...">
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="add_edit_video.php" class="action-icon text-primary" title="Add Track">
                            <i class="fas fa-plus fa-lg"></i>
                        </a>
                    </div>
                </div>

                <div class="bulk-action-bar" id="bulk-action-bar">
                    <div class="bulk-action-text">
                        <span id="selected-count">0</span> videos selected
                    </div>
                    <div class="bulk-action-buttons">
                        <button type="submit" name="bulk_action" value="activate" class="btn btn-success">
                            <i class="fas fa-plus fa-lg"></i> Activate
                        </button>
                        <button type="submit" name="bulk_action" value="deactivate" class="btn btn-warning">
                            <i class="fas fa-ban fa-lg"></i> Deactivate
                        </button>
                        <button type="submit" name="bulk_action" value="delete" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete the selected videos?')">
                            <i class="fas fa-trash fa-lg"></i> Delete
                        </button>
                    </div>
                </div>

                <!-- <table class="table"> -->
                <table style="background-color: #1E1E2D !important;" class="admin-table">
                    <thead>
                        <tr>
                            <th width="30">
                                <div class="checkbox-container">
                                    <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes()">
                                </div>
                            </th>
                            <th>TRACK</th>
                            <th>VIDEO</th>
                            <th>GENRE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="videos-table-body">
                        <?php foreach ($videos as $video): ?>
                        <tr>
                            <td>
                                <div class="checkbox-container">
                                    <input type="checkbox" name="selected_videos[]" value="<?php echo $video['id']; ?>"
                                        class="video-checkbox" onchange="updateSelectedCount()">
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <img src="<?php echo htmlspecialchars( $video['thumbnail'] ? $video['thumbnail'] : 'assets/default-thumbnail.jpg'); ?>"
                                        alt="Thumbnail" class="thumbnail">
                                    <div>
                                        <div class="video-title">
                                            <?php echo htmlspecialchars($video['title']); ?></div>
                                        <div class="video-artist">
                                            <?php 
                                            // Get artist name from artist_id
                                            $artistStmt = $conn->prepare("SELECT name FROM artists WHERE id = ?");
                                            $artistStmt->bind_param("i", $video['artist_id']);
                                            $artistStmt->execute();
                                            $artistResult = $artistStmt->get_result();
                                            $artist = $artistResult->fetch_assoc();
                                            echo htmlspecialchars($artist['name']);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="video-container"
                                    onclick="showVideo('<?php echo htmlspecialchars( $video['file_path']); ?>', '<?php echo htmlspecialchars($video['title']); ?>')">
                                    <div class="play-button">
                                        <i class="fas fa-play" style="font-size: 10px;"></i>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($video['genre_name'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-start">

                                    <?php if ($video['is_new']): ?>
                                    <span class="status-badge new">NEW</span>
                                    <?php endif; ?>

                                    <?php if ($video['is_featured']): ?>
                                    <!-- <span class="status-badge new">Featured</span> -->
                                    <?php endif; ?>

                                    <label class="switch mt-1">
                                        <input type="checkbox" <?php echo $video['is_active'] ? 'checked' : ''; ?>
                                            onchange="window.location.href='video.php?toggle_status=<?php echo $video['id']; ?>'">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <a href="add_edit_video.php?id=<?php echo $video['id']; ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $video['id']; ?>)"
                                        class="btn btn-sm btn-outline-danger delete-track">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (count($videos) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">No videos found. <a
                                    href="add_edit_video.php">Add a new video</a>.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
    </main>
    </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title"></h3>
                <span class="close" onclick="closeVideoModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="video-player" id="video-player">
                    <!-- Video will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
    // Delete confirmation
    function confirmDelete(videoId) {
        if (confirm('Are you sure you want to delete this video?')) {
            window.location.href = 'video.php?delete=' + videoId;
        }
    }

    // Apply filters
    function applyFilters() {
        const statusFilter = document.getElementById('status-filter').value;
        const genreFilter = document.getElementById('genre-filter').value;

        let url = 'video.php?status=' + statusFilter + '&genre=' + genreFilter;
        window.location.href = url;
    }

    // Video modal functions
    function showVideo(videoUrl, videoTitle) {
        const modal = document.getElementById('videoModal');
        const modalTitle = document.getElementById('modal-title');
        const videoPlayer = document.getElementById('video-player');

        modalTitle.textContent = videoTitle;

        // Create video element
        let videoElement;
        if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
            // Extract YouTube ID and create iframe
            let videoId = videoUrl.split('v=')[1];
            if (!videoId) {
                videoId = videoUrl.split('/').pop();
            }

            const ampersandPosition = videoId.indexOf('&');
            if (ampersandPosition !== -1) {
                videoId = videoId.substring(0, ampersandPosition);
            }

            videoElement = document.createElement('iframe');
            videoElement.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            videoElement.allow =
                "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
            videoElement.allowFullscreen = true;
        } else {
            // Regular video file
            videoElement = document.createElement('video');
            videoElement.src = videoUrl;
            videoElement.controls = true;
            videoElement.autoplay = true;
        }

        // Clear previous content and append new video
        videoPlayer.innerHTML = '';
        videoPlayer.appendChild(videoElement);

        // Show modal
        modal.style.display = 'flex';
    }

    function closeVideoModal() {
        const modal = document.getElementById('videoModal');
        const videoPlayer = document.getElementById('video-player');

        // Stop any playing videos
        videoPlayer.innerHTML = '';

        // Hide modal
        modal.style.display = 'none';
    }

    // Close modal when clicking outside content
    window.onclick = function(event) {
        const modal = document.getElementById('videoModal');
        if (event.target === modal) {
            closeVideoModal();
        }
    }

    // Multi-select functionality
    function toggleAllCheckboxes() {
        const mainCheckbox = document.getElementById('select-all');
        const checkboxes = document.getElementsByClassName('video-checkbox');

        for (let i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = mainCheckbox.checked;
        }

        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.getElementsByClassName('video-checkbox');
        const bulkActionBar = document.getElementById('bulk-action-bar');
        const selectedCountSpan = document.getElementById('selected-count');

        let selectedCount = 0;
        for (let i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                selectedCount++;
            }
        }

        selectedCountSpan.textContent = selectedCount;

        if (selectedCount > 0) {
            bulkActionBar.style.display = 'flex';
        } else {
            bulkActionBar.style.display = 'none';
        }
    }

    // Search functionality
    document.getElementById('search-input').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.getElementById('videos-table-body').getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const titleElement = rows[i].querySelector('.video-title');
            const artistElement = rows[i].querySelector('.video-artist');

            if (!titleElement || !artistElement) continue;

            const title = titleElement.textContent.toLowerCase();
            const artist = artistElement.textContent.toLowerCase();

            if (title.includes(searchValue) || artist.includes(searchValue)) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    });
    </script>
    <script src="../assets/js/tracks.js"></script>
    <script src="../assets/js/app.js"></script>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/6.6.3/wavesurfer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>


</body>

</html>