//====================================== audio js ===================================//

    $(document).ready(function () {
        // Music player functionality
        let isPlaying = false;
    let currentSongId = 3; // Default to the third song (Sunny)
    let currentTime = 0;
    let duration = 140; // Default duration in seconds (2:20)
    let progressInterval;
    let currentSongTitle = "Sunny";

    // Initialize player
    updateNowPlaying(currentSongTitle);
    updateDuration("2:20");

    // Play button click handler
    $("#play_button").on("click", function () {
        togglePlayState();
            });

    // Song selection handler
    $(".jp-playlist li").on("click", function () {
                const songId = $(this).data("song-id");
    const songDuration = $(this).data("duration");
    const songTitle = $(this).find(".jp-playlist-item").text().trim().replace("▶", "").trim();

    // Update current song
    currentSongId = songId;
    currentSongTitle = songTitle;
    duration = convertToSeconds(songDuration);

    // Update UI
    $(".jp-playlist-item").removeClass("jp-playlist-current");
    $(this).find(".jp-playlist-item").addClass("jp-playlist-current");
    updateNowPlaying(songTitle);
    updateDuration(songDuration);

    // Reset progress
    resetProgress();

    // Start playing
    if (!isPlaying) {
        togglePlayState();
                }
            });

    // Progress bar click handler
    $(".jp-progress").on("click", function (e) {
                const progressWidth = $(this).width();
    const clickPosition = e.pageX - $(this).offset().left;
    const percentClicked = (clickPosition / progressWidth) * 100;

    // Update progress bar
    $("#jp-play-bar").css("width", percentClicked + "%");

    // Update current time
    currentTime = Math.floor((percentClicked / 100) * duration);
    $("#jp-current-time").text(formatTime(currentTime));
            });

    // Helper functions
    function togglePlayState() {
        isPlaying = !isPlaying;

    if (isPlaying) {
        // Start progress
        $("#player_now_playing").addClass("is-playing");
    startProgress();
                } else {
        // Pause progress
        $("#player_now_playing").removeClass("is-playing");
    clearInterval(progressInterval);
                }
            }

    function startProgress() {
        // Clear any existing interval
        clearInterval(progressInterval);

    // Start new interval
    progressInterval = setInterval(function () {
        currentTime++;

                    if (currentTime >= duration) {
        // End of song
        currentTime = 0;
    clearInterval(progressInterval);
    isPlaying = false;
    $("#player_now_playing").removeClass("is-playing");
                    }

    // Update progress bar
    const percentComplete = (currentTime / duration) * 100;
    $("#jp-play-bar").css("width", percentComplete + "%");

    // Update time display
    $("#jp-current-time").text(formatTime(currentTime));
                }, 1000);
            }

    function resetProgress() {
        currentTime = 0;
    $("#jp-play-bar").css("width", "0%");
    $("#jp-current-time").text("00:00");
    clearInterval(progressInterval);
            }

    function updateNowPlaying(title) {
        $("#current_song_title").text(title);
            }

    function updateDuration(time) {
        $("#jp-duration").text(time);
            }

    function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
    seconds = seconds % 60;
    return (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
            }

    function convertToSeconds(timeString) {
                const parts = timeString.split(":");
    return parseInt(parts[0]) * 60 + parseInt(parts[1]);
            }

    // Visual effects
    $(".featured_album_image").hover(
    function () {
        $(this).find(".background_image").css("transform", "scale(1.05)");
                },
    function () {
        $(this).find(".background_image").css("transform", "scale(1)");
                }
    );

    // Button hover effects
    $(".featured_album_link a, .play_button_large").hover(
    function () {
        $(this).css("transform", "translateY(-2px)");
                },
    function () {
        $(this).css("transform", "translateY(0)");
                }
    );
        });



//======================================================== video js ===================================//
// Custom Video Player JavaScript
const video = document.getElementById('customVideo');
const playPauseBtn = document.getElementById('playPauseBtn');
const progressContainer = document.getElementById('progressContainer');
const progressBar = document.getElementById('progressBar');
const timeDisplay = document.getElementById('timeDisplay');
const muteBtn = document.getElementById('muteBtn');
const volumeSlider = document.getElementById('volumeSlider');

// Playlist items
const playlistItems = document.querySelectorAll('.video-playlist li');

// Function to play next video in playlist
function playNextVideo() {
    let currentIndex = Array.from(playlistItems).findIndex(item => item.classList.contains('active'));
    // If last video, loop back to first video
    let nextIndex = (currentIndex + 1) % playlistItems.length;
    playlistItems[currentIndex].classList.remove('active');
    playlistItems[nextIndex].classList.add('active');
    const nextVideoUrl = playlistItems[nextIndex].getAttribute('data-video');
    video.src = nextVideoUrl;
    video.play();
    playPauseBtn.textContent = '❚❚';
}

// Auto play video on load and set muted
window.addEventListener('load', function () {
    video.muted = true;
    muteBtn.textContent = '🔇';
    video.play();
});

// Toggle play/pause
playPauseBtn.addEventListener('click', function () {
    if (video.paused) {
        video.play();
        playPauseBtn.textContent = '❚❚';
    } else {
        video.pause();
        playPauseBtn.textContent = '▶';
    }
});

// Update progress bar and time display
video.addEventListener('timeupdate', function () {
    const percent = (video.currentTime / video.duration) * 100;
    progressBar.style.width = percent + '%';
    updateTimeDisplay();
});

// Click to seek within video
progressContainer.addEventListener('click', function (e) {
    const rect = progressContainer.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const width = rect.width;
    const newTime = (clickX / width) * video.duration;
    video.currentTime = newTime;
});

// Update time display (current time and duration)
function updateTimeDisplay() {
    const formatTime = time => {
        const minutes = Math.floor(time / 60);
        const seconds = Math.floor(time % 60);
        return `${minutes < 10 ? '0' + minutes : minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
    };
    timeDisplay.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration) || '00:00'}`;
}

// Mute/unmute functionality
muteBtn.addEventListener('click', function () {
    video.muted = !video.muted;
    muteBtn.textContent = video.muted ? '🔇' : '🔊';
});

// Volume control with dynamic background fill
function updateVolumeSliderBackground() {
    const value = volumeSlider.value;
    const percentage = value * 100;
    volumeSlider.style.background = `linear-gradient(90deg, #ff6b6b ${percentage}%, rgba(255,255,255,0.2) ${percentage}%)`;
}
volumeSlider.addEventListener('input', function () {
    video.volume = volumeSlider.value;
    if (video.volume == 0) {
        video.muted = true;
        muteBtn.textContent = '🔇';
    } else {
        video.muted = false;
        muteBtn.textContent = '🔊';
    }
    updateVolumeSliderBackground();
});
updateVolumeSliderBackground();

// When video ends, play next video from the playlist
video.addEventListener('ended', function () {
    playNextVideo();
});

// Playlist item click functionality
$(document).ready(function () {
    $('.video-playlist li').click(function () {
        $('.video-playlist li').removeClass('active');
        $(this).addClass('active');
        const videoUrl = $(this).data('video');
        video.src = videoUrl;
        video.play();
        playPauseBtn.textContent = '❚❚';
    });
});


// ===========================================  latest videos js ===================================//


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
