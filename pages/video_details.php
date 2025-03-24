<?php
ob_start();
include '../includes/config_db.php';
require_once '../layout/header.php';


// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Fetch video details from the database
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$sql = "SELECT v.*, a.name as artist_name 
        FROM videos v 
        LEFT JOIN artists a ON v.artist_id = a.id 
        WHERE v.id = $video_id";
$result = $conn->query($sql);
$video = $result->fetch_assoc();

// Update view count
if ($video) {
    $update_views = "UPDATE videos SET views = views + 1 WHERE id = $video_id";
    $conn->query($update_views);
    $video['views']++; // Update the view count in our current data
}

// Fetch recent videos for the "Recent Videos" sidebar
$recent_videos_sql = "SELECT v.*, a.name as artist_name 
                      FROM videos v 
                      LEFT JOIN artists a ON v.artist_id = a.id 
                      WHERE v.id != $video_id AND v.is_active = 1 
                      ORDER BY v.created_at DESC LIMIT 8";
$recent_videos_result = $conn->query($recent_videos_sql);
$recent_videos = [];
while ($row = $recent_videos_result->fetch_assoc()) {
    $recent_videos[] = $row;
}

// Fetch comments with user information including profile pictures
$comments_sql = "SELECT c.*, u.username, u.profile_picture 
                 FROM comments c 
                 LEFT JOIN users u ON c.user_id = u.id 
                 WHERE c.video_id = $video_id 
                 ORDER BY c.created_at DESC";
$comments_result = $conn->query($comments_sql);
$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row;
}

// Check if the current user has liked or disliked this video
$user_rating = null;
if ($is_logged_in) {
    $rating_check_sql = "SELECT * FROM ratings WHERE user_id = $current_user_id AND item_id = $video_id AND item_type = 'video'";
    $rating_result = $conn->query($rating_check_sql);
    if ($rating_result && $rating_result->num_rows > 0) {
        $user_rating = $rating_result->fetch_assoc();
    }
}


// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment']) && $is_logged_in) {
    $comment = $conn->real_escape_string($_POST['comment']);

    // Ensure $current_user_id is valid
    if (isset($current_user_id) && is_numeric($current_user_id)) {
        // Check if user exists
        $check_user_sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $conn->prepare($check_user_sql);
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists, proceed with comment insertion
            $insert_comment_sql = "INSERT INTO comments (video_id, user_id, comment) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_comment_sql);
            $stmt->bind_param("iis", $video_id, $current_user_id, $comment);

            if ($stmt->execute()) {
                // Redirect to avoid form resubmission
                header("Location: video_details.php?id=$video_id");
                exit();
            } else {
                echo "Error inserting comment: " . $conn->error;
            }
        } else {
            // echo "Error: User does not exist.";
        }
        $stmt->close();
    } else {
        echo "Error: Invalid user ID.";
    }
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment']) && $is_logged_in) {
    $comment_id = intval($_POST['delete_comment']);
    $check_comment_sql = "SELECT * FROM comments WHERE id = $comment_id AND user_id = $current_user_id";
    $check_result = $conn->query($check_comment_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        // Delete the comment
        $delete_sql = "DELETE FROM comments WHERE id = $comment_id";
        if ($conn->query($delete_sql)) {
            // Redirect to avoid form resubmission
            header("Location: video_details.php?id=$video_id");
            exit();
        }
    }
}

// Add the timeAgo function to PHP environment
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'Just now';
}

// Handle like/dislike action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $is_logged_in) {
    $action = $_POST['action'];
    
    // Check if user has already rated this video
    $existing_rating_sql = "SELECT id, rating FROM ratings WHERE user_id = $current_user_id AND item_id = $video_id AND item_type = 'video'";
    $existing_rating_result = $conn->query($existing_rating_sql);
    
    if ($existing_rating_result->num_rows > 0) {
        // User has already rated, update their rating
        $existing_rating = $existing_rating_result->fetch_assoc();
        $rating_id = $existing_rating['id'];
        $previous_rating = $existing_rating['rating'];
        
        if (($action == 'like' && $previous_rating == 5) || ($action == 'dislike' && $previous_rating == 1)) {
            // User clicked the same button again, remove their rating
            $delete_rating_sql = "DELETE FROM ratings WHERE id = $rating_id";
            $conn->query($delete_rating_sql);
            
            // Update video stats
            if ($action == 'like') {
                $update_video_sql = "UPDATE videos SET likes = likes - 1 WHERE id = $video_id";
            } else {
                $update_video_sql = "UPDATE videos SET dislikes = dislikes - 1 WHERE id = $video_id";
            }
            $conn->query($update_video_sql);
        } else {
            // User changed their rating
            $new_rating = ($action == 'like') ? 5 : 1;
            $update_rating_sql = "UPDATE ratings SET rating = $new_rating WHERE id = $rating_id";
            $conn->query($update_rating_sql);
            
            // Update video stats - remove previous rating and add new one
            if ($previous_rating == 5) {
                $update_video_sql = "UPDATE videos SET likes = likes - 1, dislikes = dislikes + 1 WHERE id = $video_id";
            } else {
                $update_video_sql = "UPDATE videos SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = $video_id";
            }
            $conn->query($update_video_sql);
        }
    } else {
        // User hasn't rated this video before
        $rating_value = ($action == 'like') ? 5 : 1;
        $insert_rating_sql = "INSERT INTO ratings (user_id, item_id, item_type, rating) VALUES ($current_user_id, $video_id, 'video', $rating_value)";
        $conn->query($insert_rating_sql);
        
        // Update video stats
        if ($action == 'like') {
            $update_video_sql = "UPDATE videos SET likes = likes + 1 WHERE id = $video_id";
        } else {
            $update_video_sql = "UPDATE videos SET dislikes = dislikes + 1 WHERE id = $video_id";
        }
        $conn->query($update_video_sql);
    }
    
    // Refresh the page to show updated counts
    header("Location: video_details.php?id=$video_id");
    exit();

}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - Video Player</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/video_details.css">
    <style>
    .dropdown-toggle::after {
        display: none;
        /* Hide default caret */
    }

    .btn-link {
        color: #6c757d;
        font-size: 1.2rem;
    }

    .btn-link:hover {
        color: #495057;
        text-decoration: none;
    }

    .dropdown-menu {
        min-width: 100px;
    }
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
                <li class="nav-item  active">
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

    <div class="particles"></div>
    <div class="container" style="margin-top:130px;">
        <div class="row">
            <!-- Main Video Column -->
            <div class="col-lg-8">
                <div class="main-video-container">
                    <video id="main-video" controls autoplay class="w-100" style="border-radius: 15px;">
                        <source id="video-source"
                            src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $video['file_path'])); ?>"
                            type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-details mt-3">
                        <h1 id="video-title" class="text-light"><?php echo htmlspecialchars($video['title']); ?></h1>
                        <div class="meta-info d-flex align-items-center gap-3">
                            <span id="video-views" class="text-light"><?php echo number_format($video['views']); ?>
                                views</span>
                            •
                            <span id="video-artist"
                                class="text-light"><?php echo htmlspecialchars($video['artist_name'] ?? 'Unknown Artist'); ?></span>
                            •
                            <span id="video-release"
                                class="text-light"><?php echo htmlspecialchars($video['release_year']); ?></span>
                        </div>
                    </div>
                    <!-- Combined Rating and Comment Section -->
                    <div class="interaction-controls mt-3">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <!-- Like/Dislike Section -->
                            <div class="like-dislike-container d-flex align-items-center me-3">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="like">
                                    <button type="submit" id="like-button"
                                        class="interaction-button <?php echo ($is_logged_in && $user_rating && $user_rating['rating'] == 5) ? 'active' : ''; ?>"
                                        <?php echo (!$is_logged_in) ? 'disabled' : ''; ?>>
                                        <svg viewBox="0 0 24 24" class="like-icon me-1" width="24" height="24">
                                            <path
                                                d="M18.77,11h-4.23l1.52-4.94C16.38,5.03,15.54,4,14.38,4c-0.58,0-1.14,0.24-1.52,0.65L7,11v10h10.43 c1.06,0,1.98-0.67,2.19-1.61l1.34-6C21.23,12.15,20.18,11,18.77,11z"
                                                fill="currentColor"></path>
                                            <path d="M1,21h4V11H1V21z" fill="currentColor"></path>
                                        </svg>
                                        <span id="like-count"><?php echo number_format($video['likes']); ?></span>
                                    </button>
                                </form>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="dislike">
                                    <button type="submit" id="dislike-button"
                                        class="interaction-button ms-2 <?php echo ($is_logged_in && $user_rating && $user_rating['rating'] == 1) ? 'dislike-active' : ''; ?>"
                                        <?php echo (!$is_logged_in) ? 'disabled' : ''; ?>>
                                        <svg viewBox="0 0 24 24" class="dislike-icon me-1" width="24" height="24">
                                            <path
                                                d="M18.77,11h-4.23l1.52-4.94C16.38,5.03,15.54,4,14.38,4c-0.58,0-1.14,0.24-1.52,0.65L7,11v10h10.43 c1.06,0,1.98-0.67,2.19-1.61l1.34-6C21.23,12.15,20.18,11,18.77,11z"
                                                fill="currentColor" transform="rotate(180, 12, 12)"></path>
                                            <path d="M1,21h4V11H1V21z" fill="currentColor"
                                                transform="rotate(180, 12, 12)"></path>
                                        </svg>
                                        <span id="dislike-count"><?php echo number_format($video['dislikes']); ?></span>
                                    </button>
                                </form>
                            </div>
                            <!-- Share Button -->
                            <button id="share-button" class="interaction-button me-3">
                                <svg viewBox="0 0 24 24" class="share-icon me-1" width="24" height="24">
                                    <path
                                        d="M15,5.63L20.66,12L15,18.37V15v-1h-1c-3.96,0-7.14,1-9.33,3.02L4,18.5l0.23-0.67 C5.76,13,8.39,10,13,10h1V9V5.63 M14,3v6c-4.97,0-9,2.69-10,8c2-3.08,5.06-5,10-5v6l8-7.5L14,3L14,3z"
                                        fill="currentColor"></path>
                                </svg>
                                <span>Share</span>
                            </button>
                            <!-- Download Button (Optional) -->
                            <button id="download-button" class="interaction-button">
                                <svg viewBox="0 0 24 24" class="download-icon me-1" width="24" height="24">
                                    <path
                                        d="M17,18v1H6v-1H17z M16.5,11.4l-3.9,3.9c-0.3,0.3-0.7,0.3-1,0l-3.9-3.9c-0.3-0.3-0.3-0.7,0-1l0.9-0.9 c0.3-0.3,0.7-0.3,1,0l1.6,1.6V7.5c0-0.4,0.3-0.7,0.7-0.7h1.3c0.4,0,0.7,0.3,0.7,0.7v3.5l1.6-1.6c0.3-0.3,0.7-0.3,1,0l0.9,0.9 C16.8,10.7,16.8,11.1,16.5,11.4z"
                                        fill="currentColor"></path>
                                </svg>
                                <span>Download</span>
                            </button>
                        </div>
                    </div>

                    <!-- Video Description (Optional) -->
                    <?php if (!empty($video['description'])): ?>
                    <div class="video-description mt-3">
                        <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Comments Section -->
                    <div class="comments-section mt-4 mb-4">
                        <?php if ($is_logged_in): ?>
                        <?php
                        // Get user profile picture and username
                        if (isset($current_user_id) && is_numeric($current_user_id)) {
                            $user_query = "SELECT profile_picture, username FROM users WHERE id = ?";
                            $stmt = $conn->prepare($user_query);
                            $stmt->bind_param("i", $current_user_id);
                            $stmt->execute();
                            $user_result = $stmt->get_result();
                            
                            if ($user_result->num_rows > 0) {
                                $user_data = $user_result->fetch_assoc();
                                $username = $user_data['username'] ?? 'User';
                                $first_letter = strtoupper(substr($username, 0, 1));
                                
                                if (!empty($user_data['profile_picture'])) {
                                    // Use actual profile picture
                                    $profile_pic = $user_data['profile_picture'];
                                    $use_letter_avatar = false;
                                } else {
                                    // Use letter avatar
                                    $profile_pic = '';
                                    $use_letter_avatar = true;
                                }
                            } else {
                                $profile_pic = '';
                                $first_letter = 'U';
                                $use_letter_avatar = true;
                            }

                            $stmt->close();
                        } else {
                            $profile_pic = '';
                            $first_letter = 'U';
                            $use_letter_avatar = true;
                        }
                        ?>
                        <form class="comment-form mb-4" id="comment-form" method="POST" action="">
                            <div class="d-flex gap-3">
                                <?php if ($use_letter_avatar): ?>
                                <div class="rounded-circle d-flex justify-content-center align-items-center bg-primary text-white"
                                    style="width: 40px; height: 40px; font-weight: bold;">
                                    <?php echo $first_letter; ?>
                                </div>
                                <?php else: ?>
                                <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="rounded-circle" width="40" height="40" alt="User avatar">
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <input type="text" class="form-control comment-input" name="comment"
                                        placeholder="Add a comment..." required>
                                    <div class="comment-actions-container mt-2" id="comment-actions">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn cancel-btn" id="cancel-btn">CANCEL</button>
                                            <button type="submit" class="btn btn-primary comment-btn">COMMENT</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="comments-container mb-4">
                            Please <a href="./login.php">login</a> to comment on this video.
                        </div>
                        <?php endif; ?>

                        <!-- Comment List Container -->
                        <div class="comments-container">
                            <ul class="comment-list list-unstyled" id="comment-list">
                                <?php if (empty($comments)): ?>
                                <li class="no-comments text-center py-3">
                                    <p>No comments yet. Be the first to comment!</p>
                                </li>
                                <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                <?php
                                // Get comment author's profile picture and username
                                $comment_user_id = $comment['user_id'];
                                $profile_pic_query = "SELECT profile_picture, username FROM users WHERE id = ?";
                                $stmt = $conn->prepare($profile_pic_query);
                                $stmt->bind_param("i", $comment_user_id);
                                $stmt->execute();
                                $pic_result = $stmt->get_result();
                                
                                if ($pic_result->num_rows > 0) {
                                    $pic_data = $pic_result->fetch_assoc();
                                    $comment_username = $pic_data['username'] ?? 'User '.$comment_user_id;
                                    $comment_first_letter = strtoupper(substr($comment_username, 0, 1));
                                    
                                    if (!empty($pic_data['profile_picture'])) {
                                        // Use actual profile picture
                                        $comment_profile_pic = $pic_data['profile_picture'];
                                        $comment_use_letter_avatar = false;
                                    } else {
                                        // Use letter avatar
                                        $comment_profile_pic = '';
                                        $comment_use_letter_avatar = true;
                                    }
                                } else {
                                    $comment_profile_pic = '';
                                    $comment_first_letter = 'U';
                                    $comment_use_letter_avatar = true;
                                    $comment_username = 'User '.$comment_user_id;
                                }
                                $stmt->close();
                                ?>
                                <li class="comment-item mb-4">
                                    <div class="d-flex">
                                        <?php if ($comment_use_letter_avatar): ?>
                                        <div class="rounded-circle d-flex justify-content-center align-items-center bg-primary text-white me-3"
                                            style="width: 40px; height: 40px; font-weight: bold;">
                                            <?php echo $comment_first_letter; ?>
                                        </div>
                                        <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($comment_profile_pic, ENT_QUOTES, 'UTF-8'); ?>"
                                            class="rounded-circle me-3" width="40" height="40" alt="User avatar">
                                        <?php endif; ?>
                                        <div class="comment-content flex-grow-1">
                                            <div
                                                class="comment-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="comment-author">
                                                        <?php echo htmlspecialchars($comment['username'] ?? $comment_username); ?>
                                                    </span>
                                                    <span class="comment-time">
                                                        <?php echo date('F j, Y', strtotime($comment['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <?php if ($is_logged_in && $comment['user_id'] == $current_user_id): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-link dropdown-toggle" type="button"
                                                        id="dropdownMenuButton<?php echo $comment['id']; ?>"
                                                        data-bs-toggle="dropdown" aria-expanded="false"
                                                        style="border: none; background: none;">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end"
                                                        aria-labelledby="dropdownMenuButton<?php echo $comment['id']; ?>">
                                                        <li>
                                                            <form method="POST" action="" class="d-inline">
                                                                <input type="hidden" name="delete_comment"
                                                                    value="<?php echo $comment['id']; ?>">
                                                                <button type="submit"
                                                                    class="dropdown-item text-danger">Delete</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="comment-text">
                                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <?php if (count($comments) > 10): ?>
                        <!-- Show More Comments Button -->
                        <div class="text-center mt-4 mb-5">
                            <button class="btn show-more-btn" id="load-more-btn">SHOW MORE</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Recent Videos Sidebar -->
            <div class="col-lg-4 rel-vid">
                <h2 class="text-light mb-4 related-heading">Recent Videos</h2>
                <div class="related-videos" id="related-videos">
                    <?php foreach ($recent_videos as $recent): ?>
                    <div class="video-card"
                        onclick="window.location.href='video_details.php?id=<?php echo $recent['id']; ?>'">
                        <div class="thumbnail-container">
                            <img class="thumbnail"
                                src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $recent['thumbnail'])); ?>"
                                alt="Video thumbnail">
                            <div class="duration"><?php echo htmlspecialchars($recent['duration']); ?></div>
                        </div>
                        <div class="video-info">
                            <h3 class="video-title"><?php echo htmlspecialchars($recent['title']); ?></h3>
                            <div class="channel-name">
                                <?php echo htmlspecialchars($recent['artist_name'] ?? 'Unknown Artist'); ?>
                                <span class="verified-icon">✓</span>
                            </div>
                            <div class="video-metadata">
                                <?php echo number_format($recent['views']); ?> views
                                <span class="metadata-dot">•</span>
                                <?php echo timeAgo($recent['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
require_once '../layout/footer.php';

?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // ====================
        // Dynamic Particle Effect
        // ====================
        function createParticles() {
            const container = document.querySelector('.particles');
            for (let i = 0; i < 100; i++) {
                const particle = document.createElement('div');
                particle.style.cssText = `
                    position: absolute;
                    width: 2px;
                    height: 2px;
                    background: rgba(62, 166, 255, 0.5);
                    border-radius: 50%;
                    top: ${Math.random() * 100}vh;
                    left: ${Math.random() * 100}vw;
                    animation: particle-float ${5 + Math.random() * 10}s infinite linear;
                    opacity: ${Math.random() * 0.5 + 0.2};
                `;
                container.appendChild(particle);
            }
        }
        createParticles();

        // Add CSS keyframes for particle animation
        const styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.innerText = `
            @keyframes particle-float {
                0% { transform: translateY(0); }
                50% { transform: translateY(-${Math.random() * 100 + 50}px); opacity: 0.2; }
                100% { transform: translateY(0); opacity: 0.7; }
            }
        `;
        document.head.appendChild(styleSheet);

        // ====================
        // Comment System
        // ====================
        const commentForm = document.getElementById('comment-form');
        const commentInput = commentForm?.querySelector('.comment-input');
        const commentActions = document.getElementById('comment-actions');
        const cancelBtn = document.getElementById('cancel-btn');

        // Show/hide comment actions when input is focused
        if (commentInput) {
            commentInput.addEventListener('focus', function() {
                commentActions.style.display = 'block';
            });

            // Hide comment actions when cancel button is clicked
            cancelBtn.addEventListener('click', function() {
                commentActions.style.display = 'none';
                commentInput.value = '';
                commentInput.blur();
            });
        }

        // Load more comments functionality
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            let commentsShown = 10;
            const commentsPerLoad = 10;
            const commentItems = document.querySelectorAll('.comment-item');

            // Initially hide comments beyond the first 10
            for (let i = commentsShown; i < commentItems.length; i++) {
                commentItems[i].style.display = 'none';
            }

            loadMoreBtn.addEventListener('click', function() {
                // Show the next batch of comments
                for (let i = commentsShown; i < commentsShown + commentsPerLoad && i < commentItems
                    .length; i++) {
                    commentItems[i].style.display = 'block';
                }

                commentsShown += commentsPerLoad;

                // Hide load more button if all comments are shown
                if (commentsShown >= commentItems.length) {
                    loadMoreBtn.style.display = 'none';
                }
            });
        }

        // ====================
        // Notification System
        // ====================
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'yt-notification';
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 2000);
            }, 10);
        }

        // Add notification styles
        const notificationStyle = document.createElement('style');
        notificationStyle.textContent = `
            .yt-notification {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background-color: rgba(33, 33, 33, 0.9);
                color: white;
                padding: 10px 16px;
                border-radius: 4px;
                font-size: 14px;
                z-index: 1000;
                opacity: 0;
                transition: transform 0.3s ease, opacity 0.3s ease;
            }
            .yt-notification.show {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        `;
        document.head.appendChild(notificationStyle);

        // ====================
        // Authentication check for interactions
        // ====================
        const authCheckButtons = document.querySelectorAll('.interaction-button');
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        authCheckButtons.forEach(button => {
            if (!button.hasAttribute('disabled')) {
                button.addEventListener('click', function(e) {
                    if (!isLoggedIn && (this.id === 'like-button' || this.id ===
                            'dislike-button')) {
                        e.preventDefault();
                        showNotification('Please log in to rate this video');
                    }
                });
            }
        });

        // ====================
        // Recent Videos Functionality
        // ====================
        const recentVideos = document.querySelectorAll('.video-card');

        recentVideos.forEach(videoCard => {
            videoCard.addEventListener('click', function(e) {
                e.preventDefault();
                const videoUrl = this.getAttribute('onclick').match(
                    /video_details\.php\?id=(\d+)/)[0];
                window.location.href = videoUrl;
            });
        });

        // Format time function for video duration display
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            seconds = Math.floor(seconds % 60);
            return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        }

        // Time ago function for comment timestamps
        function timeAgo(timestamp) {
            const now = new Date();
            const past = new Date(timestamp);
            const diff = Math.floor((now - past) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
            if (diff < 2592000) return `${Math.floor(diff / 86400)} days ago`;
            if (diff < 31536000) return `${Math.floor(diff / 2592000)} months ago`;
            return `${Math.floor(diff / 31536000)} years ago`;
        }
        // ====================
        // Copy Video URL to Clipboard
        // ====================
        const shareButton = document.getElementById('share-button');
        if (shareButton) {
            shareButton.addEventListener('click', function() {
                // Get current video URL
                const videoUrl = window.location.href;

                // Copy the URL to the clipboard
                copyToClipboard(videoUrl);

                // Show a notification
                showNotification('Video URL copied to clipboard');
            });
        }

        function copyToClipboard(url) {
            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);

            // Select and copy the URL
            tempInput.select();
            document.execCommand('copy');

            // Remove the temporary input
            document.body.removeChild(tempInput);
        }

        // ====================
        // Download Video Functionality
        // ====================
        const downloadButton = document.getElementById('download-button');
        if (downloadButton) {
            downloadButton.addEventListener('click', function() {
                if (!isLoggedIn) {
                    showNotification('Please log in to download this video');
                    return;
                }

                // Get video source URL
                const videoSource = document.getElementById('video-source').getAttribute('src');
                const videoTitle = document.getElementById('video-title').textContent;

                // Create a temporary anchor element
                const tempAnchor = document.createElement('a');
                tempAnchor.href = videoSource;
                tempAnchor.download = `${videoTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.mp4`;
                document.body.appendChild(tempAnchor);

                // Trigger the download
                tempAnchor.click();

                // Remove the temporary anchor
                document.body.removeChild(tempAnchor);

                // Show a notification
                showNotification('Download started');
            });
        }

        // ====================
        // Comments Sorting Functionality
        // ====================
        function sortComments(sortType) {
            const commentList = document.getElementById('comment-list');
            const comments = Array.from(commentList.getElementsByTagName('li'));

            // Skip if there are no comments or only the "no comments" message
            if (comments.length <= 1 && comments[0].classList.contains('no-comments')) {
                return;
            }

            // Sort comments based on the selected criteria
            comments.sort(function(a, b) {
                if (sortType === 'newest') {
                    // Sort by date (newest first)
                    const dateA = new Date(a.querySelector('.comment-time').textContent);
                    const dateB = new Date(b.querySelector('.comment-time').textContent);
                    return dateB - dateA;
                } else {
                    // Sort by default (Top comments)
                    // This is a placeholder - in a real implementation, you'd sort by likes/engagement
                    return 0;
                }
            });

            // Clear the comment list
            while (commentList.firstChild) {
                commentList.removeChild(commentList.firstChild);
            }

            // Append sorted comments
            comments.forEach(comment => {
                commentList.appendChild(comment);
            });
        }

        // ====================
        // Video Player Enhancements
        // ====================
        const videoPlayer = document.getElementById('main-video');

        // Save video progress in localStorage
        if (videoPlayer) {
            const videoId = <?php echo $video_id; ?>;
            const storageKey = `video_progress_${videoId}`;

            // Load saved progress if exists
            const savedProgress = localStorage.getItem(storageKey);
            if (savedProgress) {
                videoPlayer.currentTime = parseFloat(savedProgress);
            }

            // Save progress periodically
            videoPlayer.addEventListener('timeupdate', function() {
                localStorage.setItem(storageKey, videoPlayer.currentTime);
            });

            // Clear saved progress when video ends
            videoPlayer.addEventListener('ended', function() {
                localStorage.removeItem(storageKey);

                // Auto-play next video functionality
                const nextVideo = document.querySelector('.video-card');
                if (nextVideo) {
                    setTimeout(() => {
                        nextVideo.click();
                    }, 3000);
                }
            });

            // Add keyboard shortcuts for player
            document.addEventListener('keydown', function(e) {
                // Only handle shortcuts if not typing in an input
                if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName ===
                    'TEXTAREA') {
                    return;
                }

                switch (e.key) {
                    case ' ':
                    case 'k':
                        // Play/pause
                        e.preventDefault();
                        if (videoPlayer.paused) {
                            videoPlayer.play();
                        } else {
                            videoPlayer.pause();
                        }
                        break;
                    case 'ArrowRight':
                        // Forward 10 seconds
                        videoPlayer.currentTime += 10;
                        break;
                    case 'ArrowLeft':
                        // Rewind 10 seconds
                        videoPlayer.currentTime -= 10;
                        break;
                    case 'f':
                        // Toggle fullscreen
                        e.preventDefault();
                        if (document.fullscreenElement) {
                            document.exitFullscreen();
                        } else {
                            videoPlayer.requestFullscreen();
                        }
                        break;
                    case 'm':
                        // Toggle mute
                        videoPlayer.muted = !videoPlayer.muted;
                        break;
                }
            });
        }

        // ====================
        // Comment Form Validation
        // ====================
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                const commentText = commentInput.value.trim();

                if (commentText === '') {
                    e.preventDefault();
                    showNotification('Please enter a comment');
                }
            });

            // Enable/disable comment button based on input
            commentInput.addEventListener('input', function() {
                const submitButton = commentForm.querySelector('.comment-btn');
                submitButton.disabled = this.value.trim() === '';
            });
        }

        // ====================
        // Video Rating System
        // ====================
        // Get elements with null checks
        const likeButton = document.getElementById('like-button');
        const dislikeButton = document.getElementById('dislike-button');
        const likeCount = document.getElementById('like-count');
        const dislikeCount = document.getElementById('dislike-count');

        // Get video ID from a data attribute to avoid embedding PHP directly
        const videoId = likeButton?.dataset.videoId;
        const isLoggedIn = likeButton?.dataset.loggedIn === 'true';

        if (likeButton && dislikeButton && videoId) {
            // Like button event handler
            likeButton.addEventListener('click', function(e) {
                e.preventDefault();
                handleRating('like', videoId, isLoggedIn);
            });

            // Dislike button event handler
            dislikeButton.addEventListener('click', function(e) {
                e.preventDefault();
                handleRating('dislike', videoId, isLoggedIn);
            });
        }

        function handleRating(action, videoId, isLoggedIn) {
            // Check login status
            if (!isLoggedIn) {
                showNotification(`Please log in to ${action} this video`);
                return;
            }

            // Send AJAX request to update rating
            const formData = new FormData();
            formData.append('action', action);
            formData.append('video_id', videoId);

            fetch('./update_rating.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update UI with the new counts
                        if (likeCount) likeCount.textContent = data.likes;
                        if (dislikeCount) dislikeCount.textContent = data.dislikes;

                        // Toggle active classes based on the user's current rating
                        if (action === 'like' && data.user_rating > 0) {
                            likeButton.classList.add('active');
                            dislikeButton.classList.remove('dislike-active');
                        } else if (action === 'dislike' && data.user_rating < 0) {
                            dislikeButton.classList.add('dislike-active');
                            likeButton.classList.remove('active');
                        } else {
                            // Neutral state
                            likeButton.classList.remove('active');
                            dislikeButton.classList.remove('dislike-active');
                        }

                        showNotification(data.message);
                    } else {
                        showNotification('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred');
                });
        }

        // Helper function to show notifications with proper cleanup
        function showNotification(message) {
            // Remove any existing notification first
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                document.body.removeChild(existingNotification);
            }

            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            document.body.appendChild(notification);

            // Remove after 3 seconds
            const timeout = setTimeout(() => {
                notification.classList.add('fade-out');
                const removeTimeout = setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                    clearTimeout(removeTimeout);
                }, 300);
            }, 3000);
        }
    });
    <?php 
    if (!function_exists('timeAgo')) {
        echo "
        function timeAgo(\$datetime) {
            \$now = new DateTime();
            \$ago = new DateTime(\$datetime);
            \$diff = \$now->diff(\$ago);
            
            if (\$diff->y > 0) {
                return \$diff->y . ' year' . (\$diff->y > 1 ? 's' : '') . ' ago';
            }
            if (\$diff->m > 0) {
                return \$diff->m . ' month' . (\$diff->m > 1 ? 's' : '') . ' ago';
            }
            if (\$diff->d > 0) {
                return \$diff->d . ' day' . (\$diff->d > 1 ? 's' : '') . ' ago';
            }
            if (\$diff->h > 0) {
                return \$diff->h . ' hour' . (\$diff->h > 1 ? 's' : '') . ' ago';
            }
            if (\$diff->i > 0) {
                return \$diff->i . ' minute' . (\$diff->i > 1 ? 's' : '') . ' ago';
            }
            return 'Just now';
        }
        ";
    }
    ?>
    </script>
</body>

</html>