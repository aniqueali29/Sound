<?php
session_start();
require_once '../../includes/config_db.php';
// // // // Admin credentials
// $name = "Anique Ali";
// $email = "aniqueali29gmail.com";
// $username = "aniqueali";
// $password = password_hash("admin123", PASSWORD_DEFAULT); // Hash password

// // Insert query
// $sql = "INSERT INTO admins (username, name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("ssss", $username, $name, $email, $password);

// if ($stmt->execute()) {
//     echo "Admin added successfully!";
// } else {
//     echo "Error: " . $stmt->error;
// }

// $stmt->close();

$error_msg = "";
$success_msg = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sound";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


    // Get form data and sanitize inputs
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);
    
    // Query to check if the admin exists
    $sql = "SELECT id, username, password, name FROM admins WHERE username = ? AND deleted_at IS NULL";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                // Password is correct, set session variables
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_name'] = $row['name'];
                
                // Update last login time
                $update_sql = "UPDATE admins SET updated_at = NOW() WHERE id = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $row['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                // Set success message and redirect
                $success_msg = "Login successful. Redirecting...";
                echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 1500);</script>";
            } else {
                $error_msg = "Invalid username or password";
            }
        } else {
            $error_msg = "Invalid username or password";
        }
        
        $stmt->close();
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
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

    input[type="text"],
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

    input[type="text"]:focus,
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

    .forgot-password {
        text-align: center;
        margin-top: 20px;
    }

    .forgot-password a {
        color: var(--admin-light);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .forgot-password a:hover {
        color: var(--admin-primary);
    }

    .login-header {
        text-align: center;
        margin-bottom: 25px;
    }

    .login-header img {
        max-width: 80px;
        margin-bottom: 15px;
    }

    /* Additional animation for input fields */
    @keyframes highlight {
        0% {
            border-color: var(--admin-primary);
        }

        50% {
            border-color: var(--admin-secondary);
        }

        100% {
            border-color: var(--admin-primary);
        }
    }

    input:focus {
        animation: highlight 2s infinite;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <!-- You can add your logo here -->
            <!-- <img src="logo.png" alt="Admin Logo"> -->
            <h2>Admin Login</h2>
        </div>

        <?php if (!empty($error_msg)): ?>
        <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
        <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Login</button>
            </div>
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </form>
    </div>

    <?php
    // Additional PHP functions that might be useful
    
    // Function to hash passwords (use this when creating new admin accounts)
    function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    // Function to log login attempts (optional security feature)
    function logLoginAttempt($conn, $username, $success) {
        $sql = "INSERT INTO login_logs (username, ip_address, success) VALUES (?, ?, ?)";
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $ip, $success);
        $stmt->execute();
        $stmt->close();
    }
    
    // Example of how to create a new admin account
    function createAdminAccount($conn, $username, $name, $email, $password) {
        $hashed_password = hashPassword($password);
        
        $sql = "INSERT INTO admins (username, name, email, password) VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
    ?>
</body>

</html>