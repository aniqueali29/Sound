<?php
session_start();
include './includes/config_db.php';


$user_logged_in = isset($_SESSION['user_id']);
$user = [];

if ($user_logged_in) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT username, profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}


// Function to get featured content
function getFeaturedContent($conn, $type, $limit = 1) {
    $table = ($type == 'music') ? 'music' : 'videos';
    
    $sql = "SELECT t.id, t.title, 
    " . ($type == 'music' ? "t.thumbnail_path" : "t.thumbnail") . " AS img,
    t.release_year, t.is_new, 
    " . ($type == 'music' ? "t.rating," : "") . " 
    a.name as artist_name, g.name as genre, l.name as language,
    " . ($type == 'music' ? "t.plays" : "t.views") . " AS views
    FROM $table t
    JOIN artists a ON t.artist_id = a.id
    LEFT JOIN genres g ON t.genre_id = g.id
    LEFT JOIN languages l ON t.language_id = l.id
    WHERE t.is_featured = 1 AND t.is_active = 1 AND t.deleted_at IS NULL 
    " . ($type == 'music' ? "AND t.album_id IS NULL" : "") . " 
    ORDER BY t.created_at DESC
    LIMIT $limit";

    $result = $conn->query($sql);
    $items = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// Function to get latest content
function getLatestContent($conn, $type, $limit = 3) {
    $table = ($type == 'music') ? 'music' : 'videos';
    
    $sql = "SELECT t.id, t.title, 
    " . ($type == 'music' ? "t.thumbnail_path" : "t.thumbnail") . " AS img,
    t.release_year, t.is_new, 
    " . ($type == 'music' ? "t.rating," : "") . " 
    a.name as artist_name, g.name as genre, l.name as language,
    " . ($type == 'music' ? "t.plays" : "t.views") . " AS views
FROM $table t
JOIN artists a ON t.artist_id = a.id
LEFT JOIN genres g ON t.genre_id = g.id
LEFT JOIN languages l ON t.language_id = l.id
WHERE t.is_active = 1 AND t.deleted_at IS NULL 
" . ($type == 'music' ? "AND t.album_id IS NULL" : "") . " 
ORDER BY t.created_at DESC
LIMIT $limit";

    $result = $conn->query($sql);
    $items = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// Function to get all genres
function getAllGenres($conn) {
    $sql = "SELECT id, name FROM genres ORDER BY name ASC";
    $result = $conn->query($sql);
    $genres = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $genres[] = $row;
        }
    }
    
    return $genres;
}

// Get featured content
$featuredMusic = getFeaturedContent($conn, 'music');
$featuredVideo = getFeaturedContent($conn, 'videos');

// Get latest content
$latestMusic = getLatestContent($conn, 'music');
$latestVideos = getLatestContent($conn, 'videos');

// Get all genres for browse categories
$genres = getAllGenres($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUND Entertainment - Music & Videos</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Roboto:wght@300;400;500;700&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --primary: #0ff47a;
        --primary-dark: #0cc962;
        --secondary: #8c9eff;
        --dark: #060b19;
        --dark-light: #121a2f;
        --text-light: #ffffff;
        --text-dim: #a0a0a0;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background-color: var(--dark);
        color: var(--text-light);
        line-height: 1.6;
        overflow-x: hidden;
        background-image:
            radial-gradient(circle at 10% 20%, rgba(91, 2, 154, 0.2) 0%, rgba(0, 0, 0, 0) 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 65, 108, 0.2) 0%, rgba(0, 0, 0, 0) 40%);
    }

    /* Header & Navigation */
    header {
        background-color: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(15, 244, 122, 0.3);
    }

    .navbartwo,
    header {
        transition: all 0.3s ease;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
    }

    .logo {
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(to right, var(--secondary), var(--primary));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-decoration: none;
    }

    .nav-menu {
        display: flex;
        list-style: none;
    }

    .nav-menu li {
        margin-left: 1.5rem;
    }

    .nav-link {
        color: var(--text-light);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        padding: 0.5rem 0;
    }

    .nav-link:after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0;
        left: 0;
        background-color: var(--primary);
        transition: width 0.3s ease;
    }

    .nav-link:hover {
        color: var(--primary);
    }

    .nav-link:hover:after {
        width: 100%;
    }

    .menu-toggle {
        display: none;
        flex-direction: column;
        cursor: pointer;
    }

    .bar {
        width: 25px;
        height: 3px;
        background-color: var(--text-light);
        margin: 3px 0;
        transition: all 0.3s ease;
    }

    .profile-dropdown {
        position: relative;
        display: inline-block;
    }

    .profile-btn {
        display: flex;
        align-items: center;
        border: none;
        background: none;
        cursor: pointer;
        padding: 10px;
    }

    .profile-pic,
    .profile-initial {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 8px;
    }

    .profile-initial {
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #333;
        /* Adjust color as needed */
        color: white;
        font-weight: bold;
        font-size: 18px;
    }

    .profile_user {
        color: white;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 150px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        z-index: 1000;
        overflow: hidden;
    }

    .dropdown-content a {
        display: block;
        padding: 10px;
        color: #333;
        text-decoration: none;
        transition: background 0.2s ease-in-out;
    }

    .dropdown-content a:hover {
        background-color: #f4f4f4;
    }

    .show {
        display: block;
    }


    /* Hero Section */
    .hero {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .hero-content {
        text-align: center;
        z-index: 2;
        max-width: 800px;
        padding: 0 2rem;
        /* margin-bottom:80px ; */
        /* gap: 100px !important; */
    }

    .hero-label {
        display: inline-block;
        background: linear-gradient(90deg, #ff6b6b, #f02e65);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        /* margin-bottom: -1.5rem; */
    }

    .hero-title {
        color: #ff7b54;
        text-align: center;
        font-size: 3.3rem;
        margin-top: 2rem;
        text-shadow: 0 0 15px rgba(255, 123, 84, 0.7);
        font-family: 'Orbitron', sans-serif;
        letter-spacing: 4px;
        position: relative;

    }

    .hero-title::after {
        content: '';
        position: absolute;
        width: 100px;
        height: 3px;
        background: linear-gradient(90deg, transparent, #ff7b54, transparent);
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
    }

    .hero-subtitle {
        font-size: 1.25rem;
        color: #e0e0e0;
        margin-bottom: 2.5rem;
        line-height: 1.6;
        padding-top: 20px;
        font-family: "Quicksand", sans-serif;
    }

    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .hero-btn {
        padding: 1rem 2.5rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .primary-btn {
        background-color: transparent;
        color: #8c9eff;
        border: 2px solid #8c9eff;
    }

    .primary-btn:hover {
        background-color: #8c9eff;
        color: #060b19;
        transform: translateY(-2px);
        /* box-shadow: 0 5px 15px #8c9eff; */
    }

    .secondary-btn {
        background-color: #8c9eff;
        color: #060b19;
        border: 2px solid #8c9eff;
    }

    .secondary-btn:hover {
        background-color: transparent;
        color: #8c9eff;
        transform: translateY(-2px);
        /* box-shadow: 0 5px 15px #8c9eff; */
    }

    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.9)),
            linear-gradient(45deg, rgba(115, 3, 192, 0.6), rgba(240, 46, 101, 0.6));
        z-index: 1;
    }

    .wave-animation {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 2;
        opacity: 0.4;
    }

    .hero-floating-elements {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        overflow: hidden;
    }

    .floating-icon {
        position: absolute;
        opacity: 0.4;
        animation: float 6s infinite ease-in-out;
    }

    .icon-1 {
        top: 20%;
        left: 15%;
        animation-delay: 0s;
    }

    .icon-2 {
        top: 60%;
        right: 20%;
        animation-delay: 1s;
    }

    .icon-3 {
        bottom: 20%;
        left: 30%;
        animation-delay: 2s;
    }

    .icon-4 {
        top: 30%;
        right: 30%;
        animation-delay: 3s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(5deg);
        }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 3rem;
        }

        .hero-subtitle {
            font-size: 1rem;
        }

        .hero-buttons {
            flex-direction: column;
        }

        .nav-links {
            display: none;
        }
    }

    /* Section Styles */
    section {
        padding: 3rem 0;
    }

    .section-title {
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        text-align: center;
        margin-bottom: 1rem;
        color: var(--text-light);
        position: relative;
        display: inline-block;
    }

    .section-title:after {
        content: '';
        position: absolute;
        width: 50%;
        height: 3px;
        background: linear-gradient(to right, var(--secondary), var(--primary));
        bottom: -10px;
        left: 25%;
    }

    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .section-description {
        max-width: 700px;
        margin: 0 auto;
        color: var(--text-dim);
    }

    /* Featured Section */
    .featured-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-bottom: 2rem;
    }

    /* Latest Content Sections */
    .content-tabs {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
    }

    .tab-btn {
        background: none;
        border: none;
        color: var(--text-light);
        font-size: 1.1rem;
        padding: 0.5rem 1.5rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        border-color: var(--primary);
        color: var(--primary);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .content-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    /* Media Grid */
    .media-card {
        background-color: var(--dark-light);
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .media-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(15, 244, 122, 0.15);
        border-color: rgba(15, 244, 122, 0.3);
    }

    .media-img {
        position: relative;
        overflow: hidden;
        height: 200px;
    }

    .featured .media-img {
        height: 250px;
    }

    .media-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .media-card:hover .media-img img {
        transform: scale(1.1);
    }

    .media-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(0deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0) 50%);
        opacity: 0;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding-bottom: 1rem;
    }

    .media-card:hover .media-overlay {
        opacity: 1;
    }

    .media-actions {
        display: flex;
        gap: 1rem;
    }

    .action-btn {
        width: 40px;
        height: 40px;
        background-color: var(--primary);
        color: var(--dark);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: scale(1.1);
    }

    .media-body {
        padding: 1rem;
    }

    .media-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .media-meta {
        color: var(--text-dim);
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        margin-bottom: 20px;
    }

    .media-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .tag {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        background-color: rgba(140, 158, 255, 0.2);
    }

    .tag.genre {
        background-color: rgba(140, 158, 255, 0.2);
    }

    .tag.type {
        background-color: rgba(255, 65, 108, 0.2);
    }

    .tag.language {
        background-color: rgba(15, 244, 122, 0.2);
    }

    .media-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .rating {
        color: #ffc107;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .new-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: var(--primary);
        color: var(--dark);
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.3rem 0.7rem;
        border-radius: 50px;
        z-index: 2;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(15, 244, 122, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(15, 244, 122, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(15, 244, 122, 0);
        }
    }

    /* Browse Categories */
    .categories {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .category-card {
        background-color: var(--dark-light);
        border-radius: 10px;
        overflow: hidden;
        position: relative;
        height: 150px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(15, 244, 122, 0.2);
    }

    .category-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        filter: brightness(0.6);
        transition: all 0.3s ease;
    }

    .category-card:hover .category-img {
        filter: brightness(0.8);
        transform: scale(1.1);
    }

    .category-name {
        position: absolute;
        bottom: 20px;
        left: 20px;
        font-family: 'Orbitron', sans-serif;
        font-weight: 600;
        z-index: 1;
    }

    .category-icon {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 40px;
        height: 40px;
        background-color: var(--primary);
        color: var(--dark);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    /* About Section */
    .about-section {
        background-color: var(--dark-light);
        border-radius: 20px;
        padding: 3rem;
        margin-top: 3rem;
        text-align: center;
        border: 1px solid rgba(15, 244, 122, 0.2);
    }

    .about-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .about-features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin-top: 2rem;
    }

    .feature {
        text-align: center;
        padding: 1.5rem;
        border-radius: 10px;
        background-color: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .feature:hover {
        transform: translateY(-5px);
        border-color: rgba(15, 244, 122, 0.3);
    }

    .feature-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--primary);
    }

    .feature-title {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .feature-description {
        color: var(--text-dim);
        font-size: 0.9rem;
    }

    /* Footer */
    footer {
        background-color: rgba(0, 0, 0, 0.3);
        padding: 3rem 0 1rem;
        margin-top: 3rem;
        border-top: 1px solid rgba(15, 244, 122, 0.2);
    }

    .footer-content {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .footer-column h3 {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
        color: var(--primary);
    }

    .footer-column p {
        color: var(--text-dim);
        margin-bottom: 1rem;
    }

    .footer-links {
        list-style: none;
    }

    .footer-links li {
        margin-bottom: 0.8rem;
    }

    .footer-links a {
        color: var(--text-dim);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .footer-links a:hover {
        color: var(--primary);
    }

    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .social-link {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background-color: var(--primary);
        color: var(--dark);
        transform: translateY(-3px);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .footer-bottom p {
        color: var(--text-dim);
        font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .featured-grid {
            grid-template-columns: 1fr;
        }

        .content-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .categories {
            grid-template-columns: repeat(2, 1fr);
        }

        .about-features {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .footer-content {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .hero {
            height: 400px;
        }

        .hero-title {
            font-size: 2rem;
        }

        .content-grid {
            grid-template-columns: 1fr;
        }

        .menu-toggle {
            display: flex;
        }

        .nav-menu {
            position: absolute;
            flex-direction: column;
            background-color: var(--dark);
            width: 100%;
            top: 80px;
            left: -100%;
            opacity: 0;
            transition: all 0.5s ease;
            padding: 2rem 0;
            border-top: 1px solid rgba(15, 244, 122, 0.2);
        }

        .nav-menu.active {
            left: 0;
            opacity: 1;
        }

        .nav-menu li {
            margin: 1rem 0;
            text-align: center;
        }

        .footer-content {
            grid-template-columns: 1fr;
        }
    }

    /* Particle Animation */
    .particles {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -999;
        overflow: hidden;
    }

    .particle {
        position: absolute;
        width: 2px;
        height: 2px;
        background-color: var(--primary);
        border-radius: 50%;
        opacity: 0.5;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 5%;
        background-color: transparent;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(140, 158, 255, 0.2);
        /* border: none;
            outline: none; */
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        color: #ff7b54;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
        text-decoration: none;
    }

    .logo-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-links {
        display: flex;
        gap: 2.5rem;
    }

    .nav-link {
        color: #e0e0e0;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        letter-spacing: 0.5px;
        position: relative;
        transition: color 0.3s;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -5px;
        left: 0;
        background-color: #8c9eff;
        transition: width 0.3s ease;
    }

    .nav-link:hover {
        color: #8c9eff;
    }

    .nav-link:hover::after {
        width: 100%;
    }

    .nav-link.active {
        color: #8c9eff;
    }

    .nav-link.active::after {
        width: 100%;
    }

    .auth-buttons {
        display: flex;
        gap: 1rem;
    }

    .nav-btn {
        padding: 0.6rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s;
        letter-spacing: 0.5px;
        text-decoration: none;
    }

    .login-btn {
        background-color: transparent;
        color: #8c9eff;
        border: 2px solid #8c9eff;
    }

    .login-btn:hover {
        background-color: #8c9eff;
        color: var(--dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(140, 158, 255, 0.4);
    }

    .signup-btn {
        background-color: #8c9eff;
        color: var(--dark);
        border: 2px solid #8c9eff;
    }

    .signup-btn:hover {
        background-color: transparent;
        color: #8c9eff;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(140, 158, 255, 0.4);
    }

    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        color: #e0e0e0;
        font-size: 1.5rem;
        cursor: pointer;
    }

    /* Glass effect for dropdowns */
    .dropdown {
        position: relative;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        margin-top: 1rem;
        background: rgba(6, 11, 25, 0.95);
        backdrop-filter: blur(10px);
        min-width: 180px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(140, 158, 255, 0.2);
        z-index: 1;
    }

    .dropdown:hover .dropdown-content {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .dropdown-content a {
        color: #e0e0e0;
        padding: 0.8rem 1.5rem;
        text-decoration: none;
        display: block;
        text-align: left;
        transition: all 0.2s;
        font-size: 0.9rem;
    }

    .dropdown-content a:hover {
        background-color: rgba(140, 158, 255, 0.1);
        color: #8c9eff;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px) translateX(-50%);
        }

        to {
            opacity: 1;
            transform: translateY(0) translateX(-50%);
        }
    }

    /* Mobile Navigation */
    @media (max-width: 992px) {

        .nav-links {
            display: none;
        }

        /* .mobile-menu-btn {
                display: block;
            } */

        .mobile-nav {
            position: fixed;
            top: 4rem;
            left: 0;
            right: 0;
            background-color: rgba(6, 11, 25, 0.98);
            backdrop-filter: blur(10px);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            transform: translateY(-100%);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(140, 158, 255, 0.2);
        }

        .mobile-nav.active {
            transform: translateY(0);
            opacity: 1;
        }

        .mobile-links {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .mobile-link {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(140, 158, 255, 0.1);
        }

        .mobile-link:hover,
        .mobile-link.active {
            color: #8c9eff;
        }

        .mobile-auth {
            display: flex !important;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* .mobile-auth {
                display: none;
            } */


        .auth-buttons {
            display: none;
        }

        .mobile-auth .nav-btn {
            width: 100%;
            text-align: center;
        }
    }

    /* Second Navbar Styles */
    .navbartwo {
        position: fixed;
        top: 2rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        width: auto;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 10012;
    }

    .nav-container {
        background: rgba(18, 18, 18, 0.8);
        backdrop-filter: blur(10px);
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



    /* Featured Section Buttons */
    .media-footer .hero-btn {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
        border-width: 1px;
        margin-left: auto;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .media-footer .hero-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(140, 158, 255, 0.2);
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .navbartwo {
            top: 1.5rem;
        }

        .nav-container {
            padding: 0.6rem 0.8rem;
        }

        .nav-item a {
            font-size: 0.9rem;
            padding: 0.6rem 1rem;
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

    }

    .tab-content {
        opacity: 0;
        transition: opacity 0.3s ease;
        height: 0;
        overflow: hidden;
    }

    .tab-content.active {
        opacity: 1;
        height: auto;
        overflow: visible;
    }

    .artist-list li,
    .video-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(140, 158, 255, 0.1);
    }

    .artist-list span {
        color: var(--primary);
        font-size: 0.8rem;
        background: rgba(15, 244, 122, 0.1);
        padding: 0.2rem 0.5rem;
        border-radius: 3px;
    }

    .video-list i {
        color: var(--secondary);
        opacity: 0.8;
    }

    .highlight {
        color: var(--primary);
        font-weight: 700;
        margin-left: 1rem;
    }

    /* Hero Section */
    .hero {
        height: 100vh;
        min-height: 600px;
        /* Minimum height for smaller screens */
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        padding: 80px 0 40px;
        /* Add padding for navigation */
    }

    .hero-content {
        text-align: center;
        z-index: 2;
        max-width: 800px;
        padding: 0 1.5rem;
        margin: 0 auto;
    }

    .hero-label {
        font-size: 0.75rem;
        padding: 0.4rem 1.2rem;
        margin-bottom: 0.5rem;
    }

    .hero-title {
        font-size: 2.5rem;
        line-height: 1.2;
        margin: 1rem 0;
        letter-spacing: 2px;
    }

    .hero-title::after {
        width: 80px;
        bottom: -5px;
    }

    .hero-subtitle {
        font-size: 1rem;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }

    .hero-buttons {
        flex-direction: column;
        gap: 1rem;
        max-width: 300px;
        margin: 0 auto;
    }

    .hero-btn {
        width: 100%;
        padding: 1rem 1.5rem;
        font-size: 0.9rem;
    }

    /* Medium screens (tablets) */
    @media (min-width: 768px) {
        .hero {
            min-height: 700px;
        }

        .hero-title {
            font-size: 3rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            padding: 0;
        }

        .hero-buttons {
            flex-direction: row;
            max-width: none;
        }

        .hero-btn {
            width: auto;
        }

        .hero-floating-elements {
            display: block;
            /* Show on tablets and up */
        }
    }

    /* Large screens */
    @media (min-width: 992px) {
        .hero-title {
            font-size: 3.3rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
        }

    }

    /* Small mobile devices */
    @media (max-width: 480px) {
        .hero {
            min-height: 500px;
        }

        .hero-title {
            font-size: 2rem;
            letter-spacing: 1px;
        }

        .hero-subtitle {
            font-size: 0.9rem;
            padding: 0 0.5rem;
        }

    }

    /* Floating Icons Adjustments */
    .hero-floating-elements {
        display: block;
    }

    /* Mobile adjustments */
    @media (max-width: 768px) {
        .floating-icon {
            width: 30px !important;
            height: 30px !important;
            opacity: 0.3;
        }

        .icon-1 {
            top: 10% !important;
            left: 5% !important;
        }

        .icon-2 {
            top: 70% !important;
            right: 5% !important;
        }

        .icon-3 {
            bottom: 15% !important;
            left: 20% !important;
        }

        .icon-4 {
            top: 20% !important;
            right: 20% !important;
        }

        .media-footer {
            flex-direction: row;
            align-items: center;
        }

        .media-footer .hero-btn {
            width: auto;
            flex-grow: 0;
        }

        .auth-buttons {
            display: none;
        }

        .mobile-auth {
            flex-direction: row !important;
            flex-wrap: wrap;
            gap: 0.75rem !important;
        }

        .mobile-auth .nav-btn {
            width: auto !important;
            padding: 0.6rem 1.2rem !important;
            flex-grow: 0;
        }

        .mobile-auth {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem 0 0;
            margin-top: 1rem;
            border-top: 1px solid rgba(140, 158, 255, 0.1);
        }

        .mobile-auth .nav-btn {
            width: 100%;
            text-align: center;
            font-size: 0.95rem;
            padding: 0.8rem;
        }

        .auth-buttons {
            display: none;
        }

        .mobile-auth {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem 0 0;
            margin-top: 1rem;
            border-top: 1px solid rgba(140, 158, 255, 0.1);
        }

        .mobile-auth .nav-btn {
            width: 100%;
            text-align: center;
            font-size: 0.95rem;
            padding: 0.8rem;
        }
    }

    /* Smaller mobile devices */
    @media (max-width: 480px) {
        .floating-icon {
            width: 25px !important;
            height: 25px !important;
        }

        .icon-1 {
            left: 2% !important;
        }

        .icon-2 {
            right: 2% !important;
        }

        .media-footer {
            flex-direction: row;
            align-items: center;
            gap: 1rem;
            padding-top: 20px;
        }

        .media-footer .hero-btn {
            width: auto;
            padding: 0.75rem 1rem;
        }

        .media-meta {
            margin: 20px 0;
        }

        .section-title {
            display: block;
            width: 100%;
            text-align: center;
            margin: 0 auto;
        }

        .section-title::after {
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
        }

        .section-description {
            margin-top: 30px;
        }

        .about-section {
            padding: 0px;
            padding-top: 2rem;
        }
    }

    @media (max-width: 992px) {
        .menu-toggle {
            display: flex;
        }

        .nav-menu {
            position: absolute;
            flex-direction: column;
            background-color: var(--dark);
            width: 100%;
            top: 80px;
            left: -100%;
            opacity: 0;
            transition: all 0.5s ease;
            padding: 2rem 0;
            border-top: 1px solid rgba(15, 244, 122, 0.2);
        }

        .nav-menu.active {
            left: 0;
            opacity: 1;
        }

        .nav-menu li {
            margin: 1rem 0;
            text-align: center;
        }

        .mobile-auth {
            flex-direction: row !important;
            flex-wrap: wrap;
            gap: 0.75rem !important;
            justify-content: center !important;

        }

        .mobile-auth .nav-btn {
            width: auto !important;
            padding: 0.6rem 1.2rem !important;
            flex-grow: 0;
            justify-content: center !important;
        }
    }

    .desktop-hide {
        display: none;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #1B1E37;

        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content a {
        color: white;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .show {
        display: block;
    }
    </style>
</head>

<body>
    <!-- Particle Background -->
    <div class="particles" id="particles"></div>

    <header>
        <div class="container">
            <!-- Desktop Navbar -->
            <nav class="navbar">
                <a href="#" class="logo">SOUND</a>
                <ul class="nav-menu">
                    <li><a href="./index.php" class="nav-link">Home</a></li>
                    <li><a href="./pages/music.php" class="nav-link">Music</a></li>
                    <li><a href="./pages/video.php" class="nav-link">Videos</a></li>
                    <li><a href="./pages/albums.php" class="nav-link">Albums</a></li>
                    <li><a href="./pages/artists.php" class="nav-link">Artists</a></li>
                    <li class="mobile-auth desktop-hide">
                        <a href="./pages/login.php" class="nav-btn login-btn">Login</a>
                        <a href="./pages/login.php" class="nav-btn signup-btn">Sign Up</a>
                    </li>
                </ul>
                <div class="menu-toggle">
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                </div>
                <div class="auth-buttons">
                    <?php if (!empty($user_logged_in) && !empty($user) && is_array($user)): ?>
                    <div class="profile-dropdown">
                        <button class="profile-btn" id="profileBtn">
                            <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?= './uploads/' . htmlspecialchars($user['profile_picture']) ?>" alt="Profile"
                                class="profile-pic">
                            <?php else: ?>
                            <span class="profile-initial">
                                <?= isset($user['username']) ? strtoupper(substr($user['username'], 0, 1)) : 'U' ?>
                            </span>
                            <?php endif; ?>
                            <span class="profile_user">
                                <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Guest' ?>
                            </span>
                        </button>
                        <div class="dropdown-content" id="dropdownMenu">
                            <a href="./pages/dashboard.php">Dashboard</a>
                            <a href="./pages/logout.php">Logout</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="./pages/login.php" class="nav-btn login-btn">Login</a>
                    <a href="./pages/login.php" class="nav-btn signup-btn">Sign Up</a>
                    <?php endif; ?>
                </div>
            </nav>
    </header>
    <!-- Second Navbar -->
    <nav class="navbartwo">
        <div class="nav-container">
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-floating-elements">
            <svg class="floating-icon icon-1" width="40" height="40" viewBox="0 0 24 24" fill="white">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"></path>
            </svg>
            <svg class="floating-icon icon-2" width="50" height="50" viewBox="0 0 24 24" fill="white">
                <path
                    d="M12 3c-4.97 0-9 4.03-9 9v7c0 1.1.9 2 2 2h4v-8H5v-1c0-3.87 3.13-7 7-7s7 3.13 7 7v1h-4v8h4c1.1 0 2-.9 2-2v-7c0-4.97-4.03-9-9-9z">
                </path>
            </svg>
            <svg class="floating-icon icon-3" width="45" height="45" viewBox="0 0 24 24" fill="white">
                <path
                    d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14zM8 15c0-1.66 1.34-3 3-3 .35 0 .69.07 1 .18V6h5v2h-3v7.03c-.02 1.64-1.35 2.97-3 2.97-1.66 0-3-1.34-3-3z">
                </path>
            </svg>
            <svg class="floating-icon icon-4" width="35" height="35" viewBox="0 0 24 24" fill="white">
                <path
                    d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z">
                </path>
            </svg>
        </div>

        <div class="hero-content">
            <span class="hero-label">Experience the Beat and Videos</span>
            <h1 class="hero-title">SOUND Entertainment</h1>
            <p class="hero-subtitle">Stream thousands of tracks and music videos from your favorite artists. Find new
                sounds, create playlists, and share your discoveries with friends.</p>
            <div class="hero-buttons">
                <button class="hero-btn primary-btn" onclick="window.location.href='./pages/music';">Start
                    Listening</button>
                <button class="hero-btn secondary-btn" onclick="window.location.href='./pages/Video';">Start
                    Watching</button>

            </div>
        </div>
    </section>

    <!-- Featured Section -->
    <section>
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Content</h2>
                <p class="section-description">Our picks of the most popular music and videos this week</p>
            </div>
            <div class="featured-grid">
                <?php if (!empty($featuredMusic)): ?>
                <?php $music = $featuredMusic[0]; ?>
                <!-- Featured Music Card -->
                <div class="media-card featured">
                    <div class="media-img">
                        <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "./", $music['img']) ?? '../img/bg-img/a2.jpg'); ?>"
                            alt="Featured Album">
                        <?php if ($music['is_new']): ?>
                        <div class="new-badge">NEW</div>
                        <?php endif; ?>
                        <div class="media-overlay">
                            <div class="media-actions">
                                <div class="action-btn" onclick="window.location.href='./pages/music';">
                                    <i class="fas fa-play"></i>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="media-body">
                        <h3 class="media-title"><?php echo htmlspecialchars($music['title']); ?></h3>
                        <p class="text-info mb-2"><?php echo htmlspecialchars($music['artist_name']); ?></p>
                        <div class="media-meta">
                            <span><i class="fas fa-calendar-alt"></i>
                                <?php echo htmlspecialchars($music['release_year']); ?></span>
                            <span><i class="fas fa-headphones"></i> <?php echo number_format($music['views']); ?>
                                listens</span>
                        </div>
                        <div class="media-tags">
                            <?php if (!empty($music['genre'])): ?>
                            <span class="tag genre"><?php echo htmlspecialchars($music['genre']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($music['language'])): ?>
                            <span class="tag language"><?php echo htmlspecialchars($music['language']); ?></span>
                            <?php endif; ?>
                            <span class="tag type">Album</span>
                        </div>
                        <div class="media-footer">
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($music['rating'], 1); ?></span>
                            </div>
                            <a href="./pages/music.php" class="hero-btn primary-btn">Listen Now</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($featuredVideo)): ?>
                <?php $video = $featuredVideo[0]; ?>
                <!-- Featured Video Card -->
                <div class="media-card featured">
                    <div class="media-img">
                        <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "./", $video['img']) ?? '../img/bg-img/a4.jpg'); ?>"
                            alt="Featured Video">
                        <?php if ($video['is_new']): ?>
                        <div class="new-badge">NEW</div>
                        <?php endif; ?>
                        <div class="media-overlay">
                            <div class="media-actions">
                                <div class="action-btn"
                                    onclick="window.location.href='./pages/video_details.php?id=<?php echo $video['id']; ?>';">
                                    <i class="fas fa-play"></i>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="media-body">
                        <h3 class="media-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                        <p class="text-info mb-2"><?php echo htmlspecialchars($video['artist_name']); ?></p>
                        <div class="media-meta">
                            <span><i class="fas fa-calendar-alt"></i>
                                <?php echo htmlspecialchars($video['release_year']); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo number_format($video['views']); ?> views</span>
                        </div>
                        <div class="media-tags">
                            <?php if (!empty($video['genre'])): ?>
                            <span class="tag genre"><?php echo htmlspecialchars($video['genre']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($video['language'])): ?>
                            <span class="tag language"><?php echo htmlspecialchars($video['language']); ?></span>
                            <?php endif; ?>
                            <span class="tag type">Music Video</span>
                        </div>
                        <div class="media-footer">
                            <div class="rating">
                                <!-- <i class="fas fa-star"></i> -->
                                <!-- <span><?php echo number_format($video['rating'], 1); ?></span> -->
                            </div>
                            <a href="./pages/video_details.php?id=<?php echo $video['id']; ?>"
                                class="hero-btn secondary-btn">Watch Now</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Latest Content Section -->
    <section id="latest">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest Additions</h2>
                <p class="section-description">Check out our newest music and video releases</p>
            </div>

            <!-- Tabs -->
            <div class="content-tabs">
                <button class="tab-btn active" data-tab="music">Latest Music</button>
                <button class="tab-btn" data-tab="videos">Latest Videos</button>
            </div>

            <!-- Music Tab Content -->
            <div class="tab-content active" id="music-tab">
                <div class="content-grid">
                    <?php foreach ($latestMusic as $music): ?>
                    <!-- Music Card -->
                    <div class="media-card">
                        <div class="media-img">
                            <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "./", $music['img'] ?? '../img/bg-img/a1.jpg')); ?>"
                                alt="Music Album">
                            <?php if ($music['is_new']): ?>
                            <div class="new-badge">NEW</div>
                            <?php endif; ?>
                            <div class="media-overlay">
                                <div class="media-actions">
                                    <div class="action-btn" onclick="window.location.href='./pages/music';">
                                        <i class="fas fa-play"></i>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="media-body">
                            <h3 class="media-title"><?php echo htmlspecialchars($music['title']); ?></h3>
                            <div class="media-meta">
                                <span><?php echo htmlspecialchars($music['artist_name']); ?></span>
                                <span><?php echo htmlspecialchars($music['release_year']); ?></span>
                            </div>
                            <div class="media-tags">
                                <?php if (!empty($music['genre'])): ?>
                                <span class="tag genre"><?php echo htmlspecialchars($music['genre']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($music['language'])): ?>
                                <span class="tag language"><?php echo htmlspecialchars($music['language']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($music['rating'], 1); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Videos Tab Content -->
            <div class="tab-content" id="videos-tab">
                <div class="content-grid">
                    <?php foreach ($latestVideos as $video): ?>
                    <!-- Video Card -->
                    <div class="media-card">
                        <div class="media-img">
                            <img src="<?php echo htmlspecialchars(str_replace(["../../../", "../../"], "./", $video['img']) ?? '../img/bg-img/a6.jpg'); ?>"
                                alt="Music Video">
                            <?php if ($video['is_new']): ?>
                            <div class="new-badge">NEW</div>
                            <?php endif; ?>
                            <div class="media-overlay">
                                <div class="media-actions">
                                    <div class="action-btn"
                                        onclick="window.location.href='./pages/video_details.php?id=<?php echo $video['id']; ?>';">
                                        <i class="fas fa-play"></i>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="media-body">
                            <h3 class="media-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                            <div class="media-meta">
                                <span><?php echo htmlspecialchars($video['artist_name']); ?></span>
                                <span><?php echo htmlspecialchars($video['release_year']); ?></span>
                            </div>
                            <div class="media-tags">
                                <?php if (!empty($video['genre'])): ?>
                                <span class="tag genre"><?php echo htmlspecialchars($video['genre']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($video['language'])): ?>
                                <span class="tag language"><?php echo htmlspecialchars($video['language']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rating">
                                <!-- <i class="fas fa-star"></i> -->
                                <!-- <span><?php echo isset($video['rating']) ? number_format($video['rating'], 1) : 'N/A'; ?></span> -->
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Browse Categories Section -->
    <section id="browse">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Browse Categories</h2>
                <p class="section-description">Explore our extensive collection by category</p>
            </div>

            <div class="categories">
                <?php 
            // Define default categories if no genres are available
            $defaultCategories = [
                ["name" => "Pop", "icon" => "fas fa-music", "img" => "https://cdn.culture.ru/images/ab3a5f04-7fb4-5faf-8554-7276de5edb21"],
                ["name" => "Rock", "icon" => "fas fa-guitar", "img" => "https://i.pinimg.com/originals/d2/df/4d/d2df4da8afa882eb45531eede48e8e7d.jpg"],
                ["name" => "Jazz", "icon" => "fas fa-saxophone", "img" => "https://www.infoworld.com/wp-content/uploads/2024/06/shutterstock_1082977484-100937321-orig.jpg?quality=50&amp;strip=all&amp;w=1024"],
                ["name" => "Hip Hop", "icon" => "fas fa-microphone", "img" => "https://www.zastavki.com/pictures/originals/2013/_Guys_and_Cassette_player__swag_047288_.jpg"],
                ["name" => "Regional", "icon" => "fas fa-map-marker-alt", "img" => "https://likerka-loft.ru/wp-content/uploads/2017/08/img_5852.jpg"],
                ["name" => "Music Videos", "icon" => "fas fa-film", "img" => "https://i.ytimg.com/vi/4BJcYCMTrFI/maxresdefault.jpg"],
                ["name" => "Latest", "icon" => "fas fa-calendar-alt", "img" => "http://i.scdn.co/image/ab67616d0000b273bb94f9982fd2c1f7ffc72a44"],
                ["name" => "Top Rated", "icon" => "fas fa-star", "img" => "https://i.ytimg.com/vi/oTxNmXjKLk0/maxresdefault.jpg"]
            ];
            
            // Use genres from database if available, otherwise use default
            $categoriesToShow = !empty($genres) ? $genres : $defaultCategories;
            $count = 0;
            
            foreach ($categoriesToShow as $category):
                // Only show up to 8 categories
                if ($count >= 8) break;
                
                // Get appropriate icon based on genre name
                $icon = "fas fa-music"; // default
                $img = "../img/bg-img/a" . (($count % 10) + 1) . ".jpg";
                
                // For database genres
                $name = isset($category['name']) ? $category['name'] : $category;
                
                // Determine icon based on name
                switch (strtolower($name)) {
                    case 'rock': $icon = "fas fa-guitar"; break;
                    case 'jazz': $icon = "fas fa-saxophone"; break;
                    case 'hip hop': case 'rap': $icon = "fas fa-microphone"; break;
                    case 'regional': case 'folk': $icon = "fas fa-map-marker-alt"; break;
                    case 'music videos': case 'videos': $icon = "fas fa-film"; break;
                    case 'latest': case 'new': $icon = "fas fa-calendar-alt"; break;
                    case 'top rated': case 'popular': $icon = "fas fa-star"; break;
                    default: $icon = "fas fa-music";
                }
                
                // For default categories
                if (isset($defaultCategories[$count]['icon'])) {
                    $icon = $defaultCategories[$count]['icon'];
                }
                if (isset($defaultCategories[$count]['img'])) {
                    $img = $defaultCategories[$count]['img'];
                }
            ?>
                <!-- Category -->
                <div class="category-card">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($name); ?>" class="category-img">
                    <h3 class="category-name"><?php echo htmlspecialchars($name); ?></h3>
                    <div class="category-icon">
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                </div>
                <?php 
                $count++;
            endforeach; 
            ?>
            </div>
        </div>
    </section>

    <?php
// Close the database connection
$conn->close();
?>



    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">About SOUND Entertainment</h2>
                <p class="section-description">Your ultimate destination for music and videos in both regional and
                    English languages</p>
            </div>

            <div class="about-content">
                <p>SOUND Entertainment is dedicated to bringing you the best music and video content from around the
                    world. Our platform hosts both new and classic content across various genres, artists, and
                    languages, making it easy for you to discover your next favorite song or music video.</p>

                <div class="about-features">
                    <!-- Feature 1 -->
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3 class="feature-title">Multilingual Content</h3>
                        <p class="feature-description">Enjoy music and videos in both regional languages and English,
                            catering to diverse audiences worldwide.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="feature-title">User Ratings & Reviews</h3>
                        <p class="feature-description">Share your opinions and see what others think about your favorite
                            content through our rating system.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-music"></i>
                        </div>
                        <h3 class="feature-title">Extensive Library</h3>
                        <p class="feature-description">Access a vast collection of music and videos across different
                            genres, artists, and time periods.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>



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
                        <div class="social-link"><i class="fab fa-spotify"></i></div>
                        <div class="social-link"><i class="fab fa-soundcloud"></i></div>
                        <div class="social-link"><i class="fab fa-apple"></i></div>
                        <div class="social-link"><i class="fab fa-tiktok"></i></div>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-column">
                    <h3>Explore</h3>
                    <ul class="footer-links">
                        <li><a href="./pages/music.php">Musics</a></li>
                        <li><a href="./pages/video.php">Videos</a></li>
                        <li><a href="./pages/albums.php">Albums</a></li>
                        <li><a href="./pages/artists.php">Artist</a></li>
                    </ul>
                </div>

                <!-- Column 3: Spotlight Artists -->
                <div class="footer-column">
                    <h3>Artist Spotlight</h3>
                    <ul class="footer-links artist-list">
                        <li><a href="./pages/music.php">Neon Pulse ↗</a><span>Electronic</span></li>
                        <li><a href="./pages/music.php">Maya Thorne ↗</a><span>Indie Pop</span></li>
                        <li><a href="./pages/music.php">Echo Valley ↗</a><span>Hip-Hop</span></li>
                        <li><a href="./pages/music.php">Aqua Dreams ↗</a><span>Ambient</span></li>
                    </ul>
                </div>

                <!-- Column 4: Video Hub -->
                <div class="footer-column">
                    <h3>Video Hub</h3>
                    <ul class="footer-links video-list">
                        <li><a href="./pages/video.php">Behind the Beats</a><i class="fas fa-video"></i></li>
                        <li><a href="./pages/video.php">Studio Sessions</a><i class="fas fa-headphones"></i></li>
                        <li><a href="./pages/video.php">Live Performances</a><i class="fas fa-microphone"></i></li>
                        <li><a href="./pages/video.php">Dance Covers</a><i class="fas fa-bolt"></i></li>
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
document.addEventListener("DOMContentLoaded", function() {
    const profileBtn = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");

    profileBtn.addEventListener("click", function(event) {
        event.stopPropagation();
        dropdownMenu.classList.toggle("show");
    });

    document.addEventListener("click", function(event) {
        if (!profileBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove("show");
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    menuToggle.addEventListener('click', function() {
        this.classList.toggle('active');
        navMenu.classList.toggle('active');
        // Add padding adjustment
        if (navMenu.classList.contains('active')) {
            document.body.style.paddingTop = document.querySelector('header').offsetHeight + 'px';
        } else {
            document.body.style.paddingTop = '';
        }
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.navbar') && navMenu.classList.contains('active')) {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });

    // Close menu on resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });

    const firstNavbar = document.querySelector('header');
    const secondNavbar = document.querySelector('.navbartwo');
    const SCROLL_THRESHOLD = 200; // Switch after 200px scroll;

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
});


// Tab Switching Functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    const mobileAuthButtons = document.querySelectorAll('.mobile-auth .nav-btn');
    mobileAuthButtons.forEach(button => {
        button.addEventListener('click', () => {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            const tabId = button.getAttribute('data-tab');
            button.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });

    // Initialize first tab as active
    document.querySelector('.tab-btn').click();
});


// Particle animation
function createParticles() {
    const particles = document.getElementById('particles');
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

        // Animation
        const duration = Math.random() * 20 + 10;
        particle.style.animation = `float ${duration}s linear infinite`;

        particles.appendChild(particle);

        // Animation function
        animate(particle);
    }
}

function animate(particle) {
    // Random movement
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

// Initialize particles
window.addEventListener('load', createParticles);
</script>
</body>

</html>