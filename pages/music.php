<?php include '../layout/header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SONIC ARCHIVE - Modern Music Hub</title>
    <!-- Bootstrap CSS (v5) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-..." crossorigin="anonymous">
    <style>
    :root {
        --neon-green: #0ff47a;
        --deep-space: #0a0a14;
        --stellar-purple: #6c43f5;
        --cosmic-pink: #ff3b8d;
        --holographic-gradient: linear-gradient(45deg, var(--neon-green), var(--stellar-purple));
    }

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

    body {
        background: var(--deep-space);
        color: #fff;
        font-family: 'Space Grotesk', sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }

    .particles {
        position: fixed;
        width: 100vw;
        height: 100vh;
        z-index: -1;
        top: 0;
        left: 0;
    }

    /* Header */
    .stellar-header {
        position: relative;
        text-align: center;
        padding: 4rem 0;
        overflow: hidden;
    }

    .stellar-header h1 {
        font-size: 4rem;
        background: var(--holographic-gradient);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 0 30px rgba(15, 244, 122, 0.3);
        animation: text-glow 2s ease-in-out infinite alternate;
        margin-bottom: 0.5rem;
    }

    .stellar-header p {
        font-size: 1.5rem;
        color: #ccc;
    }

    @keyframes text-glow {
        from {
            text-shadow: 0 0 10px rgba(15, 244, 122, 0.3);
        }

        to {
            text-shadow: 0 0 40px rgba(15, 244, 122, 0.6);
        }
    }

    .filter-icon {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: 60px;
        height: 60px;
        background-color: rgba(255, 255, 255, 0.1);
        /* Subtle dark background */
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 0 10px rgba(0, 255, 170, 0.4);
        cursor: pointer;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out, background 0.3s ease-in-out;
    }

    .filter-icon:hover {
        transform: scale(1.1);
        background-color: rgba(255, 255, 255, 0.2);
        box-shadow: 0 0 20px rgba(0, 255, 170, 0.8);
    }

    .filter-icon svg {
        width: 32px;
        height: 32px;
        fill: #00ffaa;
        /* Neon cyan-green for dark theme */
        transition: transform 0.3s ease-in-out, filter 0.3s ease-in-out;
    }

    .filter-icon:hover svg {
        transform: scale(1.2);
        filter: drop-shadow(0 0 5px #00ffaa);
    }

    .pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        background: rgba(0, 255, 170, 0.2);
        border-radius: 50%;
        animation: pulseAnimation 1.5s infinite ease-in-out;
    }

    @keyframes pulseAnimation {
        0% {
            transform: scale(1);
            opacity: 0.6;
        }

        50% {
            transform: scale(1.5);
            opacity: 0.2;
        }

        100% {
            transform: scale(2);
            opacity: 0;
        }
    }



    /* Offcanvas Filter Section for Mobile */
    .offcanvas {
        background: var(--deep-space);
        color: #fff;
    }

    .offcanvas .offcanvas-header,
    .offcanvas .offcanvas-body {
        border: none;
    }

    /* Desktop Filter Section */
    .hologram-filter {
        background: rgba(15, 15, 30, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        box-shadow: 0 0 40px rgba(15, 244, 122, 0.1);
        padding: 2rem;
        margin: 2rem auto;
        max-width: 1400px;
        position: relative;
    }

    .hologram-filter::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: var(--holographic-gradient);
        z-index: -1;
        border-radius: 24px;
        animation: hologram-border 6s linear infinite;
    }

    @keyframes hologram-border {
        0% {
            opacity: 0.5;
        }

        50% {
            opacity: 1;
        }

        100% {
            opacity: 0.5;
        }
    }

    .filter-section {
        margin: 2rem 0;
        padding: 2rem;
        background: rgba(20, 20, 30, 0.7);
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .filter-options {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .filter-options select {
        padding: 0.75rem 1rem;
        background-color: #1c1c24;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        min-width: 150px;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' width='292.4' height='292.4'%3E%3Cpath fill='%23fff' d='M287 69.4a17.6 17.6 0 0 0-13-5.4H18.4c-5 0-9.3 1.8-12.9 5.4A17.6 17.6 0 0 0 0 82.2c0 5 1.8 9.3 5.4 12.9l128 127.9c3.6 3.6 7.8 5.4 12.8 5.4s9.2-1.8 12.8-5.4L287 95c3.5-3.5 5.4-7.8 5.4-12.8 0-5-1.9-9.2-5.5-12.8z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 0.65rem auto;
        transition: all 0.3s ease;
    }

    .filter-options select:hover {
        background-color: #2a2a2a;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .filter-options select:focus {
        outline: none;
        border-color: var(--neon-green);
        box-shadow: 0 0 0 2px rgba(15, 244, 122, 0.3);
    }

    .search-bar {
        display: flex;
        gap: 0.75rem;
    }

    .search-bar input {
        padding: 0.75rem 1rem;
        background-color: #1c1c24;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        flex-grow: 1;
        transition: all 0.3s ease;
    }

    .search-bar input:hover {
        background-color: #2a2a2a;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .search-bar input:focus {
        outline: none;
        border-color: var(--neon-green);
        box-shadow: 0 0 0 2px rgba(15, 244, 122, 0.3);
        background-color: #2a2a2a;
    }

    .search-bar input::placeholder {
        color: #aaa;
    }

    .search-bar button {
        padding: 0.75rem 1.5rem;
        background-color: var(--neon-green);
        color: #000;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .search-bar button:hover {
        background-color: #0dc66b;
        transform: translateY(-2px);
    }

    /* Album Grid */
    .album-grid {
        margin: 2rem 0;
    }

    /* Card (No Tilt) */
    .quantum-card {
        background: rgba(20, 20, 30, 0.9);
        border-radius: 20px;
        padding: 1.5rem;
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .quantum-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
    }

    .quantum-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 200%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(15, 244, 122, 0.1), transparent);
        transition: 0.6s;
    }

    .quantum-card:hover::before {
        left: 100%;
    }

    /* Card Image and Centered Play Button */
    .card-image-container {
        position: relative;
        overflow: hidden;
        height: 220px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .card-image-container::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
        background: linear-gradient(to top, rgba(18, 18, 18, 1), transparent);
        pointer-events: none;
    }

    .morph-play {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: var(--neon-green);
        border-radius: 50%;
        opacity: 0;
        transition: all 0.4s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 30px rgba(15, 244, 122, 0.5);
        z-index: 2;
    }

    .quantum-card:hover .morph-play {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.1);
    }

    /* Card Information */
    .card-info {
        padding: 1.25rem;
        position: relative;
    }

    .card-title {
        font-size: 1.15rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        transition: color 0.3s ease;
        color: #e0e0e0;
    }

    .quantum-card:hover .card-title {
        color: var(--neon-green);
    }

    .card-artist {
        font-size: 0.95rem;
        color: #aaa;
        margin-bottom: 0.75rem;
    }

    .card-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: #888;
        align-items: center;
    }

    .card-rating {
        color: #ffc107;
        display: flex;
        gap: 2px;
        font-size: 0.9rem;
    }

    .genre-tag {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        background: rgba(29, 185, 84, 0.2);
        color: var(--neon-green);
        border-radius: 4px;
        font-size: 0.75rem;
        margin-right: 0.5rem;
    }

    /* Load More Button */
    #loadMoreBtn {
        display: none;
        margin: 2rem auto;
        padding: 0.8rem 2rem;
        background: var(--neon-green);
        color: #000;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #loadMoreBtn:hover {
        background: #0dc66b;
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .stellar-header h1 {
            font-size: 2.5rem;
        }
    }

    @media (max-width: 576px) {

        .filter-options select,
        .search-bar input {
            min-width: 120px;
        }

        .card-image-container {
            height: 180px;
        }

        .card-info {
            padding: 1rem;
        }
    }

    /* Hidden Cards (after first 12) */
    .hidden-card {
        display: none;
    }

        /* New Tag Styling */
        .card-tag {
        position: absolute;
        top: 10px;
        left: 10px;
        background: var(--neon-green);
        color: var(--deep-space);
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        z-index: 2;
        box-shadow: 0 2px 10px rgba(15, 244, 122, 0.3);
    }

    .morph-play {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: var(--neon-green);
        border-radius: 50%;
        opacity: 0;
        transition: all 0.4s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 30px rgba(15, 244, 122, 0.5);
        z-index: 2;
    }

    </style>
</head>

<body>

    <!-- Floating Particle Background -->
    <div class="particles"></div>
    <main class="container mt-5">
        <header class="stellar-header">
            <h1>SONIC ARCHIVE</h1>
            <p>Explore the Universe of Sound</p>
        </header>
        <!-- Desktop Filter Section -->
        <section class="hologram-filter d-none d-md-block">
            <div class="filter-section">
                <div class="filter-options">
                    <select id="album-filter">
                        <option value="">Album</option>
                        <option value="hybrid">Hybrid Theory</option>
                        <option value="afterhours">After Hours</option>
                        <option value="dilchahtahai">Dil Chahta Hai</option>
                        <option value="dawnfm">Dawn FM</option>
                        <option value="meteora">Meteora</option>
                        <option value="rockstar">Rockstar</option>
                        <option value="blurryface">Blurryface</option>
                        <option value="starboy">Starboy</option>
                    </select>
                    <select id="artist-filter">
                        <option value="">Artist</option>
                        <option value="lp">Linkin Park</option>
                        <option value="weeknd">The Weeknd</option>
                        <option value="sel">Shankar-Ehsaan-Loy</option>
                        <option value="arr">A.R. Rahman</option>
                        <option value="top">Twenty One Pilots</option>
                    </select>
                    <select id="year-filter">
                        <option value="">Year</option>
                        <option value="2000">2000</option>
                        <option value="2001">2001</option>
                        <option value="2003">2003</option>
                        <option value="2011">2011</option>
                        <option value="2015">2015</option>
                        <option value="2016">2016</option>
                        <option value="2020">2020</option>
                        <option value="2022">2022</option>
                        <option value="2023">2023</option>
                    </select>
                    <select id="genre-filter">
                        <option value="">Genre</option>
                        <option value="rock">Rock</option>
                        <option value="rnb">R&B</option>
                        <option value="pop">Pop</option>
                        <option value="hiphop">Hip Hop</option>
                        <option value="alternative">Alternative</option>
                        <option value="bollywood">Bollywood</option>
                    </select>
                    <select id="language-filter">
                        <option value="">Language</option>
                        <option value="english">English</option>
                        <option value="hindi">Hindi</option>
                        <option value="punjabi">Punjabi</option>
                        <option value="tamil">Tamil</option>
                    </select>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Search for music...">
                    <button>Search</button>
                </div>
            </div>
        </section>
        <!-- Offcanvas Filter Section for Mobile -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileFilter" aria-labelledby="mobileFilterLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileFilterLabel">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"
                style="filter: invert(1);"></button>
            </div>
            <div class="offcanvas-body">
                <div class="filter-options">
                    <select id="album-filter-mobile">
                        <option value="">Album</option>
                        <option value="hybrid">Hybrid Theory</option>
                        <option value="afterhours">After Hours</option>
                        <option value="dilchahtahai">Dil Chahta Hai</option>
                        <option value="dawnfm">Dawn FM</option>
                        <option value="meteora">Meteora</option>
                        <option value="rockstar">Rockstar</option>
                        <option value="blurryface">Blurryface</option>
                        <option value="starboy">Starboy</option>
                    </select>
                    <select id="artist-filter-mobile">
                        <option value="">Artist</option>
                        <option value="lp">Linkin Park</option>
                        <option value="weeknd">The Weeknd</option>
                        <option value="sel">Shankar-Ehsaan-Loy</option>
                        <option value="arr">A.R. Rahman</option>
                        <option value="top">Twenty One Pilots</option>
                    </select>
                    <select id="year-filter-mobile">
                        <option value="">Year</option>
                        <option value="2000">2000</option>
                        <option value="2001">2001</option>
                        <option value="2003">2003</option>
                        <option value="2011">2011</option>
                        <option value="2015">2015</option>
                        <option value="2016">2016</option>
                        <option value="2020">2020</option>
                        <option value="2022">2022</option>
                        <option value="2023">2023</option>
                    </select>
                    <select id="genre-filter-mobile">
                        <option value="">Genre</option>
                        <option value="rock">Rock</option>
                        <option value="rnb">R&B</option>
                        <option value="pop">Pop</option>
                        <option value="hiphop">Hip Hop</option>
                        <option value="alternative">Alternative</option>
                        <option value="bollywood">Bollywood</option>
                    </select>
                    <select id="language-filter-mobile">
                        <option value="">Language</option>
                        <option value="english">English</option>
                        <option value="hindi">Hindi</option>
                        <option value="punjabi">Punjabi</option>
                        <option value="tamil">Tamil</option>
                    </select>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Search for music...">
                    <button>Search</button>
                </div>
            </div>
        </div>
        <!-- Album Grid using Bootstrap's grid system -->
        <section class="album-grid">
            <div class="row g-4">
                <!-- First 12 album cards (visible) -->
                <!-- Album Card 1 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <!-- New Tag -->
                            <div class="card-tag">New</div>
                            <div class="morph-play"><i class="fas fa-play"></i></div>

                            <img src="/api/placeholder/400/320" alt="Hybrid Theory" class="card-image">
                            <div class="morph-play"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title">Hybrid Theory</h3>
                            <div class="card-artist">Linkin Park</div>
                            <div class="card-meta">
                                <span>2000 <span class="genre-tag">Rock</span></span>
                                <span class="card-rating">★★★★☆</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Album Card 2 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <img src="/api/placeholder/400/320" alt="After Hours" class="card-image">
                            <div class="new-badge">NEW</div>
                            <div class="morph-play"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title">After Hours</h3>
                            <div class="card-artist">The Weeknd</div>
                            <div class="card-meta">
                                <span>2020 <span class="genre-tag">R&B</span></span>
                                <span class="card-rating">★★★★★</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Album Card 3 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <img src="/api/placeholder/400/320" alt="Dil Chahta Hai" class="card-image">
                            <div class="morph-play"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title">Dil Chahta Hai</h3>
                            <div class="card-artist">Shankar-Ehsaan-Loy</div>
                            <div class="card-meta">
                                <span>2001 <span class="genre-tag">Bollywood</span></span>
                                <span class="card-rating">★★★★☆</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Album Card 4 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <img src="/api/placeholder/400/320" alt="Dawn FM" class="card-image">
                            <div class="new-badge">NEW</div>
                            <div class="morph-play"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title">Dawn FM</h3>
                            <div class="card-artist">The Weeknd</div>
                            <div class="card-meta">
                                <span>2022 <span class="genre-tag">Pop</span> <span class="genre-tag">R&B</span></span>
                                <span class="card-rating">★★★★☆</span>
                            </div>
                        </div>
                    </div>
                </div>
        </section>
        <!-- Load More Button -->
        <button id="loadMoreBtn">Load More</button>

        <!-- Mobile Filter Toggle Button in Top Right -->
        <button class="d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileFilter"
            aria-controls="mobileFilter">
            <div class="filter-icon">
                <div class="pulse"></div>
                <svg viewBox="0 0 24 24">
                    <path d="M10 18h4v-2h-4v2zm-7-10v2h18v-2h-18zm3 6h12v-2h-12v2z" />
                </svg>
            </div>
        </button>


    </main>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Dynamic Particle Effect (basic implementation)
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
    // Make album cards clickable to navigate to album details page
    document.querySelectorAll('.quantum-card').forEach(card => {
        card.addEventListener('click', function() {
            const albumTitle = this.querySelector('.card-title').textContent;
            window.location.href = `album-details.html?album=${encodeURIComponent(albumTitle)}`;
        });
    });
    // Load More Button Functionality
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const hiddenCards = document.querySelectorAll('.hidden-card');
    if (hiddenCards.length > 0) {
        window.addEventListener('scroll', () => {
            const gridBottom = document.querySelector('.album-grid').getBoundingClientRect().bottom;
            if (gridBottom <= window.innerHeight + 100) {
                loadMoreBtn.style.display = 'block';
            }
        });
    }
    loadMoreBtn.addEventListener('click', () => {
        document.querySelectorAll('.hidden-card').forEach(card => {
            card.classList.remove('hidden-card');
        });
        loadMoreBtn.style.display = 'none';
    });
    // Optional: CSS keyframes for particle animation
    const styleSheet = document.createElement('style');
    styleSheet.type = 'text/css';
    styleSheet.innerText = `
      @keyframes particle-float {
        0% { transform: translateY(0); opacity: 1; }
        100% { transform: translateY(-100px); opacity: 0; }
      }
    `;
    document.head.appendChild(styleSheet);
    </script>
</body>

</html>