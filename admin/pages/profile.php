<?php
include '../../includes/config_db.php';

require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];


// Get admin ID from session
$admin_id = $_SESSION['admin_id'];

// Initialize variables for error/success messages
$error = '';
$success = '';

// Process form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    // Validate inputs
    if (empty($name) || empty($username) || empty($email)) {
        $error = "All fields are required";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username is already taken by another admin
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ? AND deleted_at IS NULL");
        $stmt->bind_param("si", $username, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already taken";
        } else {
            // Check if email is already taken by another admin
            $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ? AND deleted_at IS NULL");
            $stmt->bind_param("si", $email, $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already in use";
            } else {
                // Process profile picture upload
                $profile_picture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                        $error = "Only JPG, PNG, and GIF images are allowed";
                    } else if ($_FILES['profile_picture']['size'] > $max_size) {
                        $error = "Image size should be less than 2MB";
                    } else {
                        // Create uploads directory if it doesn't exist
                        $upload_dir = '../../uploads/admin_profiles/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                        $target_path = $upload_dir . $new_filename;
                        
                        // Upload file
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                            $profile_picture = $target_path;
                            
                            // Delete old profile picture if exists
                            $stmt = $conn->prepare("SELECT profile_picture FROM admins WHERE id = ?");
                            $stmt->bind_param("i", $admin_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $old_profile = $result->fetch_assoc();
                            
                            if ($old_profile && !empty($old_profile['profile_picture']) && file_exists($old_profile['profile_picture'])) {
                                unlink($old_profile['profile_picture']);
                            }
                        } else {
                            $error = "Failed to upload image";
                        }
                    }
                }
                
                // If no error, update admin profile
                if (empty($error)) {
                    if ($profile_picture) {
                        $stmt = $conn->prepare("UPDATE admins SET name = ?, username = ?, email = ?, profile_picture = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $name, $username, $email, $profile_picture, $admin_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE admins SET name = ?, username = ?, email = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $name, $username, $email, $admin_id);
                    }
                    
                    if ($stmt->execute()) {
                        $success = "Profile updated successfully";
                    } else {
                        $error = "Error updating profile: " . $conn->error;
                    }
                }
            }
        }
    }
}

// Process form submission for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required";
    } else if ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        if (!$admin || !password_verify($current_password, $admin['password'])) {
            $error = "Current password is incorrect";
        } else {
            // Hash new password and update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $admin_id);
            
            if ($stmt->execute()) {
                $success = "Password changed successfully";
            } else {
                $error = "Error changing password: " . $conn->error;
            }
        }
    }
}

// Fetch admin data
$stmt = $conn->prepare("SELECT id, username, name, email, profile_picture, created_at FROM admins WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: logout.php');
    exit();
}

$admin = $result->fetch_assoc();
include './header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
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

        body {
            background-color: var(--admin-background);
            color: #fff;
            font-family: 'Roboto', sans-serif;
        }

        .container {
            padding-top: 30px;
            padding-bottom: 30px;
        }

        .profile-container {
            max-width: 800px;
            margin: 30px auto;
        }

        .card {
            background-color: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--admin-border);
            padding: 15px 20px;
        }

        .card-body {
            padding: 25px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(to right, rgba(0, 198, 255, 0.1), rgba(110, 66, 193, 0.1));
            border-radius: 8px;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 3px solid var(--admin-primary);
            box-shadow: 0 0 15px rgba(0, 198, 255, 0.3);
        }

        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
            font-size: 40px;
            color: var(--admin-primary);
            border: 3px solid var(--admin-primary);
            box-shadow: 0 0 15px rgba(0, 198, 255, 0.3);
        }

        .profile-info h3 {
            color: #fff;
            margin-bottom: 5px;
        }

        .profile-info .text-muted {
            color: var(--admin-light) !important;
        }

        .nav-tabs {
            border-bottom: 1px solid var(--admin-border);
            margin-bottom: 20px;
        }

        .nav-tabs .nav-item {
            margin-bottom: -1px;
        }

        .nav-tabs .nav-link {
            color: var(--admin-light);
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            padding: 12px 20px;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: #fff;
            background-color: var(--admin-hover);
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--admin-primary);
            background-color: transparent;
            border-bottom: 2px solid var(--admin-primary);
        }

        .form-group label {
            color: #fff;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--admin-border);
            border-radius: 5px;
            color: #fff;
            padding: 12px 15px;
            height: auto;
            transition: all 0.3s;
        }

        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.3);
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 198, 255, 0.25);
            color: #fff;
        }

        .form-control::placeholder {
            color: var(--admin-light);
            opacity: 0.7;
        }

        .form-control:disabled,
        .form-control[readonly] {
            background-color: #181824;
            opacity: 1;
        }

        .custom-file-label {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--admin-border);
            color: var(--admin-light);
            height: auto;
            padding: 12px 15px;
        }

        .custom-file-label::after {
            height: 100%;
            padding: 12px 15px;
            background-color: var(--admin-primary);
            color: #fff;
        }

        .form-text {
            color: var(--admin-light);
            font-size: 0.85rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--admin-primary), var(--admin-secondary));
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 198, 255, 0.3);
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--admin-secondary), var(--admin-primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 198, 255, 0.4);
        }

        .btn-primary:active,
        .btn-primary:focus {
            background: var(--admin-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 198, 255, 0.5) !important;
        }

        .alert-success {
            background-color: rgba(0, 230, 118, 0.1);
            border-color: var(--admin-success);
            color: var(--admin-success);
        }

        .alert-danger {
            background-color: rgba(255, 61, 113, 0.1);
            border-color: var(--admin-danger);
            color: var(--admin-danger);
        }

        .alert .close {
            color: #fff;
            opacity: 0.8;
            text-shadow: none;
        }

        .alert .close:hover {
            opacity: 1;
        }

        /* Animation for success message */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 30px, 0);
            }

            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <h1>Admin Profile</h1>
    </div>
    <div class="container profile-container">
        <div class="card">
            <div class="card-header">
                <!-- <h2 class="mb-0"></h2> -->
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="profile-header">
                    <?php if (!empty($admin['profile_picture']) && file_exists($admin['profile_picture'])): ?>
                    <img src="<?php echo $admin['profile_picture']; ?>" alt="Profile Picture" class="profile-image">
                    <?php else: ?>
                    <div class="profile-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                    <?php endif; ?>

                    <div class="profile-info">
                        <h3>
                            <?php echo htmlspecialchars($admin['name']); ?>
                        </h3>
                        <p class="text-muted">@
                            <?php echo htmlspecialchars($admin['username']); ?>
                        </p>
                        <p class="text-muted"><i class="fas fa-calendar-alt mr-2"></i>Admin since:
                            <?php echo date('F j, Y', strtotime($admin['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab"
                            aria-controls="profile" aria-selected="true">
                            <i class="fas fa-user-edit mr-2"></i>Edit Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab"
                            aria-controls="password" aria-selected="false">
                            <i class="fas fa-key mr-2"></i>Change Password
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="name"><i class="fas fa-user mr-2"></i>Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($admin['name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="username"><i class="fas fa-at mr-2"></i>Username</label>
                                <input type="text" class="form-control" id="username" name="username" readonly
                                    value="<?php echo htmlspecialchars($admin['username']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope mr-2"></i>Email</label>
                                <input type="email" readonly class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($admin['email']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="profile_picture"><i class="fas fa-image mr-2"></i>Profile Picture</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="profile_picture"
                                        name="profile_picture">
                                    <label class="custom-file-label" for="profile_picture">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Max file size: 2MB. Allowed formats: JPG, PNG,
                                    GIF</small>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="current_password"><i class="fas fa-lock mr-2"></i>Current Password</label>
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password">
                            </div>

                            <div class="form-group">
                                <label for="new_password"><i class="fas fa-key mr-2"></i>New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <small class="form-text text-muted">Password must be at least 8 characters long</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password"><i class="fas fa-check-circle mr-2"></i>Confirm New
                                    Password</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password">
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key mr-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Show filename in file input
        $('.custom-file-input').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
        
        // Add ripple effect to buttons
        $('.btn').on('mousedown', function (e) {
            const $this = $(this);
            const offset = $this.offset();
            const x = e.pageX - offset.left;
            const y = e.pageY - offset.top;

            const $ripple = $('<span class="ripple"></span>');
            $ripple.css({
                top: y,
                left: x
            });

            $this.append($ripple);

            setTimeout(function () {
                $ripple.remove();
            }, 800);
        });
    </script>
<script src="../assets/js/app.js"></script>
</body>

</html>