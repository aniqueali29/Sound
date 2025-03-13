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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --neon-green: #0ff47a;
        --deep-space: #0a0a14;
        --stellar-purple: #6c43f5;
        --cosmic-pink: #ff3b8d;
        --holographic-gradient: linear-gradient(45deg, var(--neon-green), var(--stellar-purple));
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

    @keyframes text-glow {
        from {
            text-shadow: 0 0 10px rgba(15, 244, 122, 0.3);
        }

        to {
            text-shadow: 0 0 40px rgba(15, 244, 122, 0.6);
        }
    }

    /* Enhanced Card Styling */
    .quantum-card {
        background: rgba(20, 20, 30, 0.9);
        border-radius: 20px;
        padding: 1.5rem;
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .quantum-card:hover {
        transform: translateY(-6px) rotate(2deg);
        box-shadow: 0 10px 30px rgba(15, 244, 122, 0.3);
        border: 1px solid var(--neon-green);
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

    .card-image-container {
        position: relative;
        overflow: hidden;
        height: 220px;
        border-radius: 16px;
    }

    .card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .quantum-card:hover .card-image {
        transform: scale(1.1);
    }

    .card-image-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.8));
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

    .quantum-card:hover .morph-play {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.1);
    }

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

    /* Responsive Design */
    @media (max-width: 768px) {
        .stellar-header h1 {
            font-size: 2.5rem;
        }

        .card-image-container {
            height: 180px;
        }

        .card-info {
            padding: 1rem;
        }
    }
    </style>
</head>

<body>
    <main class="container mt-5">
        <header class="stellar-header">
            <h1>SONIC ARCHIVE</h1>
            <p>Explore the Universe of Sound</p>
        </header>
        <!-- Album Grid -->
        <section class="album-grid">
            <div class="row g-4">
                <!-- Album Card 1 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="quantum-card">
                        <div class="card-image-container">
                            <img src="/api/placeholder/400/320" alt="Hybrid Theory" class="card-image">
                            <!-- New Tag -->
                            <div class="card-tag">New</div>
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
                            <!-- New Tag -->
                            <div class="card-tag">Trending</div>
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
                <!-- Add more cards as needed -->
            </div>
        </section>
    </main>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>