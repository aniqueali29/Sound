<?php
require_once '../../includes/config_db.php';
session_start();

// Initialize variables
$error_msg = "";
$success_msg = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize email input
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists in the admins table
    $sql = "SELECT id, username, name, email FROM admins WHERE email = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Admin found, generate OTP
        $row = $result->fetch_assoc();
        $admin_id = $row['id'];
        $admin_name = $row['name'];
        $admin_email = $row['email'];
        
        // Generate a 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        
        // Set expiry time (15 minutes from now)
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Update the admin record with the OTP and expiry
        $update_sql = "UPDATE admins SET password_reset_token = ?, password_reset_expiry = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $otp, $expiry, $admin_id);
        
        if ($update_stmt->execute()) {
            // OTP saved, now send email
            if (sendOTPEmail($admin_email, $admin_name, $otp)) {
                // Store email in session for verification page
                $_SESSION['reset_email'] = $admin_email;
                // Redirect to OTP verification page
                header("Location: verify_otp.php");
                exit();
            } else {
                $error_msg = "Failed to send OTP email. Please try again later.";
                
                // Revert the OTP if email fails
                $clear_sql = "UPDATE admins SET password_reset_token = NULL, password_reset_expiry = NULL WHERE id = ?";
                $clear_stmt = $conn->prepare($clear_sql);
                $clear_stmt->bind_param("i", $admin_id);
                $clear_stmt->execute();
                $clear_stmt->close();
            }
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
        
        $update_stmt->close();
    } else {
        // Email not found, but don't reveal this for security
        $success_msg = "If your email exists in our system, you will receive an OTP.";
        // Wait a bit to prevent timing attacks
        sleep(1);
    }
    
    $stmt->close();
    $conn->close();
}

// Function to send OTP email using PHPMailer
function sendOTPEmail($email, $name, $otp) {
    // Require PHPMailer
    require '../../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aniqueali000@gmail.com';
        $mail->Password   = 'dytp mxiz ghfb kwru';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@example.com', 'Music Streaming Admin');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        
        // Email body
        $mail->Body = '
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #e0e0e0;
                    border-radius: 5px;
                }
                .header {
                    background-color: #1e1e2d;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    padding: 20px;
                }
                .otp-box {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    padding: 15px;
                    background-color: #f5f5f5;
                    border-radius: 5px;
                    margin: 20px 0;
                    letter-spacing: 5px;
                }
                .footer {
                    font-size: 12px;
                    color: #666;
                    text-align: center;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Password Reset OTP</h2>
                </div>
                <div class="content">
                    <h3>Hello ' . htmlspecialchars($name) . ',</h3>
                    <p>We received a request to reset your password for your admin account. If you did not make this request, you can ignore this email.</p>
                    <p>Your One-Time Password (OTP) to reset your password is:</p>
                    <div class="otp-box">' . $otp . '</div>
                    <p>This OTP will expire in 15 minutes.</p>
                    <p>Thank you,<br>Music Streaming Admin Team</p>
                </div>
                <div class="footer">
                    <p>This email was sent automatically. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = "Hello $name,\n\nWe received a request to reset your password for your admin account. If you did not make this request, you can ignore this email.\n\nYour One-Time Password (OTP) to reset your password is: $otp\n\nThis OTP will expire in 15 minutes.\n\nThank you,\nMusic Streaming Admin Team";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password reset email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin</title>
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
        
        input[type="email"] {
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

        input[type="email"]:focus {
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Forgot Password</h2>
        </div>
        
        <div class="description">
            Enter your email address below and we'll send you an OTP to reset your password.
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Send OTP</button>
            </div>
            <div class="back-to-login">
                <a href="./login.php">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>