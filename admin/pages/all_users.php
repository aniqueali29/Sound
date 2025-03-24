<?php

include '../../includes/config_db.php';

require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];


// Fetch all users
$users_query = "SELECT id, username, name, email, profile_picture, created_at 
                FROM users 
                WHERE deleted_at IS NULL 
                ORDER BY created_at DESC";
$users_result = $conn->query($users_query);

// Function to fetch user's recent activity
function getUserRecentActivity($conn, $user_id) {
    // Get content interactions (music plays/ratings/favorites/comments)
    $content_activity = "SELECT 'music_rating' as type, m.title, r.rating, r.created_at
                       FROM ratings r
                       JOIN music m ON r.item_id = m.id
                       WHERE r.user_id = ? AND r.item_type = 'music'
                       
                       UNION
                       
                       SELECT 'favorite_music' as type, m.title, NULL as rating, f.created_at
                       FROM favorites f
                       JOIN music m ON f.music_id = m.id
                       WHERE f.user_id = ? AND f.music_id IS NOT NULL
                       
                       UNION
                       
                       SELECT 'favorite_video' as type, v.title, NULL as rating, f.created_at
                       FROM favorites f
                       JOIN videos v ON f.video_id = v.id
                       WHERE f.user_id = ? AND f.video_id IS NOT NULL
                       
                       UNION
                       
                       SELECT 'favorite_album' as type, a.title, NULL as rating, f.created_at
                       FROM favorites f
                       JOIN albums a ON f.album_id = a.id
                       WHERE f.user_id = ? AND f.album_id IS NOT NULL
                       
                       UNION
                       
                       SELECT 'video_comment' as type, v.title, NULL as rating, c.created_at
                       FROM comments c
                       JOIN videos v ON c.video_id = v.id
                       WHERE c.user_id = ?
                       
                       UNION
                       
                       SELECT 'video_rating' as type, v.title, r.rating, r.created_at
                       FROM ratings r
                       JOIN videos v ON r.item_id = v.id
                       WHERE r.user_id = ? AND r.item_type = 'video'";
    
    $stmt = $conn->prepare($content_activity);
    $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = array();
    while($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    // Get account activities
    $account_activity = "SELECT 
                        'account_activity' as type,
                        activity_type as title,
                        NULL as rating,
                        created_at,
                        ip_address,
                        user_agent,
                        additional_info
                        FROM user_account_activities 
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                        LIMIT 10";
                        
    $stmt = $conn->prepare($account_activity);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    // Sort all activities by date, newest first
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to most recent 15 activities
    return array_slice($activities, 0, 15);
}

// Count total users
$total_users_query = "SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL";
$total_result = $conn->query($total_users_query);
$total_users = $total_result->fetch_assoc()['total'];

// Count new users in last 30 days
$new_users_query = "SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$new_result = $conn->query($new_users_query);
$new_users = $new_result->fetch_assoc()['total'];

// Most active users (by comments, ratings, favorites)
$active_users_query = "SELECT u.id, u.username, u.profile_picture, 
                       COUNT(DISTINCT c.id) + COUNT(DISTINCT r.id) + COUNT(DISTINCT f.id) + COUNT(DISTINCT ua.id) as activity_count
                       FROM users u
                       LEFT JOIN comments c ON u.id = c.user_id
                       LEFT JOIN ratings r ON u.id = r.user_id
                       LEFT JOIN favorites f ON u.id = f.user_id
                       LEFT JOIN user_account_activities ua ON u.id = ua.user_id
                       WHERE u.deleted_at IS NULL
                       GROUP BY u.id
                       ORDER BY activity_count DESC
                       LIMIT 5";
$active_users_result = $conn->query($active_users_query);

// Store all user activities in an array for modal access
$all_user_activities = [];
if($users_result->num_rows > 0) {
    // Store current result
    $users_data = $users_result->fetch_all(MYSQLI_ASSOC);
    
    // Reset the result pointer
    $users_result->data_seek(0);
    
    foreach($users_data as $user) {
        $all_user_activities[$user['id']] = getUserRecentActivity($conn, $user['id']);
    }
}

// Count login activity in last 7 days
$login_count_query = "SELECT COUNT(*) as count FROM user_account_activities 
                      WHERE activity_type = 'login' 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$login_count_result = $conn->query($login_count_query);
$login_count = $login_count_result->fetch_assoc()['count'];

?>
<?php include './header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users & Account Activity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
    :root {
        --admin-primary: #4361ee;
        --admin-primary-light: #4895ef;
        --admin-secondary: #7209b7;
        --admin-success: #06d6a0;
        --admin-danger: #ef476f;
        --admin-warning: #ffd166;
        --admin-dark: #1a1a2e;
        --admin-dark-accent: #16213e;
        --admin-light: #e2e2f0;
        --admin-background: #0f0f1a;
        --admin-card-bg: #1a1a2e;
        --admin-hover: rgba(255, 255, 255, 0.05);
        --admin-border: rgba(255, 255, 255, 0.08);
        --admin-gradient: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
        --admin-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        --admin-transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background-color: var(--admin-background);
        color: #fff;
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .container {
        padding: 30px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .stat-card {
        background: var(--admin-card-bg);
        border-radius: 16px;
        padding: 30px;
        margin: 0;
        min-width: 240px;
        box-shadow: var(--admin-shadow);
        flex: 1;
        position: relative;
        overflow: hidden;
        transition: var(--admin-transition);
        border: 1px solid var(--admin-border);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
        border-color: var(--admin-primary-light);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: var(--admin-gradient);
    }

    .stat-card:nth-child(1)::before {
        background: linear-gradient(135deg, #4361ee 0%, #4cc9f0 100%);
    }

    .stat-card:nth-child(2)::before {
        background: linear-gradient(135deg, #7209b7 0%, #f72585 100%);
    }

    .stat-card:nth-child(3)::before {
        background: linear-gradient(135deg, #06d6a0 0%, #4cc9f0 100%);
    }

    .stat-card h3 {
        color: var(--admin-light);
        margin-top: 0;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stat-card .number {
        font-size: 42px;
        font-weight: 700;
        margin: 20px 0 10px;
        color: #fff;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .stat-card .icon {
        position: absolute;
        bottom: 25px;
        right: 25px;
        font-size: 48px;
        opacity: 0.15;
        color: #fff;
        transition: var(--admin-transition);
    }

    .stat-card:hover .icon {
        transform: scale(1.1);
        opacity: 0.2;
    }

    .stat-card:nth-child(1) .icon {
        color: #4cc9f0;
    }

    .stat-card:nth-child(2) .icon {
        color: #f72585;
    }

    .stat-card:nth-child(3) .icon {
        color: #06d6a0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
        }

        .stat-card {
            min-width: 100%;
        }
    }

    .card {
        background: var(--admin-card-bg);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: var(--admin-shadow);
        border: 1px solid var(--admin-border);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--admin-border);
        padding-bottom: 15px;
    }

    .card h2 {
        color: #fff;
        margin: 0;
        font-size: 20px;
        font-weight: 600;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th,
    table td {
        padding: 15px;
        color: white; 
        text-align: left;
    }

    table tr {
        border-bottom: 1px solid var(--admin-border);
        transition: var(--admin-transition);
    }

    table tr:hover {
        background-color: var(--admin-hover);
    }

    table th {
        color: var(--admin-light);
        font-weight: 500;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        background-color: var(--admin-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
        user-select: none;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-info .details {
        margin-left: 15px;
    }

    .user-info .username {
        font-weight: 600;
        color: #fff;
        margin-bottom: 3px;
        font-size: 15px;
    }

    .user-info .email {
        font-size: 13px;
        color: var(--admin-light);
    }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-primary {
        background-color: var(--admin-primary);
        color: #fff;
    }

    .badge-secondary {
        background-color: var(--admin-secondary);
        color: #fff;
    }

    .badge-success {
        background-color: var(--admin-success);
        color: #fff;
    }

    /* Activity Timeline Styles */
    .activity-timeline {
        margin-top: 20px;
        padding: 10px;
    }

    .timeline-item {
        display: flex;
        margin-bottom: 20px;
        position: relative;
    }

    .timeline-item:before {
        content: '';
        position: absolute;
        left: 20px;
        top: 40px;
        bottom: -20px;
        width: 2px;
        background: rgba(255, 255, 255, 0.1);
    }

    .timeline-item:last-child:before {
        display: none;
    }

    .timeline-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--admin-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        margin-right: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
        font-size: 16px;
    }

    .timeline-icon.favorite {
        background: var(--admin-danger);
    }

    .timeline-icon.comment {
        background: var(--admin-success);
    }

    .timeline-icon.rating {
        background: var(--admin-secondary);
    }

    .timeline-content {
        flex: 1;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--admin-border);
        position: relative;
        transition: var(--admin-transition);
    }

    .timeline-content:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateY(-2px);
    }

    .timeline-title {
        font-weight: 600;
        margin-bottom: 8px;
        color: #fff;
        font-size: 15px;
    }

    .timeline-time {
        font-size: 12px;
        color: var(--admin-light);
        display: flex;
        align-items: center;
        margin-top: 10px;
    }

    .timeline-time i {
        margin-right: 5px;
        font-size: 14px;
        opacity: 0.7;
    }

    .rating-stars {
        color: var(--admin-warning);
        font-size: 18px;
        letter-spacing: 2px;
        margin: 5px 0;
    }

    .view-btn {
        background: transparent;
        border: 1px solid var(--admin-primary);
        color: var(--admin-primary);
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: var(--admin-transition);
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .view-btn:hover {
        background: var(--admin-primary);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }

    .active-users {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 20px;
    }

    .active-user {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        padding: 20px;
        display: flex;
        align-items: center;
        width: calc(20% - 16px);
        box-sizing: border-box;
        transition: var(--admin-transition);
        border: 1px solid var(--admin-border);
    }

    .active-user:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .active-count {
        background: var(--admin-primary);
        color: #fff;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        margin-left: auto;
        font-weight: 600;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .modal.active {
        display: block;
        opacity: 1;
    }

    .modal-content {
        background: var(--admin-dark);
        margin: 5% auto;
        max-width: 700px;
        width: 90%;
        border-radius: 15px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.5);
        position: relative;
        transform: translateY(-50px);
        opacity: 0;
        transition: all 0.4s ease;
        border: 1px solid var(--admin-border);
        overflow: hidden;
    }

    .modal.active .modal-content {
        transform: translateY(0);
        opacity: 1;
    }

    .modal-header {
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--admin-border);
        background: var(--admin-dark-accent);
    }

    .modal-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        color: #fff;
    }

    .modal-body {
        padding: 25px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: var(--admin-primary);
        border-radius: 10px;
    }

    .close-modal {
        color: var(--admin-light);
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        transition: var(--admin-transition);
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close-modal:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
    }

    .user-profile {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--admin-border);
    }

    .profile-details {
        margin-left: 20px;
    }

    .profile-details h4 {
        margin: 0 0 5px;
        font-size: 18px;
        font-weight: 600;
    }

    .profile-details p {
        margin: 0;
        font-size: 14px;
        color: var(--admin-light);
    }

    .activity-header {
        margin: 20px 0 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .activity-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        position: relative;
        padding-left: 15px;
    }

    .activity-header h4::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 5px;
        height: 18px;
        background: var(--admin-primary);
        border-radius: 3px;
    }

    .modal-footer {
        padding: 15px 25px;
        text-align: right;
        border-top: 1px solid var(--admin-border);
        background: var(--admin-dark-accent);
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: var(--admin-transition);
        font-weight: 500;
        font-size: 14px;
        border: none;
        outline: none;
    }

    .btn-primary {
        background: var(--admin-primary);
        color: #fff;
    }

    .btn-primary:hover {
        background: var(--admin-primary-light);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }

    .btn-secondary {
        background: transparent;
        border: 1px solid var(--admin-border);
        color: var(--admin-light);
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    .no-activity {
        text-align: center;
        padding: 30px;
        color: var(--admin-light);
        font-style: italic;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 10px;
        border: 1px dashed var(--admin-border);
    }

    @media (max-width: 992px) {
        .active-user {
            width: calc(33.33% - 14px);
        }

        .modal-content {
            margin: 10% auto;
            width: 95%;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
        }

        .active-user {
            width: calc(50% - 10px);
        }

        .stat-card {
            flex: 1 0 calc(50% - 10px);
        }

        .modal-content {
            margin: 15% auto;
        }

        .modal-body {
            padding: 20px;
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 15px;
        }

        .active-user {
            width: 100%;
        }

        .stat-card {
            flex: 1 0 100%;
        }

        table {
            display: block;
            overflow-x: auto;
        }

        .user-info .email {
            display: none;
        }

        .modal-content {
            margin: 5% auto;
            width: 95%;
            height: 90%;
            max-height: 90%;
        }

        .modal-body {
            max-height: calc(90vh - 130px);
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="dashboard-header">
            <div class="stat-card">
                <h3>TOTAL USERS</h3>
                <div class="number"><?php echo $total_users; ?></div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <h3>LOGINS (LAST 7 DAYS)</h3>
                <div class="number"><?php echo $login_count; ?></div>
                <div class="icon"><i class="fas fa-sign-in-alt"></i></div>
            </div>
            <div class="stat-card">
                <h3>ACTIVE USERS</h3>
                <div class="number">
                    <?php 
                    // Count users with activity in last 7 days
                    $active_count_query = "SELECT COUNT(DISTINCT user_id) as count FROM (
                        SELECT user_id FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        UNION
                        SELECT user_id FROM ratings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        UNION
                        SELECT user_id FROM favorites WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        UNION
                        SELECT user_id FROM user_account_activities WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ) as active_users";
                    $active_count_result = $conn->query($active_count_query);
                    echo $active_count_result->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>

        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Users</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($users_result->num_rows > 0):
                        while($user = $users_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php if($user['profile_picture']): ?>
                                    <img src="<?php echo htmlspecialchars('../' . $user['profile_picture']); ?>"
                                        alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
                                    <?php else: ?>
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="details">
                                    <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <button class="view-btn"
                                onclick="openUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo htmlspecialchars(addslashes($user['created_at'])); ?>', '<?php echo htmlspecialchars(addslashes($user['profile_picture'] ?: '')); ?>')">
                                <i class="fas fa-eye"></i> View Activity
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No users found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="userActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>User Activity</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="user-profile">
                    <div class="user-avatar" id="modalUserAvatar"></div>
                    <div class="profile-details">
                        <h4 id="modalUsername"></h4>
                        <p id="modalEmail"></p>
                        <p id="modalJoinDate"></p>
                    </div>
                </div>

                <div class="activity-tabs">
                    <button class="tab-btn active" onclick="showTab('all')">All Activity</button>
                    <!-- <button class="tab-btn" onclick="showTab('content')">Content Interactions</button>
                    <button class="tab-btn" onclick="showTab('account')">Account Activity</button> -->
                </div>

                <div class="activity-header">
                    <h4>Recent Activities</h4>
                </div>

                <div id="userActivityTimeline" class="activity-timeline">
                    <!-- Activity items will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
    <script src="../assets/js/app.js"></script>

    <script>
    // Store all user activities as a JSON object
    const userActivities = <?php echo json_encode($all_user_activities); ?>;
    const modal = document.getElementById('userActivityModal');
    let currentTab = 'all';
    let currentUserId = null;

    function openUserModal(userId, username, email, joinDate, profilePic) {
        // Set current user ID
        currentUserId = userId;

        // Set user info in modal
        document.getElementById('modalUsername').textContent = username;
        document.getElementById('modalEmail').textContent = email;
        document.getElementById('modalJoinDate').textContent = 'Joined: ' + formatDate(joinDate);

        const avatarContainer = document.getElementById('modalUserAvatar');

        // Clear previous content
        avatarContainer.innerHTML = '';

        // Set avatar (either image or initial)
        if (profilePic) {
            const img = document.createElement('img');
            img.src = "../" + profilePic;
            img.alt = username;
            img.className = 'user-avatar';
            avatarContainer.appendChild(img);
        } else {
            avatarContainer.textContent = username.charAt(0).toUpperCase();
        }

        // Reset tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector('.tab-btn:first-child').classList.add('active');
        currentTab = 'all';

        // Load user activities
        loadUserActivities();

        // Show the modal with animation
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }

    function showTab(tabName) {
        currentTab = tabName;

        // Update active tab button
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`).classList.add('active');

        // Reload activities with filter
        loadUserActivities();
    }

    function loadUserActivities() {
        // Load user activities into timeline
        const timelineContainer = document.getElementById('userActivityTimeline');
        timelineContainer.innerHTML = '';

        const activities = userActivities[currentUserId] || [];

        // Filter activities based on current tab
        let filteredActivities = activities;
        if (currentTab === 'content') {
            filteredActivities = activities.filter(activity =>
                !activity.hasOwnProperty('activity_type')
            );
        } else if (currentTab === 'account') {
            filteredActivities = activities.filter(activity =>
                activity.type === 'account_activity'
            );
        }

        if (filteredActivities.length > 0) {
            filteredActivities.forEach(activity => {
                const timelineItem = document.createElement('div');
                timelineItem.className = 'timeline-item';

                let iconClass = 'fas fa-star';
                let iconType = 'rating';

                // Set icon and content based on activity type
                if (activity.type === 'account_activity') {
                    // Account activities
                    switch (activity.title) {
                        case 'login':
                            iconClass = 'fas fa-sign-in-alt';
                            iconType = 'login';
                            break;
                        case 'logout':
                            iconClass = 'fas fa-sign-out-alt';
                            iconType = 'logout';
                            break;
                        case 'password_change':
                            iconClass = 'fas fa-key';
                            iconType = 'password';
                            break;
                        case 'profile_update':
                            iconClass = 'fas fa-user-edit';
                            iconType = 'profile';
                            break;
                        case 'account_created':
                            iconClass = 'fas fa-user-plus';
                            iconType = 'account';
                            break;
                        case 'failed_login':
                            iconClass = 'fas fa-exclamation-triangle';
                            iconType = 'warning';
                            break;
                        case 'password_reset_requested':
                        case 'password_reset_completed':
                            iconClass = 'fas fa-unlock';
                            iconType = 'reset';
                            break;
                        default:
                            iconClass = 'fas fa-user-clock';
                            iconType = 'account';
                    }
                } else {
                    // Content activities
                    if (activity.type.includes('favorite')) {
                        iconClass = 'fas fa-heart';
                        iconType = 'favorite';
                    } else if (activity.type.includes('comment')) {
                        iconClass = 'fas fa-comment';
                        iconType = 'comment';
                    } else if (activity.type.includes('rating')) {
                        iconClass = 'fas fa-star';
                        iconType = 'rating';
                    }
                }

                // Format activity title and content
                let activityTitle = '';
                let additionalInfo = '';

                if (activity.type === 'account_activity') {
                    switch (activity.title) {
                        case 'login':
                            activityTitle = 'Logged in to account';
                            if (activity.ip_address) {
                                additionalInfo = `IP: ${activity.ip_address}`;
                            }
                            break;
                        case 'logout':
                            activityTitle = 'Logged out of account';
                            break;
                        case 'password_change':
                            activityTitle = 'Changed password';
                            break;
                        case 'profile_update':
                            activityTitle = 'Updated profile information';
                            if (activity.additional_info) {
                                additionalInfo = `Fields: ${activity.additional_info}`;
                            }
                            break;
                        case 'account_created':
                            activityTitle = 'Account created';
                            break;
                        case 'failed_login':
                            activityTitle = 'Failed login attempt';
                            if (activity.ip_address) {
                                additionalInfo = `IP: ${activity.ip_address}`;
                            }
                            break;
                        case 'password_reset_requested':
                            activityTitle = 'Requested password reset';
                            break;
                        case 'password_reset_completed':
                            activityTitle = 'Completed password reset';
                            break;
                        default:
                            activityTitle = 'Account activity: ' + activity.title;
                    }
                } else {
                    if (activity.type === 'music_rating') {
                        activityTitle = 'Rated the song ';
                    } else if (activity.type === 'video_rating') {
                        activityTitle = 'Rated the video ';
                    } else if (activity.type === 'favorite_music') {
                        activityTitle = 'Favorited the song ';
                    } else if (activity.type === 'favorite_video') {
                        activityTitle = 'Favorited the video ';
                    } else if (activity.type === 'favorite_album') {
                        activityTitle = 'Favorited the album ';
                    } else if (activity.type === 'video_comment') {
                        activityTitle = 'Commented on the video ';
                    }
                }

                // Create timeline content HTML
                let timelineContent = `
                    <div class="timeline-icon ${iconType}">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">
                            ${activityTitle}
                            ${activity.type !== 'account_activity' ? `<strong>${activity.title}</strong>` : ''}
                        </div>
                        ${activity.rating ? `<div class="rating-stars">${getRatingStars(activity.rating)}</div>` : ''}
                        ${additionalInfo ? `<div class="additional-info">${additionalInfo}</div>` : ''}
                        <div class="timeline-time">
                            <i class="far fa-clock"></i> ${formatDate(activity.created_at)}
                        </div>
                    </div>
                `;

                timelineItem.innerHTML = timelineContent;
                timelineContainer.appendChild(timelineItem);
            });
        } else {
            timelineContainer.innerHTML = '<div class="no-activity">No activities found.</div>';
        }
    }

    function closeModal() {
        modal.classList.remove('active');
        setTimeout(() => {
            document.body.style.overflow = ''; // Restore scrolling
        }, 300);
    }

    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }

    // Helper function to display star ratings
    function getRatingStars(rating) {
        let stars = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;

        // Add full stars
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star"></i>';
        }

        // Add half star if needed
        if (hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        }

        // Add empty stars
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star"></i>';
        }

        return stars;
    }

    // Close modal when clicking outside of modal content
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    </script>

    <style>
    /* Additional CSS for account activity */
    .activity-tabs {
        display: flex;
        margin-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .tab-btn {
        padding: 8px 15px;
        border: none;
        background: none;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        margin-right: 10px;
        border-bottom: 3px solid transparent;
    }

    .tab-btn.active {
        color: #4a90e2;
        border-bottom: 3px solid #4a90e2;
    }

    .tab-btn:hover {
        color: #4a90e2;
    }

    .timeline-icon.login,
    .timeline-icon.account {
        background-color: #4a90e2;
    }

    .timeline-icon.logout {
        background-color: #7e57c2;
    }

    .timeline-icon.password,
    .timeline-icon.reset {
        background-color: #66bb6a;
    }

    .timeline-icon.profile {
        background-color: #26a69a;
    }

    .timeline-icon.warning {
        background-color: #ff7043;
    }

    .additional-info {
        font-size: 0.85em;
        color: #666;
        margin-top: 3px;
    }
    </style>
</body>

</html>