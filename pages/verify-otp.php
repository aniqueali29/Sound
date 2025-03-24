<?php
session_start();
require '../includes/config_db.php'; 
require '../includes/user_activity_functions.php';

// Check if reset email and token exist in session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])) {
    // Redirect to reset password page
    header("Location: reset-password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$token = $_SESSION['reset_token'];
$error = "";
$success = "";

// Process OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);

    // Validate OTP
    if (empty($otp)) {
        $error = "OTP is required.";
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $error = "Invalid OTP format. OTP should be 6 digits.";
    } else {
        // Check if token is valid and not expired
        $stmt = $conn->prepare("SELECT id, password_reset_token, password_reset_expiry FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $error = "Invalid reset request.";
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $dbToken = $user['password_reset_token'];
            $expiry = strtotime($user['password_reset_expiry']);

            // Check if token is expired
            if (time() > $expiry) {
                $error = "OTP has expired. Please request a new one.";
                
                // Record failed verification attempt
                recordUserActivity($conn, $user_id, 'otp_verification_failed', 'OTP expired');
            }
            // Verify token and OTP
            elseif (password_verify($token . $otp, $dbToken)) {
                // OTP is valid, store verification status in session
                $_SESSION['otp_verified'] = true;
                // Store verification time for session expiry
                $_SESSION['otp_verified_time'] = time();
                
                // Record successful OTP verification
                recordUserActivity($conn, $user_id, 'otp_verification_success');

                // Redirect to new password page
                header("Location: new-password.php");
                exit();
            } else {
                $error = "Invalid OTP. Please try again.";
                
                // Record failed verification attempt
                recordUserActivity($conn, $user_id, 'otp_verification_failed', 'Invalid OTP');
            }
        }
        $stmt->close();
    }
}

// Resend OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_otp'])) {
    // Get user ID for logging
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        // Record OTP resend request
        recordUserActivity($conn, $user_id, 'otp_resend_requested');
    }
    $stmt->close();
    
    // Redirect back to reset password page to generate new OTP
    header("Location: reset-password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Neon Sound | Verify OTP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-orange: #FF6B35;
        --neon-blue: #4682B4;
        --deep-space: #0D0C1D;
        --neon-accent: #FF3B8D;
        --text-color: #ffffff;
        --gradient-primary: linear-gradient(45deg, #FF3B8D, #FF6B35);
        --gradient-secondary: linear-gradient(45deg, #3E78B2, #004E98);
        --neon-green: #0ff47a;
        }

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

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .neon-text {
            font-weight: 700;
            letter-spacing: 2px;
            font-size: 2.2rem;
            margin: 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .neon-text::after {
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
            padding: 0.75rem 1rem;
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

        .text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        /* OTP Input styling */
        .otp-input {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .otp-digit {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 1.5rem;
            border-radius: 8px;
            margin: 0 4px;
        }

        .resend-timer {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .btn-link {
            color: var(--neon-accent) !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-link:hover {
            color: var(--neon-orange) !important;
            text-decoration: none;
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

            .neon-text {
                font-size: 1.8rem;
            }

            .neon-text::after {
                font-size: 0.8rem;
            }

            .otp-digit {
                width: 40px;
                height: 50px;
                font-size: 1.2rem;
                margin: 0 2px;
            }
        }
    </style>

</head>

<body>
    <!-- Add music note decorations -->
    <i class="fas fa-music music-note note-1"></i>
    <i class="fas fa-music music-note note-2"></i>
    <i class="fas fa-music music-note note-3"></i>

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

        <div class="form-wrapper mt-4">
            <div class="text-center mb-4">
                <i class="fas fa-shield-alt reset-icon"></i>
                <h3>Verify OTP</h3>
                <p class="text-muted">A 6-digit code has been sent to <?php echo htmlspecialchars($email); ?></p>
            </div>

            <form method="POST" action="" novalidate>
                <div class="otp-input">
                    <input type="text" maxlength="1" class="form-control otp-digit" data-index="1" autocomplete="off">
                    <input type="text" maxlength="1" class="form-control otp-digit" data-index="2" autocomplete="off">
                    <input type="text" maxlength="1" class="form-control otp-digit" data-index="3" autocomplete="off">
                    <input type="text" maxlength="1" class="form-control otp-digit" data-index="4" autocomplete="off">
                    <input type="text" maxlength="1" class="form-control otp-digit" data-index="5" autocomplete="off">
                    <input type="text" maxlength="1" class="form-control otp-digit" data-index="6" autocomplete="off">
                </div>
                <input type="hidden" id="full-otp" name="otp">

                <button type="submit" name="verify_otp" class="btn btn-primary w-100">
                    <i class="fas fa-check-circle me-2"></i> Verify OTP
                </button>

                <div class="resend-timer mt-3 text-center">
                    <span id="timer">Resend OTP in <span id="countdown">15:00</span></span>
                    <form method="POST" action="" id="resend-form" style="display: none;">
                        <button type="submit" name="resend_otp" class="btn btn-link p-0">
                            <i class="fas fa-redo me-1"></i> Resend OTP
                        </button>
                    </form>
                </div>

                <div class="text-center mt-3">
                    <a href="./reset-password.php" class="btn-link">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpDigits = document.querySelectorAll('.otp-digit');
            const fullOtp = document.getElementById('full-otp');

            // Focus first input on load
            otpDigits[0].focus();

            // Auto-focus next input
            otpDigits.forEach(digit => {
                digit.addEventListener('input', function() {
                    const index = parseInt(this.dataset.index);

                    if (this.value.length === 1) {
                        // Move to next input
                        if (index < 6) {
                            otpDigits[index].focus();
                        }
                    }

                    // Update hidden field with full OTP
                    updateFullOtp();
                });

                // Handle backspace
                digit.addEventListener('keydown', function(e) {
                    const index = parseInt(this.dataset.index);

                    if (e.key === 'Backspace' && this.value.length === 0) {
                        // Move to previous input on backspace if current is empty
                        if (index > 1) {
                            otpDigits[index - 2].focus();
                        }
                    }
                });

                // Only allow numbers
                digit.addEventListener('keypress', function(e) {
                    if (isNaN(e.key)) {
                        e.preventDefault();
                    }
                });
            });

            function updateFullOtp() {
                let otp = '';
                otpDigits.forEach(digit => {
                    otp += digit.value;
                });
                fullOtp.value = otp;
            }

            // Countdown timer
            const countdown = document.getElementById('countdown');
            const timer = document.getElementById('timer');
            const resendForm = document.getElementById('resend-form');

            let minutes = 15;
            let seconds = 0;

            const interval = setInterval(function() {
                if (minutes === 0 && seconds === 0) {
                    clearInterval(interval);
                    timer.style.display = 'none';
                    resendForm.style.display = 'block';
                    return;
                }

                if (seconds === 0) {
                    minutes--;
                    seconds = 59;
                } else {
                    seconds--;
                }

                countdown.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        });
    </script>
</body>

</html>