<?php
session_start();

function get_db_connection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sound";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

function check_admin_auth() {
    // Check if admin session exists
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        // Redirect to login page
        header("Location: ./pages/login.php");
        exit();
    }
    
    return true;
}

// Get admin details
function get_admin_details() {
    if (!check_admin_auth()) {
        return false;
    }
    
    // Get database connection
    $conn = get_db_connection();
    
    $admin_id = $_SESSION['admin_id'];
    $admin_details = [];
    
    // Query to get admin details including profile picture
    $sql = "SELECT id, username, name, email, profile_picture FROM admins 
            WHERE id = ? AND deleted_at IS NULL";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin_details = $result->fetch_assoc();
            
            if (empty($admin_details['profile_picture'])) {
                $admin_details['profile_picture'] = '../assets/images/default-avatar.png';
            }
        } else {
            session_unset();
            session_destroy();
            header("Location: ./pages/login.php");
            exit();
        }
        
        $stmt->close();
    }
    
    $conn->close();
    return $admin_details;
}

$admin_logged_in = check_admin_auth();

$admin = get_admin_details();

$sql1 = "SELECT COUNT(*) AS count FROM admins";
$sql2 = "SELECT COUNT(*) AS count FROM albums";
$sql3 = "SELECT COUNT(*) AS count FROM artists";
$sql4 = "SELECT COUNT(*) AS count FROM comments";
$sql5 = "SELECT COUNT(*) AS count FROM favorites";
$sql6 = "SELECT COUNT(*) AS count FROM genres";
$sql7 = "SELECT COUNT(*) AS count FROM languages";
$sql8 = "SELECT COUNT(*) AS count FROM music";
$sql9 = "SELECT COUNT(*) AS count FROM ratings";
$sql10 = "SELECT COUNT(*) AS count FROM users";
$sql11 = "SELECT COUNT(*) AS count FROM videos";
$conn = get_db_connection();
// Execute each query separately
$result1 = $conn->query($sql1);
$result2 = $conn->query($sql2);
$result3 = $conn->query($sql3);
$result4 = $conn->query($sql4);
$result5 = $conn->query($sql5);
$result6 = $conn->query($sql6);
$result7 = $conn->query($sql7);
$result8 = $conn->query($sql8);
$result9 = $conn->query($sql9);
$result10 = $conn->query($sql10);
$result11 = $conn->query($sql11);

$row1 = $result1->fetch_assoc();

$row2 = $result2->fetch_assoc();

$row3 = $result3->fetch_assoc();

$row4 = $result4->fetch_assoc();

$row5 = $result5->fetch_assoc();

$row6 = $result6->fetch_assoc();

$row7 = $result7->fetch_assoc();

$row8 = $result8->fetch_assoc();

$row9 = $result9->fetch_assoc();

$row10 = $result10->fetch_assoc();

$row11 = $result11->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sound Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .stat-card {
        background-color: #1e1e2d;
        border-radius: 5px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .stat-card:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
    }

    .stat-card.neon-orange:before {
        background-color: #ff6a00;
    }

    .stat-card.neon-green:before {
        background-color: #00e676;
    }

    .stat-card.neon-pink:before {
        background-color: #ff0099;
    }

    .stat-card.neon-blue:before {
        background-color: #00c6ff;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
    }

    .stat-icon i {
        font-size: 24px;
    }

    .neon-orange .stat-icon i {
        color: #ff6a00;
    }

    .neon-green .stat-icon i {
        color: #00e676;
    }

    .neon-pink .stat-icon i {
        color: #ff0099;
    }

    .neon-blue .stat-icon i {
        color: #00c6ff;
    }

    .stat-info h3 {
        font-size: 14px;
        font-weight: 400;
        color: #a2a3b7;
        margin-bottom: 5px;
    }

    .stat-info h2 {
        font-size: 24px;
        font-weight: 500;
        color: #ffffff;
        margin: 0;
    }

    /* Grid Layout */
    .grid-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .grid-item {
        background-color: #1e1e2d;
        border-radius: 5px;
        overflow: hidden;
    }

    .grid-item.span-2 {
        grid-column: span 2;
    }

    .grid-item.span-3 {
        grid-column: span 3;
    }

    .grid-item.span-4 {
        grid-column: span 4;
    }

    /* Card Styles */
    .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .card-header h3 {
        font-size: 16px;
        font-weight: 500;
        color: #ffffff;
        margin: 0;
    }

    .card-body {
        padding: 20px;
        flex: 1;
    }

    /* Dropdown Styles */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropbtn {
        background-color: rgba(255, 255, 255, 0.05);
        color: #a2a3b7;
        padding: 8px 15px;
        font-size: 13px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .dropbtn i {
        margin-left: 8px;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #1e1e2d;
        min-width: 160px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        z-index: 1;
        border-radius: 3px;
    }

    .dropdown-content a {
        color: #a2a3b7;
        padding: 10px 15px;
        text-decoration: none;
        display: block;
        font-size: 13px;
    }

    .dropdown-content a:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* Button Styles */
    .view-all {
        background-color: transparent;
        color: #00c6ff;
        border: none;
        font-size: 13px;
        cursor: pointer;
        padding: 5px 10px;
    }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead th {
        padding: 15px;
        text-align: left;
        color: #a2a3b7;
        font-weight: 500;
        font-size: 13px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .data-table tbody td {
        padding: 15px;
        font-size: 14px;
        color: #ffffff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .track-info {
        display: flex;
        align-items: center;
    }

    .track-info img {
        width: 40px;
        height: 40px;
        border-radius: 5px;
        margin-right: 10px;
        object-fit: cover;
    }

    .status-active {
        color: #00e676;
        background-color: rgba(0, 230, 118, 0.1);
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .status-pending {
        color: #ffcc00;
        background-color: rgba(255, 204, 0, 0.1);
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .status-inactive {
        color: #ff3d71;
        background-color: rgba(255, 61, 113, 0.1);
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .actions {
        display: flex;
        gap: 5px;
    }

    .action-btn {
        width: 30px;
        height: 30px;
        border-radius: 3px;
        background-color: rgba(255, 255, 255, 0.05);
        border: none;
        color: #a2a3b7;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    /* Canvas Styles for Charts */
    canvas {
        width: 100% !important;
        height: 300px !important;
    }

    .logo {
        margin: 80px;
        /* margin-top:20px; */
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(to right, #8c9eff, #0ff47a);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <a href="./index.php" class="logo">SOUND</a>


            <ul class="menu">
                <li class="active">
                    <a href="../index.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="./pages/tracks.php">
                        <i class="fas fa-music"></i>
                        <span>Tracks</span>
                        <span class="badge neon-badge"><?php echo $row8['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/video.php">
                        <i class="fas fa-microphone"></i>
                        <span>Video</span>
                        <span class="badge neon-badge"><?php echo $row11['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/artist.php">
                        <i class="fas fa-microphone"></i>
                        <span>Artists</span>
                        <span class="badge neon-badge"><?php echo $row3['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/albums.php">
                        <i class="fas fa-compact-disc"></i>
                        <span>Albums</span>
                        <span class="badge neon-badge"><?php echo $row2['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/languages.php">
                        <i class="fa-solid fa-language"></i>
                        <span>Languages</span>
                        <span class="badge neon-badge"><?php echo $row7['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/genres.php">
                        <i class="fa-solid fa-genderless"></i>
                        <span>Genres</span>
                        <span class="badge neon-badge"><?php echo $row6['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/all_users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                        <span class="badge neon-badge"><?php echo $row10['count'] ?></span>
                    </a>
                </li>
                <li>
                    <a href="./pages/profile.php">
                        <i class="fas fa-cog"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Content Area -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="top-nav">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="search-container">
                    <input type="text" id="search-input" placeholder="Search...">
                    <i class="fas fa-search"></i>

                    <div class="search-results">

                    </div>
                </div>
                <div class="nav-right">
                    <div class="nav-user">
                        <?php if($admin['profile_picture']): ?>
                        <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $admin['profile_picture'])); ?>"
                            alt="<?php echo htmlspecialchars($admin['username']); ?>" class="user-avatar">
                        <?php else: ?>
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($admin['username']); ?></span>
                        <i class="fas fa-chevron-down"></i>

                        <div class="user-dropdown">
                            <div class="user-header">
                                <?php if($admin['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../", $admin['profile_picture'])); ?>"
                                    alt="<?php echo htmlspecialchars($admin['username']); ?>" class="user-avatar">
                                <?php else: ?>
                                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                <?php endif; ?>
                                <div>
                                    <h4><?= htmlspecialchars($admin['name']); ?></h4>
                                    <p><?= htmlspecialchars($admin['email']); ?></p>
                                    <button class="view-profile"
                                        onclick="window.location.href='./pages/profile.php'">View Profile</button>
                                </div>
                            </div>
                            <ul>
                                <li><a href="./pages/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                                <li><a href="./pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main>
                <div class="page-header">
                    <h1>Sound Dashboard</h1>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card neon-orange">
                        <div class="stat-icon">
                            <i class="fas fa-headphones"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Musics</h3>
                            <h2><?php echo $row8['count'] ?></h2>
                        </div>
                    </div>
                    <div class="stat-card neon-green">
                        <div class="stat-icon">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Videos</h3>
                            <h2><?php echo $row11['count'] ?></h2>
                        </div>
                    </div>
                    <div class="stat-card neon-pink">
                        <div class="stat-icon">
                            <i class="fa-solid fa-folder"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Albums</h3>
                            <h2><?php echo $row2['count'] ?></h2>
                        </div>
                    </div>
                    <div class="stat-card neon-blue">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Users</h3>
                            <h2><?php echo $row10['count'] ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid-container">
                    <div class="grid-item span-2">
                        <div class="card">
                            <div class="card-header">
                                <h3>Music Play Analytics</h3>
                                <div class="dropdown">
                                    <button class="dropbtn">This Month <i class="fas fa-chevron-down"></i></button>
                                    <div class="dropdown-content">
                                        <a href="#" data-period="today">Today</a>
                                        <a href="#" data-period="week">This Week</a>
                                        <a href="#" data-period="month">This Month</a>
                                        <a href="#" data-period="year">This Year</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="playAnalyticsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="grid-item span-2">
                        <div class="card">
                            <div class="card-header">
                                <h3>Top Genres</h3>
                                <div class="dropdown">
                                    <button class="dropbtn">This Month <i class="fas fa-chevron-down"></i></button>
                                    <div class="dropdown-content">
                                        <a href="#" data-period="today">Today</a>
                                        <a href="#" data-period="week">This Week</a>
                                        <a href="#" data-period="month">This Month</a>
                                        <a href="#" data-period="year">This Year</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="genreChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script src="./assets/js/app.js"></script>

</body>

</html>