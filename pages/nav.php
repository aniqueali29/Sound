<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Transition on Scroll</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 200vh;
            /* For scrolling demonstration */
            background-color: #f5f5f5;
        }

        /* First Navbar Styles - Always Transparent */
        .header-area {
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999;
            transition: all 0.5s ease;
            background-color: transparent;
        }

        .oneMusic-main-menu {
            background-color: transparent;
            padding: 0;
            transition: all 0.5s ease;
        }

        .classy-nav-container {
            position: relative;
            z-index: 100;
        }

        .classy-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
            padding: 0 15px;
        }

        .nav-brand img {
            max-height: 50px;
        }

        .classy-menu {
            position: relative;
        }

        .classynav ul {
            display: flex;
            list-style: none;
        }

        .classynav>ul>li {
            margin-right: 15px;
            position: relative;
        }

        .classynav ul li a {
            color: #ffffff;
            font-weight: 500;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .dropdown {
            position: absolute;
            background-color: #fff;
            min-width: 200px;
            top: 100%;
            left: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 100;
        }

        .dropdown li a {
            color: #333 !important;
            padding: 10px 20px;
            font-size: 14px;
        }

        .classynav ul li:hover>.dropdown {
            display: block;
        }

        .dropdown .dropdown {
            left: 100%;
            top: 0;
        }

        .login-register-cart-button {
            display: flex;
            align-items: center;
        }

        .login-register-btn a {
            color: #ffffff;
            text-decoration: none;
            margin-right: 30px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .cart-btn p {
            color: #ffffff;
            position: relative;
        }

        .cart-btn .quantity {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: #f8b600;
            color: #000;
            font-size: 12px;
            padding: 0 5px;
            border-radius: 50%;
        }

        /* Second Navbar Styles */
        .navbar {
            position: fixed;
            top: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: auto;
            opacity: 0;
            visibility: hidden;
            transition: all 0.5s ease;
        }

        .nav-container {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 3rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            position: relative;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            padding: 0.75rem 1.25rem;
            border-radius: 2rem;
            transition: all 0.3s ease;
        }

        .nav-item.active a,
        .nav-item:hover a {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Icons classes */
        .fa-solid {
            width: 20px;
            height: 20px;
            display: inline-block;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            filter: brightness(0) invert(1);
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .fa-house {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 576 512'%3E%3Cpath d='M575.8 255.5c0 18-15 32.1-32 32.1l-32 0 .7 160.2c0 2.7-.2 5.4-.5 8.1l0 16.2c0 22.1-17.9 40-40 40l-16 0c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1L416 512l-24 0c-22.1 0-40-17.9-40-40l0-24 0-64c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32 14.3-32 32l0 64 0 24c0 22.1-17.9 40-40 40l-24 0-31.9 0c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2l-16 0c-22.1 0-40-17.9-40-40l0-112c0-.9 0-1.9 .1-2.8l0-69.7-32 0c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z'/%3E%3C/svg%3E");
        }

        .fa-user {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath d='M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z'/%3E%3C/svg%3E");
        }

        .fa-briefcase {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath d='M184 48l144 0c4.4 0 8 3.6 8 8l0 40L176 96l0-40c0-4.4 3.6-8 8-8zm-56 8l0 40L64 96C28.7 96 0 124.7 0 160l0 96 192 0 128 0 192 0 0-96c0-35.3-28.7-64-64-64l-64 0 0-40c0-30.9-25.1-56-56-56L184 0c-30.9 0-56 25.1-56 56zM512 288l-192 0 0 32c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32l0-32L0 288 0 416c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-128z'/%3E%3C/svg%3E");
        }

        .fa-bolt {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath d='M349.4 44.6c5.9-13.7 1.5-29.7-10.6-38.5s-28.6-8-39.9 1.8l-256 224c-10 8.8-13.6 22.9-8.9 35.3S50.7 288 64 288l111.5 0L98.6 467.4c-5.9 13.7-1.5 29.7 10.6 38.5s28.6 8 39.9-1.8l256-224c10-8.8 13.6-22.9 8.9-35.3s-16.6-20.7-30-20.7l-111.5 0L349.4 44.6z'/%3E%3C/svg%3E");
        }

        .nav-item.active .fa-solid,
        .nav-item:hover .fa-solid {
            opacity: 1;
        }

        /* Responsive Styles for second navbar */
        @media (max-width: 768px) {
            .navbar {
                top: 1.5rem;
            }

            .nav-container {
                padding: 0.6rem 0.8rem;
            }

            .nav-item a {
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }

            .fa-solid {
                width: 16px;
                height: 16px;
            }
        }

        @media (max-width: 480px) {
            .nav-container {
                padding: 0.4rem 0.2rem;
            }

            .nav-links {
                gap: 0.5rem;
            }

            .nav-item a {
                font-size: 0.6rem;
                padding: 0.4rem 0.7rem;
            }

            .fa-solid {
                width: 12px;
                height: 12px;
            }
        }

        /* Demo content */
        .content {
            padding-top: 200px;
            padding-bottom: 50px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .content h1 {
            margin-bottom: 30px;
        }

        .section {
            height: 500px;
            margin: 50px 0;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        /* Add a background to the hero section for better visibility of transparent nav */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.3)), url('https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- First Navbar (Always Transparent) -->
    <header class="header-area">
        <!-- Navbar Area -->
        <div class="oneMusic-main-menu">
            <div class="classy-nav-container breakpoint-off">
                <div class="container">
                    <!-- Menu -->
                    <nav class="classy-navbar justify-content-between" id="oneMusicNav">

                        <!-- Nav brand -->
                        <a href="index.php" class="nav-brand"><img src="img/core-img/logo.png" alt=""></a>

                        <!-- Navbar Toggler -->
                        <div class="classy-navbar-toggler">
                            <span class="navbarToggler"><span></span><span></span><span></span></span>
                        </div>

                        <!-- Menu -->
                        <div class="classy-menu">

                            <!-- Close Button -->
                            <div class="classycloseIcon">
                                <div class="cross-wrap"><span class="top"></span><span class="bottom"></span></div>
                            </div>

                            <!-- Nav Start -->
                            <div class="classynav">
                                <ul>
                                    <li><a href="index.php">Home</a></li>
                                    <li><a href="albums-store.php">Albums</a></li>
                                    <li><a href="#">Pages</a>
                                        <ul class="dropdown">
                                            <li><a href="index.php">Home</a></li>
                                            <li><a href="albums-store.php">Albums</a></li>
                                            <li><a href="event.php">Events</a></li>
                                            <li><a href="blog.php">News</a></li>
                                            <li><a href="contact.php">Contact</a></li>
                                            <li><a href="elements.php">Elements</a></li>
                                            <li><a href="login.php">Login</a></li>
                                            <li><a href="#">Dropdown</a>
                                                <ul class="dropdown">
                                                    <li><a href="#">Even Dropdown</a></li>
                                                    <li><a href="#">Even Dropdown</a></li>
                                                    <li><a href="#">Even Dropdown</a></li>
                                                    <li><a href="#">Even Dropdown</a>
                                                        <ul class="dropdown">
                                                            <li><a href="#">Deeply Dropdown</a></li>
                                                            <li><a href="#">Deeply Dropdown</a></li>
                                                            <li><a href="#">Deeply Dropdown</a></li>
                                                            <li><a href="#">Deeply Dropdown</a></li>
                                                            <li><a href="#">Deeply Dropdown</a></li>
                                                        </ul>
                                                    </li>
                                                    <li><a href="#">Even Dropdown</a></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                    <li><a href="event.php">Events</a></li>
                                    <li><a href="blog.php">News</a></li>
                                    <li><a href="contact.php">Contact</a></li>
                                </ul>

                                <!-- Login/Register & Cart Button -->
                                <div class="login-register-cart-button d-flex align-items-center">
                                    <!-- Login/Register -->
                                    <div class="login-register-btn mr-50">
                                        <a href="login.php" id="loginBtn">Login / Register</a>
                                    </div>

                                    <!-- Cart Button -->
                                    <div class="cart-btn">
                                        <p><span class="icon-shopping-cart"></span> <span class="quantity">1</span></p>
                                    </div>
                                </div>
                            </div>
                            <!-- Nav End -->

                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Second Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Navigation Links -->
            <ul class="nav-links">
                <li class="nav-item active">
                    <a href="./index.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./about.php">
                        <i class="fa-solid fa-user"></i>
                        <span>About</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./my_work.php">
                        <i class="fa-solid fa-briefcase"></i>
                        <span>Works</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./contact.php">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Contact</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section with Background for Demo -->
    <div class="hero-section">
        <div>
            <h1>Welcome to Our Site</h1>
            <p>Scroll down to see the navigation change</p>
        </div>
    </div>

    <!-- Demo Content -->
    <div class="content">
        <h1>Content Section</h1>
        <p>As you scroll, the navigation bar changes.</p>

        <div class="section">Section 1</div>
        <div class="section">Section 2</div>
        <div class="section">Section 3</div>
    </div>

    <script>
        // Get navbar elements
        const headerArea = document.querySelector('.header-area');
        const secondNavbar = document.querySelector('.navbar');

        // Set threshold for when to show/hide navbars (in pixels)
        const SCROLL_THRESHOLD = 100;

        // Function to handle scroll events
        function handleScroll() {
            const scrollPosition = window.scrollY;

            // Show/hide navbars based on scroll position
            if (scrollPosition <= SCROLL_THRESHOLD) {
                // At the top - only show first navbar
                headerArea.style.opacity = '1';
                headerArea.style.visibility = 'visible';
                secondNavbar.style.opacity = '0';
                secondNavbar.style.visibility = 'hidden';
            } else {
                // When scrolling starts - hide first navbar, show second navbar
                headerArea.style.opacity = '0';
                headerArea.style.visibility = 'hidden';
                secondNavbar.style.opacity = '1';
                secondNavbar.style.visibility = 'visible';
            }
        }

        // Listen for scroll events
        window.addEventListener('scroll', handleScroll);

        // Initialize on page load
        handleScroll();
    </script>
</body>

</html>