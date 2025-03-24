**VERIFY_OTP.PHP**
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
    // Get OTP from form
    $entered_otp = $_POST['otp'];
    
    // Validate OTP
    // $conn = connectDB();
    $sql = "SELECT id FROM admins WHERE email = ? AND password_reset_token = ? AND password_reset_expiry > NOW() AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $entered_otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // OTP is valid
        $row = $result->fetch_assoc();
        $admin_id = $row['id'];
        
        // Store admin ID for reset page
        $_SESSION['reset_admin_id'] = $admin_id;
        
        // Redirect to password reset page
        header("Location: reset_password.php");
        exit();
    } else {
        // OTP is invalid or expired
        $error_msg = "Invalid or expired OTP. Please try again or request a new OTP.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

**FORGOT_PASSWORD.PHP**
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

**RESET_PASSWORD.PHP**
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
    $conn = connectDB();
    
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
        $update_stmt->bind_param("si", $hashed_password, $admin_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Your password has been successfully reset. You can now login with your new password.";
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
