<?php
require_once '../../includes/config_db.php';
session_start();

// Check if user came from forgot password page
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

// Initialize variables
$error_msg = "";
$success_msg = "";
$email = $_SESSION['reset_email'];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get OTP from form and trim any whitespace
    $entered_otp = trim($_POST['otp']);
    
    // Debugging: Log the OTP being validated
    error_log("Validating OTP: " . $entered_otp . " for email: " . $email);

    // Ensure $conn is available
    if (!isset($conn)) {
        $error_msg = "Database connection is missing! Check config_db.php.";
        error_log($error_msg);
    } else {
        // First, check if the email exists and get the stored token and expiry time
        $sql = "SELECT id, password_reset_token, password_reset_expiry FROM admins 
                WHERE email = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error_msg = "Prepare statement failed: " . $conn->error;
            error_log($error_msg);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $admin_id = $row['id'];
                $stored_token = $row['password_reset_token'];
                $expiry_time = strtotime($row['password_reset_expiry']);
                $current_time = time();
                
                // Debugging: Log the stored token and times
                error_log("Admin ID: " . $admin_id);
                error_log("Stored token: " . $stored_token);
                error_log("Expiry time: " . date('Y-m-d H:i:s', $expiry_time));
                error_log("Current time: " . date('Y-m-d H:i:s', $current_time));
                
                // Check if the token matches (both trimmed for safety)
                if (trim($entered_otp) === trim($stored_token)) {
                    // Check if the token is expired
                    if ($expiry_time > $current_time) {
                        // OTP is valid and not expired
                        $_SESSION['reset_admin_id'] = $admin_id;
                        
                        // Optional: Update the log
                        error_log("OTP validation successful. Redirecting to reset_password.php");
                        
                        // Redirect to password reset page
                        header("Location: reset_password.php");
                        exit();
                    } else {
                        $error_msg = "Your OTP has expired. Please request a new one.";
                        error_log("OTP expired. Expiry: " . date('Y-m-d H:i:s', $expiry_time) . ", Current: " . date('Y-m-d H:i:s', $current_time));
                    }
                } else {
                    $error_msg = "Invalid OTP. Please check and try again.";
                    error_log("OTP mismatch. Entered: '" . $entered_otp . "', Stored: '" . $stored_token . "'");
                }
            } else {
                $error_msg = "No account found with this email address or reset token has not been set.";
                error_log("No matching record found for email: " . $email);
            }
            
            $stmt->close();
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Admin</title>
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
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            letter-spacing: 8px;
            text-align: center;
            font-size: 20px;
        }

        input[type="text"]:focus {
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

        .resend-otp {
            text-align: center;
            margin-top: 20px;
        }

        .resend-otp a {
            color: var(--admin-light);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .resend-otp a:hover {
            color: var(--admin-primary);
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .description {
            text-align: center;
            color: var(--admin-light);
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .email-info {
            text-align: center;
            color: var(--admin-primary);
            margin-bottom: 15px;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Verify OTP</h2>
        </div>
        
        <div class="description">
            Please enter the 6-digit OTP sent to your email address.
        </div>
        
        <div class="email-info">
            <?php echo htmlspecialchars(substr($email, 0, 3) . '***' . substr($email, strpos($email, '@'))); ?>
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="otp">One-Time Password</label>
                <input type="text" id="otp" name="otp" required maxlength="6" minlength="6" pattern="\d{6}" inputmode="numeric">
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Verify OTP</button>
            </div>
            <div class="resend-otp">
                <a href="forgot_password.php">Resend OTP</a>
            </div>
        </form>
    </div>

    <script>
        // Auto focus and format OTP input
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            otpInput.focus();
            
            // Only allow numbers
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    </script>
</body>
</html>