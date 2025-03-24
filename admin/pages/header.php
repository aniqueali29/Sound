<?php
include '../../includes/config_db.php';

// Query each table separately
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

// // Fetch and display each result separately
$row1 = $result1->fetch_assoc();
// echo "<p><strong>Admins:</strong> " . $row1['count'] . " records</p>";

$row2 = $result2->fetch_assoc();
// echo "<p><strong>Albums:</strong> " . $row2['count'] . " records</p>";

$row3 = $result3->fetch_assoc();
// echo "<p><strong>Artists:</strong> " . $row3['count'] . " records</p>";

$row4 = $result4->fetch_assoc();
// echo "<p><strong>Comments:</strong> " . $row4['count'] . " records</p>";

$row5 = $result5->fetch_assoc();
// echo "<p><strong>Favorites:</strong> " . $row5['count'] . " records</p>";

$row6 = $result6->fetch_assoc();
// echo "<p><strong>Genres:</strong> " . $row6['count'] . " records</p>";

$row7 = $result7->fetch_assoc();
// echo "<p><strong>Languages:</strong> " . $row7['count'] . " records</p>";

$row8 = $result8->fetch_assoc();
// echo "<p><strong>Music:</strong> " . $row8['count'] . " records</p>";

$row9 = $result9->fetch_assoc();
// echo "<p><strong>Ratings:</strong> " . $row9['count'] . " records</p>";

$row10 = $result10->fetch_assoc();
// echo "<p><strong>Users:</strong> " . $row10['count'] . " records</p>";

$row11 = $result11->fetch_assoc();
// echo "<p><strong>Videos:</strong> " . $row11['count'] . " records</p>";

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
    .logo {
        margin: 80px;
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
</body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <a href="../index.php" class="logo">SOUND</a>

        <ul class="menu">
            <li class="active">
                <a href="../index.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                    <!-- <span class="badge neon-badge">5</span> -->
                </a>
            </li>
            <li>
                <a href="./tracks.php">
                    <i class="fas fa-music"></i>
                    <span>Tracks</span>
                    <span class="badge neon-badge"><?php echo $row8['count'] ?></span>
                </a>
                <!-- <ul class="submenu">
                    <li><a href="./add_edit_track.php"><i class="fas fa-plus-circle"></i> Add New Track</a></li>
                    <li><a href="./tracks.php"><i class="fas fa-list"></i> All Tracks</a></li>
                </ul> -->
            </li>
            <li>
                <a href="./video.php">
                    <i class="fas fa-microphone"></i>
                    <span>Video</span>
                    <span class="badge neon-badge"><?php echo $row11['count'] ?></span>
                </a>
            </li>
            <li>
                <a href="./artist.php">
                    <i class="fas fa-microphone"></i>
                    <span>Artists</span>
                    <span class="badge neon-badge"><?php echo $row3['count'] ?></span>
                </a>
            </li>
            <li>
                <a href="./albums.php">
                    <i class="fas fa-compact-disc"></i>
                    <span>Albums</span>
                    <span class="badge neon-badge"><?php echo $row2['count'] ?></span>
                </a>
            </li>
            <li>
                <a href="./languages.php">
                    <i class="fa-solid fa-language"></i>
                    <span>Languages</span>
                    <span class="badge neon-badge"><?php echo $row7['count'] ?></span>
                </a>
            </li>
            <!-- <span class="badge neon-badge">3</span> -->
            <li>
                <a href="./genres.php">
                    <i class="fa-solid fa-genderless"></i>
                    <span>Genres</span>
                    <span class="badge neon-badge"><?php echo $row6['count'] ?></span>
                </a>
            </li>
            <li>
                <a href="./all_users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                    <span class="badge neon-badge"><?php echo $row10['count'] ?></span>

                </a>
            </li>
            <li>
                <a href="./profile.php">
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
                    <!-- Search results will be populated by JavaScript -->
                </div>
            </div>
            <div class="nav-right">
                <div class="nav-user">
                    <?php if($admin['profile_picture']): ?>
                    <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../../", $admin['profile_picture'])); ?>"
                        alt="<?php echo htmlspecialchars($admin['username']); ?>" class="user-avatar">
                    <?php else: ?>
                    <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($admin['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>

                    <div class="user-dropdown">
                        <div class="user-header">
                            <?php if($admin['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "../../", $admin['profile_picture'])); ?>"
                                alt="<?php echo htmlspecialchars($admin['username']); ?>" class="user-avatar">
                            <?php else: ?>
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                            <?php endif; ?>
                            <div>
                                <h4><?= htmlspecialchars($admin['name']); ?></h4>
                                <p><?= htmlspecialchars($admin['email']); ?></p>
                                <button class="view-profile" onclick="window.location.href='./profile.php'">View
                                    Profile</button>
                            </div>
                        </div>
                        <ul>
                            <li><a href="./profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a href="./logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Main Content -->
        <main>


            <script>
            // 6. User dropdown toggle
            const navUser = document.querySelector('.nav-user');
            const userDropdown = document.querySelector('.user-dropdown');

            navUser.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });

            // Close user dropdown when clicking outside
            document.addEventListener('click', function() {
                userDropdown.classList.remove('show');
            });
            </script>