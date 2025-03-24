<?php
// Start session if not already started
require_once '../../includes/config_db.php';
session_start();

// Initialize variables
$error_msg = "";
$success_msg = "";

// Check if user came from OTP verification page
if (!isset($_SESSION['reset_admin_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$admin_id = $_SESSION['reset_admin_id'];

// Process form submission to reset password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure $conn is available
    if (!isset($conn)) {
        die("Database connection is missing! Check config_db.php.");
    }

    // Get and validate passwords
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password strength
    if (strlen($password) < 8) {
        $error_msg = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Update the admin's password and clear the reset token
        $update_sql = "UPDATE admins SET password = ?, password_reset_token = NULL, password_reset_expiry = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);

        if (!$update_stmt) {
            die("Prepare statement failed: " . $conn->error);
        }

        $update_stmt->bind_param("si", $hashed_password, $admin_id);

        if ($update_stmt->execute()) {
            $success_msg = "Your password has been successfully reset. You can now log in with your new password.";
            // Clear session variables
            unset($_SESSION['reset_admin_id']);
            unset($_SESSION['reset_email']);
        } else {
            $error_msg = "Failed to reset password. Please try again.";
        }

        $update_stmt->close();
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden !important;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .login-container {
            background-color: var(--admin-card-bg);
            padding: 35px;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            width: 380px;
            border: 1px solid var(--admin-border);
        }
        
        h2 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--admin-light);
            font-size: 14px;
            font-weight: 500;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(0, 198, 255, 0.25);
        }
        
        .btn {
            background: linear-gradient(to right, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 198, 255, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            color: var(--admin-danger);
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 10px;
            background-color: rgba(255, 61, 113, 0.1);
            border-radius: 6px;
        }
        
        .success {
            color: var(--admin-success);
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 10px;
            background-color: rgba(0, 230, 118, 0.1);
            border-radius: 6px;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: var(--admin-light);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-to-login a:hover {
            color: var(--admin-primary);
        }

        .password-requirements {
            font-size: 12px;
            color: var(--admin-light);
            margin-top: 5px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Reset Password</h2>
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if (empty($success_msg)): ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <div class="password-requirements">Password must be at least 8 characters long.</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>