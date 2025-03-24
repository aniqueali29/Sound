<?php
session_start();
require '../includes/config_db.php';
require '../includes/user_activity_functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoloader
require '../vendor/autoload.php';

// Initialize variables
$email = "";
$error = "";
$success = "";
$show_form = true;

// Display reset expired message if it exists
if (isset($_SESSION['reset_expired'])) {
    $error = $_SESSION['reset_expired'];
    unset($_SESSION['reset_expired']);
}

// Function to generate random OTP
function generateOTP($length = 6)
{
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[rand(0, 9)];
    }
    return $otp;
}

// Function to send password reset email
function sendResetEmail($email, $otp, $token)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@sound.com', 'Sound');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - OTP Verification';

        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Arial', sans-serif; color: #333; }
                .container { padding: 20px; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(45deg, #0ff47a, #6c43f5); padding: 20px; color: white; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 5px 5px; }
                .otp { font-size: 24px; font-weight: bold; background: #eee; padding: 10px; text-align: center; letter-spacing: 5px; margin: 20px 0; }
                .footer { text-align: center; font-size: 12px; color: #666; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>SOUND</h1>
                </div>
                <div class='content'>
                    <h2>Password Reset Request</h2>
                    <p>We received a request to reset your password. Use the following OTP to verify your identity:</p>
                    <div class='otp'>$otp</div>
                    <p>This OTP will expire in 15 minutes.</p>
                    <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " Sound. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $message;
        $mail->AltBody = "Your OTP for password reset is: $otp. This OTP will expire in 15 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Process reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);

    // Validate email
    if (empty($email)) {
        $error = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $error = "No account found with that email address.";
        } else {
            // Get user ID for activity logging
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            // Generate OTP and token
            $token = bin2hex(random_bytes(32)); // Create a secure token
            $otp = sprintf("%06d", mt_rand(100000, 999999)); // Generate a 6-digit OTP

            // Set expiry time (15 minutes from now)
            $expiry = date('Y-m-d H:i:s', time() + (15 * 60));

            // Save token and OTP in database
            $updateStmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expiry = ? WHERE email = ?");
            $hashedToken = password_hash($token . $otp, PASSWORD_DEFAULT); // Store hashed token+OTP
            $updateStmt->bind_param("sss", $hashedToken, $expiry, $email);

            if ($updateStmt->execute()) {
                // Record password reset request
                recordPasswordResetRequest($conn, $user_id);
                
                // Send email with OTP
                if (sendResetEmail($email, $otp, $token)) {
                    // Store data in session for verification
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_token'] = $token;

                    $success = "OTP has been sent to your email. Please check your inbox.";
                    $show_form = false;

                    // Redirect to OTP verification page
                    header("Location: verify-otp.php");
                    exit();
                } else {
                    $error = "Failed to send email. Please try again later.";
                }
            } else {
                $error = "Database error. Please try again later.";
            }
            $updateStmt->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Neon Sound | Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>

    body {
        background-color: #060B19;
        background-image:
            radial-gradient(circle at 15% 15%, rgba(62, 120, 178, 0.15) 0%, rgba(0, 0, 0, 0) 50%),
            radial-gradient(circle at 85% 85%, rgba(255, 59, 141, 0.15) 0%, rgba(0, 0, 0, 0) 50%);
        color: var(--text-color);
        font-family: 'Poppins', sans-serif;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0;
        overflow-x: hidden;
        position: relative;
        background-color: #060b19;
        background-image:
            radial-gradient(circle at 10% 20%, rgba(91, 2, 154, 0.2) 0%, rgba(0, 0, 0, 0) 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 65, 108, 0.2) 0%, rgba(0, 0, 0, 0) 40%);
        color: #fff;
        font-family: 'Quicksand', sans-serif;
        overflow-x: hidden;
    }


    /* Stars background effect */
    body::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(white, rgba(255, 255, 255, 0.2) 2px, transparent 2px);
        background-size: 100px 100px;
        background-position: 0 0, 50px 50px;
        opacity: 0.1;
        pointer-events: none;
    }

    .auth-container {
        width: 100%;
        max-width: 450px;
        padding: 2rem;
        background: rgba(18, 26, 47, 0.9);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
        z-index: 1;
    }


    /* Animated border glow effect */
    .auth-container::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: var(--gradient-primary);
        z-index: -1;
        border-radius: 18px;
        animation: borderGlow 6s linear infinite;
    }

    .logo {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .logo-text {
        font-weight: 700;
        letter-spacing: 2px;
        font-size: 2.2rem;
        margin: 0;
        background: var(--gradient-primary);
        -webkit-text-fill-color: transparent;
        position: relative;
        display: inline-block;
    }

    .logo-text::after {
        content: 'Entertainment';
        display: block;
        font-size: 1rem;
        font-weight: 400;
        letter-spacing: 4px;
        text-align: right;
        margin-top: -8px;
        color: #fff;
        -webkit-text-fill-color: #fff;
    }

    .reset-icon {
        font-size: 2.5rem;
        color: var(--neon-accent);
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 500;
        color: var(--text-color);
        margin-bottom: 0.25rem;
        display: block;
        font-size: 0.9rem;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--text-color);
        border-radius: 8px;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--neon-orange);
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .input-icon {
        position: absolute;
        left: 12px;
        top: 42px;
        color: rgba(255, 255, 255, 0.5);
        transition: color 0.3s ease;
    }

    .btn-neon {
        background: var(--gradient-primary);
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 1rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    }

    .btn-neon:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
    }

    .btn-neon::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(to bottom right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.4) 50%, rgba(255, 255, 255, 0) 100%);
        transform: rotate(45deg);
        transition: all 0.5s ease;
        opacity: 0;
    }

    .btn-neon:hover::after {
        animation: btnShine 1.5s ease;
    }

    .alert {
        background: rgba(13, 12, 29, 0.7);
        border-left: 3px solid;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 1rem;
        animation: fadeIn 0.5s ease-in-out;
        font-size: 0.9rem;
    }

    .alert-danger {
        border-left-color: var(--neon-accent);
        color: var(--neon-accent);
    }

    .alert-success {
        border-left-color: var(--neon-orange);
        color: var(--neon-orange);
    }

    a {
        color: var(--neon-blue);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    a:hover {
        color: var(--neon-orange);
    }

    /* Floating music notes decoration */
    .music-note {
        position: absolute;
        opacity: 0.2;
        animation: float 10s ease-in-out infinite;
        font-size: 2rem;
        color: var(--neon-orange);
        z-index: -1;
    }

    .note-1 {
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }

    .note-2 {
        top: 40%;
        right: 15%;
        animation-delay: 2s;
    }

    .note-3 {
        bottom: 30%;
        left: 20%;
        animation-delay: 4s;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(10deg);
        }
    }

    @keyframes btnShine {
        0% {
            opacity: 0;
            left: -50%;
        }

        50% {
            opacity: 0.8;
        }

        100% {
            opacity: 0;
            left: 150%;
        }
    }

    @keyframes borderGlow {

        0%,
        100% {
            opacity: 0.5;
        }

        50% {
            opacity: 0.2;
        }
    }

    @media (max-width: 576px) {
        .auth-container {
            padding: 1.5rem;
            width: 90%;
            max-width: 360px;
        }

        .logo-text {
            font-size: 1.8rem;
        }

        .logo-text::after {
            font-size: 0.8rem;
        }
    }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="logo">
            <h2 class="neon-text">SOUND</h2>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
        <div class="form-wrapper mt-4">
            <div class="text-center mb-4">
                <i class="fas fa-key reset-icon"></i>
                <h3>Reset Your Password</h3>
                <p class="text-light">Enter your email address to receive a one-time password.</p>
            </div>

            <form method="POST" action="" novalidate>
                <div class="position-relative">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="Enter your registered email" value="<?php echo htmlspecialchars($email); ?>"
                        required>
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                </div>

                <button type="submit" name="request_reset" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane me-2"></i> Send OTP
                </button>

                <div class="text-center mt-3">
                    <a href="./login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Login
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>

</html>