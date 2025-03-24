document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    const wavesurfers = {};
    let currentlyPlaying = null;

    // Filter form change handlers
    document.getElementById('status-filter')?.addEventListener('change', function() {
        document.getElementById('tracks-filter-form').submit();
    });

    document.getElementById('genre-filter')?.addEventListener('change', function() {
        document.getElementById('tracks-filter-form').submit();
    });

    // Select all checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    const trackCheckboxes = document.querySelectorAll('.track-checkbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;

            trackCheckboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });

            updateActionButtons();
        });
    }

    // Individual checkboxes
    trackCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateActionButtons();

            // Update "select all" checkbox
            if (selectAllCheckbox) {
                const allChecked = Array.from(trackCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(trackCheckboxes).some(cb => cb.checked);

                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });

    // Delete button handler
    document.getElementById('delete-selected')?.addEventListener('click', function() {
        const selectedTracks = getSelectedTracks();

        if (selectedTracks.length > 0) {
            document.getElementById('selected-tracks-input').value = JSON.stringify(selectedTracks);

            // Show confirmation modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    });

    // Activate selected tracks button
    document.getElementById('activate-selected')?.addEventListener('click', function() {
        const selectedTracks = getSelectedTracks();

        if (selectedTracks.length > 0) {
            document.getElementById('status-tracks-input').value = JSON.stringify(selectedTracks);
            document.getElementById('batch-status-value').value = "1";
            document.getElementById('batch-status-form').submit();
        }
    });

    // Deactivate selected tracks button
    document.getElementById('deactivate-selected')?.addEventListener('click', function() {
        const selectedTracks = getSelectedTracks();

        if (selectedTracks.length > 0) {
            document.getElementById('status-tracks-input').value = JSON.stringify(selectedTracks);
            document.getElementById('batch-status-value').value = "0";
            document.getElementById('batch-status-form').submit();
        }
    });

    // Individual active toggle switches
    document.querySelectorAll('.active-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const trackId = this.getAttribute('data-id');
            const isActive = this.checked ? 1 : 0;

            // Set form values
            document.getElementById('status-track-id').value = trackId;
            document.getElementById('status-value').value = isActive;

            // Submit the form
            document.getElementById('active-status-form').submit();
        });
    });

    // Function to get selected track IDs
    function getSelectedTracks() {
        const selected = [];
        document.querySelectorAll('.track-checkbox:checked').forEach(function(checkbox) {
            selected.push(parseInt(checkbox.getAttribute('data-id')));
        });
        return selected;
    }

    // Individual delete buttons
    document.querySelectorAll('.delete-track').forEach(function(button) {
        button.addEventListener('click', function() {
            const trackId = this.getAttribute('data-id');
            const trackTitle = this.getAttribute('data-title');

            document.getElementById('delete-track-id').value = trackId;
            document.querySelector('#deleteModal .modal-body').innerHTML =
                `Are you sure you want to delete the track "${trackTitle}"?`;

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    });

    // Function to update action buttons state
    function updateActionButtons() {
        const selectedCount = document.querySelectorAll('.track-checkbox:checked').length;

        // Handle activate/deactivate buttons
        const activateSelectedBtn = document.getElementById('activate-selected');
        const deactivateSelectedBtn = document.getElementById('deactivate-selected');
        const deleteSelectedBtn = document.getElementById('delete-selected');
        const exportSelectedBtn = document.getElementById('export-selected');

        if (activateSelectedBtn) activateSelectedBtn.disabled = selectedCount === 0;
        if (deactivateSelectedBtn) deactivateSelectedBtn.disabled = selectedCount === 0;
        if (deleteSelectedBtn) deleteSelectedBtn.disabled = selectedCount === 0;
        if (exportSelectedBtn) exportSelectedBtn.disabled = selectedCount === 0;

        // Update delete modal message for multiple tracks
        if (selectedCount > 1) {
            const modalBody = document.querySelector('#deleteModal .modal-body');
            if (modalBody) {
                modalBody.textContent = `Are you sure you want to delete ${selectedCount} selected tracks?`;
            }
        }
    }

    // Create dynamic waveforms for each track
    document.querySelectorAll('.track-row').forEach(function(row) {
        const trackId = row.getAttribute('data-id');
        const waveformContainer = row.querySelector('.waveform');

        if (!waveformContainer) return;

        // Create a unique ID for this waveform container
        const waveformContainerId = `waveform-container-${trackId}`;

        // Replace the placeholder with a div for WaveSurfer
        waveformContainer.innerHTML = `
    <div class="waveform-container" data-track-id="${trackId}">
        <div id="${waveformContainerId}" class="waveform-visualization"></div>
        <div class="waveform-controls d-flex align-items-center mt-1">
            <button class="waveform-play-btn btn btn-sm btn-outline-secondary me-2" data-playing="false">
                <i class="fas fa-play"></i>
            </button>
            <span class="waveform-time small text-muted">0:00 / 0:00</span>
        </div>
    </div>
    `;

        // Initialize WaveSurfer for this track
        const wavesurfer = WaveSurfer.create({
            container: `#${waveformContainerId}`,
            waveColor: '#d9d9d9',
            progressColor: '#4a6cf7',
            cursorColor: '#6e42c1',
            barWidth: 2,
            barRadius: 3,
            cursorWidth: 1,
            height: 40,
            barGap: 2,
            responsive: true,
            normalize: true,
            barHeight: 0.8,
        });

        // Get the audio element for this track
        const audioElement = document.getElementById(`audio-${trackId}`);

        // Store the wavesurfer instance, audio element, controls
        wavesurfers[trackId] = {
            instance: wavesurfer,
            audio: audioElement,
            button: waveformContainer.querySelector('.waveform-play-btn'),
            timeDisplay: waveformContainer.querySelector('.waveform-time')
        };

        // Load the audio
        if (audioElement) {
            const audioSource = audioElement.querySelector('source')?.src;
            if (audioSource) {
                wavesurfer.load(audioSource);
            }
        }

        // Set up event listeners for this wavesurfer instance
        wavesurfer.on('ready', function() {
            const duration = formatTime(wavesurfer.getDuration());
            wavesurfers[trackId].timeDisplay.textContent = `0:00 / ${duration}`;
        });

        wavesurfer.on('audioprocess', function() {
            if (wavesurfer.isPlaying()) {
                const currentTime = formatTime(wavesurfer.getCurrentTime());
                const totalTime = formatTime(wavesurfer.getDuration());
                wavesurfers[trackId].timeDisplay.textContent =
                    `${currentTime} / ${totalTime}`;
            }
        });

        wavesurfer.on('finish', function() {
            wavesurfers[trackId].button.innerHTML = '<i class="fas fa-play"></i>';
            wavesurfers[trackId].button.setAttribute('data-playing', 'false');
            wavesurfers[trackId].timeDisplay.textContent =
                `0:00 / ${formatTime(wavesurfer.getDuration())}`;
            currentlyPlaying = null;
        });

        // Play button click handler
        wavesurfers[trackId].button.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePlayback(trackId);
        });

        // Click on waveform to seek
        waveformContainer.addEventListener('click', function(e) {
            // Don't trigger if clicking on the play button
            if (e.target.closest('.waveform-play-btn')) {
                return;
            }

            // Calculate the click position relative to the waveform container
            const waveformVisualization = wavesurfer.container;
            const rect = waveformVisualization.getBoundingClientRect();
            const relativeX = e.clientX - rect.left;
            const seekPosition = relativeX / rect.width;

            // If not currently playing this track, start it
            if (currentlyPlaying !== trackId) {
                // Stop any currently playing track
                if (currentlyPlaying) {
                    stopPlayback(currentlyPlaying);
                }

                // Seek and play
                wavesurfer.seekTo(seekPosition);
                wavesurfer.play();
                wavesurfers[trackId].button.innerHTML = '<i class="fas fa-pause"></i>';
                wavesurfers[trackId].button.setAttribute('data-playing', 'true');
                currentlyPlaying = trackId;
            } else {
                // Just seek
                wavesurfer.seekTo(seekPosition);
            }
        });
    });

    // Row click handler for checkbox toggle
    document.querySelectorAll('.track-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Don't toggle if clicking on a button or checkbox
            if (e.target.tagName === 'BUTTON' ||
                e.target.tagName === 'INPUT' ||
                e.target.tagName === 'A' ||
                e.target.closest('button') ||
                e.target.closest('a') ||
                e.target.closest('input') ||
                e.target.closest('.waveform-container')) {
                return;
            }

            const checkbox = this.querySelector('.track-checkbox');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;

                // Trigger change event
                const event = new Event('change');
                checkbox.dispatchEvent(event);
            }
        });
    });

    // Toggle play/pause function
    function togglePlayback(trackId) {
        // Get the wavesurfer instance
        const trackObj = wavesurfers[trackId];
        if (!trackObj) return;

        const wavesurfer = trackObj.instance;
        const playButton = trackObj.button;

        // Check if this is the currently playing track
        if (currentlyPlaying === trackId) {
            // If playing, pause it
            if (wavesurfer.isPlaying()) {
                wavesurfer.pause();
                playButton.innerHTML = '<i class="fas fa-play"></i>';
                playButton.setAttribute('data-playing', 'false');
                currentlyPlaying = null;
            } else {
                // If paused, resume it
                wavesurfer.play();
                playButton.innerHTML = '<i class="fas fa-pause"></i>';
                playButton.setAttribute('data-playing', 'true');
                currentlyPlaying = trackId;
            }
        } else {
            // Stop any currently playing track
            if (currentlyPlaying && wavesurfers[currentlyPlaying]) {
                stopPlayback(currentlyPlaying);
            }

            // Start playing this track
            wavesurfer.play();
            playButton.innerHTML = '<i class="fas fa-pause"></i>';
            playButton.setAttribute('data-playing', 'true');
            currentlyPlaying = trackId;
        }
    }

    // Stop playback function
    function stopPlayback(trackId) {
        const trackObj = wavesurfers[trackId];
        if (!trackObj) return;

        const wavesurfer = trackObj.instance;
        const playButton = trackObj.button;

        wavesurfer.pause();
        wavesurfer.seekTo(0);
        playButton.innerHTML = '<i class="fas fa-play"></i>';
        playButton.setAttribute('data-playing', 'false');
    }

    // Format time function (converts seconds to MM:SS format)
    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) {
            return '0:00';
        }

        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);

        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // Handle window resize to redraw waveforms
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            Object.keys(wavesurfers).forEach(function(trackId) {
                const wavesurfer = wavesurfers[trackId]?.instance;
                if (wavesurfer) {
                    wavesurfer.drawer.fireEvent('redraw');
                }
            });
        }, 500);
    });
});