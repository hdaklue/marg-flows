import videojs from 'video.js';
import 'video.js/dist/video-js.css';

// Basic VideoJS player with custom progress bar

// Deep merge utility function
function mergeDeep(target, source) {
    const output = Object.assign({}, target);
    if (isObject(target) && isObject(source)) {
        Object.keys(source).forEach(key => {
            if (isObject(source[key])) {
                if (!(key in target))
                    Object.assign(output, { [key]: source[key] });
                else
                    output[key] = mergeDeep(target[key], source[key]);
            } else {
                Object.assign(output, { [key]: source[key] });
            }
        });
    }
    return output;
}

function isObject(item) {
    return item && typeof item === 'object' && !Array.isArray(item);
}

export default function videoAnnotation(userConfig = null) {
    // Default configuration
    const defaultConfig = {
        features: {
            enableAnnotations: true,
            enableComments: true,
            enableProgressBarAnnotations: true,
            enableVideoAnnotations: true,
            enableResolutionSelector: true,
            enableVolumeControls: true,
            enableFullscreenButton: true,
            enableSettingsMenu: true
        },
        ui: {
            progressBarMode: 'auto-hide',
            showControls: true,
            helpTooltipLimit: 3,
            theme: 'auto'
        },
        annotations: {
            showCommentsOnProgressBar: true,
            enableProgressBarComments: true,
            enableVideoComments: true,
            enableContextMenu: true,
            enableHapticFeedback: true
        },
        timing: {
            progressBarAutoHideDelay: 2000,
            progressBarHoverHideDelay: 1000,
            longPressDuration: 500,
            playPauseOverlayDuration: 800,
            helpTooltipDuration: 3000
        },
        callbacks: {
            onComment: null,
            onPlay: null,
            onPause: null,
            onSeek: null,
            onVolumeChange: null,
            onResolutionChange: null,
            onFullscreenChange: null
        }
    };

    // Handle config gracefully - use defaults if null/undefined
    const config = userConfig ? mergeDeep(defaultConfig, userConfig) : defaultConfig;

    return {
        player: null,
        videoElement: null,
        comments: [], // Array of comment objects: {commentId, avatar, name, body, timestamp}
        onComment: null, // Callback function (like $wire)
        currentTime: 0,
        duration: 0,
        progressBarWidth: 0,
        hoverX: 0, // Mouse hover position for add button
        showHoverAdd: false, // Show hover add button
        helpTooltipCount: 0, // Track tooltip show count
        config: config, // Store config for component use
        isPlaying: false,
        volume: 1.0,
        isMuted: false,
        isFullscreen: false,
        showPlayPauseOverlay: false,
        videoLoaded: false,
        showSettingsMenu: false,
        showCommentsOnProgressBar: config.annotations.showCommentsOnProgressBar,
        showResolutionMenu: false,
        showVolumeSlider: false,
        showVolumeModal: false,
        showProgressBar: true,
        progressBarMode: config.ui.progressBarMode,
        progressBarTimeout: null,
        qualitySources: [],
        currentResolution: null,
        currentResolutionSrc: null,
        // Touch handling
        touchStartTime: 0,
        touchStartPos: { x: 0, y: 0 },
        isTouchMove: false,
        touchTimeout: null,
        longPressTimeout: null,
        // Click handling
        clickTimeout: null,
        clickCount: 0,
        // Mobile tap handling
        tapTimeout: null,
        tapCount: 0,
        lastTapTime: 0,
        // Mobile comment interactions
        activeCommentId: null,
        // Right-click context menu
        showContextMenu: false,
        contextMenuX: 0,
        contextMenuY: 0,
        contextMenuTime: 0,


        init() {
            // Initialize comments from window global
            this.comments = window.videoComments || [];

            // Cross-browser compatibility checks
            this.detectBrowser();


            // Initialize quality sources from data attribute
            this.initializeQualitySources();

            // Wait for DOM refs to be available
            this.$nextTick(() => {
                this.videoElement = this.$refs.videoPlayer;
                if (this.videoElement) {
                    this.setupCrossBrowserCompatibility();
                    this.setupVideoJS();
                    this.setupEventListeners();
                }
            });
        },

        detectBrowser() {
            // Detect browser for specific fixes
            this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            this.isAndroid = /Android/.test(navigator.userAgent);
            this.isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
            this.isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
        },

        setupCrossBrowserCompatibility() {
            if (this.videoElement) {
                // iOS Safari specific fixes
                if (this.isIOS) {
                    this.videoElement.setAttribute('playsinline', 'true');
                    this.videoElement.setAttribute('webkit-playsinline', 'true');

                    // Prevent iOS from showing native controls
                    this.videoElement.controls = false;
                }

                // Android specific fixes
                if (this.isAndroid) {
                    // Ensure touch events work properly
                    this.videoElement.style.touchAction = 'manipulation';
                }

                // Safari specific fixes
                if (this.isSafari) {
                    // Fix Safari double-tap zoom
                    this.videoElement.style.touchAction = 'manipulation';
                }
            }
        },

        initializeQualitySources() {
            // Wait for next tick to ensure DOM is ready
            this.$nextTick(() => {
                const qualitySourcesAttr = this.$refs.videoPlayer?.getAttribute('data-quality-sources');
                if (qualitySourcesAttr) {
                    try {
                        this.qualitySources = JSON.parse(qualitySourcesAttr);
                        // Find current resolution (selected or first)
                        const selectedSource = this.qualitySources.find(source => source.selected);
                        this.currentResolution = selectedSource || this.qualitySources[0] || null;
                        this.currentResolutionSrc = this.currentResolution ? this.currentResolution.src : null;

                    } catch (e) {
                        console.warn('Failed to parse quality sources:', e);
                        this.qualitySources = [];
                        this.currentResolution = null;
                        this.currentResolutionSrc = null;
                    }
                } else {
                    this.qualitySources = [];
                    this.currentResolution = null;
                    this.currentResolutionSrc = null;
                }
            });
        },


        setupVideoJS() {
            // Check if we already have a player instance or if videojs already initialized this element
            if (this.player || this.videoElement.classList.contains('vjs-v8')) {
                return;
            }

            // Initialize Video.js with ID instead of element reference - no controls
            this.player = videojs(this.videoElement.id, {
                // Use fluid mode to maintain aspect ratio
                fluid: true,
                responsive: true,
                fill: false,
                // Disable all controls - we'll create our own
                controls: false
            });

            this.setupPlayerEventListeners();

        },

        setupPlayerEventListeners() {
            if (!this.player) return;

            // Setup player event listeners
            this.player.ready(() => {
                this.duration = this.player.duration() || 0;
                this.updateProgressBarWidth();
            });

            this.player.on('timeupdate', () => {
                this.currentTime = this.player.currentTime();
            });

            this.player.on('play', () => {
                this.isPlaying = true;
                this.showPlayPauseOverlay = true;
                setTimeout(() => {
                    this.showPlayPauseOverlay = false;
                }, 800);
            });

            this.player.on('pause', () => {
                this.isPlaying = false;
                this.showPlayPauseOverlay = true;
                setTimeout(() => {
                    this.showPlayPauseOverlay = false;
                }, 800);
            });

            this.player.on('volumechange', () => {
                this.volume = this.player.volume();
                this.isMuted = this.player.muted();
            });

            this.player.on('fullscreenchange', () => {
                this.isFullscreen = this.player.isFullscreen();
            });

            this.player.on('loadedmetadata', () => {
                this.duration = this.player.duration();
                this.updateProgressBarWidth();
            });

            this.player.on('loadeddata', () => {
                this.videoLoaded = true;
                this.initializeProgressBarVisibility();
            });

            this.player.on('resize', () => {
                this.updateProgressBarWidth();
            });
        },


        setupEventListeners() {
            // Listen for custom events to load comments
            this.$el.addEventListener('loadComments', (event) => {
                this.comments = event.detail.comments || [];
                this.$nextTick(() => {
                    this.renderCommentMarkers();
                });
            });

            // Listen for window resize to update progress bar width
            window.addEventListener('resize', () => {
                this.updateProgressBarWidth();
            });
        },

        updateProgressBarWidth() {
            this.$nextTick(() => {
                const progressBar = this.$refs.progressBar;
                if (progressBar) {
                    this.progressBarWidth = progressBar.offsetWidth;
                }
            });
        },


        loadComment(commentId) {
            // Fire event with comment ID
            this.$dispatch('loadComment', {
                commentId: commentId
            });

            // Call the onComment callback if provided
            if (this.onComment && typeof this.onComment === 'function') {
                this.onComment('loadComment', {
                    commentId: commentId
                });
            }
        },

        seekToComment(timestamp) {
            if (this.player && timestamp >= 0) {
                const seconds = timestamp / 1000;
                this.player.currentTime(seconds);
            }
        },

        getCommentPosition(timestamp) {
            if (this.duration <= 0) return 0;

            // Get current progress bar width dynamically
            const progressBar = this.$refs.progressBar;
            if (!progressBar) return 0;

            const currentWidth = progressBar.offsetWidth;
            const seconds = timestamp / 1000;
            const position = (seconds / this.duration) * currentWidth;

            return position;
        },

        getTooltipPosition(timestamp) {
            const position = this.getCommentPosition(timestamp);
            const progressBar = this.$refs.progressBar;
            if (!progressBar) return 'left-1/2 -translate-x-1/2';

            const containerWidth = progressBar.offsetWidth;
            const tooltipWidth = 200; // approximate tooltip width

            // If tooltip would go off the left edge
            if (position < tooltipWidth / 2) {
                return 'left-0 translate-x-0';
            }
            // If tooltip would go off the right edge
            else if (position > containerWidth - tooltipWidth / 2) {
                return 'right-0 translate-x-0';
            }
            // Default centered position
            else {
                return 'left-1/2 -translate-x-1/2';
            }
        },

        getArrowPosition(timestamp) {
            const position = this.getCommentPosition(timestamp);
            const progressBar = this.$refs.progressBar;
            if (!progressBar) return 'left-1/2 -translate-x-1/2';

            const containerWidth = progressBar.offsetWidth;
            const tooltipWidth = 200; // approximate tooltip width

            // If tooltip is aligned to the left edge
            if (position < tooltipWidth / 2) {
                // Arrow should be positioned where the comment actually is
                const arrowLeft = Math.max(8, position); // 8px minimum from edge
                return `left-[${arrowLeft}px] -translate-x-1/2`;
            }
            // If tooltip is aligned to the right edge
            else if (position > containerWidth - tooltipWidth / 2) {
                // Arrow should be positioned where the comment actually is
                const arrowRight = Math.max(8, containerWidth - position); // 8px minimum from edge
                return `right-[${arrowRight}px] translate-x-1/2`;
            }
            // Default centered position
            else {
                return 'left-1/2 -translate-x-1/2';
            }
        },

        renderCommentMarkers() {
            // This will be called after comments are loaded
            this.$nextTick(() => {
                this.updateProgressBarWidth();
            });
        },

        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        },

        onProgressBarClick(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const percentage = clickX / rect.width;
            const newTime = percentage * this.duration;

            if (this.player) {
                this.player.currentTime(newTime);
            }
        },

        handleProgressBarClick(event) {
            // Prevent video click handler from firing
            event.preventDefault();
            event.stopPropagation();

            // Clear initial hide timeout on interaction
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
            }

            // Store click info for potential double-click detection
            const rect = event.currentTarget.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const percentage = clickX / rect.width;
            const targetTime = percentage * this.duration;

            this.clickCount++;

            if (this.clickCount === 1) {
                // Store event data for timeout execution
                const storedEventData = {
                    clickX: clickX,
                    rect: rect,
                    percentage: percentage,
                    targetTime: targetTime
                };

                // First click - wait to see if there's a second click
                this.clickTimeout = setTimeout(() => {
                    // Single click confirmed
                    console.log('Single click: Seeking to', this.formatTime(storedEventData.targetTime), 'at', Math.round(storedEventData.percentage * 100) + '%');

                    // Perform seek operation directly
                    if (this.player) {
                        this.player.currentTime(storedEventData.targetTime);
                    }

                    this.clickCount = 0;
                }, 300); // 300ms delay to detect double click
            }
        },

        handleProgressBarDoubleClick(event) {
            // Double click detected - cancel single click timeout
            if (this.clickTimeout) {
                clearTimeout(this.clickTimeout);
                this.clickTimeout = null;
            }
            this.clickCount = 0;

            // Double click - add comment at position
            event.preventDefault();
            event.stopPropagation(); // Prevent video click handler from firing

            const rect = event.currentTarget.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const percentage = clickX / rect.width;
            const targetTime = percentage * this.duration;
            const currentTimeMs = Math.floor(targetTime * 1000);

            console.log('Double click: Adding comment at', this.formatTime(targetTime), 'at', Math.round(percentage * 100) + '%', '(' + currentTimeMs + 'ms)');

            // Fire event with calculated timeline position
            this.$dispatch('addComment', {
                timestamp: currentTimeMs,
                currentTime: targetTime
            });

            // Call the onComment callback if provided
            if (this.onComment && typeof this.onComment === 'function') {
                this.onComment('addComment', {
                    timestamp: currentTimeMs,
                    currentTime: targetTime
                });
            }
        },

        updateHoverPosition(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            this.hoverX = event.clientX - rect.left;
        },

        addCommentAtPosition() {
            // Use the stored hover position instead of trying to parse styles
            const progressBar = this.$refs.progressBar;
            const rect = progressBar.getBoundingClientRect();
            const percentage = this.hoverX / rect.width;
            const targetTime = percentage * this.duration;
            const currentTimeMs = Math.floor(targetTime * 1000);

            // Fire event with calculated timeline position
            this.$dispatch('addComment', {
                timestamp: currentTimeMs,
                currentTime: targetTime
            });

            // Call the onComment callback if provided
            if (this.onComment && typeof this.onComment === 'function') {
                this.onComment('addComment', {
                    timestamp: currentTimeMs,
                    currentTime: targetTime
                });
            }
        },

        destroy() {
            try {
                if (this.player && typeof this.player.dispose === 'function') {
                    this.player.dispose();
                }
            } catch (e) {
                // Silently handle disposal errors
            } finally {
                this.player = null;
            }

            if (window.removeEventListener) {
                window.removeEventListener('resize', this.updateProgressBarWidth);
            }
        },

        // Custom control methods
        togglePlay() {
            if (!this.player) return;

            if (this.isPlaying) {
                this.player.pause();
            } else {
                this.player.play();
            }
        },

        setVolume(level) {
            if (!this.player) return;
            this.player.volume(level);
        },

        toggleMute() {
            if (!this.player) return;

            if (this.isMuted) {
                // Unmuting - set volume to 50% and unmute
                this.player.muted(false);
                this.player.volume(0.5);
                this.volume = 0.5;
            } else {
                // Muting - set volume to 0% and mute
                this.player.volume(0);
                this.player.muted(true);
                this.volume = 0;
            }
        },

        toggleFullscreen() {
            if (!this.player) return;

            if (this.isFullscreen) {
                this.player.exitFullscreen();
            } else {
                this.player.requestFullscreen();
            }
        },

        handleVideoClick() {
            if (!this.player) return;

            // If video hasn't loaded yet, load it first and then play
            if (!this.videoLoaded) {
                this.player.load();
                this.player.ready(() => {
                    this.player.play().catch(e => {
                        // Handle autoplay restrictions silently
                        console.log('Autoplay prevented:', e);
                    });
                });
                return;
            }

            // Toggle play/pause for loaded video
            this.togglePlay();
        },

        toggleCommentsOnProgressBar() {
            this.showCommentsOnProgressBar = !this.showCommentsOnProgressBar;
            this.showSettingsMenu = false;
        },

        changeResolution(newSource) {
            if (!this.player || !newSource) return;

            // Remember current time
            const currentTime = this.player.currentTime();
            const wasPlaying = this.isPlaying;

            // Update current resolution immediately
            this.currentResolution = newSource;
            this.currentResolutionSrc = newSource.src;

            // Change video source
            this.player.src({
                src: newSource.src,
                type: newSource.type || 'video/mp4'
            });

            // Restore playback position and state
            this.player.ready(() => {
                this.player.currentTime(currentTime);
                if (wasPlaying) {
                    this.player.play().catch(e => {
                        console.log('Resume play failed:', e);
                    });
                }

                // Refresh comment markers after resolution change
                this.$nextTick(() => {
                    this.updateProgressBarWidth();
                    this.renderCommentMarkers();
                });
            });

            this.showResolutionMenu = false;
        },

        isCurrentResolution(source) {
            if (!this.currentResolutionSrc || !source) return false;
            return this.currentResolutionSrc === source.src;
        },

        // Touch event handlers for video area
        handleTouchStart(event) {
            this.touchStartTime = Date.now();

            // Safely get touch coordinates with fallbacks
            const touch = event.touches && event.touches[0] ? event.touches[0] : event;
            this.touchStartPos = {
                x: touch.clientX || touch.pageX || 0,
                y: touch.clientY || touch.pageY || 0
            };
            this.isTouchMove = false;

            // Start long press timer for mobile video comment
            if (this.isTouchDevice() && this.config.annotations.enableVideoComments) {
                this.longPressTimeout = setTimeout(() => {
                    if (!this.isTouchMove && this.player) {
                        // Pause video and add comment at current time
                        this.player.pause();
                        const currentTimeMs = Math.floor(this.player.currentTime() * 1000);

                        console.log('Video long press: Adding comment at', this.formatTime(this.player.currentTime()));

                        // Add haptic feedback if enabled
                        if (this.config.annotations.enableHapticFeedback) {
                            this.triggerHapticFeedback(100);
                        }

                        // Fire event with current timeline position
                        this.$dispatch('addComment', {
                            timestamp: currentTimeMs,
                            currentTime: this.player.currentTime()
                        });

                        // Call the onComment callback if provided
                        if (this.onComment && typeof this.onComment === 'function') {
                            this.onComment('addComment', {
                                timestamp: currentTimeMs,
                                currentTime: this.player.currentTime()
                            });
                        }
                    }
                }, this.config.timing.longPressDuration);
            }

            // Add touch move listener to detect movement with passive support check
            const passiveSupported = this.isPassiveSupported();
            document.addEventListener('touchmove', this.handleTouchMoveDetection,
                passiveSupported ? { passive: false } : false);
        },

        isPassiveSupported() {
            let passiveSupported = false;
            try {
                const options = {
                    get passive() {
                        passiveSupported = true;
                        return false;
                    }
                };
                window.addEventListener('test', null, options);
                window.removeEventListener('test', null, options);
            } catch (err) {
                passiveSupported = false;
            }
            return passiveSupported;
        },

        handleTouchMoveDetection(event) {
            if (this.touchStartPos.x && this.touchStartPos.y) {
                const currentTouch = event.touches[0];
                const deltaX = Math.abs(currentTouch.clientX - this.touchStartPos.x);
                const deltaY = Math.abs(currentTouch.clientY - this.touchStartPos.y);

                // If moved more than 10px, consider it a move
                if (deltaX > 10 || deltaY > 10) {
                    this.isTouchMove = true;

                    // Cancel long press if user moves
                    if (this.longPressTimeout) {
                        clearTimeout(this.longPressTimeout);
                        this.longPressTimeout = null;
                    }
                }
            }
        },

        handleTouchEnd(event) {
            const touchEndTime = Date.now();
            const touchDuration = touchEndTime - this.touchStartTime;

            // Clear long press timeout
            if (this.longPressTimeout) {
                clearTimeout(this.longPressTimeout);
                this.longPressTimeout = null;
            }

            // Remove touch move listener
            document.removeEventListener('touchmove', this.handleTouchMoveDetection);

            // Only handle tap if it was a short touch and minimal movement
            if (touchDuration < 300 && !this.isTouchMove) {
                // Prevent default click event from also firing
                event.preventDefault();
                this.handleVideoClick();
            }

            // Reset touch state
            this.touchStartPos = { x: 0, y: 0 };
        },

        // Progress bar touch handlers
        onProgressBarTouchStart(event) {
            this.touchStartTime = Date.now();
            this.isTouchMove = false;

            // Clear initial hide timeout on interaction
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
            }

            // Store touch position for progress bar
            const touch = event.touches[0];
            const rect = event.currentTarget.getBoundingClientRect();
            this.hoverX = touch.clientX - rect.left;

            // Start long press timer for mobile add comment
            this.longPressTimeout = setTimeout(() => {
                if (!this.isTouchMove) {
                    // Long press detected - add comment
                    const percentage = this.hoverX / rect.width;
                    const targetTime = percentage * this.duration;
                    const currentTimeMs = Math.floor(targetTime * 1000);

                    console.log('Mobile long press: Adding comment at', this.formatTime(targetTime), 'at', Math.round(percentage * 100) + '%', '(' + currentTimeMs + 'ms)');

                    // Don't show help tooltip on mobile long press
                    // this.showHoverAdd = true;

                    // Add haptic feedback if available (cross-browser)
                    this.triggerHapticFeedback(100);

                    // Fire event with calculated timeline position
                    this.$dispatch('addComment', {
                        timestamp: currentTimeMs,
                        currentTime: targetTime
                    });

                    // Call the onComment callback if provided
                    if (this.onComment && typeof this.onComment === 'function') {
                        this.onComment('addComment', {
                            timestamp: currentTimeMs,
                            currentTime: targetTime
                        });
                    }
                }
            }, 500); // 500ms long press

            event.preventDefault(); // Prevent scrolling
            event.stopPropagation(); // Prevent video click handler
        },

        onProgressBarTouchMove(event) {
            this.isTouchMove = true;
            event.preventDefault(); // Prevent scrolling
            event.stopPropagation(); // Prevent video click handler

            // Cancel long press since user is moving
            if (this.longPressTimeout) {
                clearTimeout(this.longPressTimeout);
                this.longPressTimeout = null;
            }

            // Update hover position for touch
            const touch = event.touches[0];
            const rect = event.currentTarget.getBoundingClientRect();
            this.hoverX = touch.clientX - rect.left;
        },

        onProgressBarTouchEnd(event) {
            const touchEndTime = Date.now();
            const touchDuration = touchEndTime - this.touchStartTime;

            event.preventDefault();
            event.stopPropagation(); // Prevent video click handler

            // Cancel long press timeout if still active
            if (this.longPressTimeout) {
                clearTimeout(this.longPressTimeout);
                this.longPressTimeout = null;
            }

            // Remove old tooltip logic since we disabled it above

            // Handle tap to seek (short touch without movement)
            if (touchDuration < 500 && !this.isTouchMove) {
                const touch = event.changedTouches[0];
                const rect = event.currentTarget.getBoundingClientRect();
                const clickX = touch.clientX - rect.left;
                const percentage = clickX / rect.width;
                const newTime = percentage * this.duration;

                console.log('Mobile tap: Seeking to', this.formatTime(newTime), 'at', Math.round(percentage * 100) + '%');

                if (this.player) {
                    this.player.currentTime(newTime);
                }
            }
        },

        // Comment touch handlers
        handleCommentTouchStart(event, comment) {
            this.touchStartTime = Date.now();
            this.isTouchMove = false;

            // Add visual feedback for touch
            event.currentTarget.style.transform = 'translateX(-50%) scale(0.95)';
        },

        handleCommentTouchEnd(event, comment) {
            const touchEndTime = Date.now();
            const touchDuration = touchEndTime - this.touchStartTime;

            // Reset visual feedback
            event.currentTarget.style.transform = 'translateX(-50%) scale(1)';

            // Only handle tap if it was a short touch
            if (touchDuration < 300 && !this.isTouchMove) {
                event.preventDefault();
                this.seekToComment(comment.timestamp);
            }
        },

        // Mobile comment interaction handlers
        handleCommentClick(event, comment) {
            event.preventDefault();
            event.stopPropagation();

            // Always load the comment
            this.loadComment(comment.commentId);

            // Check if this is a touch device using enhanced detection
            if (this.isTouchDevice()) {
                // On mobile, toggle comment tooltip visibility
                if (this.activeCommentId === comment.commentId) {
                    // If already active, hide it and seek to comment
                    this.activeCommentId = null;
                    this.seekToComment(comment.timestamp);
                } else {
                    // Show this comment's tooltip
                    this.activeCommentId = comment.commentId;
                }
            } else {
                // On desktop, just seek to comment
                this.seekToComment(comment.timestamp);
            }
        },

        isCommentTooltipVisible(comment) {
            if (this.isTouchDevice()) {
                // On mobile, show tooltip only if this comment is active
                return this.activeCommentId === comment.commentId;
            } else {
                // On desktop, use CSS hover (handled by group-hover class)
                return false; // Let CSS handle hover
            }
        },

        hideCommentTooltip() {
            this.activeCommentId = null;
        },

        // Cross-browser haptic feedback
        triggerHapticFeedback(duration = 50) {
            try {
                // Standard vibration API
                if (navigator.vibrate) {
                    navigator.vibrate(duration);
                }
                // Legacy webkit vibration
                else if (navigator.webkitVibrate) {
                    navigator.webkitVibrate(duration);
                }
                // iOS haptic feedback (if available)
                else if (window.AudioContext || window.webkitAudioContext) {
                    // Create a short audio feedback as fallback
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);

                    oscillator.frequency.value = 800;
                    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.1);
                }
            } catch (error) {
                // Silently fail if haptic feedback is not supported
                console.debug('Haptic feedback not available:', error);
            }
        },

        // Enhanced touch detection for cross-browser compatibility
        isTouchDevice() {
            return (('ontouchstart' in window) ||
                (navigator.maxTouchPoints > 0) ||
                (navigator.msMaxTouchPoints > 0));
        },

        // Progress bar visibility management
        initializeProgressBarVisibility() {
            if (this.progressBarMode === 'auto-hide') {
                // Show progress bar initially
                this.showProgressBar = true;

                // Hide after configured delay
                this.progressBarTimeout = setTimeout(() => {
                    this.showProgressBar = false;
                }, this.config.timing.progressBarAutoHideDelay);
            } else {
                // Always visible mode
                this.showProgressBar = true;
            }
        },

        handleVideoHover() {
            if (this.progressBarMode === 'auto-hide' && !this.isTouchDevice()) {
                this.showProgressBar = true;

                // Clear existing timeout
                if (this.progressBarTimeout) {
                    clearTimeout(this.progressBarTimeout);
                }
            }
        },

        handleVideoLeave() {
            if (this.progressBarMode === 'auto-hide' && !this.isTouchDevice()) {
                // Hide progress bar after configured delay
                this.progressBarTimeout = setTimeout(() => {
                    this.showProgressBar = false;
                }, this.config.timing.progressBarHoverHideDelay);
            }
        },

        toggleProgressBarMode() {
            this.progressBarMode = this.progressBarMode === 'auto-hide' ? 'always-visible' : 'auto-hide';

            if (this.progressBarMode === 'always-visible') {
                // Clear timeout and show progress bar
                if (this.progressBarTimeout) {
                    clearTimeout(this.progressBarTimeout);
                }
                this.showProgressBar = true;
            } else {
                // Start auto-hide behavior
                this.initializeProgressBarVisibility();
            }
        },

        // Right-click context menu handlers
        handleVideoRightClick(event) {
            if (!this.isTouchDevice() && this.player && this.config.annotations.enableContextMenu) {
                event.preventDefault();

                // Pause video and capture time
                this.player.pause();
                this.contextMenuTime = this.player.currentTime();

                // Position context menu
                this.contextMenuX = event.clientX;
                this.contextMenuY = event.clientY;
                this.showContextMenu = true;

                console.log('Right-click: Context menu at', this.formatTime(this.contextMenuTime), this.contextMenuX, this.contextMenuY);
            }
        },


        addCommentFromContextMenu() {
            const currentTimeMs = Math.floor(this.contextMenuTime * 1000);

            console.log('Context menu: Adding comment at', this.formatTime(this.contextMenuTime));

            // Fire event with calculated timeline position
            this.$dispatch('addComment', {
                timestamp: currentTimeMs,
                currentTime: this.contextMenuTime
            });

            // Call the onComment callback if provided
            if (this.onComment && typeof this.onComment === 'function') {
                this.onComment('addComment', {
                    timestamp: currentTimeMs,
                    currentTime: this.contextMenuTime
                });
            }

            this.hideContextMenu();
        },

        hideContextMenu() {
            this.showContextMenu = false;
        },

        // Internal configuration toggle methods
        toggleAnnotationsMode() {
            this.config.features.enableAnnotations = !this.config.features.enableAnnotations;
            
            // Update related features
            if (!this.config.features.enableAnnotations) {
                this.config.features.enableComments = false;
                this.config.features.enableProgressBarAnnotations = false;
                this.config.features.enableVideoAnnotations = false;
                // Keep settings menu available for progress bar control
                this.config.annotations.showCommentsOnProgressBar = false;
                this.config.annotations.enableProgressBarComments = false;
                this.config.annotations.enableVideoComments = false;
                this.config.annotations.enableContextMenu = false;
                this.showCommentsOnProgressBar = false;
            } else {
                this.config.features.enableComments = true;
                this.config.features.enableProgressBarAnnotations = true;
                this.config.features.enableVideoAnnotations = true;
                this.config.features.enableSettingsMenu = true;
                this.config.annotations.showCommentsOnProgressBar = true;
                this.config.annotations.enableProgressBarComments = true;
                this.config.annotations.enableVideoComments = true;
                this.config.annotations.enableContextMenu = true;
                this.showCommentsOnProgressBar = true;
            }
            // Always ensure progress bar is visible for seeking
            this.showProgressBar = true;
        },

        setSimplePlayerMode() {
            this.config.features.enableAnnotations = false;
            this.config.features.enableComments = false;
            this.config.features.enableProgressBarAnnotations = false;
            this.config.features.enableVideoAnnotations = false;
            this.config.features.enableSettingsMenu = true; // Keep settings available for progress bar control
            this.config.annotations.showCommentsOnProgressBar = false;
            this.config.annotations.enableProgressBarComments = false;
            this.config.annotations.enableVideoComments = false;
            this.config.annotations.enableContextMenu = false;
            this.config.ui.progressBarMode = 'auto-hide';
            this.config.ui.helpTooltipLimit = 1;
            this.showCommentsOnProgressBar = false;
            this.progressBarMode = 'auto-hide';
            // Initialize progress bar visibility with auto-hide behavior
            this.initializeProgressBarVisibility();
        },

        setAdvancedAnnotationMode() {
            this.config.features.enableAnnotations = true;
            this.config.features.enableComments = true;
            this.config.features.enableProgressBarAnnotations = true;
            this.config.features.enableVideoAnnotations = true;
            this.config.features.enableSettingsMenu = true;
            this.config.annotations.showCommentsOnProgressBar = true;
            this.config.annotations.enableProgressBarComments = true;
            this.config.annotations.enableVideoComments = true;
            this.config.annotations.enableContextMenu = true;
            this.config.ui.progressBarMode = 'auto-hide';
            this.config.ui.helpTooltipLimit = 5;
            this.showCommentsOnProgressBar = true;
            this.progressBarMode = 'auto-hide';
            // Always ensure progress bar is visible for seeking
            this.showProgressBar = true;
        }
    }
}
