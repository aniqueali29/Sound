<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- The above 4 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- Title -->
    <title>One Music - Modern Music HTML5 Template</title>

    <!-- Favicon -->
    <link rel="icon" href="img/core-img/favicon.ico">

    <!-- fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Narrow:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

    <!-- Stylesheet -->
    <!-- <link rel="stylesheet" href="./css/nav.php"> -->
    <link rel="stylesheet" href="style.css">

    <style>
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
    z-index: 10012;
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
    </style>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader d-flex align-items-center justify-content-center">
        <div class="lds-ellipsis">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <!-- ##### Header Area Start ##### -->
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
                                    <!-- <li><a href="#">Pages</a>
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
                                    </li> -->
                                    <li><a href="./pages/music.php">Music</a></li>
                                    <li><a href="./pages/vidoe.php">Video</a></li>
                                    <li><a href="./pages/albums.php">Albums</a></li>
                                    <!-- <li><a href="contact.php">Contact</a></li> -->
                                </ul>

                                <!-- Login/Register & Cart Button -->
                                <div class="login-register-cart-button d-flex align-items-center">
                                    <!-- Login/Register -->
                                    <div class="login-register-btn mr-50">
                                        <a href="./pages/login.php" id="loginBtn">Login / Register</a>
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
    <!-- ##### Header Area End ##### -->
    <!-- second -->
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
                    <a href="./pages/music.php">
                    <i class="fa-solid fa-music"></i>
                        <span>Music</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./pages/video.php">
                    <i class="fa-solid fa-video"></i>
                        <span>Video</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="./pages/albums.php">
                    <i class="fa-solid fa-record-vinyl"></i>
                        <span>Albums</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>




    <!-- ##### Hero Area Start ##### -->
    <section class="hero-area">
        <div class="hero-slides owl-carousel">
            <!-- Single Hero Slide -->
            <div class="single-hero-slide d-flex align-items-center justify-content-center">
                <!-- Slide Img -->
                <div class="slide-img bg-img" style="background-image: url(img/bg-img/bg-1.jpg);"></div>
                <!-- Slide Content -->
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-slides-content text-center">
                                <h6 data-animation="fadeInUp" data-delay="100ms">Latest Album</h6>
                                <h2 data-animation="fadeInUp" data-delay="300ms">Discover Fresh Beats <span>Discover
                                        Fresh Beats</span>
                                </h2>
                                <a data-animation="fadeInUp" data-delay="500ms" href="#"
                                    class="btn oneMusic-btn mt-50">Discover <i class="fa fa-angle-double-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single Hero Slide -->
            <div class="single-hero-slide d-flex align-items-center justify-content-center">
                <!-- Slide Img -->
                <div class="slide-img bg-img" style="background-image: url(img/bg-img/bg-2.jpg);"></div>
                <!-- Slide Content -->
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-slides-content text-center">
                                <h6 data-animation="fadeInUp" data-delay="100ms">Beyond Time</h6>
                                <h2 data-animation="fadeInUp" data-delay="300ms">Timeless Videos <span>Timeless
                                        Videos</span></h2>
                                <a data-animation="fadeInUp" data-delay="500ms" href="#"
                                    class="btn oneMusic-btn mt-50">Discover <i class="fa fa-angle-double-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ##### Hero Area End ##### -->

    <!-- ##### Featured Album Start ##### -->
    <div class="featured-section">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="section_title_container text-center" style="padding-bottom:80px;">
                    <div class="section_subtitle">Events</div>
                    <div class="section_title">
                        <h1>Featured Album</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="container" style="margin:0 auto;">
            <div class="row featured_row mt-4">
                <!-- Featured Album Player -->
                <div class="col-md-6 order-md-1 featured_album_col">
                    <div class="featured_album_player_container">
                        <div class="featured_album_player">
                            <div
                                class="featured_album_title_bar d-flex flex-row align-items-center justify-content-between mb-4">
                                <div class="featured_album_title_container">
                                    <div class="featured_album_artist">Maria Smith</div>
                                    <div class="featured_album_title">Love is all Around</div>
                                </div>
                                <div class="featured_album_link">
                                    <a href="#">BUY IT ON ITUNES</a>
                                </div>
                            </div>

                            <div class="jp-playlist">
                                <ul>
                                    <li data-song-id="1" data-duration="2:33">
                                        <div>
                                            <a href="javascript:;" class="jp-playlist-item">
                                                <span class="play-icon">▶</span> Better Days
                                            </a>
                                            <div class="song_duration">2:33</div>
                                        </div>
                                    </li>
                                    <li data-song-id="2" data-duration="2:04">
                                        <div>
                                            <a href="javascript:;" class="jp-playlist-item">
                                                <span class="play-icon">▶</span> Dubstep
                                            </a>
                                            <div class="song_duration">2:04</div>
                                        </div>
                                    </li>
                                    <li class="jp-playlist-current" data-song-id="3" data-duration="2:20">
                                        <div>
                                            <a href="javascript:;" class="jp-playlist-item jp-playlist-current">
                                                <span class="play-icon">▶</span> Sunny
                                            </a>
                                            <div class="song_duration">2:20</div>
                                        </div>
                                    </li>
                                    <li data-song-id="4" data-duration="2:33">
                                        <div>
                                            <a href="javascript:;" class="jp-playlist-item">
                                                <span class="play-icon">▶</span> Better Days (Remix)
                                            </a>
                                            <div class="song_duration">2:33</div>
                                        </div>
                                    </li>
                                    <li data-song-id="5" data-duration="2:04">
                                        <div>
                                            <a href="javascript:;" class="jp-playlist-item">
                                                <span class="play-icon">▶</span> Dubstep (Club Mix)
                                            </a>
                                            <div class="song_duration">2:04</div>
                                        </div>
                                    </li>
                                    <li data-song-id="6" data-duration="2:20">
                                        <div>
                                            <a href="javascript:;" class="jp-playlist-item">
                                                <span class="play-icon">▶</span> Sunny Day
                                            </a>
                                            <div class="song_duration">2:20</div>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div id="player_now_playing" class="player_now_playing">
                                <div class="now_playing_title">
                                    <div id="current_song_title">Sunny</div>
                                </div>
                                <div id="play_button" class="play_button_large"></div>
                            </div>

                            <div class="player_controls">
                                <div class="time_controls d-flex">
                                    <div id="jp-current-time" class="jp-current-time">00:00</div>
                                    <div class="jp-progress">
                                        <div class="jp-seek-bar">
                                            <div id="jp-play-bar" class="jp-play-bar" style="width: 0%;"></div>
                                        </div>
                                    </div>
                                    <div id="jp-duration" class="jp-duration">2:20</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Featured Album Image -->
                <div class="col-md-6 order-md-2">
                    <div class="featured_album_image">
                        <div class="background_image" style="background-image:url('./img/bg-img/album_bg.jpg')">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ##### Featured Album End ##### -->


    <!-- ##### Video Section  Start ##### -->
    <section class="video-section">
        <!-- Section Heading -->
        <div class="section_title_container">
            <div class="section_subtitle">Watch Now</div>
            <div class="section_title">
                <h1>Featured Video</h1>
            </div>
        </div>
        <!-- Video Player and Playlist Layout -->
        <div class="container">
            <div class="row g-4">
                <!-- Video Player Column -->
                <div class="col-md-8 video-player-col">
                    <div class="custom-video-container">
                        <video id="customVideo" poster="https://via.placeholder.com/800x450.png?text=Featured+Video">
                            <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4" />
                            Your browser does not support HTML5 video.
                        </video>
                        <!-- Custom Controls -->
                        <div class="video-controls">
                            <button id="playPauseBtn">▶</button>
                            <div class="progress-container" id="progressContainer">
                                <div class="progress-bar" id="progressBar"></div>
                            </div>
                            <div class="time-display" id="timeDisplay">00:00 / 00:00</div>
                            <!-- Volume Controls now placed after time display -->
                            <div class="volume-controls">
                                <button id="muteBtn">🔊</button>
                                <input type="range" id="volumeSlider" min="0" max="1" step="0.05" value="1" />
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Video Playlist Column -->
                <div class="col-md-4 video-playlist-col">
                    <div class="video-playlist">
                        <ul>
                            <li class="active" data-video="https://www.w3schools.com/html/mov_bbb.mp4">
                                <strong>Big Buck Bunny</strong>
                                <small class="d-block">Sample Video 1</small>
                            </li>
                            <li data-video="https://www.w3schools.com/html/movie.mp4">
                                <strong>Bear Video</strong>
                                <small class="d-block">Sample Video 2</small>
                            </li>
                            <li data-video="https://www.w3schools.com/html/mov_bbb.mp4">
                                <strong>Another Sample</strong>
                                <small class="d-block">Sample Video 3</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ##### Video Section  End ##### -->

    <!-- ##### Latest Albums Area Start ##### -->
    <section class="albums-section">
        <div class="container">
            <div class="section-subtitle">See what's new</div>
            <h2 class="section-title">Latest Albums</h2>
            <p class="section-description">
                Discover our brand-new collection of albums, curated to bring you the best of what’s trending. From
                emerging artists to timeless legends, there’s something here for everyone.
            </p>
            <!-- Album Grid -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 alb-row">
                <!-- Album Card 1 -->
                <div class="col col-lg-3 col-md-6">
                    <div class="album-card modern-card position-relative">
                        <img src="./img/bg-img/a2.jpg" alt="Album Cover 2" class="album-image img-fluid" />
                        <!-- Rating Element -->
                        <div class="video-rating">
                            <span class="rating-number">4.5/5</span>
                        </div>
                        <div class="album-info text-white">
                            <h5 class="album-title">First</h5>
                            <p class="album-artist">by Singer Name</p>
                        </div>
                    </div>
                </div>
                <!-- Album Card 3 -->
                <div class="col col-lg-3 col-md-6 ">
                    <div class="album-card modern-card position-relative">
                        <img src="./img/bg-img/aaaa.jpg" alt="Album Cover 3" class="album-image img-fluid" />
                        <!-- Rating Element -->
                        <div class="video-rating">
                            <span class="rating-number">4.5/5</span>
                        </div>
                        <div class="album-info text-white">
                            <h5 class="album-title">Second Song</h5>
                            <p class="album-artist">by Indie Star</p>
                        </div>
                    </div>
                </div>
                <!-- Album Card 4 -->
                <div class="col  col-lg-3 col-md-6 ">
                    <div class="album-card modern-card position-relative">
                        <img src="./img/bg-img/a11.jpg" alt="Album Cover 4" class="album-image img-fluid" />
                        <!-- Rating Element -->
                        <div class="video-rating">
                            <span class="rating-number">4.5/5</span>
                        </div>
                        <div class="album-info text-white">
                            <h5 class="album-title">The Album</h5>
                            <p class="album-artist">by Rock Legends</p>
                        </div>
                    </div>
                </div>
                <!-- Album Card 5 -->
                <div class="col col-lg-3  ">
                    <div class="album-card modern-card position-relative">
                        <img src="./img/bg-img/a3.jpg" alt="Album Cover 5" class="album-image img-fluid" />
                        <!-- Rating Element -->
                        <div class="video-rating">
                            <span class="rating-number">4.5/5</span>
                        </div>
                        <div class="album-info text-white">
                            <h5 class="album-title">Unplugged</h5>
                            <p class="album-artist">by Acoustic Trio</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /row -->
        </div>
    </section>


    <!-- ##### Latest Albums Area End ##### -->

    <!-- ##### Latest videos Now Area Start ##### -->
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
    <!-- ##### Buy Now Area End ##### -->

    <!-- ##### Featured Artist Area Start ##### -->
    <section class="featured-artist-area section-padding-100 bg-img bg-overlay bg-fixed"
        style="background-color: black ;">
        <div class="container">
            <div class="row align-items-end">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="featured-artist-thumb" >
                        <img src="img/bg-img/fa.jpg" alt="" style="height: 300px; width: 100%; object-fit: cover" >
                    </div>
                </div>
                <div class="col-12 col-md-7 col-lg-8">
                    <div class="featured-artist-content">
                        <!-- Section Heading -->
                        <div class="section-heading white text-left mb-30">
                            <p>A New Era of Entertainment</p>
                            <h2>Bringing Entertainment Closer</h2>
                        </div>
                        <p>Welcome to SOUND, the ultimate destination for music and video entertainment. Whether you're
                            looking for the latest chart-toppers or timeless classics, we’ve got it all. Our platform
                            hosts an extensive collection of songs and videos in both regional and English languages,
                            neatly organized by album, artist, year, genre, and language. <br><br>
                            Explore the latest releases, dive into curated categories, and share your opinions through
                            our review and rating features. With a streamlined interface and user-friendly design, SOUND
                            reinvents your entertainment experience.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ##### Featured Artist Area End ##### -->

    <!-- ##### Contact Area Start ##### -->
    <section class="contact-area section-padding-100 bg-img bg-overlay bg-fixed has-bg-img"
        style="background-image: url(img/bg-img/bg-2.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-heading white wow fadeInUp" data-wow-delay="100ms">
                        <p>See what’s new</p>
                        <h2>Get In Touch</h2>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <!-- Contact Form Area -->
                    <div class="contact-form-area">
                        <form action="#" method="post">
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group wow fadeInUp" data-wow-delay="100ms">
                                        <input type="text" class="form-control" id="name" placeholder="Name">
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group wow fadeInUp" data-wow-delay="200ms">
                                        <input type="email" class="form-control" id="email" placeholder="E-mail">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group wow fadeInUp" data-wow-delay="300ms">
                                        <input type="text" class="form-control" id="subject" placeholder="Subject">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group wow fadeInUp" data-wow-delay="400ms">
                                        <textarea name="message" class="form-control" id="message" cols="30" rows="10"
                                            placeholder="Message"></textarea>
                                    </div>
                                </div>
                                <div class="col-12 text-center wow fadeInUp" data-wow-delay="500ms">
                                    <button class="btn oneMusic-btn mt-30" type="submit">Send <i
                                            class="fa fa-angle-double-right"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ##### Contact Area End ##### -->

    <!-- ##### Footer Area Start ##### -->
    <footer class="footer-area">
        <div class="container">
            <div class="row d-flex flex-wrap align-items-center">
                <div class="col-12 col-md-6">
                    <a href="#"><img src="img/core-img/logo.png" alt=""></a>
                    <p class="copywrite-text"><a
                            href="#"><!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                            Copyright &copy;
                            <script>document.write(new Date().getFullYear());</script> All rights reserved | This
                            template is made with <i class="fa fa-heart-o" aria-hidden="true"></i> by <a
                                href="https://colorlib.com" target="_blank">Colorlib</a>
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. --></p>
                </div>

                <div class="col-12 col-md-6">
                    <div class="footer-nav">
                        <ul>
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Albums</a></li>
                            <li><a href="#">Events</a></li>
                            <li><a href="#">News</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- ##### Footer Area Start ##### -->

    <!-- ##### All Javascript Script ##### -->
    <!-- jQuery-2.2.4 js -->
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <!-- Popper js -->
    <script src="js/bootstrap/popper.min.js"></script>
    <!-- Bootstrap js -->
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <!-- All Plugins js -->
    <script src="js/plugins/plugins.js"></script>
    <!-- Active js -->
    <script src="js/active.js"></script>
    <script src="./js/app.js"></script>
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