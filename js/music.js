document.addEventListener('DOMContentLoaded', function () {
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

    // Filter Functionality
    const filterElements = {
        album: document.getElementById('album-filter'),
        artist: document.getElementById('artist-filter'),
        year: document.getElementById('year-filter'),
        genre: document.getElementById('genre-filter'),
        language: document.getElementById('language-filter'),
        albumMobile: document.getElementById('album-filter-mobile'),
        artistMobile: document.getElementById('artist-filter-mobile'),
        yearMobile: document.getElementById('year-filter-mobile'),
        genreMobile: document.getElementById('genre-filter-mobile'),
        languageMobile: document.getElementById('language-filter-mobile'),
        searchInput: document.getElementById('search-input'),
        searchButton: document.getElementById('search-btn'),
        searchInputMobile: document.getElementById('search-input-mobile'),
        searchButtonMobile: document.getElementById('search-btn-mobile')
    };

    // Apply filters function
    function applyFilters() {
        let url = new URL(window.location.href);

        // Get values from desktop or mobile filters based on viewport
        const isMobile = window.innerWidth < 768;

        const albumValue = isMobile ? filterElements.albumMobile.value : filterElements.album.value;
        const artistValue = isMobile ? filterElements.artistMobile.value : filterElements.artist.value;
        const yearValue = isMobile ? filterElements.yearMobile.value : filterElements.year.value;
        const genreValue = isMobile ? filterElements.genreMobile.value : filterElements.genre.value;
        const languageValue = isMobile ? filterElements.languageMobile.value : filterElements.language.value;
        const searchValue = isMobile ? filterElements.searchInputMobile.value : filterElements.searchInput.value;

        // Clear existing parameters
        url.search = '';

        // Track if we need to maintain the noinsert parameter
        const noInsert = new URLSearchParams(window.location.search).get('noinsert');
        if (noInsert) {
            url.searchParams.set('noinsert', noInsert);
        }

        // Add new parameters - only add non-empty values
        if (albumValue && albumValue !== "Album") url.searchParams.set('album', albumValue);
        if (artistValue && artistValue !== "Artist") url.searchParams.set('artist', artistValue);
        if (yearValue && yearValue !== "Year") url.searchParams.set('year', yearValue);
        if (genreValue && genreValue !== "Genre") url.searchParams.set('genre', genreValue);
        if (languageValue && languageValue !== "Language") url.searchParams.set('language', languageValue);
        if (searchValue) url.searchParams.set('search', searchValue);

        // Navigate to new URL
        window.location.href = url.toString();
    }

    // Add event listeners to filter elements
    filterElements.album.addEventListener('change', applyFilters);
    filterElements.artist.addEventListener('change', applyFilters);
    filterElements.year.addEventListener('change', applyFilters);
    filterElements.genre.addEventListener('change', applyFilters);
    filterElements.language.addEventListener('change', applyFilters);
    filterElements.albumMobile.addEventListener('change', applyFilters);
    filterElements.artistMobile.addEventListener('change', applyFilters);
    filterElements.yearMobile.addEventListener('change', applyFilters);
    filterElements.genreMobile.addEventListener('change', applyFilters);
    filterElements.languageMobile.addEventListener('change', applyFilters);
    filterElements.searchButton.addEventListener('click', applyFilters);
    filterElements.searchButtonMobile.addEventListener('click', applyFilters);

    // Search on Enter key
    filterElements.searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
    filterElements.searchInputMobile.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    // Sync mobile and desktop filters
    function syncFilters(source, target) {
        source.addEventListener('change', function () {
            target.value = source.value;
        });
    }

    syncFilters(filterElements.album, filterElements.albumMobile);
    syncFilters(filterElements.albumMobile, filterElements.album);
    syncFilters(filterElements.artist, filterElements.artistMobile);
    syncFilters(filterElements.artistMobile, filterElements.artist);
    syncFilters(filterElements.year, filterElements.yearMobile);
    syncFilters(filterElements.yearMobile, filterElements.year);
    syncFilters(filterElements.genre, filterElements.genreMobile);
    syncFilters(filterElements.genreMobile, filterElements.genre);
    syncFilters(filterElements.language, filterElements.languageMobile);
    syncFilters(filterElements.languageMobile, filterElements.language);
    syncFilters(filterElements.searchInput, filterElements.searchInputMobile);
    syncFilters(filterElements.searchInputMobile, filterElements.searchInput);

    // Audio Player Logic
    const audioPlayer = {
        element: document.getElementById('audioElement'),
        container: document.getElementById('audioPlayerSection'),
        image: document.getElementById('audioPlayerImage'),
        songTitle: document.getElementById('audioPlayerSongTitle'),
        artist: document.getElementById('audioPlayerArtist'),
        playBtn: document.getElementById('playBtn'),
        prevBtn: document.getElementById('prevBtn'),
        nextBtn: document.getElementById('nextBtn'),
        progressFill: document.getElementById('progressFill'),
        progressBar: document.getElementById('progressBar'),
        currentTimeDisplay: document.getElementById('currentTime'),
        durationDisplay: document.getElementById('duration'),
        volumeIcon: document.getElementById('volumeIcon'),
        volumeSlider: document.getElementById('volumeSlider'),
        volumeFill: document.getElementById('volumeFill'),
        volumePercentage: document.getElementById('volumePercentage'),
        closePlayerBtn: document.getElementById('closePlayerBtn'),

        currentIndex: 0,
        playlist: [],
        isPlaying: false,
        volume: 0.7, // Default volume (70%)

        init: function () {
            this.loadPlaylist();
            this.attachEventListeners();
            this.updateVolumeUI();
        },

        loadPlaylist: function () {
            const musicItems = document.querySelectorAll('.music-item');
            this.playlist = Array.from(musicItems).map(item => ({
                id: item.dataset.id,
                title: item.dataset.title,
                artist: item.dataset.artist,
                album: item.dataset.album,
                file: item.dataset.file,
                duration: item.dataset.duration,
                image: item.querySelector('img').src
            }));
        },

        attachEventListeners: function () {
            // Play/pause button
            this.playBtn.addEventListener('click', () => {
                this.togglePlay();
            });

            // Previous button
            this.prevBtn.addEventListener('click', () => {
                this.playPrev();
            });

            // Next button
            this.nextBtn.addEventListener('click', () => {
                this.playNext();
            });

            // Progress bar click
            this.progressBar.addEventListener('click', (e) => {
                const percent = e.offsetX / this.progressBar.offsetWidth;
                this.element.currentTime = percent * this.element.duration;
                this.updateProgressBar();
            });

            // Volume controls
            this.volumeIcon.addEventListener('click', () => {
                this.toggleMute();
            });

            this.volumeSlider.addEventListener('click', (e) => {
                const percent = e.offsetX / this.volumeSlider.offsetWidth;
                this.setVolume(percent);
            });

            // Time update
            this.element.addEventListener('timeupdate', () => {
                this.updateProgressBar();
            });

            // Audio ended
            this.element.addEventListener('ended', () => {
                this.playNext();
            });

            // Close player
            this.closePlayerBtn.addEventListener('click', () => {
                this.pause();
                this.container.classList.remove('active');
            });

            // Load metadata
            this.element.addEventListener('loadedmetadata', () => {
                this.updateDurationDisplay();
            });

            // Click on music items
            document.querySelectorAll('.music-item').forEach((item, index) => {
                // Main click event on the music item
                item.addEventListener('click', (event) => {
                    // Check if the clicked target is NOT the three-dot button or its contents
                    if (!event.target.closest('.more-btn')) {
                        this.currentIndex = index;
                        this.loadAndPlay();
                    }
                });

                // Stop propagation on the three-dot button to prevent triggering the player
                item.querySelector('.more-btn').addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            });
            document.querySelectorAll('.music-item').forEach((item, index) => {
                item.addEventListener('click', (event) => {
                    if (!event.target.closest('.track-btn')) {
                        this.currentIndex = index;
                        this.loadAndPlay();
                    }
                });
                item.querySelector('.track-btn').addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            });

        },

        loadAndPlay: function () {
            if (this.playlist.length === 0) return;

            const current = this.playlist[this.currentIndex];
            this.element.src = current.file; // Adjust path as needed
            this.songTitle.textContent = current.title;
            this.artist.textContent = current.artist;
            this.image.src = current.image;

            this.container.classList.add('active');
            this.play();
        },

        togglePlay: function () {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        },

        play: function () {
            this.element.play();
            this.isPlaying = true;
            this.playBtn.innerHTML = '<i class="fas fa-pause"></i>';
        },

        pause: function () {
            this.element.pause();
            this.isPlaying = false;
            this.playBtn.innerHTML = '<i class="fas fa-play"></i>';
        },

        playNext: function () {
            this.currentIndex = (this.currentIndex + 1) % this.playlist.length;
            this.loadAndPlay();
        },

        playPrev: function () {
            this.currentIndex = (this.currentIndex - 1 + this.playlist.length) % this.playlist
                .length;
            this.loadAndPlay();
        },

        updateProgressBar: function () {
            const percent = (this.element.currentTime / this.element.duration) * 100 || 0;
            this.progressFill.style.width = `${percent}%`;
            this.updateTimeDisplay();
        },

        updateTimeDisplay: function () {
            this.currentTimeDisplay.textContent = this.formatTime(this.element.currentTime);
        },

        updateDurationDisplay: function () {
            this.durationDisplay.textContent = this.formatTime(this.element.duration);
        },

        formatTime: function (seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
        },

        toggleMute: function () {
            if (this.element.volume > 0) {
                this.lastVolume = this.volume;
                this.setVolume(0);
            } else {
                this.setVolume(this.lastVolume || 0.7);
            }
        },

        setVolume: function (volumeLevel) {
            this.volume = Math.max(0, Math.min(1, volumeLevel));
            this.element.volume = this.volume;
            this.updateVolumeUI();
        },

        updateVolumeUI: function () {
            this.volumeFill.style.width = `${this.volume * 100}%`;
            this.volumePercentage.textContent = `${Math.round(this.volume * 100)}%`;

            // Update icon based on volume level
            if (this.volume === 0) {
                this.volumeIcon.innerHTML = '<i class="fas fa-volume-mute"></i>';
            } else if (this.volume < 0.5) {
                this.volumeIcon.innerHTML = '<i class="fas fa-volume-down"></i>';
            } else {
                this.volumeIcon.innerHTML = '<i class="fas fa-volume-up"></i>';
            }
        }
    };

    // Initialize audio player
    audioPlayer.init();

    // Load More Functionality
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            const hiddenCards = document.querySelectorAll('.hidden-card');
            const cardsToShow = Array.from(hiddenCards).slice(0, 12);

            cardsToShow.forEach(card => {
                card.classList.remove('hidden-card');
            });

            if (document.querySelectorAll('.hidden-card').length === 0) {
                loadMoreBtn.style.display = 'none';
            }
        });
    }
});















document.addEventListener('DOMContentLoaded', function () {
    // Variables
    const musicDetailsModal = document.getElementById('musicDetailsModal');
    const rateReviewModal = document.getElementById('rateReviewModal');
    const rateReviewBtn = document.getElementById('rateReviewBtn');
    const reviewsContainer = document.getElementById('reviewsContainer');
    const rateReviewForm = document.getElementById('rateReviewForm');
    const starRating = document.querySelector('.star-rating');
    const ratingValue = document.getElementById('ratingValue');
    const music_idInput = document.getElementById('music_id');

    // Current music details
    let currentmusic_id = null;

    // Modal initialization
    if (musicDetailsModal) {
        musicDetailsModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            // Get data from the button
            currentmusic_id = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const artist = button.getAttribute('data-artist');
            const album = button.getAttribute('data-album');
            const coverImage = button.getAttribute('data-cover');

            // Update the modal content
            document.getElementById('modalSongTitle').textContent = title;
            document.getElementById('modalArtistName').textContent = artist;
            document.getElementById('modalAlbumTitle').textContent = album;
            document.getElementById('modalCoverImage').src = coverImage;

            // Load reviews for this music
            loadMusicReviews(currentmusic_id);

            // Load rating distribution
            loadRatingDistribution(currentmusic_id);
        });
    }

    if (rateReviewBtn) {
        rateReviewBtn.addEventListener('click', function () {
            const musicModal = bootstrap.Modal.getInstance(musicDetailsModal);
            musicModal.hide();

            // Ensure music_id is correctly assigned
            if (!currentmusic_id) {
                alert("Error: Missing music ID. Please try again.");
                return;
            }

            // Set music ID in form
            music_idInput.value = currentmusic_id;

            rateReviewForm.reset();
            resetStarRating();

            const rateModal = new bootstrap.Modal(rateReviewModal);
            rateModal.show();
        });
    }

    // Star rating functionality
    if (starRating) {
        const stars = starRating.querySelectorAll('i');

        stars.forEach(star => {
            star.addEventListener('click', function () {
                const rating = parseInt(this.getAttribute('data-rating'));
                setRating(rating);
            });

            star.addEventListener('mouseover', function () {
                const rating = parseInt(this.getAttribute('data-rating'));
                highlightStars(rating);
            });

            star.addEventListener('mouseout', function () {
                const currentRating = parseInt(ratingValue.value) || 0;
                highlightStars(currentRating);
            });
        });
    }

    // Form submission
    if (rateReviewForm) {
        rateReviewForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(rateReviewForm);
            const music_id = formData.get('music_id');
            const rating = formData.get('rating');
            const review = formData.get('review');

            // Validate
            if (rating == 0) {
                alert('Please select a rating');
                return;
            }

            if (review.trim() === '') {
                alert('Please write a review');
                return;
            }

            // Submit using AJAX
            submitRatingAndReview(music_id, rating, review);
        });
    }

    // Functions
    function loadMusicReviews(music_id) {
        reviewsContainer.innerHTML = `
        <div class="loading-spinner text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

        // AJAX request to get reviews
        fetch(`../includes/get_reviews.php?music_id=${music_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReviews(data.reviews);
                } else {
                    reviewsContainer.innerHTML =
                        `<p class="text-center">No reviews yet. Be the first to review!</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading reviews:', error);
                reviewsContainer.innerHTML =
                    `<p class="text-center">Failed to load reviews. Please try again.</p>`;
            });
    }

    function loadRatingDistribution(music_id) {
        // AJAX request to get rating distribution
        fetch(`../includes/get_rating_distribution.php?music_id=${music_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateRatingDistribution(data.distribution, data.average, data.total);
                }
            })
            .catch(error => {
                console.error('Error loading rating distribution:', error);
            });
    }

    function displayReviews(reviews) {
        if (reviews.length === 0) {
            reviewsContainer.innerHTML =
                `<p class="text-center">No reviews yet. Be the first to review!</p>`;
            return;
        }

        let html = '';

        reviews.forEach(review => {
            const date = new Date(review.created_at);
            const formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            html += `
        <div class="review-item">
            <div class="review-header">
                <div class="reviewer-info">
                    <span class="reviewer-name">${review.username}</span>
                    <span class="review-date">${formattedDate}</span>
                    <div class="review-rating">
                        ${getStarIcons(review.rating)}
                    </div>
                </div>
            </div>
            <div class="review-content">
                ${review.review}
            </div>
        </div>
    `;
        });

        reviewsContainer.innerHTML = html;
    }

    function getStarIcons(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars += '<i class="fas fa-star"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        return stars;
    }

    function updateRatingDistribution(distribution, average, total) {
        // Update average rating
        const ratingStarsEl = document.querySelector('.rating-average .rating-stars');
        const ratingCountEl = document.querySelector('.rating-average .rating-count');

        ratingStarsEl.innerHTML = getStarIconsWithHalf(average);
        ratingCountEl.textContent = `${average.toFixed(1)} (${total} ratings)`;

        // Update distribution bars
        const ratingBars = document.querySelectorAll('.rating-bar-item');

        for (let i = 5; i >= 1; i--) {
            const percentage = distribution[i] || 0;
            const barIndex = 5 - i;

            const fillEl = ratingBars[barIndex].querySelector('.rating-fill');
            const percentageEl = ratingBars[barIndex].querySelector('.rating-percentage');

            fillEl.style.width = `${percentage}%`;
            percentageEl.textContent = `${percentage}%`;
        }
    }

    function getStarIconsWithHalf(rating) {
        let stars = '';
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;

        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                stars += '<i class="fas fa-star"></i>';
            } else if (i === fullStars + 1 && halfStar) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        return stars;
    }

    function setRating(rating) {
        ratingValue.value = rating;
        highlightStars(rating);
    }

    function highlightStars(rating) {
        const stars = starRating.querySelectorAll('i');

        stars.forEach((star, index) => {
            const starRating = index + 1;

            if (starRating <= rating) {
                star.classList.remove('far');
                star.classList.add('fas', 'selected');
            } else {
                star.classList.remove('fas', 'selected');
                star.classList.add('far');
            }
        });
    }

    function resetStarRating() {
        ratingValue.value = 0;
        highlightStars(0);
    }

    function submitRatingAndReview(music_id, rating, review) {
        // Show loading state
        const submitBtn = document.querySelector('.submit-review-btn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
        submitBtn.disabled = true;

        // Prepare form data
        const formData = new FormData();
        formData.append('music_id', music_id);
        formData.append('rating', rating);
        formData.append('review', review);

        // Before submitting the form, log what's being sent
        console.log('Submitting form with data:', {
            music_id: formData.get('music_id') || formData.get('music_id'),
            rating: formData.get('rating'),
            review: formData.get('review')
        });

        // AJAX request to submit rating and review
        fetch('../includes/submit_rating_review.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;

                if (data.success) {
                    // Close the review modal
                    const rateModal = bootstrap.Modal.getInstance(rateReviewModal);
                    rateModal.hide();

                    // Reopen the music details modal
                    const musicModal = new bootstrap.Modal(musicDetailsModal);
                    musicModal.show();

                    // Reload reviews and rating distribution
                    loadMusicReviews(music_id);
                    loadRatingDistribution(music_id);

                    // Show success message
                    alert('Thank you for your rating and review!');
                } else {
                    alert(data.message || 'Failed to submit your rating and review. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error submitting rating and review:', error);
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                alert('An error occurred. Please try again later.');
            });
    }
});