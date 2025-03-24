    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <!-- Column 1: Brand Info -->
                <div class="footer-column">
                    <h3>SOUND Entertainment</h3>
                    <p>Discover cutting-edge music and immersive videos across genres. Join millions of music
                        enthusiasts in a revolutionary audio-visual experience.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-spotify"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-soundcloud"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-apple"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-column">
                    <h3>Explore</h3>
                    <ul class="footer-links">
                        <li><a href="./music.php">Musics</a></li>
                        <li><a href="./video.php">Videos</a></li>
                        <li><a href="./albums.php">Albums</a></li>
                        <li><a href="./artists.php">Artist</a></li>
                    </ul>
                </div>

                <!-- Column 3: Spotlight Artists -->
                <div class="footer-column">
                    <h3>Artist Spotlight</h3>
                    <ul class="footer-links artist-list">
                        <li><a href="./music.php">Neon Pulse ↗</a><span>Electronic</span></li>
                        <li><a href="./music.php">Maya Thorne ↗</a><span>Indie Pop</span></li>
                        <li><a href="./music.php">Echo Valley ↗</a><span>Hip-Hop</span></li>
                        <li><a href="./music.php">Aqua Dreams ↗</a><span>Ambient</span></li>
                    </ul>
                </div>

                <!-- Column 4: Video Hub -->
                <div class="footer-column">
                    <h3>Video Hub</h3>
                    <ul class="footer-links video-list">
                        <li><a href="./video.php">Behind the Beats</a><i class="fas fa-video"></i></li>
                        <li><a href="./video.php">Studio Sessions</a><i class="fas fa-headphones"></i></li>
                        <li><a href="./video.php">Live Performances</a><i class="fas fa-microphone"></i></li>
                        <li><a href="./video.php">Dance Covers</a><i class="fas fa-bolt"></i></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 SOUND Entertainment. All rights reserved. <span class="highlight">#FeelTheVibe</span></p>
            </div>
        </div>
    </footer>
    </body>
    <!-- JavaScript -->
    <script>
/**
 * Main JavaScript functionality for website
 */

// Wait for DOM to be fully loaded before executing any code
document.addEventListener("DOMContentLoaded", function() {
    // Initialize all UI components
    initProfileDropdown();
    initNavigationMenu();
    initTabSystem();

    // Set up window event listeners
    setupWindowEvents();
});

/**
 * Profile dropdown functionality
 */
function initProfileDropdown() {
    const profileBtn = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");

    if (!profileBtn || !dropdownMenu) return;

    // Toggle dropdown when profile button is clicked
    profileBtn.addEventListener("click", function(event) {
        event.stopPropagation();
        dropdownMenu.classList.toggle("show");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function(event) {
        if (!profileBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove("show");
        }
    });
}

/**
 * Navigation menu functionality
 */
function initNavigationMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const firstNavbar = document.querySelector('header');
    const secondNavbar = document.querySelector('.navbarstwo');

    if (!menuToggle || !navMenu) return;

    // Toggle menu on hamburger icon click - FIX: Added more robust toggle handling
    menuToggle.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation(); // Prevent event bubbling

        // Toggle active class on both elements
        this.classList.toggle('active');
        navMenu.classList.toggle('active');

        // Adjust body padding based on menu state
        if (navMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
            document.body.style.paddingTop = firstNavbar ? firstNavbar.offsetHeight + 'px' : '';
        } else {
            document.body.style.overflow = '';
            document.body.style.paddingTop = '';
        }
    });

    // Close menu when clicking outside - FIX: Improved click detection
    document.addEventListener('click', function(event) {
        // Only process if menu is currently active
        if (navMenu.classList.contains('active')) {
            // Check if click is outside both the toggle button and menu
            if (!menuToggle.contains(event.target) && !navMenu.contains(event.target)) {
                menuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
                document.body.style.paddingTop = '';
            }
        }
    });

    // Handle mobile auth buttons
    const mobileAuthButtons = document.querySelectorAll('.mobile-auth .nav-btn');
    mobileAuthButtons.forEach(button => {
        button.addEventListener('click', () => {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.style.overflow = '';
            document.body.style.paddingTop = '';
        });
    });

    // Navbar visibility on scroll
    if (firstNavbar && secondNavbar) {
        const SCROLL_THRESHOLD = 200;

        window.addEventListener('scroll', () => {
            const scrollPosition = window.scrollY;

            if (scrollPosition > SCROLL_THRESHOLD) {
                // Hide first navbar and show second
                firstNavbar.style.opacity = '0';
                firstNavbar.style.visibility = 'hidden';
                secondNavbar.style.opacity = '1';
                secondNavbar.style.visibility = 'visible';
            } else {
                // Show first navbar and hide second
                firstNavbar.style.opacity = '1';
                firstNavbar.style.visibility = 'visible';
                secondNavbar.style.opacity = '0';
                secondNavbar.style.visibility = 'hidden';
            }
        });
    }
}

/**
 * Tab system functionality
 */
function initTabSystem() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabButtons.length === 0) return;

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to selected tab
            const tabId = button.getAttribute('data-tab');
            button.classList.add('active');
            const tabContent = document.getElementById(`${tabId}-tab`);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });

    // Activate first tab by default
    if (tabButtons[0]) {
        tabButtons[0].click();
    }
}

/**
 * Window event handlers
 */
function setupWindowEvents() {
    // Handle window resize
    window.addEventListener('resize', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');

        if (menuToggle && navMenu && window.innerWidth > 768) {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.style.overflow = '';
            document.body.style.paddingTop = '';
        }
    });

    // Initialize particles on load
    window.addEventListener('load', createParticles);
}

/**
 * Particles animation system
 */
function createParticles() {
    const particles = document.getElementById('particles');
    if (!particles) return;

    const numberOfParticles = 50;

    for (let i = 0; i < numberOfParticles; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';

        // Random position
        const posX = Math.random() * window.innerWidth;
        const posY = Math.random() * window.innerHeight;
        particle.style.left = `${posX}px`;
        particle.style.top = `${posY}px`;

        // Random size
        const size = Math.random() * 2 + 1;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;

        // Random opacity
        particle.style.opacity = Math.random() * 0.5 + 0.3;

        // Animation settings
        const duration = Math.random() * 20 + 10;
        particle.style.animation = `float ${duration}s linear infinite`;

        particles.appendChild(particle);
        animate(particle);
    }
}

/**
 * Animate a single particle with random movement
 */
function animate(particle) {
    const moveX = Math.random() * 100 - 50;
    const moveY = Math.random() * 100 - 50;
    const duration = Math.random() * 20 + 10;

    particle.animate([{
            transform: 'translate(0, 0)'
        },
        {
            transform: `translate(${moveX}px, ${moveY}px)`
        }
    ], {
        duration: duration * 1000,
        iterations: Infinity,
        direction: 'alternate',
        easing: 'ease-in-out'
    });
}
    </script>
    </body>

    </html>