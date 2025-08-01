import Hammer from 'hammerjs';
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

// accept comments as a param before config.
export default function videoAnnotation(userConfig = null, initialComments = []) {
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
            progressBarMode: 'always',
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
        comments: initialComments || [], // Array of comment objects: {commentId, avatar, name, body, timestamp}
        currentTime: 0,
        duration: 0,
        progressBarWidth: 0,
        hoverX: 0, // Mouse hover position for add button
        showHoverAdd: false, // Show hover add button
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
        // Frame navigation
        frameRate: 30, // Default, will be auto-detected
        frameDuration: 1 / 30, // Calculated from frameRate
        wasPlayingBeforeFrame: false, // Track if video was playing before frame nav
        frameNavigationDirection: null, // 'forward', 'backward', or null
        frameNavigationTimeout: null, // Timeout for hiding feedback
        showFrameHelpers: true, // Show frame navigation helper arrows
        currentFrameNumber: 0, // Real-time frame number for display
        // Unified Input Handling
        pointerState: {
            isDown: false,
            startTime: 0,
            startPos: { x: 0, y: 0 },
            currentPos: { x: 0, y: 0 },
            hasMoved: false,
            longPressTriggered: false,
            ghostClickPrevention: false,
            activePointer: null // 'mouse', 'touch', or null
        },
        longPressTimeout: null,
        hasUserInteracted: false,
        clickPreventionTimeout: null,
        lastVideoClickTime: 0,
        // Unified region creation toolbar
        showRegionToolbar: false,
        currentRegionId: null,
        tempRegionFrame: null,
        // Mobile comment interactions
        activeCommentId: null,
        // Right-click context menu
        showContextMenu: false,
        contextMenuX: 0,
        contextMenuY: 0,
        contextMenuTime: 0,
        // Draggable seek circle
        isDragging: false,
        showTooltip: false,
        dragStartX: 0,
        dragCurrentTime: 0,
        boundHandleDragMove: null,
        boundEndDrag: null,
        wasPlayingBeforeDrag: false,

        // Region creation global handlers
        boundUpdateRegionCreation: null,
        boundFinishRegionCreation: null,

        // Hammer.js touch interface
        hammer: null,
        touchInterface: {
            enabled: false,
            mode: 'NORMAL', // NORMAL, REGION_CREATE, COMMENT_ADD, CONTEXT_MENU
            contextMenuVisible: false,
            unifiedTimelineVisible: false,
            actionModalVisible: false,
        },

        // Region Management
        regions: [], // Array of region objects: {id, startTime, endTime, startFrame, endFrame, title, description}
        maxRegions: 20,
        isCreatingRegion: false,
        regionCreationStart: null,
        regionCreationEnd: null,
        showRegionBar: true,
        regionBarWidth: 0,
        dragStartRegion: null,
        isDraggingRegion: false,
        regionDragOffset: 0,
        showRegionTooltip: null, // ID of region showing tooltip
        hiddenRegions: new Set(), // Set of region IDs that are hidden

        // Getter for current video time - always references actual player time
        get getCurrentTime() {
            const currentTime = this.player ? this.player.currentTime() : 0;
            return currentTime;
        },

        // Getter for mobile device detection
        get isMobile() {
            return this.isTouchDevice();
        },

        // Getter for frame-aligned progress percentage
        get frameAlignedProgressPercentage() {
            if (!this.duration || this.duration <= 0) return 0;
            const frameState = this.currentFrameState;
            const progressPercentage = frameState.progressPercentage;

            // Adjust progress fill to center around the circle position
            // The circle should be centered at the current time, not at the end of the fill
            return progressPercentage;
        },

        // Single Source of Truth: Current Frame State
        get currentFrameState() {
            if (!this.player || !this.duration) {
                return {
                    rawTime: 0,
                    frameAlignedTime: 0,
                    frameNumber: 0,
                    progressPercentage: 0,
                    isValid: false
                };
            }

            const rawTime = this.player.currentTime();
            const frameAlignedTime = this.roundToNearestFrame(rawTime);
            const frameNumber = this.getFrameNumber(frameAlignedTime);
            const progressPercentage = (frameAlignedTime / this.duration) * 100;

            return {
                rawTime,
                frameAlignedTime,
                frameNumber,
                progressPercentage,
                isValid: true
            };
        },

        init() {
            // Comments are already initialized from initialComments parameter
            // Keep window.videoComments as fallback for backward compatibility
            if (!this.comments || this.comments.length === 0) {
                this.comments = window.videoComments || [];
            }

            // Cross-browser compatibility checks
            this.detectBrowser();

            // Safari check - don't load player for Safari
            if (this.isSafari) {
                return; // Exit early, don't initialize anything for Safari
            }

            // Initialize quality sources from data attribute
            this.initializeQualitySources();

            // Wait for DOM refs to be available
            this.$nextTick(() => {
                this.videoElement = this.$refs.videoPlayer;
                if (this.videoElement) {
                    this.setupVideoJS();
                    this.setupEventListeners();
                    this.setupTouchInterface();
                    this.resizeVideoWrapper(); // Initial sizing
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

            // Initialize Video.js player
            this.player = videojs(this.videoElement.id, {
                autoplay: false,
                controls: false,
                muted: false,
                preload: 'auto',
                playsinline: true,
                fluid: true
            });

            this.player.ready(() => {
                // Don't auto-play on initialization to avoid conflicts with browser policies
                // Let user interaction trigger playback instead
                console.log("Video player ready");
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
                // Update using single source of truth
                const frameState = this.currentFrameState;
                this.currentTime = frameState.frameAlignedTime;
                this.currentFrameNumber = frameState.frameNumber;

            });

            this.player.on('play', () => {
                this.isPlaying = true;
                this.showPlayPauseOverlay = true;
                setTimeout(() => {
                    this.showPlayPauseOverlay = false;
                }, 800);

                // Resume auto-hide behavior when playing (if in auto-hide mode)
                if (this.progressBarMode === 'auto-hide') {
                    this.progressBarTimeout = setTimeout(() => {
                        this.showProgressBar = false;
                    }, this.config.timing.progressBarAutoHideDelay);
                }
            });

            this.player.on('pause', () => {
                this.isPlaying = false;
                this.showPlayPauseOverlay = true;
                setTimeout(() => {
                    this.showPlayPauseOverlay = false;
                }, 800);

                // Show progress bar when paused
                this.showProgressBar = true;
                // Clear any auto-hide timeout when paused
                if (this.progressBarTimeout) {
                    clearTimeout(this.progressBarTimeout);
                    this.progressBarTimeout = null;
                }
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
                this.detectFrameRate();
                this.updateAspectRatio();
            });

            this.player.on('loadeddata', () => {
                this.videoLoaded = true;
                this.initializeProgressBarVisibility();
            });

            this.player.on('resize', () => {
                this.updateProgressBarWidth();
                this.updateAspectRatio();
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

            // Listen for external seek commands
            this.$el.addEventListener('video-annotation:seek-comment', (event) => {
                if (this.player && event.detail.timestamp) {
                    this.hasUserInteracted = true;
                    const seconds = event.detail.timestamp / 1000; // Convert from ms to seconds
                    this.player.currentTime(seconds);
                }
            });

            // Listen for external region loading
            this.$el.addEventListener('video-annotation:load-regions', (event) => {
                this.loadRegions(event.detail.regions || []);
            });

            // Throttled resize handler for better performance
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.updateProgressBarWidth();
                    this.resizeVideoWrapper(); // Resize video wrapper on window resize
                    // Update video container size on window resize
                    if (this.player) {
                        const width = this.player.videoWidth();
                        const height = this.player.videoHeight();
                        if (width && height) {
                            this.updateVideoContainerSize(width, height);
                        }
                    }
                }, 100);
            });

            // Note: Keyboard events are handled via @keydown.window in the template
        },

        setupTouchInterface() {
            // Only enable touch interface on touch devices
            if (!this.isTouchDevice()) {
                return;
            }

            this.touchInterface.enabled = true;
            console.log('Touch interface enabled, setting up Hammer.js');

            // Initialize Hammer.js on the main video container with optimized settings
            this.$nextTick(() => {
                const videoContainer = this.$el.querySelector('.relative.flex.justify-center');
                if (videoContainer) {
                    this.hammer = new Hammer(videoContainer, {
                        recognizers: [
                            // Pinch (highest priority - can interrupt others)
                            [Hammer.Pinch, { enable: true }],

                            // Pan (works with pinch simultaneously) - optimized thresholds
                            [Hammer.Pan, {
                                direction: Hammer.DIRECTION_ALL,
                                threshold: 10, // Increased for better touch recognition
                                pointers: 1
                            }],

                            // Tap - optimized to prevent multiple rapid detections
                            [Hammer.Tap, {
                                taps: 1,
                                threshold: 20, // Higher threshold for cleaner detection
                                time: 200,     // Shorter time window
                                interval: 400  // Longer interval to prevent rapid duplicates
                            }],

                            // Double tap - properly configured
                            [Hammer.Tap, {
                                event: 'doubletap',
                                taps: 2,
                                threshold: 15,
                                time: 250,
                                interval: 400  // Increased interval
                            }],

                            // Press - optimized timing
                            [Hammer.Press, {
                                time: 500,     // Increased from 250ms
                                threshold: 15   // Added threshold
                            }],

                            // Swipe - optimized for better recognition
                            [Hammer.Swipe, {
                                direction: Hammer.DIRECTION_ALL,
                                threshold: 30,  // Reduced threshold for easier swipes
                                velocity: 0.2   // Reduced velocity requirement
                            }]
                        ]
                    });

                    // Configure recognizer relationships to prevent conflicts
                    this.hammer.get('doubletap').recognizeWith('tap');
                    this.hammer.get('tap').requireFailure('doubletap');
                    this.hammer.get('press').recognizeWith(['pan', 'swipe']);
                    this.hammer.get('pan').requireFailure('swipe');

                    // Set up gesture handlers
                    console.log('Hammer.js created successfully, setting up handlers');
                    this.setupGestureHandlers();
                }
            });
        },

        setupGestureHandlers() {
            if (!this.hammer) return;

            // TAP: Context-aware action with debouncing
            this.hammer.on('tap', (event) => {
                console.log('Hammer tap detected, mode:', this.touchInterface.mode);

                // Debounce rapid taps (same as handleVideoClick)
                const now = Date.now();
                if (this.lastVideoClickTime && (now - this.lastVideoClickTime) < 300) {
                    console.log('Hammer tap debounced - too rapid');
                    return;
                }
                this.lastVideoClickTime = now;

                if (this.touchInterface.mode === 'NORMAL') {
                    console.log('Calling togglePlay from Hammer tap');
                    this.togglePlay();
                } else if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.confirmRegionCreation();
                    this.exitRegionCreationMode();
                }
            });

            // LONG PRESS: Direct add comment
            this.hammer.on('press', (event) => {
                if (this.touchInterface.mode === 'NORMAL') {
                    this.addCommentAtCurrentFrame();
                } else if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.exitRegionCreationMode(); // Cancel creation
                }
            });

            // DOUBLE TAP: Quick add comment
            this.hammer.on('doubletap', (event) => {
                if (this.touchInterface.mode === 'NORMAL' && this.config.annotations?.enableVideoComments) {
                    this.addCommentAtCurrentFrame();
                }
            });

            // PAN: Context-aware dragging
            this.hammer.on('panstart', (event) => {
                this.handleTouchPanStart(event);
            });

            this.hammer.on('panmove', (event) => {
                this.handleTouchPanMove(event);
            });

            this.hammer.on('panend', (event) => {
                this.handleTouchPanEnd(event);
            });

            // SWIPE: Context-aware based on current mode
            this.hammer.on('swipeleft', (event) => {
                if (this.touchInterface.mode === 'NORMAL') {
                    this.stepForward();
                } else if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.shrinkRegionEnd();
                }
            });

            this.hammer.on('swiperight', (event) => {
                if (this.touchInterface.mode === 'NORMAL') {
                    this.stepBackward();
                } else if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.expandRegionEnd();
                }
            });

            this.hammer.on('swipeup', (event) => {
                if (this.touchInterface.mode === 'NORMAL') {
                    this.toggleUnifiedTimeline();
                } else if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.expandRegionStart();
                }
            });

            this.hammer.on('swipedown', (event) => {
                if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.shrinkRegionStart();
                }
            });
        },

        updateProgressBarWidth() {
            this.$nextTick(() => {
                const progressBar = this.$refs.progressBar;
                if (progressBar) {
                    this.progressBarWidth = progressBar.offsetWidth;
                    // Circle positioning removed
                }

                // Also update region bar width
                const regionBar = this.$refs.regionBar;
                if (regionBar) {
                    this.regionBarWidth = regionBar.offsetWidth;
                }
            });
        },


        loadComment(commentId) {
            // Emit event for external handling (e.g., show comment details)
            this.$dispatch('video-annotation:view-comment', {
                commentId: commentId
            });
        },

        getCommentPosition(timestamp) {
            // Safety checks for invalid inputs
            if (!timestamp || typeof timestamp !== 'number') return 0;
            if (this.duration <= 0 || !this.progressBarWidth) return 0;

            // Timestamp is already in seconds from Laravel CommentTime->asSeconds()
            // No need to divide by 1000 like we do for milliseconds
            const seconds = timestamp;
            const position = (seconds / this.duration) * this.progressBarWidth;

            // Ensure position is within bounds
            return Math.max(0, Math.min(position, this.progressBarWidth));
        },

        getTooltipPosition(timestamp) {
            const position = this.getCommentPosition(timestamp);
            if (!this.progressBarWidth) return 'left-1/2 -translate-x-1/2';

            const tooltipWidth = 200; // approximate tooltip width

            // If tooltip would go off the left edge
            if (position < tooltipWidth / 2) {
                return 'left-0 translate-x-0';
            }
            // If tooltip would go off the right edge
            else if (position > this.progressBarWidth - tooltipWidth / 2) {
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

        // Computed hover time based on mouse position
        get hoverTime() {
            if (!this.duration || !this.$refs.progressBar) {
                return '0:00';
            }

            const rect = this.$refs.progressBar.getBoundingClientRect();
            const percentage = this.hoverX / rect.width;
            const timeInSeconds = percentage * this.duration;

            return this.formatTime(Math.max(0, Math.min(timeInSeconds, this.duration)));
        },

        // Frame navigation methods
        detectFrameRate() {
            if (!this.player) {
                return;
            }

            try {
                // Use VideoJS API instead of direct tech access
                let detectedRate = null;

                // Try to get from video element metadata if available
                if (this.videoElement && this.videoElement.videoTracks && this.videoElement.videoTracks.length > 0) {
                    const track = this.videoElement.videoTracks[0];
                    if (track.getSettings && track.getSettings().frameRate) {
                        detectedRate = track.getSettings().frameRate;
                    }
                }

                // Fallback to common frame rates based on duration patterns
                if (!detectedRate && this.duration > 0) {
                    // Use 30fps as safe default for web video
                    detectedRate = 30;
                }

                this.frameRate = detectedRate || 30;
                this.frameDuration = 1 / this.frameRate;

            } catch (e) {
                // Fallback to 30fps on any error
                this.frameRate = 30;
                this.frameDuration = 1 / this.frameRate;
            }
        },

        stepForward() {
            if (!this.player) return;

            // If creating region, expand region end instead of normal frame step
            if (this.isCreatingRegion) {
                this.expandRegionEnd();
                return;
            }

            // Store playing state and pause if needed
            this.wasPlayingBeforeFrame = !this.player.paused();
            if (this.wasPlayingBeforeFrame) {
                this.player.pause();
            }

            const currentTime = this.player.currentTime();
            const newTime = Math.min(currentTime + this.frameDuration, this.duration);
            this.player.currentTime(newTime);

            // Show visual feedback
            this.showFrameNavigationFeedback('forward');
        },

        stepBackward() {
            if (!this.player) return;

            // If creating region, reduce region end instead of normal frame step
            if (this.isCreatingRegion) {
                this.reduceRegionEnd();
                return;
            }

            // Store playing state and pause if needed
            this.wasPlayingBeforeFrame = !this.player.paused();
            if (this.wasPlayingBeforeFrame) {
                this.player.pause();
            }

            const currentTime = this.player.currentTime();
            const newTime = Math.max(currentTime - this.frameDuration, 0);
            this.player.currentTime(newTime);

            // Show visual feedback
            this.showFrameNavigationFeedback('backward');
        },

        showFrameNavigationFeedback(direction) {
            // Clear any existing timeout
            if (this.frameNavigationTimeout) {
                clearTimeout(this.frameNavigationTimeout);
            }

            // Set direction for visual feedback
            this.frameNavigationDirection = direction;

            // Hide feedback after 800ms
            this.frameNavigationTimeout = setTimeout(() => {
                this.frameNavigationDirection = null;
            }, 800);
        },

        toggleFrameHelpers() {
            this.showFrameHelpers = !this.showFrameHelpers;
        },

        // Round timestamp to nearest frame boundary
        roundToNearestFrame(timestamp) {
            if (this.frameDuration <= 0) {
                return timestamp; // Fallback if frame rate not detected
            }

            const frameNumber = Math.round(timestamp / this.frameDuration);
            return frameNumber * this.frameDuration;
        },

        // Get current timestamp aligned to frame boundary
        getCurrentFrameTime() {
            return this.currentFrameState.frameAlignedTime;
        },

        // Calculate frame number from timestamp
        getFrameNumber(timestamp) {
            if (this.frameDuration <= 0) {
                return Math.round(timestamp * 30); // Fallback assuming 30fps
            }

            return Math.round(timestamp / this.frameDuration);
        },

        // Get current frame number
        getCurrentFrameNumber() {
            return this.currentFrameState.frameNumber;
        },

        // Region Management Methods

        startRegionCreation(event) {
            if (this.isCreatingRegion) return;

            const rect = event.currentTarget.getBoundingClientRect();
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clickX = clientX - rect.left;
            const percentage = clickX / rect.width;
            const startTime = percentage * this.duration;

            this.isCreatingRegion = true;
            this.regionCreationStart = {
                time: this.roundToNearestFrame(startTime),
                x: clickX,
                frame: this.getFrameNumber(this.roundToNearestFrame(startTime))
            };

            // Initialize end position to start position
            this.regionCreationEnd = {
                time: this.roundToNearestFrame(startTime),
                x: clickX,
                frame: this.getFrameNumber(this.roundToNearestFrame(startTime))
            };

            // Hide existing regions during creation
            this.showRegionTooltip = null;

            // Add global document listeners for region creation
            this.boundUpdateRegionCreation = this.handleGlobalRegionUpdate.bind(this);
            this.boundFinishRegionCreation = this.handleGlobalRegionFinish.bind(this);

            document.addEventListener('mousemove', this.boundUpdateRegionCreation);
            document.addEventListener('mouseup', this.boundFinishRegionCreation);
            document.addEventListener('touchmove', this.boundUpdateRegionCreation);
            document.addEventListener('touchend', this.boundFinishRegionCreation);

            // Pause video during region creation and seek to start time
            if (this.player) {
                // Always pause first
                if (this.isPlaying) {
                    this.player.pause();
                }
                // Then seek to the region start time
                this.player.currentTime(this.roundToNearestFrame(startTime));
            }
        },

        updateRegionCreation(event) {
            if (!this.isCreatingRegion || !this.regionCreationStart) return;

            const rect = event.currentTarget.getBoundingClientRect();
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const currentX = clientX - rect.left;
            const percentage = currentX / rect.width;
            const currentTime = percentage * this.duration;
            const frameAlignedTime = this.roundToNearestFrame(currentTime);

            // Update visual feedback for region being created
            this.regionCreationEnd = {
                time: frameAlignedTime,
                x: currentX,
                frame: this.getFrameNumber(frameAlignedTime)
            };

            // Real-time video seeking to the end edge for amazing UX
            if (this.player && this.player.readyState() >= 1) {
                this.player.currentTime(frameAlignedTime);
                this.player.pause();
            }
        },

        finishRegionCreation(event) {
            if (!this.isCreatingRegion || !this.regionCreationStart || !this.regionCreationEnd) return;

            // For desktop drag-based creation, use the exact end position from mouse
            // For toolbar-based creation, update to current frame
            if (event && event.type.includes('mouse')) {
                // Desktop drag - keep existing end position from mouse
                // regionCreationEnd is already set by updateRegionCreation
            } else {
                // Toolbar navigation - update region end to current frame
                const frameState = this.currentFrameState;
                const frameAlignedTime = frameState.frameAlignedTime;
                const frameNumber = frameState.frameNumber;

                this.regionCreationEnd = {
                    x: this.regionCreationEnd.x,
                    time: frameAlignedTime,
                    frame: frameNumber
                };
            }

            // Use existing confirmRegionCreation logic
            this.confirmRegionCreation();
        },

        // Explicit finish region creation via button
        confirmRegionCreation() {
            if (!this.isCreatingRegion || !this.regionCreationStart || !this.regionCreationEnd) return;

            const startTime = this.regionCreationStart.time;
            const endTime = this.regionCreationEnd.time;
            const startFrame = this.regionCreationStart.frame;
            const endFrame = this.regionCreationEnd.frame;

            // Ensure minimum region size (at least 2 frames)
            if (Math.abs(endTime - startTime) < this.frameDuration * 2) {
                return; // Don't finish if too small
            }

            // Create region object
            const region = {
                id: `region-${Date.now()}`,
                startTime: Math.min(startTime, endTime),
                endTime: Math.max(startTime, endTime),
                startFrame: Math.min(startFrame, endFrame),
                endFrame: Math.max(startFrame, endFrame),
                title: `Region ${this.regions.length + 1}`,
                description: ''
            };

            // Fire event to add comment with region data
            this.$dispatch('video-annotation:add-comment', {
                timestamp: region.startTime,
                currentTime: region.startTime,
                frameNumber: region.startFrame,
                frameRate: this.frameRate,
                regionData: {
                    startTime: region.startTime,
                    endTime: region.endTime,
                    startFrame: region.startFrame,
                    endFrame: region.endFrame,
                    duration: region.endTime - region.startTime
                }
            });

            // Add region to list if under limit
            if (this.regions.length < this.maxRegions) {
                this.regions.push(region);
                this.autoHideOldestRegions();
            }

            this.cleanupRegionCreation();
        },

        // Arrow key controls for fine-tuning end edge
        expandRegionEnd() {
            if (!this.isCreatingRegion || !this.regionCreationEnd) return;

            const newTime = Math.min(this.regionCreationEnd.time + this.frameDuration, this.duration);
            const newFrame = this.getFrameNumber(newTime);
            const rect = this.$refs.regionBar.getBoundingClientRect();
            const newX = (newTime / this.duration) * rect.width;

            this.regionCreationEnd = {
                time: newTime,
                x: newX,
                frame: newFrame
            };

            // Sync video to new position and ensure it stays paused
            if (this.player && this.player.readyState() >= 1) {
                this.player.currentTime(newTime);
                this.player.pause();
            }
        },

        reduceRegionEnd() {
            if (!this.isCreatingRegion || !this.regionCreationEnd || !this.regionCreationStart) return;

            const newTime = Math.max(this.regionCreationEnd.time - this.frameDuration, this.regionCreationStart.time + this.frameDuration);
            const newFrame = this.getFrameNumber(newTime);
            const rect = this.$refs.regionBar.getBoundingClientRect();
            const newX = (newTime / this.duration) * rect.width;

            this.regionCreationEnd = {
                time: newTime,
                x: newX,
                frame: newFrame
            };

            // Sync video to new position and ensure it stays paused
            if (this.player && this.player.readyState() >= 1) {
                this.player.currentTime(newTime);
                this.player.pause();
            }
        },

        cancelRegionCreation() {
            this.cleanupRegionCreation();
        },

        // Global handlers for region creation (work outside region bar bounds)
        handleGlobalRegionUpdate(event) {
            if (!this.isCreatingRegion || !this.regionCreationStart) return;

            const regionBar = this.$refs.regionBar;
            if (!regionBar) return;

            const rect = regionBar.getBoundingClientRect();
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const currentX = Math.max(0, Math.min(clientX - rect.left, rect.width)); // Constrain to region bar bounds
            const percentage = currentX / rect.width;
            const currentTime = percentage * this.duration;
            const frameAlignedTime = this.roundToNearestFrame(currentTime);

            // Update visual feedback for region being created
            this.regionCreationEnd = {
                time: frameAlignedTime,
                x: currentX,
                frame: this.getFrameNumber(frameAlignedTime)
            };

            // Real-time video seeking to the end edge for amazing UX
            if (this.player && this.player.readyState() >= 1) {
                this.player.currentTime(frameAlignedTime);
                this.player.pause();
            }
        },

        handleGlobalRegionFinish(event) {
            if (!this.isCreatingRegion) return;

            // Don't auto-finish on global mouse up - only via explicit finish button
            // This prevents accidental region creation when user releases mouse outside region bar
            return;
        },

        cleanupRegionCreation() {
            this.isCreatingRegion = false;
            this.regionCreationStart = null;
            this.regionCreationEnd = null;

            // Hide toolbar
            this.showRegionToolbar = false;

            // Prevent ghost clicks for a short time after region creation
            this.pointerState.ghostClickPrevention = true;
            setTimeout(() => {
                this.pointerState.ghostClickPrevention = false;
            }, 300);

            // Remove global document listeners
            if (this.boundUpdateRegionCreation) {
                document.removeEventListener('mousemove', this.boundUpdateRegionCreation);
                document.removeEventListener('touchmove', this.boundUpdateRegionCreation);
                this.boundUpdateRegionCreation = null;
            }
            if (this.boundFinishRegionCreation) {
                document.removeEventListener('mouseup', this.boundFinishRegionCreation);
                document.removeEventListener('touchend', this.boundFinishRegionCreation);
                this.boundFinishRegionCreation = null;
            }
        },

        autoHideOldestRegions() {
            if (this.regions.length > this.maxRegions) {
                // Hide oldest regions that exceed the limit
                const excessCount = this.regions.length - this.maxRegions;
                for (let i = 0; i < excessCount; i++) {
                    this.hiddenRegions.add(this.regions[i].id);
                }
            }
        },

        getRegionPosition(region) {
            if (!this.duration || !this.regionBarWidth) return { left: 0, width: 0 };

            const startPercentage = region.startTime / this.duration;
            const endPercentage = region.endTime / this.duration;

            return {
                left: startPercentage * this.regionBarWidth,
                width: (endPercentage - startPercentage) * this.regionBarWidth
            };
        },

        isRegionVisible(region) {
            return !this.hiddenRegions.has(region.id);
        },

        jumpToRegionStart(region) {
            if (this.player) {
                this.player.currentTime(region.startTime);

                // Fire event for external handling
                this.$dispatch('video-annotation:region-seek', {
                    regionId: region.id,
                    timestamp: region.startTime,
                    frameNumber: region.startFrame
                });
            }
        },

        showRegionTooltipFor(regionId) {
            this.showRegionTooltip = regionId;
        },

        hideRegionTooltips() {
            this.showRegionTooltip = null;
        },

        deleteRegion(regionId) {
            this.regions = this.regions.filter(r => r.id !== regionId);
            this.hiddenRegions.delete(regionId);
            if (this.showRegionTooltip === regionId) {
                this.showRegionTooltip = null;
            }
        },

        toggleRegionVisibility(regionId) {
            if (this.hiddenRegions.has(regionId)) {
                this.hiddenRegions.delete(regionId);
            } else {
                this.hiddenRegions.add(regionId);
            }
        },

        // Load regions from external data
        loadRegions(regionsData) {
            this.regions = regionsData || [];
            this.autoHideOldestRegions();
            this.updateProgressBarWidth(); // Refresh region bar width
        },

        // Get visible regions count
        getVisibleRegionsCount() {
            return this.regions.filter(r => this.isRegionVisible(r)).length;
        },

        // Clear all regions
        clearAllRegions() {
            this.regions = [];
            this.hiddenRegions.clear();
            this.showRegionTooltip = null;
        },

        // Note: Keyboard shortcuts are now handled directly in the template with Alpine.js @keydown directives

        addCommentAtCurrentFrame() {
            if (!this.player || !this.config.annotations?.enableVideoComments) {
                return;
            }

            this.player.pause();
            // Get frame-aligned current time
            const frameAlignedTime = this.getCurrentFrameTime();
            const frameNumber = this.getCurrentFrameNumber();

            // Emit event with frame-aligned timestamp
            this.$dispatch('video-annotation:add-comment', {
                timestamp: frameAlignedTime,
                currentTime: frameAlignedTime,
                frameNumber: frameNumber,
                frameRate: this.frameRate
            });
        },

        // ========================================
        // Unified Input Handling System
        // ========================================

        // Universal pointer event handler - works for both touch and mouse
        handlePointerStart(event, context = 'video') {
            // Prevent ghost clicks if this is a touch event after a recent touch interaction
            if (event.type === 'mousedown' && this.pointerState.ghostClickPrevention) {
                event.preventDefault();
                return false;
            }

            // Determine input type
            const isTouch = event.type.includes('touch');
            const clientX = isTouch ? event.touches[0].clientX : event.clientX;
            const clientY = isTouch ? event.touches[0].clientY : event.clientY;

            // Initialize pointer state
            this.pointerState = {
                isDown: true,
                startTime: Date.now(),
                startPos: { x: clientX, y: clientY },
                currentPos: { x: clientX, y: clientY },
                hasMoved: false,
                longPressTriggered: false,
                ghostClickPrevention: isTouch,
                activePointer: isTouch ? 'touch' : 'mouse'
            };

            // Set ghost click prevention timeout for touch events
            if (isTouch) {
                clearTimeout(this.clickPreventionTimeout);
                this.clickPreventionTimeout = setTimeout(() => {
                    this.pointerState.ghostClickPrevention = false;
                }, 400);
            }

            // Mark user interaction
            this.hasUserInteracted = true;

            // Start long press detection for annotations
            if (context === 'video' && this.config.annotations?.enableVideoComments) {
                this.longPressTimeout = setTimeout(() => {
                    if (!this.pointerState.hasMoved && this.pointerState.isDown) {
                        this.handleLongPress(event, context);
                    }
                }, this.config.timing.longPressDuration || 500);
            }

            return true;
        },

        handlePointerMove(event) {
            if (!this.pointerState.isDown) return;

            const isTouch = event.type.includes('touch');
            const clientX = isTouch ? event.touches[0].clientX : event.clientX;
            const clientY = isTouch ? event.touches[0].clientY : event.clientY;

            // Update current position
            this.pointerState.currentPos = { x: clientX, y: clientY };

            // Check if moved significantly
            const deltaX = Math.abs(clientX - this.pointerState.startPos.x);
            const deltaY = Math.abs(clientY - this.pointerState.startPos.y);
            const threshold = isTouch ? 10 : 5; // Larger threshold for touch

            if (deltaX > threshold || deltaY > threshold) {
                this.pointerState.hasMoved = true;

                // Cancel long press if user moves
                if (this.longPressTimeout) {
                    clearTimeout(this.longPressTimeout);
                    this.longPressTimeout = null;
                }
            }
        },

        handlePointerEnd(event, context = 'video') {
            if (!this.pointerState.isDown) return;

            const duration = Date.now() - this.pointerState.startTime;
            const wasLongPress = this.pointerState.longPressTriggered;
            const wasMoved = this.pointerState.hasMoved;

            // Clear long press timeout
            if (this.longPressTimeout) {
                clearTimeout(this.longPressTimeout);
                this.longPressTimeout = null;
            }

            // Handle tap/click actions
            if (!wasLongPress && !wasMoved && duration < 300) {
                this.handleTap(event, context);
            }

            // Reset pointer state
            this.pointerState.isDown = false;
            this.pointerState.longPressTriggered = false;
        },

        handleTap(event, context) {
            if (context === 'video') {
                // Use the existing handleVideoClick logic to maintain consistency
                this.handleVideoClick();
            } else if (context === 'button') {
                // Handled by specific button action in template
                return;
            }
        },

        handleLongPress(event, context) {
            this.pointerState.longPressTriggered = true;

            // Add haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(100);
            }

            if (context === 'video') {
                if (this.touchInterface.mode === 'NORMAL') {
                    // Directly add comment instead of showing context menu
                    this.addCommentAtCurrentFrame();
                } else if (this.touchInterface.mode === 'REGION_CREATE') {
                    this.exitRegionCreationMode();
                }
            } else if (context === 'progressbar' && this.config.annotations?.enableProgressBarComments) {
                // Add comment at current position
                const rect = event.currentTarget.getBoundingClientRect();
                const clientX = this.pointerState.activePointer === 'touch' ?
                    event.touches?.[0]?.clientX || this.pointerState.startPos.x :
                    this.pointerState.startPos.x;
                const clickX = clientX - rect.left;
                const percentage = clickX / rect.width;
                const targetTime = percentage * this.duration;
                const frameAlignedTime = this.roundToNearestFrame(targetTime);

                this.$dispatch('video-annotation:add-comment', {
                    timestamp: frameAlignedTime,
                    currentTime: frameAlignedTime,
                    frameNumber: this.getFrameNumber(frameAlignedTime),
                    frameRate: this.frameRate
                });
            }
        },

        // ========================================
        // Touch Interface Methods (Hammer.js)
        // ========================================

        showTouchContextMenu(event) {
            if (!this.config.annotations?.enableContextMenu) return;

            // Pause video and capture current time
            if (this.player) {
                this.player.pause();
            }

            this.touchInterface.mode = 'CONTEXT_MENU';
            this.touchInterface.contextMenuVisible = true;

            // Add haptic feedback
            if (this.config.annotations.enableHapticFeedback) {
                this.triggerHapticFeedback(100);
            }
        },

        hideTouchContextMenu() {
            this.touchInterface.mode = 'NORMAL';
            this.touchInterface.contextMenuVisible = false;
        },

        toggleUnifiedTimeline() {
            this.touchInterface.unifiedTimelineVisible = !this.touchInterface.unifiedTimelineVisible;

            // Add haptic feedback
            if (this.config.annotations.enableHapticFeedback) {
                this.triggerHapticFeedback(50);
            }
        },

        handleTouchPanStart(event) {
            if (!this.player) return;

            // Determine what we're panning based on current mode and position
            const panY = event.deltaY;
            const panX = event.deltaX;

            if (this.touchInterface.mode === 'NORMAL') {
                // Check if pan is primarily horizontal (timeline scrubbing)
                if (Math.abs(panX) > Math.abs(panY)) {
                    this.touchInterface.mode = 'SCRUBBING';
                    // Remember if video was playing before scrubbing
                    this.wasPlayingBeforeDrag = this.isPlaying;
                    if (this.wasPlayingBeforeDrag) {
                        this.player.pause();
                    }
                }
            }
        },

        handleTouchPanMove(event) {
            if (!this.player) return;

            if (this.touchInterface.mode === 'SCRUBBING') {
                // Calculate new time based on pan distance
                const containerWidth = this.$el.offsetWidth;
                const panPercentage = event.deltaX / containerWidth;
                const timeChange = panPercentage * this.duration;

                // Constrain to video bounds
                const newTime = Math.max(0, Math.min(this.currentTime + timeChange, this.duration));

                this.player.currentTime(newTime);
            }
        },

        handleTouchPanEnd(event) {
            if (this.touchInterface.mode === 'SCRUBBING') {
                // Resume playing if it was playing before
                if (this.wasPlayingBeforeDrag && this.player) {
                    this.player.play().catch(e => {
                        console.debug("Play after scrub interrupted:", e.name);
                    });
                }

                this.touchInterface.mode = 'NORMAL';
                this.wasPlayingBeforeDrag = false;
            }
        },

        enterRegionCreationMode() {
            this.touchInterface.mode = 'REGION_CREATE';
            this.touchInterface.actionModalVisible = true;
            this.isCreatingRegion = true;

            // Pause video
            if (this.player && this.isPlaying) {
                this.player.pause();
            }
        },

        exitRegionCreationMode() {
            // Force reactivity update
            this.touchInterface = {
                ...this.touchInterface,
                mode: 'NORMAL',
                actionModalVisible: false
            };
            this.isCreatingRegion = false;
            this.cleanupRegionCreation();

            console.log('Region creation exited:', {
                mode: this.touchInterface.mode,
                modalVisible: this.touchInterface.actionModalVisible
            });
        },

        // Touch-optimized region creation
        createRegionAtCurrentTime() {
            if (!this.player) return;

            // Set mode first - force reactivity update
            this.touchInterface = {
                ...this.touchInterface,
                mode: 'REGION_CREATE',
                actionModalVisible: true
            };
            this.isCreatingRegion = true;

            const currentTime = this.getCurrentFrameTime();
            const frameNumber = this.getCurrentFrameNumber();

            // Create a 2-second region centered on current time
            const regionDuration = 2.0;
            const startTime = Math.max(0, currentTime - regionDuration / 2);
            const endTime = Math.min(this.duration, currentTime + regionDuration / 2);

            this.regionCreationStart = {
                time: startTime,
                x: 0, // Not used in touch mode but needed for compatibility
                frame: this.getFrameNumber(startTime)
            };

            this.regionCreationEnd = {
                time: endTime,
                x: 0, // Not used in touch mode but needed for compatibility
                frame: this.getFrameNumber(endTime)
            };

            // Pause video
            if (this.player && this.isPlaying) {
                this.player.pause();
            }

            // Ensure the interface updates
            this.$nextTick(() => {
                console.log('Region creation started:', {
                    start: this.regionCreationStart,
                    end: this.regionCreationEnd,
                    mode: this.touchInterface.mode,
                    modalVisible: this.touchInterface.actionModalVisible
                });
            });
        },

        // Touch-based region adjustment methods
        expandRegionEnd() {
            if (!this.isCreatingRegion || !this.regionCreationEnd) return;

            // Use frame-perfect duration instead of 0.5 seconds
            const newTime = Math.min(this.duration, this.regionCreationEnd.time + this.frameDuration);
            const newFrame = this.getFrameNumber(newTime);

            // Update x position for visual alignment
            const rect = this.$refs.regionBar?.getBoundingClientRect();
            const newX = rect ? (newTime / this.duration) * rect.width : this.regionCreationEnd.x;

            this.regionCreationEnd = {
                time: newTime,
                frame: newFrame,
                x: newX
            };

            // Seek video to show the change
            this.player.currentTime(newTime);
            this.player.pause();

            // Haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(50);
            }
        },

        shrinkRegionEnd() {
            if (!this.isCreatingRegion || !this.regionCreationEnd || !this.regionCreationStart) return;

            // Use frame-perfect duration instead of 0.5 seconds
            const minTime = this.regionCreationStart.time + this.frameDuration; // Keep minimum 1 frame region
            const newTime = Math.max(minTime, this.regionCreationEnd.time - this.frameDuration);
            const newFrame = this.getFrameNumber(newTime);

            // Update x position for visual alignment
            const rect = this.$refs.regionBar?.getBoundingClientRect();
            const newX = rect ? (newTime / this.duration) * rect.width : this.regionCreationEnd.x;

            this.regionCreationEnd = {
                time: newTime,
                frame: newFrame,
                x: newX
            };

            // Seek video to show the change
            this.player.currentTime(newTime);
            this.player.pause();

            // Haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(50);
            }
        },

        expandRegionStart() {
            if (!this.isCreatingRegion || !this.regionCreationStart) return;

            // Use frame-perfect duration instead of 0.5 seconds
            const newTime = Math.max(0, this.regionCreationStart.time - this.frameDuration);
            const newFrame = this.getFrameNumber(newTime);

            // Update x position for visual alignment
            const rect = this.$refs.regionBar?.getBoundingClientRect();
            const newX = rect ? (newTime / this.duration) * rect.width : this.regionCreationStart.x;

            this.regionCreationStart = {
                time: newTime,
                frame: newFrame,
                x: newX
            };

            // Seek video to show the change
            this.player.currentTime(newTime);
            this.player.pause();

            // Haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(50);
            }
        },

        shrinkRegionStart() {
            if (!this.isCreatingRegion || !this.regionCreationStart || !this.regionCreationEnd) return;

            // Use frame-perfect duration instead of 0.5 seconds
            const maxTime = this.regionCreationEnd.time - this.frameDuration; // Keep minimum 1 frame region
            const newTime = Math.min(maxTime, this.regionCreationStart.time + this.frameDuration);
            const newFrame = this.getFrameNumber(newTime);

            // Update x position for visual alignment
            const rect = this.$refs.regionBar?.getBoundingClientRect();
            const newX = rect ? (newTime / this.duration) * rect.width : this.regionCreationStart.x;

            this.regionCreationStart = {
                time: newTime,
                frame: newFrame,
                x: newX
            };

            // Seek video to show the change
            this.player.currentTime(newTime);
            this.player.pause();

            // Haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(50);
            }
        },

        // Simplified region creation - no modal needed!
        startSimpleRegionCreation() {
            if (!this.player) return;

            // Set mode to region creation
            this.touchInterface = {
                ...this.touchInterface,
                mode: 'REGION_CREATE'
            };
            this.isCreatingRegion = true;

            // Reset region creation state for fresh start
            this.regionCreationStart = null;
            this.regionCreationEnd = null;

            // Pause video for precise frame selection
            if (this.isPlaying) {
                this.player.pause();
            }

            // Region creation started - user will set start/end with buttons
            console.log('Mobile region creation mode activated');
        },

        // Jump frames helper for region creation
        jumpFrames(frameCount) {
            if (!this.player) return;

            const currentTime = this.player.currentTime();
            const newTime = Math.max(0, Math.min(currentTime + (frameCount * this.frameDuration), this.duration));
            this.player.currentTime(newTime);

            // Add haptic feedback for frame jumps
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(30);
            }
        },

        // Set region start at current frame
        setRegionStart() {
            if (!this.player) return;

            const currentTime = this.getCurrentFrameTime();
            const frameNumber = this.getCurrentFrameNumber();

            this.regionCreationStart = {
                time: currentTime,
                x: 0, // Not used in mobile mode
                frame: frameNumber
            };

            // Haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(100);
            }

            console.log('Region start set at frame:', frameNumber);
        },

        // Set region end at current frame
        setRegionEnd() {
            if (!this.player || !this.regionCreationStart) return;

            const currentTime = this.getCurrentFrameTime();
            const frameNumber = this.getCurrentFrameNumber();

            // Ensure end is after start
            if (frameNumber <= this.regionCreationStart.frame) {
                // Auto-adjust to be at least 1 frame after start
                const minEndTime = this.regionCreationStart.time + this.frameDuration;
                this.player.currentTime(minEndTime);
                this.regionCreationEnd = {
                    time: minEndTime,
                    x: 0,
                    frame: this.regionCreationStart.frame + 1
                };
            } else {
                this.regionCreationEnd = {
                    time: currentTime,
                    x: 0,
                    frame: frameNumber
                };
            }

            // Haptic feedback
            if (this.config.annotations?.enableHapticFeedback) {
                this.triggerHapticFeedback(100);
            }

            console.log('Region end set at frame:', this.regionCreationEnd.frame);
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

        // Unified progress bar interaction handler
        handleProgressBarPointer(event, action = 'click') {
            // Prevent ghost clicks
            if (event.type === 'mousedown' && this.pointerState.ghostClickPrevention) {
                event.preventDefault();
                return;
            }

            // Mark user interaction
            this.hasUserInteracted = true;
            this.hideContextMenu();

            // Clear progress bar timeout
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
            }

            const rect = event.currentTarget.getBoundingClientRect();
            const isTouch = event.type.includes('touch');
            const clientX = isTouch ? event.touches?.[0]?.clientX || event.changedTouches?.[0]?.clientX : event.clientX;
            const clickX = clientX - rect.left;
            const percentage = clickX / rect.width;
            const targetTime = percentage * this.duration;

            if (action === 'click' || action === 'tap') {
                // Handle single click/tap - seek to position
                if (this.player) {
                    this.player.currentTime(targetTime);
                }

                // Update visual position
                this.hoverX = clickX;
                this.dragCurrentTime = targetTime;
            } else if (action === 'doubleclick') {
                // Handle double click/tap - add comment
                if (this.config.annotations?.enableProgressBarComments) {
                    const frameAlignedTime = this.roundToNearestFrame(targetTime);
                    const frameNumber = this.getFrameNumber(frameAlignedTime);
                    this.$dispatch('video-annotation:add-comment', {
                        timestamp: frameAlignedTime,
                        currentTime: frameAlignedTime,
                        frameNumber: frameNumber,
                        frameRate: this.frameRate
                    });
                }
            }
        },

        // Unified drag handling for progress bar seek circle
        handleProgressBarDragStart(event) {
            if (!this.handlePointerStart(event, 'progressbar-drag')) return;

            // Remember if video was playing
            this.wasPlayingBeforeDrag = this.isPlaying;

            this.isDragging = true;
            this.showSeekCircle = true;
            this.showTooltip = true;
            this.showProgressBar = true;

            // Clear auto-hide timeout
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
                this.progressBarTimeout = null;
            }

            const progressBar = this.$refs.progressBar;
            if (!progressBar) return;

            const rect = progressBar.getBoundingClientRect();
            const isTouch = event.type.includes('touch');
            const clientX = isTouch ? event.touches[0].clientX : event.clientX;

            this.dragStartX = clientX - rect.left;
            // Removed circle positioning: this.seekCircleX = this.dragStartX;

            const percentage = this.dragStartX / rect.width;
            this.dragCurrentTime = percentage * this.duration;

            // Add global listeners for unified drag handling
            const moveEvent = isTouch ? 'touchmove' : 'mousemove';
            const endEvent = isTouch ? 'touchend' : 'mouseup';

            this.boundHandleDragMove = this.handleProgressBarDragMove.bind(this);
            this.boundEndDrag = this.handleProgressBarDragEnd.bind(this);

            document.addEventListener(moveEvent, this.boundHandleDragMove, { passive: false });
            document.addEventListener(endEvent, this.boundEndDrag);
        },

        handleProgressBarDragMove(event) {
            if (!this.isDragging) return;

            this.handlePointerMove(event);

            const progressBar = this.$refs.progressBar;
            if (!progressBar) return;

            const rect = progressBar.getBoundingClientRect();
            const isTouch = event.type.includes('touch');
            const clientX = isTouch ? event.touches[0].clientX : event.clientX;
            let newX = clientX - rect.left;

            // Constrain to bounds
            newX = Math.max(0, Math.min(newX, rect.width));

            // Removed circle positioning: this.seekCircleX = newX;
            this.hoverX = newX;
            const percentage = newX / rect.width;
            this.dragCurrentTime = percentage * this.duration;
        },

        handleProgressBarDragEnd(event) {
            if (!this.isDragging) return;

            this.handlePointerEnd(event, 'progressbar-drag');

            // Seek to final position
            if (this.player && this.player.readyState() >= 1) {
                const targetTime = Math.max(0, Math.min(this.dragCurrentTime || 0, this.duration || 0));
                if (isFinite(targetTime)) {
                    this.player.currentTime(targetTime);
                    this.currentTime = targetTime;
                    // Circle positioning removed

                    // Resume playing if was playing before
                    if (this.wasPlayingBeforeDrag) {
                        this.player.play().catch(e => {
                            console.debug('Play after drag interrupted:', e.name);
                        });
                    }
                }
            }

            // Clean up
            this.isDragging = false;
            this.wasPlayingBeforeDrag = false;

            // Remove global listeners
            const moveEvent = this.pointerState.activePointer === 'touch' ? 'touchmove' : 'mousemove';
            const endEvent = this.pointerState.activePointer === 'touch' ? 'touchend' : 'mouseup';

            document.removeEventListener(moveEvent, this.boundHandleDragMove);
            document.removeEventListener(endEvent, this.boundEndDrag);
            this.boundHandleDragMove = null;
            this.boundEndDrag = null;

            // Hide UI elements after a delay
            setTimeout(() => {
                this.showTooltip = false;
                this.showSeekCircle = false;
            }, 100);

            // Resume auto-hide if needed
            if (this.progressBarMode === 'auto-hide' && this.isPlaying) {
                this.progressBarTimeout = setTimeout(() => {
                    this.showProgressBar = false;
                }, this.config.timing.progressBarAutoHideDelay);
            }
        },

        updateHoverPosition(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            this.hoverX = event.clientX - rect.left;

            // Only update drag time for tooltip, don't move circle unless dragging
            if (!this.isDragging) {
                const percentage = this.hoverX / rect.width;
                this.dragCurrentTime = percentage * this.duration;
            }
        },

        onProgressBarMouseEnter(event) {
            if (!this.isTouchDevice()) {
                this.showSeekCircle = true;
                this.showTooltip = true;
                // Show circle at current progress position (aligned with progress fill)
                // Circle positioning removed
                // Update hover position for tooltip only
                this.updateHoverPosition(event);
            }
        },

        onProgressBarMouseLeave() {
            // Always hide tooltip when leaving, regardless of mode
            this.showTooltip = false;

            // Only hide circle if not dragging and not in always-visible mode
            if (!this.isDragging) {
                this.showSeekCircle = false;
            }
        },



        startDrag(event) {
            // Only handle mousedown events for dragging, let touch events use separate handler
            if (event.type === 'touchstart') {
                return;
            }

            // Remember if video was playing before drag
            this.wasPlayingBeforeDrag = this.isPlaying;

            this.isDragging = true;
            this.showSeekCircle = true;
            this.showTooltip = true;
            this.showProgressBar = true; // Force show progress bar while dragging

            // Clear any auto-hide timeout while dragging
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
                this.progressBarTimeout = null;
            }

            // Get the progress bar rect, not the circle rect
            const progressBar = this.$refs.progressBar;
            if (!progressBar) {
                return;
            }

            const rect = progressBar.getBoundingClientRect();
            this.dragStartX = event.clientX - rect.left;
            // Removed circle positioning: this.seekCircleX = this.dragStartX;

            // Calculate initial time
            const percentage = this.dragStartX / rect.width;
            this.dragCurrentTime = percentage * this.duration;

            // Bind methods to this context for proper removal
            this.boundHandleDragMove = this.handleDragMove.bind(this);
            this.boundEndDrag = this.endDrag.bind(this);

            // Add global mouse move and up listeners
            document.addEventListener('mousemove', this.boundHandleDragMove);
            document.addEventListener('mouseup', this.boundEndDrag);
        },

        // Simple touch start for circle
        startCircleDrag(event) {

            // Remember if video was playing before drag
            this.wasPlayingBeforeDrag = this.isPlaying;

            this.isDragging = true;
            this.showSeekCircle = true;
            this.showTooltip = true;
            this.showProgressBar = true; // Force show progress bar while dragging

            // Clear any auto-hide timeout while dragging
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
                this.progressBarTimeout = null;
            }

            const progressBar = this.$refs.progressBar;
            if (!progressBar) return;

            const rect = progressBar.getBoundingClientRect();
            const touch = event.touches[0];
            this.dragStartX = touch.clientX - rect.left;
            // Removed circle positioning: this.seekCircleX = this.dragStartX;

            const percentage = this.dragStartX / rect.width;
            this.dragCurrentTime = percentage * this.duration;
        },

        // Simple touch move for circle
        handleTouchDragMove(event) {
            if (!this.isDragging) return;

            const progressBar = this.$refs.progressBar;
            if (!progressBar) return;

            const rect = progressBar.getBoundingClientRect();
            const touch = event.touches[0];
            let newX = touch.clientX - rect.left;

            newX = Math.max(0, Math.min(newX, rect.width));


            // Removed circle positioning: this.seekCircleX = newX;
            this.hoverX = newX;
            const percentage = newX / rect.width;
            this.dragCurrentTime = percentage * (this.duration || 0);
        },

        // Simple touch end for circle
        endTouchDrag(event) {
            if (!this.isDragging) return;


            // Seek to the dragged position
            if (this.player && this.player.readyState() >= 1) {
                // Validate that dragCurrentTime is finite and within bounds
                const targetTime = Math.max(0, Math.min(this.dragCurrentTime || 0, this.duration || 0));

                if (isFinite(targetTime)) {
                    this.player.currentTime(targetTime);
                    this.currentTime = targetTime;
                    // Circle positioning removed

                    // Resume playing if video was playing before drag
                    if (this.wasPlayingBeforeDrag) {
                        this.player.play().catch(e => {
                            // Silently handle play failure
                        });
                    }
                } else {
                    console.warn('Invalid time value:', this.dragCurrentTime);
                }
            }

            // Reset state
            this.isDragging = false;
            this.wasPlayingBeforeDrag = false;

            // Hide UI elements
            setTimeout(() => {
                this.showTooltip = false;
                this.showSeekCircle = false;
            }, 100);

            // Resume auto-hide behavior if in auto-hide mode and video is playing
            if (this.progressBarMode === 'auto-hide' && this.isPlaying) {
                this.progressBarTimeout = setTimeout(() => {
                    this.showProgressBar = false;
                }, this.config.timing.progressBarAutoHideDelay);
            }
        },

        handleDragMove(event) {
            if (!this.isDragging) return;

            const progressBar = this.$refs.progressBar;
            if (!progressBar) return;

            const rect = progressBar.getBoundingClientRect();
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            let newX = clientX - rect.left;

            // Constrain to progress bar bounds
            newX = Math.max(0, Math.min(newX, rect.width));

            // Update both circle position and drag time during drag
            // Removed circle positioning: this.seekCircleX = newX;
            this.hoverX = newX; // Also update hover position for tooltip
            const percentage = newX / rect.width;
            this.dragCurrentTime = percentage * this.duration;
        },

        endDrag(event) {
            if (!this.isDragging) return;


            // Seek to the dragged position
            if (this.player && this.player.readyState() >= 1) {
                this.player.currentTime(this.dragCurrentTime);
                // Force update the current time immediately for UI consistency
                this.currentTime = this.dragCurrentTime;
                // Update circle position to match the seek
                // Circle positioning removed

                // Resume playing if video was playing before drag
                if (this.wasPlayingBeforeDrag) {
                    this.player.play().catch(e => {
                        // Silently handle play failure
                    });
                }
            } else {
                // If player isn't ready, wait a bit and try again
                setTimeout(() => {
                    if (this.player) {
                        this.player.currentTime(this.dragCurrentTime);
                        this.currentTime = this.dragCurrentTime;
                        // Circle positioning removed

                        // Resume playing if video was playing before drag
                        if (this.wasPlayingBeforeDrag) {
                            this.player.play().catch(e => {
                                // Silently handle play failure
                            });
                        }
                    }
                }, 50);
            }

            // Remove global listeners using bound references
            if (this.boundHandleDragMove) {
                document.removeEventListener('mousemove', this.boundHandleDragMove);
                this.boundHandleDragMove = null;
            }
            if (this.boundEndDrag) {
                document.removeEventListener('mouseup', this.boundEndDrag);
                this.boundEndDrag = null;
            }

            // Set dragging to false after seek attempt
            this.isDragging = false;
            this.wasPlayingBeforeDrag = false;

            // Hide tooltip and circle after drag unless mouse is still hovering
            setTimeout(() => {
                // Force hide tooltip and circle - let mouse enter trigger them again if still hovering
                this.showTooltip = false;
                this.showSeekCircle = false;
            }, 100);

            // Resume auto-hide behavior if in auto-hide mode and video is playing
            if (this.progressBarMode === 'auto-hide' && this.isPlaying) {
                this.progressBarTimeout = setTimeout(() => {
                    this.showProgressBar = false;
                }, this.config.timing.progressBarAutoHideDelay);
            }
        },

        addCommentAtPosition() {
            // Use the stored hover position instead of trying to parse styles
            const progressBar = this.$refs.progressBar;
            const rect = progressBar.getBoundingClientRect();
            const percentage = this.hoverX / rect.width;
            const targetTime = percentage * this.duration;

            // Emit event with frame-aligned timestamp in seconds (Laravel/VideoJS standard)
            const frameAlignedTime = this.roundToNearestFrame(targetTime);
            const frameNumber = this.getFrameNumber(frameAlignedTime);
            this.$dispatch('video-annotation:add-comment', {
                timestamp: frameAlignedTime,  // Where to add comment (frame-aligned)
                currentTime: frameAlignedTime,  // Same as timestamp for progress bar actions
                frameNumber: frameNumber,  // Frame number for reference
                frameRate: this.frameRate  // Include frame rate for context
            });

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

            // Cleanup Hammer.js
            if (this.hammer) {
                this.hammer.destroy();
                this.hammer = null;
            }

            // Note: Window keyboard events are automatically cleaned up by Alpine.js
        },

        // Custom control methods
        togglePlay() {
            if (!this.player) return;

            // Mark that user has interacted with the page
            this.hasUserInteracted = true;

            if (this.isPlaying) {
                this.player.pause();
            } else {
                // Handle play promise to avoid AbortError
                const playPromise = this.player.play();
                if (playPromise !== undefined) {
                    playPromise.catch(e => {
                        // Handle play interruption silently
                        console.debug("Play interrupted:", e.name);
                    });
                }
            }

            // Ensure keyboard shortcuts work after interaction
            this.ensureFocus();
        },

        ensureFocus() {
            // Ensure the component container has focus for keyboard shortcuts
            if (this.$el && !this.$el.contains(document.activeElement)) {
                this.$el.focus();
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
            console.log('handleVideoClick called');
            if (!this.player) {
                console.log('No player available');
                return;
            }

            // Debounce rapid clicks to prevent immediate play/pause toggling
            const now = Date.now();
            if (this.lastVideoClickTime && (now - this.lastVideoClickTime) < 300) {
                console.log('Debounced - too rapid');
                return; // Ignore rapid clicks within 300ms
            }
            this.lastVideoClickTime = now;

            // If video hasn't loaded yet, load it first
            if (!this.videoLoaded) {
                this.player.load();
                // Don't auto-play after loading - wait for explicit user interaction
                return;
            }

            // Always toggle play/pause
            this.togglePlay();

            // On touch devices, also toggle progress bar visibility (better UX)
            if (this.isTouchDevice() && this.progressBarMode === 'auto-hide') {
                this.toggleProgressBarVisibility();
            }

            // Ensure focus for keyboard shortcuts
            this.ensureFocus();
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
                        // Silently handle play failure
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

        // Touch event handlers for video area (works alongside Hammer.js)
        handleTouchStart(event) {
            // Skip if we have a recent pointer interaction to prevent conflicts
            if (this.pointerState.ghostClickPrevention) {
                return;
            }

            // If Hammer.js is enabled, only handle basic touch tracking
            if (this.touchInterface.enabled && this.hammer) {
                // Let Hammer.js handle the gesture recognition
                // Just track basic touch state for compatibility
                this.touchStartTime = Date.now();
                const touch = event.touches && event.touches[0] ? event.touches[0] : event;
                this.touchStartPos = {
                    x: touch.clientX || touch.pageX || 0,
                    y: touch.clientY || touch.pageY || 0
                };
                this.isTouchMove = false;
                return;
            }

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
                    if (!this.isTouchMove && this.player && !this.progressBarLongPressTriggered) {
                        // Pause video and add comment at current time
                        this.player.pause();


                        // Add haptic feedback if enabled
                        if (this.config.annotations.enableHapticFeedback) {
                            this.triggerHapticFeedback(100);
                        }

                        // Emit event with frame-aligned timestamp in seconds (Laravel/VideoJS standard)
                        const frameAlignedTime = this.getCurrentFrameTime();
                        const frameNumber = this.getCurrentFrameNumber();
                        this.$dispatch('video-annotation:add-comment', {
                            timestamp: frameAlignedTime,
                            currentTime: frameAlignedTime,
                            frameNumber: frameNumber,
                            frameRate: this.frameRate
                        });

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
            if (this.touchStartPos && event.touches && event.touches[0]) {
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

            // Reset progress bar long press flag
            this.progressBarLongPressTriggered = false;

            // Remove touch move listener
            document.removeEventListener('touchmove', this.handleTouchMoveDetection);

            // If Hammer.js is enabled, let it handle the gesture
            if (this.touchInterface.enabled && this.hammer) {
                // Just reset touch state, Hammer.js handles the action
                this.touchStartPos = { x: 0, y: 0 };
                return;
            }

            // Only handle tap if it was a short touch and minimal movement
            // Skip if unified pointer system is handling this
            if (touchDuration < 300 && !this.isTouchMove && !this.pointerState.ghostClickPrevention) {
                this.handleVideoClick();
            }

            // Reset touch state
            this.touchStartPos = { x: 0, y: 0 };
        },

        // Progress bar touch handlers
        onProgressBarTouchStart(event) {
            // Check if touching the circle - if so, don't handle here
            const target = event.target;
            const circleElement = target.closest('.seek-circle, [data-seek-circle]');
            if (circleElement || this.isDragging) {
                return; // Let circle handle its own touch
            }


            // Show circle on touch for mobile progress bar area
            this.showSeekCircle = true;
            this.showTooltip = true;
            // Circle positioning removed

            // Update touch position for tooltip and store for progress bar
            const touch = event.touches[0];
            const rect = event.currentTarget.getBoundingClientRect();
            this.hoverX = touch.clientX - rect.left;
            const percentage = this.hoverX / rect.width;
            this.dragCurrentTime = percentage * this.duration;

            this.touchStartTime = Date.now();
            this.isTouchMove = false;

            // Clear initial hide timeout on interaction
            if (this.progressBarTimeout) {
                clearTimeout(this.progressBarTimeout);
            }

            // Start long press timer for mobile add comment (only if annotations enabled)
            if (this.config.annotations.enableProgressBarComments) {
                this.longPressActive = true;
                this.longPressTimeout = setTimeout(() => {
                    if (!this.isTouchMove && !this.isDragging && this.longPressActive) {
                        // Long press detected - add comment
                        const percentage = this.hoverX / rect.width;
                        const targetTime = percentage * this.duration;

                        // Add haptic feedback if available (cross-browser)
                        if (this.config.annotations.enableHapticFeedback) {
                            this.triggerHapticFeedback(100);
                        }

                        // Emit event with frame-aligned timestamp in seconds (Laravel/VideoJS standard)
                        const frameAlignedTime = this.roundToNearestFrame(targetTime);
                        this.$dispatch('video-annotation:add-comment', {
                            timestamp: frameAlignedTime,  // Where to add comment (frame-aligned)
                            currentTime: frameAlignedTime  // Same as timestamp for progress bar actions
                        });

                        // Prevent video long press from also firing
                        this.progressBarLongPressTriggered = true;
                    }
                }, 500); // 500ms long press
            }
        },

        onProgressBarTouchMove(event) {
            // Don't handle if circle is being dragged
            if (this.isDragging) {
                return;
            }

            this.isTouchMove = true;

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
            // Don't handle if circle dragging just ended
            if (this.isDragging) {
                return;
            }

            const touchEndTime = Date.now();
            const touchDuration = touchEndTime - this.touchStartTime;
            // Cancel long press timeout if still active
            this.longPressActive = false;
            if (this.longPressTimeout) {
                clearTimeout(this.longPressTimeout);
                this.longPressTimeout = null;
            }

            // Handle tap to seek (short touch without movement)
            if (touchDuration < 500 && !this.isTouchMove) {
                const touch = event.changedTouches[0];
                const rect = event.currentTarget.getBoundingClientRect();
                const clickX = touch.clientX - rect.left;
                const percentage = clickX / rect.width;
                const newTime = percentage * this.duration;

                if (this.player) {
                    this.player.currentTime(newTime);
                }
            }

            // Hide circle and tooltip after touch interaction (unless dragging)
            if (!this.isDragging) {
                setTimeout(() => {
                    this.showSeekCircle = false;
                    this.showTooltip = false;
                }, 100);
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
                this.$dispatch('video-annotation:seek-comment', { commentId: comment.commentId, timestamp: comment.timestamp });
            }
        },

        // Mobile comment interaction handlers
        handleCommentClick(event, comment) {

            // Always load the comment
            this.loadComment(comment.commentId);

            // Check if this is a touch device using enhanced detection
            if (this.isTouchDevice()) {
                // On mobile, toggle comment tooltip visibility
                if (this.activeCommentId === comment.commentId) {
                    // If already active, hide it and seek to comment
                    this.activeCommentId = null;
                    this.$dispatch('video-annotation:seek-comment', { commentId: comment.commentId, timestamp: comment.timestamp });
                } else {
                    // Show this comment's tooltip
                    this.activeCommentId = comment.commentId;
                }
            } else {
                // On desktop, just seek to comment
                this.$dispatch('video-annotation:seek-comment', { commentId: comment.commentId, timestamp: comment.timestamp });
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
                // Check if user has interacted with the page first
                if (!this.hasUserInteracted) {
                    return;
                }

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
            // Always show progress bar initially by default
            this.showProgressBar = true;

            // Check actual player state to determine visibility behavior
            if (this.progressBarMode === 'auto-hide') {
                // Only start auto-hide timer if video is actually playing
                if (this.player && this.isPlaying) {
                    this.progressBarTimeout = setTimeout(() => {
                        this.showProgressBar = false;
                    }, this.config.timing.progressBarAutoHideDelay);
                }
                // If paused (default state), keep progress bar visible
            }
            // If always-visible mode, keep progress bar visible
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

        toggleProgressBarVisibility() {
            if (this.progressBarMode === 'auto-hide') {
                // Clear any existing timeout
                if (this.progressBarTimeout) {
                    clearTimeout(this.progressBarTimeout);
                }

                // Toggle visibility
                this.showProgressBar = !this.showProgressBar;

                // If we just showed it, start auto-hide timer
                if (this.showProgressBar) {
                    this.progressBarTimeout = setTimeout(() => {
                        this.showProgressBar = false;
                    }, this.config.timing.progressBarAutoHideDelay);
                }
            }
        },

        // Right-click context menu handlers
        handleVideoRightClick(event) {
            if (!this.isTouchDevice() && this.player) {
                // Only show custom context menu if annotations are enabled
                if (this.config.annotations.enableContextMenu) {
                    // Pause video and capture time
                    this.player.pause();
                    this.contextMenuTime = this.player.currentTime();

                    // Position context menu
                    this.contextMenuX = event.clientX;
                    this.contextMenuY = event.clientY;
                    this.showContextMenu = true;

                }
            }
        },


        addCommentFromContextMenu() {
            // Emit event with frame-aligned timestamp in seconds (Laravel/VideoJS standard)
            const frameAlignedTime = this.roundToNearestFrame(this.contextMenuTime);
            const frameNumber = this.getFrameNumber(frameAlignedTime);
            this.$dispatch('video-annotation:add-comment', {
                timestamp: frameAlignedTime,
                currentTime: frameAlignedTime,
                frameNumber: frameNumber,
                frameRate: this.frameRate
            });


            this.hideContextMenu();
        },

        hideContextMenu() {
            this.showContextMenu = false;
        },

        // Helper method for dev tools testing - force end any stuck drag
        forceEndDrag() {
            if (this.isDragging) {
                this.endTouchDrag({ preventDefault: () => { }, stopPropagation: () => { } });
            }
        },

        // Region Creation Method for Frame Helper Button
        startRegionCreationAtCurrentFrame() {
            if (!this.player) return;

            // Pause video
            this.player.pause();

            // Get current frame state from single source of truth
            const frameState = this.currentFrameState;
            const frameAlignedTime = frameState.frameAlignedTime;
            const frameNumber = frameState.frameNumber;

            // Calculate X position for region bar visualization
            const rect = this.$refs.regionBar?.getBoundingClientRect();
            const currentX = rect ? (frameAlignedTime / this.duration) * rect.width : 0;

            // Start region creation at current frame (same as clicking region area)
            this.isCreatingRegion = true;
            this.regionCreationStart = {
                x: currentX,
                time: frameAlignedTime,
                frame: frameNumber
            };

            // Set end to same frame initially
            this.regionCreationEnd = {
                x: currentX,
                time: frameAlignedTime,
                frame: frameNumber
            };

            // Show toolbar
            this.showRegionToolbar = true;

            console.log('Region creation started at frame:', frameNumber);
        },

        cancelRegionCreation() {
            // Reset region creation state (same as existing cancelRegionCreation logic)
            this.isCreatingRegion = false;
            this.regionCreationStart = null;
            this.regionCreationEnd = null;

            // Hide toolbar
            this.showRegionToolbar = false;

            console.log('Region creation cancelled');
        },

        generateRegionId() {
            return `region-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        },

        seekToFrame(frameNumber) {
            if (!this.player || typeof frameNumber !== 'number') return;

            const targetTime = frameNumber / this.frameRate;
            this.player.currentTime(targetTime);

            console.log(`Seeked to frame ${frameNumber} (${targetTime.toFixed(3)}s)`);
        },

        // Mobile region modal navigation helpers
        goToPreviousFrame() {
            if (!this.player) return;

            const currentTime = this.player.currentTime();
            const currentFrame = this.getFrameNumber(currentTime);
            const previousFrame = Math.max(0, currentFrame - 1);

            this.seekToFrame(previousFrame);
        },

        goToNextFrame() {
            if (!this.player) return;

            const currentTime = this.player.currentTime();
            const currentFrame = this.getFrameNumber(currentTime);
            const duration = this.player.duration();
            const maxFrame = Math.floor(duration * this.frameRate);
            const nextFrame = Math.min(maxFrame, currentFrame + 1);

            this.seekToFrame(nextFrame);
        },

        jumpFrames(frameCount) {
            if (!this.player) return;

            const currentTime = this.player.currentTime();
            const currentFrame = this.getFrameNumber(currentTime);
            const duration = this.player.duration();
            const maxFrame = Math.floor(duration * this.frameRate);

            const targetFrame = Math.max(0, Math.min(maxFrame, currentFrame + frameCount));
            this.seekToFrame(targetFrame);
        },

        // Update aspect ratio based on video dimensions
        updateAspectRatio() {
            if (!this.player) return;

            const width = this.player.videoWidth();
            const height = this.player.videoHeight();

            if (width && height) {
                // Assign appropriate VideoJS fluid class
                this.assignFluidClass(width, height);

                // Update container dimensions dynamically
                this.updateVideoContainerSize(width, height);
                console.log(`Video aspect ratio updated: ${width}x${height}`);
            }
        },

        assignFluidClass(width, height) {
            if (!this.player) return;

            // Remove any existing aspect ratio classes (but keep vjs-fluid always)
            const playerEl = this.player.el();
            const aspectRatioClasses = ['vjs-16-9', 'vjs-4-3', 'vjs-9-16', 'vjs-1-1'];
            aspectRatioClasses.forEach(cls => playerEl.classList.remove(cls));

            // Always ensure vjs-fluid is present
            if (!playerEl.classList.contains('vjs-fluid')) {
                playerEl.classList.add('vjs-fluid');
            }

            // Calculate aspect ratio
            const aspectRatio = width / height;

            // Check for common aspect ratios with tolerance and add specific class
            const tolerance = 0.05;
            let specificClass = null;

            if (Math.abs(aspectRatio - (16 / 9)) < tolerance) {
                specificClass = 'vjs-16-9';
            } else if (Math.abs(aspectRatio - (4 / 3)) < tolerance) {
                specificClass = 'vjs-4-3';
            } else if (Math.abs(aspectRatio - (9 / 16)) < tolerance) {
                specificClass = 'vjs-9-16';
            } else if (Math.abs(aspectRatio - 1) < tolerance) {
                specificClass = 'vjs-1-1';
            }

            // Apply specific aspect ratio class if detected
            if (specificClass) {
                playerEl.classList.add(specificClass);
                console.log(`Applied VideoJS classes: vjs-fluid + ${specificClass} (ratio: ${aspectRatio.toFixed(3)})`);
            } else {
                console.log(`Applied VideoJS class: vjs-fluid only (custom ratio: ${aspectRatio.toFixed(3)})`);
            }
        },

        updateVideoContainerSize(videoWidth, videoHeight) {
            const videoContainer = this.$refs.videoContainer;
            if (!videoContainer) return;

            // Get the main video annotation container (the one with flex flex-col)
            const mainContainer = this.$el;
            if (!mainContainer) return;

            // Get the flex-1 parent (video area)
            const playerParent = videoContainer.closest('.flex-1');
            if (!playerParent) return;

            // Get available space from the main container
            const mainRect = mainContainer.getBoundingClientRect();
            let availableWidth = mainRect.width;
            let availableHeight = mainRect.height;

            // Subtract toolbar height from available height
            const toolbar = this.$refs('tool-bar-container')
            if (toolbar) {
                const toolbarHeight = toolbar.getBoundingClientRect().height;
                console.log(toolbarHeight);
                availableHeight;
            }

            // Add some padding for safety
            availableHeight = Math.max(availableHeight - 20, 200); // Minimum 200px height

            // Calculate aspect ratio
            const videoAspectRatio = videoWidth / videoHeight;

            // Calculate dimensions that fit within available space
            let containerWidth = availableWidth;
            let containerHeight = containerWidth / videoAspectRatio;

            // If height exceeds available space, constrain by height
            if (containerHeight > availableHeight) {
                containerHeight = availableHeight;
                containerWidth = containerHeight * videoAspectRatio;
            }

            // For flex layout, let CSS handle the dimensions naturally
            // Remove any explicit sizing to let flex work
            playerParent.style.width = '';
            playerParent.style.height = '';
            playerParent.style.flexShrink = '';
            playerParent.style.flexGrow = '';

            console.log(`Video area resized to: ${containerWidth}x${containerHeight} (available: ${availableWidth}x${availableHeight}, toolbar subtracted)`);
        },

        // Initialize Region Toolbar Drag with Hammer.js
        initRegionToolbarDrag(element) {
            if (!element) return;

            // Create Hammer.js instance for the toolbar
            const toolbarHammer = new Hammer(element);

            // Enable pan gesture
            toolbarHammer.get('pan').set({
                direction: Hammer.DIRECTION_ALL,
                threshold: 10
            });

            let startPosition = { x: 0, y: 0 };
            let currentPosition = { x: 0, y: 0 };

            toolbarHammer.on('panstart', (event) => {
                this.isDragging = true;
                this.dragStarted = true;
                startPosition.x = currentPosition.x;
                startPosition.y = currentPosition.y;

                // Add active dragging styles
                element.style.transition = 'none';
                element.style.transform = `translate(calc(-50% + ${currentPosition.x}px), ${currentPosition.y}px)`;
            });

            toolbarHammer.on('panmove', (event) => {
                if (!this.isDragging) return;

                const newX = startPosition.x + event.deltaX;
                const newY = startPosition.y + event.deltaY;

                // Constrain to viewport bounds
                const rect = element.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;

                const constrainedX = Math.max(
                    -(viewportWidth / 2 - rect.width / 2),
                    Math.min(viewportWidth / 2 - rect.width / 2, newX)
                );

                const constrainedY = Math.max(
                    -(viewportHeight - rect.height - 20),
                    Math.min(-20, newY)
                );

                currentPosition.x = constrainedX;
                currentPosition.y = constrainedY;

                element.style.transform = `translate(calc(-50% + ${currentPosition.x}px), ${currentPosition.y}px)`;
            });

            toolbarHammer.on('panend', (event) => {
                this.isDragging = false;

                // Restore transition for smooth hover effects
                element.style.transition = 'opacity 200ms';

                // Snap to edges if close enough
                const snapThreshold = 50;
                const rect = element.getBoundingClientRect();
                const viewportWidth = window.innerWidth;

                if (Math.abs(currentPosition.x + viewportWidth / 2 - rect.width / 2) < snapThreshold) {
                    // Snap to left edge
                    currentPosition.x = -(viewportWidth / 2 - rect.width / 2);
                } else if (Math.abs(currentPosition.x - viewportWidth / 2 + rect.width / 2) < snapThreshold) {
                    // Snap to right edge
                    currentPosition.x = viewportWidth / 2 - rect.width / 2;
                }

                element.style.transform = `translate(calc(-50% + ${currentPosition.x}px), ${currentPosition.y}px)`;
            });
        },

        // Resize video wrapper based on available space minus actual toolbar height
        resizeVideoWrapper() {
            const videoWrapper = this.$refs.videoWrapper;
            if (!videoWrapper) return;

            const mainContainer = this.$el;
            if (!mainContainer) return;

            // Get the main container dimensions
            const containerRect = mainContainer.getBoundingClientRect();
            const containerWidth = containerRect.width;
            const containerHeight = containerRect.height;

            // Get actual toolbar height by measuring it (wait for render if needed)
            const toolbar = mainContainer.querySelector('.absolute.bottom-0');
            let toolbarHeight = 80; // fallback
            if (toolbar) {
                // Force layout calculation
                toolbar.style.visibility = 'visible';
                const rect = toolbar.getBoundingClientRect();
                toolbarHeight = rect.height || 80;
                console.log('Actual toolbar height measured:', toolbarHeight);
            }

            // Calculate available height for video wrapper
            const availableHeight = containerHeight - toolbarHeight;

            // Set video wrapper as flex container with strict height constraints
            videoWrapper.style.width = '100%';
            videoWrapper.style.height = `${availableHeight}px`;
            videoWrapper.style.maxHeight = `${availableHeight}px`;
            videoWrapper.style.minHeight = `${availableHeight}px`;
            videoWrapper.style.display = 'flex';
            videoWrapper.style.justifyContent = 'center';
            videoWrapper.style.alignItems = 'center';
            videoWrapper.style.overflow = 'hidden';
            videoWrapper.style.flexShrink = '0';
            videoWrapper.style.flexGrow = '0';

            console.log(`Video wrapper resized to: ${containerWidth}x${availableHeight} (toolbar height: ${toolbarHeight}px, container: ${containerHeight}px)`);
        }


    };
}
