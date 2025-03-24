<?php
session_start();

require '../includes/config_db.php'; 
require '../includes/user_activity_functions.php';

// Check if user is authorized to access this page
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    // Redirect to reset password page if not properly authorized
    header("Location: reset-password.php");
    exit();
}

// Check if OTP verification session has expired (5 minutes)
if (!isset($_SESSION['otp_verified_time']) || (time() - $_SESSION['otp_verified_time'] > 300)) {
    // Clear session variables
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_token']);
    unset($_SESSION['otp_verified']);
    unset($_SESSION['otp_verified_time']);
    
    // Redirect to reset password page with expired message
    $_SESSION['reset_expired'] = "Your verification session has expired. Please try again.";
    header("Location: reset-password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$error = "";
$success = "";

// Process new password submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate passwords
    if (empty($password)) {
        $error = "Password is required.";
    } elseif (empty($confirm_password)) {
        $error = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must include at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must include at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must include at least one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must include at least one special character.";
    } else {
        // Get user ID before updating
        $idStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $idStmt->bind_param("s", $email);
        $idStmt->execute();
        $idResult = $idStmt->get_result();
        
        if ($idResult->num_rows === 0) {
            $error = "User account not found. Please contact support.";
        } else {
            $user = $idResult->fetch_assoc();
            $user_id = $user['id'];
            
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user's password and clear reset token
            $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expiry = NULL WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $result = $stmt->execute();
            
            if ($result && $stmt->affected_rows > 0) {
                // Record password reset completion
                recordPasswordResetCompletion($conn, $user_id);
                
                $success = "Your password has been updated successfully!";
                
                // Clear reset session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['otp_verified']);
                unset($_SESSION['otp_verified_time']);
                
                // Set a flag to redirect to login after displaying success message
                $_SESSION['password_reset_success'] = true;
            } else {
                $error = "Failed to update password. Please try again.";
                
                // Record failed password reset
                recordUserActivity($conn, $user_id, 'password_reset_failed');
            }
            
            $stmt->close();
        }
        
        $idStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Sound Entertainment | New Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --primary: #8c9eff;
        --dark: #060b19;
        --dark-light: #121a2f;
        --text-light: #ffffff;
        --text-dim: #a0a0a0;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--dark);
        color: var(--text-light);
        background-image:
            radial-gradient(circle at 10% 20%, rgba(91, 2, 154, 0.2) 0%, rgba(0, 0, 0, 0) 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 65, 108, 0.2) 0%, rgba(0, 0, 0, 0) 40%);
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .main-content {
        display: flex;
        justify-content: center;
        align-items: center;
        flex: 1;
        padding: 20px;
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

    .reset-icon {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 500;
        color: var(--text-light);
        margin-bottom: 0.25rem;
        display: block;
        font-size: 0.9rem;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--text-light);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(140, 158, 255, 0.2);
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .input-icon {
        position: absolute;
        right: 12px;
        top: 42px;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .input-icon:hover {
        color: var(--primary);
    }

    .btn-neon {
        background: var(--primary);
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
        box-shadow: 0 4px 15px rgba(140, 158, 255, 0.3);
    }

    .btn-neon:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(140, 158, 255, 0.4);
        background-color: rgba(140, 158, 255, 0.8);
    }

    .alert {
        background: rgba(18, 26, 47, 0.7);
        border-left: 3px solid;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 1rem;
        animation: fadeIn 0.5s ease-in-out;
        font-size: 0.9rem;
    }

    .alert-danger {
        border-left-color: #ff3b8d;
        color: #ff3b8d;
    }

    .alert-success {
        border-left-color: #0ff47a;
        color: #0ff47a;
    }

    .password-strength {
        margin-top: 0.5rem;
        margin-bottom: 1.5rem;
        display: none;
    }

    .strength-meter {
        height: 6px;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        margin-top: 5px;
        width: 0%;
        transition: all 0.5s ease;
    }

    .requirements {
        margin-top: 10px;
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .requirement {
        margin-top: 4px;
    }

    .requirement.met {
        color: #0ff47a;
    }

    .requirement.met i {
        color: #0ff47a;
    }

    .requirement i {
        color: #ff3b8d;
        margin-right: 5px;
        width: 14px;
    }

    #password-match-message {
        color: #ff3b8d;
        font-size: 0.8rem;
    }

    #strength-text {
        color: rgba(255, 255, 255, 0.8);
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

    @media (max-width: 576px) {
        .auth-container {
            padding: 1.5rem;
            width: 90%;
            max-width: 360px;
        }
    }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="auth-container">
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

            <?php if (empty($success)): ?>
            <div class="form-wrapper mt-4">
                <div class="text-center mb-4">
                    <i class="fas fa-lock reset-icon"></i>
                    <h3>Create New Password</h3>
                    <p style="color: rgba(255, 255, 255, 0.6);">Your identity has been verified. Set your new password.
                    </p>
                    <p id="session-timer" style="color: #0ff47a; font-size: 0.8rem;">Session expires in: <span
                            id="countdown">05:00</span></p>
                </div>

                <form id="password-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                    novalidate>
                    <div class="position-relative">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Enter new password" required>
                        <span class="input-icon password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <div class="password-strength">
                        <div class="d-flex justify-content-between">
                            <small>Password Strength</small>
                            <small id="strength-text">Weak</small>
                        </div>
                        <div class="strength-meter" id="strength-meter"></div>
                        <div class="requirements mt-2">
                            <div class="requirement" id="length">
                                <i class="fas fa-times-circle"></i> At least 8 characters
                            </div>
                            <div class="requirement" id="uppercase">
                                <i class="fas fa-times-circle"></i> At least 1 uppercase letter
                            </div>
                            <div class="requirement" id="lowercase">
                                <i class="fas fa-times-circle"></i> At least 1 lowercase letter
                            </div>
                            <div class="requirement" id="number">
                                <i class="fas fa-times-circle"></i> At least 1 number
                            </div>
                            <div class="requirement" id="special">
                                <i class="fas fa-times-circle"></i> At least 1 special character
                            </div>
                        </div>
                    </div>

                    <div class="position-relative mt-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                            placeholder="Confirm your new password" required>
                        <span class="input-icon password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div id="password-match-message" class="mt-1" style="font-size: 0.8rem; display: none;">
                        <i class="fas fa-exclamation-circle"></i> Passwords do not match
                    </div>

                    <button type="submit" name="update_password" id="submit-btn" class="btn btn-neon w-100 mt-4">
                        <i class="fas fa-save me-2"></i> Update Password
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.removeAttribute('style');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthMeter = document.getElementById('strength-meter');
        const strengthText = document.getElementById('strength-text');
        const passwordStrength = document.querySelector('.password-strength');
        const passwordMatchMessage = document.getElementById('password-match-message');
        const submitBtn = document.getElementById('submit-btn');
        const passwordForm = document.getElementById('password-form');

        // Password requirements
        const requirements = {
            length: {
                regex: /.{8,}/,
                element: document.getElementById('length')
            },
            uppercase: {
                regex: /[A-Z]/,
                element: document.getElementById('uppercase')
            },
            lowercase: {
                regex: /[a-z]/,
                element: document.getElementById('lowercase')
            },
            number: {
                regex: /[0-9]/,
                element: document.getElementById('number')
            },
            special: {
                regex: /[^A-Za-z0-9]/,
                element: document.getElementById('special')
            }
        };

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const value = this.value;

                // Show password strength meter
                if (value.length > 0) {
                    passwordStrength.style.display = 'block';
                } else {
                    passwordStrength.style.display = 'none';
                    return;
                }

                // Check each requirement
                let strength = 0;
                for (const [key, requirement] of Object.entries(requirements)) {
                    const isValid = requirement.regex.test(value);
                    const icon = requirement.element.querySelector('i');

                    if (isValid) {
                        requirement.element.classList.add('met');
                        icon.className = 'fas fa-check-circle';
                        strength++;
                    } else {
                        requirement.element.classList.remove('met');
                        icon.className = 'fas fa-times-circle';
                    }
                }

                // Update strength meter
                let percentage = (strength / 5) * 100;
                strengthMeter.style.width = percentage + '%';

                // Update strength text and color
                if (strength <= 2) {
                    strengthText.textContent = 'Weak';
                    strengthMeter.style.backgroundColor = '#ff3b8d'; // cosmic-pink
                } else if (strength <= 4) {
                    strengthText.textContent = 'Medium';
                    strengthMeter.style.backgroundColor = '#6c43f5'; // stellar-purple
                } else {
                    strengthText.textContent = 'Strong';
                    strengthMeter.style.backgroundColor = '#0ff47a'; // neon-green
                }

                // Check if passwords match
                checkPasswordMatch();
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    passwordMatchMessage.style.display = 'none';
                    confirmPasswordInput.style.borderColor = '#0ff47a'; // neon-green
                    submitBtn.disabled = false;
                } else {
                    passwordMatchMessage.style.display = 'block';
                    passwordMatchMessage.style.color = '#ff3b8d'; // cosmic-pink
                    confirmPasswordInput.style.borderColor = '#ff3b8d'; // cosmic-pink
                    submitBtn.disabled = true;
                }
            } else {
                passwordMatchMessage.style.display = 'none';
                confirmPasswordInput.style.borderColor = '';
            }
        }

        if (passwordForm) {
            // Form client-side validation 
            passwordForm.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Server-side validation will handle most cases,
                // but we'll do some basic client-side validation too
                if (!password || !confirmPassword || password !== confirmPassword) {
                    e.preventDefault();
                    return false;
                }

                // Check password strength
                let allRequirementsMet = true;
                for (const [key, requirement] of Object.entries(requirements)) {
                    if (!requirement.regex.test(password)) {
                        allRequirementsMet = false;
                        break;
                    }
                }

                if (!allRequirementsMet) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Session countdown timer
        const countdownEl = document.getElementById('countdown');
        if (countdownEl) {
            let timeLeft = 300; // 5 minutes in seconds

            const countdownInterval = setInterval(function() {
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = "reset-password.php";
                    return;
                }

                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;

                countdownEl.textContent =
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                // Change color when less than 1 minute remains
                if (timeLeft < 60) {
                    countdownEl.style.color = '#ff3b8d'; // cosmic-pink
                }

                timeLeft--;
            }, 1000);
        }
    });

    // Toggle password visibility
    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        if (!passwordInput) return;

        const icon = passwordInput.nextElementSibling.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    <?php if (isset($_SESSION['password_reset_success']) && $_SESSION['password_reset_success']): ?>
    // Redirect to login page after password reset success
    setTimeout(function() {
        window.location.href = "login.php";
    }, 3000);
    <?php unset($_SESSION['password_reset_success']); ?>
    <?php endif; ?>
    </script>
</body>

</html>