<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modern Video Section with Enlarged Card</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome for Play Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .videos-section {
            padding: 80px 20px;
            text-align: center;
            background-color: #121212;
        }

        .videos-section .section-subtitle {
            color: #ff5252;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .videos-section .section-title {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 40px;
            letter-spacing: -1px;
            line-height: 1.2;
            color: white !important;
        }

        /* Video Grid Layout */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Video Card Styling */
        .video-card {
            position: relative;
            border: 2px solid transparent;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s ease, border 0.3s ease, box-shadow 0.3s ease;
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            height: 100%;
        }

        .video-card:hover {
            transform: scale(1.04);
            border: 2px solid #ff5252;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.7);
        }

        .video-wrapper {
            position: relative;
            width: 100%;
            height: 400px;
            background: #000;
        }

        .video-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Modern Rating Styling */
        .video-rating {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            padding: 8px 12px;
            z-index: 3;
            display: flex;
            flex-direction: row;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transform: translateY(0);
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .video-card:hover .video-rating {
            background: rgba(0, 0, 0, 0.8);
            transform: translateY(3px);
        }

        .video-card:hover .rating-stars i {
            transform: scale(1.1);
        }

        .rating-number {
            color: white;
            font-size: 14px;
            font-weight: 700;
            background: linear-gradient(45deg, #ff5252, #FFA41B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.5px;
        }

        /* Overlay for Title and Subtitle */
        .video-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 15px 20px;
            background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.9));
            z-index: 2;
            text-align: left;
        }

        .video-overlay .video-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: white !important;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .video-overlay .video-channel {
            margin: 4px 0 0;
            font-size: 0.95rem;
            opacity: 0.8;
            color: white !important;
        }

        /* Play Icon Overlay */
        .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 82, 82, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease, background 0.3s ease;
            z-index: 2;
        }

        .play-overlay i {
            font-size: 24px;
            color: white;
        }

        .video-card:hover .play-overlay {
            opacity: 1;
            background: rgba(255, 82, 82, 0.9);
        }

        /* Loading state */
        .loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #000;
            z-index: 3;
            color: #fff;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .video-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .video-wrapper {
                height: 350px;
            }
        }

        @media (max-width: 992px) {
            .videos-section {
                padding: 60px 15px;
            }

            .videos-section .section-title {
                font-size: 36px;
                margin-bottom: 30px;
            }

            .video-grid {
                gap: 20px;
            }

            .video-wrapper {
                height: 320px;
            }
        }

        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .video-wrapper {
                height: 300px;
            }

            .video-overlay .video-title {
                font-size: 1.1rem;
            }

            .video-overlay .video-channel {
                font-size: 0.85rem;
            }

            .play-overlay {
                width: 50px;
                height: 50px;
            }

            .play-overlay i {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .videos-section {
                padding: 40px 10px;
            }

            .videos-section .section-title {
                font-size: 30px;
                margin-bottom: 25px;
            }

            .videos-section .section-subtitle {
                font-size: 14px;
                letter-spacing: 2px;
            }

            .video-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 0 auto;
                gap: 15px;
            }

            .video-wrapper {
                height: 280px;
            }
        }
    </style>
</head>

<body>
    <!-- Modern Video Section with Enlarged Card -->
    <section class="videos-section">
    <div class="container">
        <div class="section-subtitle">Watch Now</div>
        <h2 class="section-title">Latest Videos</h2>
        <div class="video-grid">
            <!-- Video Card 1 -->
            <div class="video-card">
                <div class="video-wrapper">
                    <div class="loading">Loading...</div>
                    <video class="video-preview" muted playsinline
                        data-src="https://www.w3schools.com/html/mov_bbb.mp4"></video>
                    <div class="play-overlay"><i class="fas fa-play"></i></div>
                    <!-- Rating -->
                    <div class="video-rating">
                        <span class="rating-number">4.5/5</span>
                    </div>
                    <!-- Overlay with Title and Channel over video -->
                    <div class="video-overlay">
                        <p class="video-title">Epic Journey</p>
                        <p class="video-channel">Channel One</p>
                    </div>
                </div>
            </div>
            <!-- Video Card 2 -->
            <div class="video-card">
                <div class="video-wrapper">
                    <div class="loading">Loading...</div>
                    <video class="video-preview" muted playsinline
                        data-src="https://www.w3schools.com/html/movie.mp4"></video>
                    <div class="play-overlay"><i class="fas fa-play"></i></div>
                    <!-- Rating -->
                    <div class="video-rating">
                        <span class="rating-number">3.6/5</span>
                    </div>
                    <div class="video-overlay">
                        <p class="video-title">Night Vibes</p>
                        <p class="video-channel">Urban Beats</p>
                    </div>
                </div>
            </div>
            <!-- Video Card 3 -->
            <div class="video-card">
                <div class="video-wrapper">
                    <div class="loading">Loading...</div>
                    <video class="video-preview" muted playsinline
                        data-src="https://www.w3schools.com/html/mov_bbb.mp4"></video>
                    <div class="play-overlay"><i class="fas fa-play"></i></div>
                    <!-- Rating -->
                    <div class="video-rating">
                        <span class="rating-number">5.0/5</span>
                    </div>
                    <div class="video-overlay">
                        <p class="video-title">Sky High</p>
                        <p class="video-channel">Aerial Views</p>
                    </div>
                </div>
            </div>
            <!-- Video Card 4 -->
            <div class="video-card">
                <div class="video-wrapper">
                    <div class="loading">Loading...</div>
                    <video class="video-preview" muted playsinline
                        data-src="https://www.w3schools.com/html/movie.mp4"></video>
                    <div class="play-overlay"><i class="fas fa-play"></i></div>
                    <!-- Rating -->
                    <div class="video-rating">
                        <span class="rating-number">3.1/5</span>
                    </div>
                    <div class="video-overlay">
                        <p class="video-title">Rhythm Pulse</p>
                        <p class="video-channel">Beat Lab</p>
                    </div>
                </div>
            </div>
        </div><!-- End Video Grid -->
    </div><!-- End Container -->
</section>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load videos and generate thumbnails from the videos themselves
        const videoCards = document.querySelectorAll('.video-card');

        videoCards.forEach(card => {
            const video = card.querySelector('.video-preview');
            const loadingIndicator = card.querySelector('.loading');
            const dataSrc = video.getAttribute('data-src');

            // Set the actual source
            video.src = dataSrc;

            // When metadata is loaded, we can seek to a position to create a thumbnail
            video.addEventListener('loadedmetadata', () => {
                // Seek to a position in the video (e.g., 25% through)
                // This will be used as our thumbnail
                video.currentTime = video.duration * 0.25;
            });

            // Once we've seeked to the position, the video shows that frame
            video.addEventListener('seeked', () => {
                // Remove loading indicator
                loadingIndicator.style.display = 'none';
            });

            // Handle errors
            video.addEventListener('error', () => {
                loadingIndicator.textContent = 'Error loading video';
            });

            // On hover, play the video
            card.addEventListener('mouseenter', () => {
                video.play();
            });

            // On mouse leave, pause and reset the video to thumbnail position
            card.addEventListener('mouseleave', () => {
                video.pause();
                // Reset to thumbnail position, not beginning
                video.currentTime = video.duration * 0.25;
            });
        });
    </script>
</body>

</html>