import videojs from 'video.js';
import 'video.js/dist/video-js.css';

// Note: Quality selector temporarily disabled due to compatibility issues
// import '@silvermine/videojs-quality-selector/dist/css/quality-selector.css';
// import qualitySelector from '@silvermine/videojs-quality-selector';
// videojs.registerPlugin('qualitySelector', qualitySelector);

export default function videoAnnotation() {
    return {
        player: null,
        videoElement: null,
        comments: [], // Array of comment objects: {commentId, avatar, name, body, timestamp}
        onComment: null, // Callback function (like $wire)
        currentTime: 0,
        duration: 0,
        progressBarWidth: 0,
        initRetryCount: 0, // Track retry attempts
        hoverX: 0, // Mouse hover position for add button
        showHoverAdd: false, // Show hover add button
        
        init() {
            // Prevent multiple initializations
            if (this.player) {
                return;
            }
            
            // Wait for next tick to ensure DOM is ready
            this.$nextTick(() => {
                this.videoElement = this.$refs.videoPlayer;
                
                // Multiple checks to ensure element is properly in DOM
                if (this.videoElement && 
                    document.contains(this.videoElement) && 
                    this.videoElement.parentNode &&
                    this.videoElement.offsetParent !== null) {
                    
                    // Add small delay to ensure element is fully rendered
                    setTimeout(() => {
                        this.setupVideoJS();
                        this.setupEventListeners();
                    }, 100);
                } else if (this.initRetryCount < 5) {
                    this.initRetryCount++;
                    // Retry after a short delay
                    setTimeout(() => this.init(), 200);
                }
                
                // Accept onComment callback from parent component
                if (this.onComment && typeof this.onComment === 'function') {
                    this.onComment = this.onComment;
                }
            });
        },

        setupVideoJS() {
            // Check if Video.js has already initialized this element
            if (this.videoElement.classList.contains('vjs-tech')) {
                try {
                    this.player = videojs.getPlayer(this.videoElement.id);
                    if (this.player) {
                        this.setupPlayerEventListeners();
                        return;
                    }
                } catch (e) {
                    // Continue to create new player
                }
            }

            // Check if we already have a player instance
            if (this.player && typeof this.player.dispose === 'function') {
                return;
            }

            // Double-check element is still in DOM before initializing
            if (!document.contains(this.videoElement)) {
                return;
            }

            // Initialize Video.js with ID instead of element reference
            this.player = videojs(this.videoElement.id, {
                playbackRates: [0.5, 1, 1.25, 1.5, 2],
                // Use fluid mode to maintain aspect ratio
                fluid: true,
                responsive: true,
                fill: false
            });

            this.setupPlayerEventListeners();
        },

        setupPlayerEventListeners() {
            if (!this.player) return;
            
            // Setup player event listeners
            this.player.ready(() => {
                this.duration = this.player.duration() || 0;
                this.updateProgressBarWidth();
                
                // Add quality sources after player is ready
                this.setupQualitySources();
            });

            this.player.on('timeupdate', () => {
                this.currentTime = this.player.currentTime();
            });

            this.player.on('loadedmetadata', () => {
                this.duration = this.player.duration();
                this.updateProgressBarWidth();
            });

            this.player.on('resize', () => {
                this.updateProgressBarWidth();
            });
        },

        setupQualitySources() {
            // Check if quality sources are provided via data attribute
            const qualitySourcesAttr = this.videoElement.getAttribute('data-quality-sources');
            
            if (qualitySourcesAttr) {
                try {
                    const qualitySources = JSON.parse(qualitySourcesAttr);
                    
                    // Format sources for Video.js
                    const sources = qualitySources.map(source => ({
                        src: source.src,
                        type: source.type || 'video/mp4',
                        label: source.label || source.quality || 'Auto'
                    }));

                    // Update player sources
                    this.player.src(sources);
                    
                } catch (e) {
                    // Silently handle parsing errors
                }
            }
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
        }
    }
}