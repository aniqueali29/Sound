<?php
session_start();
require '../includes/config_db.php'; // Database connection

// Register User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation: Check if fields are empty
    if (empty($username) || empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'All fields are required.';
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Username Validation (5-20 characters, letters, numbers, underscores, must start with a letter)
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{4,19}$/', $username)) {
        $_SESSION['error'] = 'Username must be 5-20 characters long and start with a letter. Only letters, numbers, and underscores are allowed.';
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Name Validation (2-50 characters, only letters and spaces)
    if (!preg_match('/^[a-zA-Z ]{2,50}$/', $name)) {
        $_SESSION['error'] = 'Full name must be 2-50 characters long and only contain letters and spaces.';
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Email Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Password Validation (Min 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character)
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $_SESSION['error'] = 'Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.';
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Check if user already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Email or username already exists.';
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    $check->close();

    // Hash password and store
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $name, $email, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Account created successfully. Please login.';
            $_SESSION['show_register'] = false; // Ensure login tab is shown
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = 'Database error: ' . $conn->error;
            $_SESSION['show_register'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        $_SESSION['show_register'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Login User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Both email and password are required.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../index.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid credentials or account not found.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Login error: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Set active tab based on session
$activeTab = 'login';
if (isset($_SESSION['show_register']) && $_SESSION['show_register']) {
    $activeTab = 'register';
    unset($_SESSION['show_register']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>SOUND Entertainment | Login & Sign Up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Quicksand:wght@300;400;500;600&display=swap');

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
            background-color: #060b19;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(91, 2, 154, 0.2) 0%, rgba(0, 0, 0, 0) 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 65, 108, 0.2) 0%, rgba(0, 0, 0, 0) 40%);
            color: #fff;
            font-family: 'Quicksand', sans-serif;
            overflow-x: hidden;
        }

        .particles {
            position: fixed;
            width: 100vw;
            height: 100vh;
            z-index: -999;
            top: 0;
            left: 0;
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

        .logo-text {
            font-weight: 700;
            letter-spacing: 2px;
            font-size: 2.2rem;
            margin: 0;
            color: #ff7b54;
            position: relative;
            display: inline-block;
            text-align: center;
            font-size: 2.5rem;
            text-shadow: 0 0 15px rgba(255, 123, 84, 0.7);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 4px;
            position: relative;
        }

        .logo-text::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #ff7b54, transparent);
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
        }

        .logo-text::after {
            content: '';
            display: block;
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 4px;
            text-align: right;
            margin-top: -12px;
            color: #fff;
            -webkit-text-fill-color: #fff;
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

        .form-control.is-invalid {
            background-color: rgba(255, 59, 141, 0.2);
            border-color: var(--neon-accent);
        }

        .btn-primary {
            background: #8c9eff;
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

        .btn-primary:hover {
            background: #7986cb;
            /* Slightly darker shade for hover */
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(140, 158, 255, 0.4);
        }

        .btn-primary::after {
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

        .btn-primary:hover::after {
            animation: btnShine 1.5s ease;
        }

        .tab-toggle {
            display: flex;
            margin-bottom: 1.5rem;
            position: relative;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 4px;
        }

        .tab-btn {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-color);
            padding: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            border-radius: 8px;
            background-color: transparent;
            color: #8c9eff;
            border: 2px solid #8c9eff;
            
        }

        .tab-btn:hover {
            background-color: #8c9eff;
            color: var(--deep-space);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(140, 158, 255, 0.4);
        }

        .tab-toggle::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            width: calc(50% - 4px);
            height: calc(100% - 8px);
            background: #8c9eff;
            border-radius: 8px;
            transition: all 0.3s ease;
            z-index: 0;
        }

        .tab-toggle.register-active::after {
            left: calc(50% + 0px);
        }

        .tab-btn.active {
            color: #fff;
        }

        .form-container {
            position: relative;
            height: 310px;
            overflow: hidden;
        }

        .form-wrapper {
            position: absolute;
            width: 100%;
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
        }

        #login-form {
            transform: translateX(0);
            opacity: 1;
        }

        #register-form {
            transform: translateX(100%);
            opacity: 0;
        }

        .form-container.show-register #login-form {
            transform: translateX(-100%);
            opacity: 0;
        }

        .form-container.show-register #register-form {
            transform: translateX(0);
            opacity: 1;
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

        .position-relative {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 70%;
            right: 12px;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--neon-orange);
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .form-check-input:checked {
            background-color: var(--neon-orange);
            border-color: var(--neon-orange);
        }

        a.text-decoration-none {
            font-size: 0.85rem;
            color: var(--neon-blue);
            transition: color 0.3s ease;
        }

        a.text-decoration-none:hover {
            color: var(--neon-orange);
        }

        .row {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }

        .col-md-6 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
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

            .form-container {
                height: 330px;
            }

            .form-wrapper {
                position: relative;
            }

            #register-form {
                display: none;
            }

            .form-container.show-register #login-form {
                display: none;
            }

            .form-container.show-register #register-form {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="particles"></div>
    <!-- Floating music notes decoration -->
    <div class="music-note note-1"><i class="fas fa-music"></i></div>
    <div class="music-note note-2"><i class="fas fa-music"></i></div>
    <div class="music-note note-3"><i class="fas fa-headphones"></i></div>

    <div class="auth-container">
        <div class="logo">
            <h1 class="logo-text">SOUND</h1>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="tab-toggle <?php echo $activeTab == 'register' ? 'register-active' : ''; ?>">
            <button type="button" class="tab-btn <?php echo $activeTab == 'login' ? 'active' : ''; ?>" id="login-tab" style="margin-right: 10px;">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
            <button type="button" class="tab-btn <?php echo $activeTab == 'register' ? 'active' : ''; ?>" id="register-tab">
                <i class="fas fa-user-plus me-2"></i> Sign Up
            </button>
        </div>

        <div class="form-container <?php echo $activeTab == 'register' ? 'show-register' : ''; ?>">
            <!-- Login Form -->
            <div class="form-wrapper" id="login-form">
                <form method="POST" action="" novalidate>
                    <input type="hidden" name="login" value="1">

                    <div class="position-relative">
                        <label for="login-email" class="form-label">Email Address</label>
                        <input type="email" id="login-email" name="email" class="form-control" placeholder="Enter your email">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    </div>

                    <div class="position-relative">
                        <label for="login-password" class="form-label">Password</label>
                        <input type="password" id="login-password" name="password" class="form-control" placeholder="Enter your password">
                        <span class="input-icon password-toggle" onclick="togglePassword('login-password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember-me">
                            <label class="form-check-label" for="remember-me">Remember me</label>
                        </div>
                        <a href="./reset-password.php" class="text-decoration-none">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </form>
            </div>

            <!-- Registration Form -->
            <div class="form-wrapper" id="register-form">
                <form method="POST" action="" novalidate>
                    <input type="hidden" name="register" value="1">

                    <div class="row">
                        <div class="col-md-6 position-relative">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter username">
                            <span class="input-icon" style="top:55%; margin-right: 13px; "><i class="fas fa-user"></i></span>
                        </div>

                        <div class="col-md-6 position-relative">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="Enter full name">
                            <span class="input-icon" style="top:56%; margin-right:15px;"><i class="fas fa-id-card"></i></span>
                        </div>
                    </div>

                    <div class="position-relative">
                        <label for="register-email" class="form-label">Email</label>
                        <input type="email" id="register-email" name="email" class="form-control" placeholder="Enter your email">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    </div>

                    <div class="position-relative">
                        <label for="register-password" class="form-label">Password</label>
                        <input type="password" id="register-password" name="password" class="form-control" placeholder="Create a password">
                        <span class="input-icon password-toggle" onclick="togglePassword('register-password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-user-plus me-2"></i> Sign Up
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');
            const formContainer = document.querySelector('.form-container');
            const tabToggle = document.querySelector('.tab-toggle');

            // Dynamic Particle Effect
            function createParticles() {
                const container = document.querySelector('.particles');
                for (let i = 0; i < 100; i++) {
                    const particle = document.createElement('div');
                    particle.style.cssText = `
                position: absolute;
                width: 2px;
                height: 2px;
                background: var(--neon-green);
                border-radius: 50%;
                top: ${Math.random() * 100}vh;
                left: ${Math.random() * 100}vw;
                animation: particle-float ${5 + Math.random() * 10}s infinite;
            `;
                    container.appendChild(particle);
                }
            }
            createParticles();

            // CSS keyframes for particle animation
            const styleSheet = document.createElement('style');
            styleSheet.type = 'text/css';
            styleSheet.innerText = `
        @keyframes particle-float {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-100px); opacity: 0; }
        }
    `;
            document.head.appendChild(styleSheet);

            // Check session status for registration success
            <?php if (isset($_SESSION['show_register']) && $_SESSION['show_register'] == false): ?>
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                formContainer.classList.remove('show-register');
                tabToggle.classList.remove('register-active');
                <?php unset($_SESSION['show_register']); ?>
            <?php endif; ?>

            loginTab.addEventListener('click', function() {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                formContainer.classList.remove('show-register');
                tabToggle.classList.remove('register-active');
            });

            registerTab.addEventListener('click', function() {
                registerTab.classList.add('active');
                loginTab.classList.remove('active');
                formContainer.classList.add('show-register');
                tabToggle.classList.add('register-active');
            });

            // Adjusting form container height for mobile
            function adjustFormHeight() {
                if (window.innerWidth <= 576) {
                    formContainer.style.height = 'auto';
                } else {
                    // Set appropriate height based on the taller form
                    const loginHeight = document.getElementById('login-form').scrollHeight;
                    const registerHeight = document.getElementById('register-form').scrollHeight;
                    formContainer.style.height = Math.max(loginHeight, registerHeight) + 'px';
                }
            }

            // Run on load and resize
            adjustFormHeight();
            window.addEventListener('resize', adjustFormHeight);
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
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
    </script>
</body>

</html>