import WaveSurfer from 'wavesurfer.js';
import RegionsPlugin from 'wavesurfer.js/dist/plugins/regions.esm.js';

// Alpine.js Audio Annotation Component
export default function audioAnnotation(userConfig = null, initialComments = []) {
    // Default configuration following Alpine.js patterns
    const defaultConfig = {
        features: {
            enableAnnotations: true,
            enableComments: true,
            enableRegions: true,
            enableKeyboardShortcuts: true,
            enableWaveformClick: true,
            enableVolumeControls: true,
            enableTimeDisplay: true
        },
        ui: {
            waveColor: '#e5e7eb',
            progressColor: '#3b82f6',
            cursorColor: '#ef4444',
            theme: 'auto'
        },
        annotations: {
            enableWaveformComments: true,
            enableHapticFeedback: true,
            regionColor: 'rgba(168, 85, 247, 0.2)',
            regionBorderColor: '#a855f7'
        },
        timing: {
            commentPrecision: 0.01, // 10ms precision
            seekPrecision: 0.1 // 100ms for keyboard seeking
        }
    };

    // Merge user config with defaults
    const config = userConfig ? { ...defaultConfig, ...userConfig } : defaultConfig;

    return {
        // Core WaveSurfer instance
        wavesurfer: null,
        regionsPlugin: null,

        // Audio state
        isLoaded: false,
        isPlaying: false,
        currentTime: 0,
        duration: 0,
        volume: 1.0,
        isMuted: false,
        previousVolume: 1.0,
        playbackRate: 1.0,
        showSpeedMenu: false,
        showSpeedModal: false,
        showVolumeModal: false,
        showVolumeSlider: false,
        showRegions: true,
        isSelectingRegion: false,
        selectedRegion: null,
        activeRegion: null,
        regionLoop: false, // Enable region looping by default
        hiddenRegions: new Set(), // Track individually hidden regions

        // Comments and annotations
        comments: initialComments || [],
        activeCommentId: null,

        // Browser detection
        isSafari: false,

        // Window size tracking
        windowWidth: window.innerWidth,

        // Configuration
        config: config,

        // Alpine.js lifecycle methods
        init() {
            // Prevent multiple initialization on same element
            if (this.$el.dataset.audioInitialized) {
                console.log('Audio annotation already initialized on this element');
                return;
            }
            this.$el.dataset.audioInitialized = 'true';

            // Detect browser
            this.detectBrowser();

            // Track window resize for responsive behavior
            window.addEventListener('resize', () => {
                this.windowWidth = window.innerWidth;
            });

            // Early exit for Safari
            if (this.isSafari) {
                return;
            }

            // Initialize on next tick to ensure DOM is ready
            this.$nextTick(() => {
                this.setupWaveSurfer();
                this.setupEventListeners();

                // Auto-load audio if provided (only once)
                const audioSrc = this.$el.dataset.audioSrc;
                if (audioSrc && !this.isLoaded) {
                    this.loadAudio(audioSrc);
                }
            });
        },

        detectBrowser() {
            this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        },

        setupWaveSurfer() {
            const container = this.$refs.waveform;
            if (!container) {
                console.error('Waveform container not found');
                return;
            }

            // Prevent multiple initialization
            if (this.wavesurfer) {
                console.log('WaveSurfer already initialized, skipping...');
                return;
            }

            try {
                // Create regions plugin
                this.regionsPlugin = RegionsPlugin.create();




                // Initialize WaveSurfer
                this.wavesurfer = WaveSurfer.create({
                    container: container,
                    waveColor: this.config.ui.waveColor,
                    progressColor: this.config.ui.progressColor,
                    cursorColor: this.config.ui.cursorColor,
                    barWidth: 2,
                    barRadius: 1,
                    responsive: true,
                    minPxPerSec: 150,
                    height: 100,
                    normalize: true,
                    dragToSeek: false,
                    plugins: [this.regionsPlugin]
                });

                const random = (min, max) => Math.random() * (max - min) + min
                const randomColor = () => `rgba(${random(0, 255)}, ${random(0, 255)}, ${random(0, 255)}, 0.5)`




                this.setupWaveSurferEvents();

            } catch (error) {
                console.error('Failed to initialize WaveSurfer:', error);
            }
        },

        setupWaveSurferEvents() {
            if (!this.wavesurfer) return;

            // Audio loading events
            this.wavesurfer.on('decode', () => {
                this.isLoaded = true;
                this.duration = this.wavesurfer.getDuration();
                this.renderCommentRegions();

                // Ensure all existing regions have handlers after a short delay
                setTimeout(() => {
                    this.ensureAllRegionsHaveHandlers();
                }, 300);
            });

            // Playback events
            this.wavesurfer.on('play', () => {
                this.isPlaying = true;
            });

            this.wavesurfer.on('pause', () => {
                this.isPlaying = false;
            });

            // Time updates
            this.wavesurfer.on('timeupdate', (time) => {
                this.currentTime = time;
            });

            // Volume changes
            this.wavesurfer.on('volume', (volume) => {
                this.volume = volume;
                // Only update muted state if volume changed externally
                if (volume === 0 && !this.isMuted) {
                    this.isMuted = true;
                } else if (volume > 0 && this.isMuted) {
                    this.isMuted = false;
                }
            });

            // Waveform interaction
            if (this.config.features.enableWaveformClick) {
                this.wavesurfer.on('click', (relativeX) => {
                    const time = relativeX * this.duration;
                    this.seekTo(time);
                });
            }

            // Region events for comments
            this.regionsPlugin.on('region-created', (region) => {
                this.handleRegionCreated(region);
            });

            this.regionsPlugin.on('region-clicked', (region) => {
                this.handleRegionClicked(region);
            });

            // Debug: Log all region events
            this.regionsPlugin.on('region-mouseenter', (region) => {
                console.log('Region mouseenter:', region.id);
            });

            // Region playback and viewport events with proper scoping
            {
                let activeRegions = new Set();

                this.regionsPlugin.on('region-in', (region) => {
                    if (activeRegions.has(region.id)) return; // Prevent duplicate handling
                    activeRegions.add(region.id);
                    this.handleRegionEnterViewport(region);

                    // Set as active region for potential looping
                    if (region.id && region.id.startsWith('comment-')) {
                        this.activeRegion = region;
                    }
                });

                this.regionsPlugin.on('region-out', (region) => {
                    if (!activeRegions.has(region.id)) return; // Prevent duplicate handling
                    activeRegions.delete(region.id);
                    this.handleRegionLeaveViewport(region);

                    // Handle region looping
                    if (this.activeRegion === region && this.regionLoop && region.id && region.id.startsWith('comment-')) {
                        // Loop the region
                        setTimeout(() => {
                            if (this.activeRegion === region && this.regionLoop) {
                                region.play();
                            }
                        }, 50); // Small delay to avoid conflicts
                    } else if (this.activeRegion === region) {
                        this.activeRegion = null;
                    }
                });
            }

            // Reset active region when user clicks on waveform
            this.wavesurfer.on('interaction', () => {
                this.activeRegion = null;
            });
        },

        setupEventListeners() {
            // Listen for external comment events
            this.$el.addEventListener('audio-annotation:add-comment', (event) => {
                this.addComment(event.detail);
            });

            this.$el.addEventListener('audio-annotation:seek-comment', (event) => {
                this.seekToComment(event.detail.commentId);
            });

            // Listen for external region management events
            this.$el.addEventListener('audio-annotation:hide-region', (event) => {
                const { id } = event.detail;
                if (id) {
                    this.hideRegionById(id);
                }
            });

            this.$el.addEventListener('audio-annotation:add-region', (event) => {
                const regionData = event.detail;
                if (regionData) {
                    this.addExternalRegion(regionData);
                }
            });

            // Listen for waveform scroll events to update bubbles during manual scrolling
            setTimeout(() => {
                const waveformElement = this.$refs.waveform;
                if (waveformElement) {
                    const waveformWrapper = waveformElement.querySelector('div');
                    if (waveformWrapper) {
                        waveformWrapper.addEventListener('scroll', () => {
                            this.updateBubblePositions();
                        });

                        // Also listen for any DOM changes that might affect positions
                        const resizeObserver = new ResizeObserver(() => {
                            this.updateBubblePositions();
                        });
                        resizeObserver.observe(waveformWrapper);
                    }
                }
            }, 1000); // Give WaveSurfer time to initialize
        },

        // Audio control methods
        togglePlay() {
            if (!this.wavesurfer || !this.isLoaded) return;

            if (this.isPlaying) {
                this.wavesurfer.pause();
            } else {
                this.wavesurfer.play();
            }
        },

        seekTo(time) {
            if (!this.wavesurfer || !this.isLoaded) return;

            const clampedTime = Math.max(0, Math.min(time, this.duration));
            this.wavesurfer.seekTo(clampedTime / this.duration);
        },

        seekForward() {
            // Jump forward 100ms, maintain play/pause state, respect boundaries
            const newTime = Math.min(this.currentTime + this.config.timing.seekPrecision, this.duration);
            this.seekTo(newTime);
        },

        seekBackward() {
            // Jump backward 100ms, maintain play/pause state, respect boundaries
            const newTime = Math.max(this.currentTime - this.config.timing.seekPrecision, 0);
            this.seekTo(newTime);
        },

        setVolume(volume) {
            if (!this.wavesurfer) return;

            const clampedVolume = Math.max(0, Math.min(1, volume));
            this.wavesurfer.setVolume(clampedVolume);
            this.volume = clampedVolume;

            // Update muted state based on volume
            this.isMuted = clampedVolume === 0;
        },

        toggleMute() {
            if (!this.wavesurfer) return;

            if (this.isMuted) {
                // Unmute: restore previous volume
                const restoreVolume = this.previousVolume > 0 ? this.previousVolume : 0.5;
                this.wavesurfer.setVolume(restoreVolume);
                this.volume = restoreVolume;
                this.isMuted = false;
            } else {
                // Mute: store current volume and set to 0
                this.previousVolume = this.volume;
                this.wavesurfer.setVolume(0);
                this.isMuted = true;
            }
        },

        setPlaybackRate(rate) {
            if (!this.wavesurfer) return;

            this.playbackRate = rate;
            this.wavesurfer.setPlaybackRate(rate);
        },

        toggleSpeedMenu() {
            this.showSpeedMenu = !this.showSpeedMenu;
        },

        toggleSpeedModal() {
            this.showSpeedModal = !this.showSpeedModal;
        },

        toggleVolumeModal() {
            this.showVolumeModal = !this.showVolumeModal;
        },

        setSpeedAndCloseModal(rate) {
            this.setPlaybackRate(rate);
            this.showSpeedModal = false;
            this.showSpeedMenu = false;
        },

        getVolumePercentage() {
            return Math.round(this.volume * 100);
        },

        toggleRegions() {
            this.showRegions = !this.showRegions;
            if (this.showRegions) {
                this.renderCommentRegions();
            } else {
                this.hideCommentRegions();
            }
        },

        toggleRegionLoop() {
            this.regionLoop = !this.regionLoop;
            console.log(`Region looping ${this.regionLoop ? 'enabled' : 'disabled'}`);
        },

        toggleRegionVisibility(commentId) {
            const commentIdStr = commentId.toString();

            // Find the region
            const region = this.regionsPlugin.getRegions().find(r => r.id === `comment-${commentId}`);
            if (!region) return;

            if (this.hiddenRegions.has(commentIdStr)) {
                // Show the region
                this.hiddenRegions.delete(commentIdStr);

                // Show the region element
                if (region.element) {
                    region.element.style.display = 'block';
                }

                // Show the bubble container
                const bubbleContainer = this.$refs.bubbleOverlay?.querySelector(`.comment-bubble-container[data-comment-id="${commentId}"]`);
                if (bubbleContainer) {
                    bubbleContainer.style.display = 'flex';
                }

                console.log(`Showing region for comment: ${commentId}`);
            } else {
                // Hide the region
                this.hiddenRegions.add(commentIdStr);

                // Hide the region element (don't remove it)
                if (region.element) {
                    region.element.style.display = 'none';
                }

                // Hide the bubble container (don't remove it)
                const bubbleContainer = this.$refs.bubbleOverlay?.querySelector(`.comment-bubble-container[data-comment-id="${commentId}"]`);
                if (bubbleContainer) {
                    bubbleContainer.style.display = 'none';
                }

                console.log(`Hidden region for comment: ${commentId}`);
            }
        },

        isRegionHidden(commentId) {
            return this.hiddenRegions.has(commentId.toString());
        },

        hideRegionById(commentId) {
            const region = this.regionsPlugin.getRegions().find(r => r.id === `comment-${commentId}`);
            if (region) {
                // Use the proper remove method
                region.remove();
                console.log(`Region removed for comment: ${commentId}`);

                // Remove the bubble container
                this.removeBubbleFromOverlay(commentId);

                // Remove from comments array if it exists
                this.comments = this.comments.filter(c => c.commentId.toString() !== commentId.toString());
            }
        },

        addExternalRegion(regionData) {
            // Expected regionData format:
            // {
            //   commentId: number,
            //   timestamp: number (start time in seconds),
            //   duration: number (optional, defaults to 2.0),
            //   body: string (comment text),
            //   name: string (author name),
            //   avatar: string (optional, author avatar URL)
            // }

            if (!this.regionsPlugin || !this.isLoaded) {
                console.warn('Audio not loaded yet, cannot add region');
                return;
            }

            const {
                commentId,
                timestamp,
                duration = 2.0,
                body,
                name,
                avatar = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=a855f7&color=fff'
            } = regionData;

            // Check if region already exists
            const existingRegion = this.regionsPlugin.getRegions().find(r => r.id === `comment-${commentId}`);
            if (existingRegion) {
                console.log(`Region already exists for comment: ${commentId}`);
                return;
            }

            // Add to comments array
            const comment = {
                commentId,
                timestamp,
                duration,
                body,
                name,
                avatar
            };

            // Check if comment already exists in array
            const existingCommentIndex = this.comments.findIndex(c => c.commentId.toString() === commentId.toString());
            if (existingCommentIndex >= 0) {
                // Update existing comment
                this.comments[existingCommentIndex] = comment;
            } else {
                // Add new comment
                this.comments.push(comment);
            }

            // Create the region
            const region = this.regionsPlugin.addRegion({
                start: timestamp,
                end: timestamp + duration,
                color: 'rgba(168, 85, 247, 0.15)',
                drag: false,
                resize: false,
                id: `comment-${commentId}`,
                content: ``,
            });

            // Store comment data for interactions
            region.commentData = comment;
            region.commentIndex = this.comments.length - 1;

            // Set up handlers for the new region
            this.scheduleRegionSetup(region, comment);

            console.log(`External region added for comment: ${commentId}`);
        },

        showAllHiddenRegions() {
            this.hiddenRegions.clear();
            this.renderCommentRegions();
            console.log('All hidden regions restored');
        },

        hideCommentRegions() {
            if (this.regionsPlugin) {
                this.regionsPlugin.clearRegions();
            }
        },

        getSpeedOptions() {
            return [
                { value: 0.25, label: '0.25x' },
                { value: 0.5, label: '0.5x' },
                { value: 0.75, label: '0.75x' },
                { value: 1.0, label: '1x' },
                { value: 1.25, label: '1.25x' },
                { value: 1.5, label: '1.5x' },
                { value: 2.0, label: '2x' }
            ];
        },

        // Comment management
        addComment(details) {
            // Pause playback when adding a comment
            if (this.isPlaying) {
                this.wavesurfer.pause();
            }

            // If details provided (from external), create comment directly
            if (details && details.start !== undefined && details.end !== undefined) {
                const comment = {
                    commentId: Date.now(),
                    timestamp: details.start,
                    duration: details.end - details.start,
                    body: details.body || `Comment at ${this.formatTimeRange(details.start, details.end)}`,
                    name: details.name || 'User',
                    avatar: details.avatar || 'https://ui-avatars.com/api/?name=User&background=a855f7&color=fff'
                };

                this.comments.push(comment);
                this.renderCommentRegions();
                return;
            }

            // Otherwise, start region selection process
            this.startRegionSelection();
        },

        startRegionSelection() {
            // Hide all existing regions
            this.hideCommentRegions();

            // Set selection mode
            this.isSelectingRegion = true;
            this.showRegions = false; // Keep regions hidden during selection

            // Create a new resizable region at current time
            const startTime = this.currentTime;
            const defaultDuration = 2.0;

            this.selectedRegion = this.regionsPlugin.addRegion({
                start: startTime,
                end: startTime + defaultDuration,
                color: 'rgba(34, 197, 94, 0.25)', // Green for selection
                drag: true, // Allow dragging during selection
                resize: true, // Allow resizing during selection
                id: 'region-selection',
                content: '', // No text content
            });

            // Add finish button styling
            if (this.selectedRegion && this.selectedRegion.element) {
                this.selectedRegion.element.classList.add('region-selecting');
            }
        },

        finishRegionSelection() {
            if (!this.selectedRegion) return;

            const start = this.selectedRegion.start;
            const end = this.selectedRegion.end;

            // Clear the selection region
            this.regionsPlugin.clearRegions();
            this.selectedRegion = null;
            this.isSelectingRegion = false;

            // Dispatch event with start and end times
            this.$dispatch('audio-annotation:add-comment', {
                start: start,
                end: end,
                timestamp: start, // For backward compatibility
                duration: end - start
            });

            // Restore regions display
            this.showRegions = true;
            this.renderCommentRegions();
        },

        cancelRegionSelection() {
            if (this.selectedRegion) {
                this.regionsPlugin.clearRegions();
                this.selectedRegion = null;
            }
            this.isSelectingRegion = false;
            this.showRegions = true;
            this.renderCommentRegions();
        },

        seekToComment(commentId) {
            const comment = this.comments.find(c => c.commentId === commentId);
            if (comment) {
                this.seekTo(comment.timestamp);
                this.activateCommentRegion(commentId);
            }
        },

        renderCommentRegions() {
            if (!this.regionsPlugin || !this.isLoaded) return;

            // Only render regions if showRegions is true and NOT in selection mode
            if (!this.showRegions || this.isSelectingRegion) {
                // Hide all existing regions and bubbles but don't remove them
                this.regionsPlugin.getRegions().forEach(region => {
                    if (region.element) region.element.style.display = 'none';
                });

                const bubbleOverlay = this.$refs.bubbleOverlay;
                if (bubbleOverlay) {
                    const containers = bubbleOverlay.querySelectorAll('.comment-bubble-container');
                    containers.forEach(container => container.style.display = 'none');
                }
                return;
            }

            // Get existing regions to avoid recreating them
            const existingRegions = this.regionsPlugin.getRegions();
            const existingRegionIds = new Set(existingRegions.map(r => r.id));

            // Add only new comment regions that don't exist yet
            this.comments.forEach((comment, index) => {
                const regionId = `comment-${comment.commentId}`;

                // Skip if region already exists
                if (existingRegionIds.has(regionId)) {
                    // Just make sure existing region is visible (unless hidden by user)
                    const existingRegion = existingRegions.find(r => r.id === regionId);
                    if (existingRegion && existingRegion.element) {
                        if (!this.isRegionHidden(comment.commentId)) {
                            existingRegion.element.style.display = 'block';
                        }
                    }

                    // Make sure existing bubble is visible (unless hidden by user)
                    const bubbleContainer = this.$refs.bubbleOverlay?.querySelector(`.comment-bubble-container[data-comment-id="${comment.commentId}"]`);
                    if (bubbleContainer) {
                        if (!this.isRegionHidden(comment.commentId)) {
                            bubbleContainer.style.display = 'flex';
                        }
                    }
                    return;
                }

                const duration = comment.duration || 2.0;
                const region = this.regionsPlugin.addRegion({
                    start: comment.timestamp,
                    end: comment.timestamp + duration,
                    color: 'rgba(168, 85, 247, 0.15)', // Purple background
                    drag: false, // Disable dragging for cleaner UX
                    resize: false, // Disable resizing
                    id: regionId,
                    content: ``, // No text content
                });

                // Store comment data for interactions
                region.commentData = comment;
                region.commentIndex = index;

                // Manually set up handlers for initial regions with proper timing
                this.scheduleRegionSetup(region, comment);
            });
        },

        scheduleRegionSetup(region, comment, attempt = 0) {
            const maxAttempts = 10;
            const delay = 100; // 100ms between attempts

            if (attempt >= maxAttempts) {
                console.warn(`Failed to setup handlers for region ${region.id} after ${maxAttempts} attempts`);
                return;
            }

            if (region.element) {
                // Element is ready, set up handlers
                this.setupRegionHandlers(region, comment);
            } else {
                // Element not ready, try again after delay
                setTimeout(() => {
                    this.scheduleRegionSetup(region, comment, attempt + 1);
                }, delay);
            }
        },

        setupRegionHandlers(region, comment) {
            // Prevent duplicate setup
            if (region._handlersSetup) {
                console.log('Handlers already setup for region:', region.id);
                return;
            }
            region._handlersSetup = true;

            if (!region.element) {
                console.warn('Region element still not ready for:', region.id);
                return;
            }

            console.log('Setting up handlers for region:', comment.commentId, 'Element:', region.element);

            // Add tooltip with comment preview
            const tooltip = `${comment.name}: ${comment.body.substring(0, 50)}${comment.body.length > 50 ? '...' : ''}`;
            region.element.setAttribute('data-tooltip', tooltip);
            region.element.setAttribute('title', tooltip);

            // Check if this region should be initially hidden
            if (this.isRegionHidden(comment.commentId)) {
                // Hide the region element
                if (region.element) {
                    region.element.style.display = 'none';
                }
            }

            // Note: Bubbles are created via viewport events (region-in) for better performance

            // Add region click handler for playback
            region.element.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent triggering waveform click
                this.handleRegionClicked(region);
            });

            // Store reference for easy access
            region.element.setAttribute('data-comment-id', comment.commentId);
        },

        handleRegionCreated(region) {
            // This handles newly created regions
            if (region.id && region.id.startsWith('comment-')) {
                const comment = region.commentData;
                if (comment) {
                    // Use the same setup method
                    this.setupRegionHandlers(region, comment);
                }
            }
        },

        createBubbleInOverlay(region, comment) {
            const bubbleOverlay = this.$refs.bubbleOverlay;
            if (!bubbleOverlay) return;

            // Check if bubble already exists
            const existingBubble = bubbleOverlay.querySelector(`[data-comment-id="${comment.commentId}"]`);
            if (existingBubble) {
                console.log('Bubble already exists for comment:', comment.commentId);
                return;
            }

            // Get region position relative to waveform
            const waveformRect = this.$refs.waveform.getBoundingClientRect();
            const regionRect = region.element.getBoundingClientRect();
            const leftPosition = regionRect.left - waveformRect.left;

            // Create bubble
            const bubble = document.createElement('div');
            bubble.className = 'comment-bubble-overlay';
            bubble.textContent = '💬';
            bubble.setAttribute('data-comment-id', comment.commentId);

            // Position bubble in overlay
            bubble.style.cssText = `
                position: absolute;
                top: 0;
                left: ${leftPosition}px;
                width: 20px;
                height: 20px;
                background: rgba(168, 85, 247, 0.9);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                border: 2px solid white;
                box-shadow: 0 2px 8px rgba(168, 85, 247, 0.3);
                z-index: 25;
                transition: all 0.2s ease-in-out;
                cursor: pointer;
                pointer-events: all;
            `;

            // Add bubble click handler
            bubble.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log('Bubble clicked for comment:', comment.commentId);

                // Pause playback when clicking on bubble
                if (this.isPlaying) {
                    this.wavesurfer.pause();
                }

                this.$dispatch('audio-annotation:view-comment', { commentId: comment.commentId });
            });

            // Add hover effects for bubble
            bubble.addEventListener('mouseenter', () => {
                bubble.style.transform = 'scale(1.1)';
                bubble.style.boxShadow = '0 4px 12px rgba(168, 85, 247, 0.5)';
            });

            bubble.addEventListener('mouseleave', () => {
                bubble.style.transform = 'scale(1)';
                bubble.style.boxShadow = '0 2px 8px rgba(168, 85, 247, 0.3)';
            });

            // Add to overlay
            bubbleOverlay.appendChild(bubble);
        },

        handleRegionEnterViewport(region) {
            if (region.id && region.id.startsWith('comment-') && region.commentData) {
                console.log('Region entered viewport:', region.id);
                // Ensure bubble exists for visible region
                const bubbleOverlay = this.$refs.bubbleOverlay;
                if (bubbleOverlay) {
                    const existingBubble = bubbleOverlay.querySelector(`[data-comment-id="${region.commentData.commentId}"]`);
                    if (!existingBubble) {
                        this.createBubbleInOverlay(region, region.commentData);
                    }
                }
            }
        },

        handleRegionLeaveViewport(region) {
            if (region.id && region.id.startsWith('comment-')) {
                console.log('Region left viewport:', region.id);
                // Only remove bubble during playback, not during manual scrolling
                if (region.commentData && this.isPlaying) {
                    this.removeBubbleFromOverlay(region.commentData.commentId);
                }
            }
        },

        removeBubbleFromOverlay(commentId) {
            const bubbleOverlay = this.$refs.bubbleOverlay;
            if (bubbleOverlay) {
                const bubble = bubbleOverlay.querySelector(`[data-comment-id="${commentId}"]`);
                if (bubble) {
                    bubble.remove();
                    console.log('Bubble removed for comment:', commentId);
                }
            }
        },

        updateBubblePositions() {
            const bubbleOverlay = this.$refs.bubbleOverlay;
            if (!bubbleOverlay) return;

            // Find all visible regions and update corresponding bubble positions
            const visibleRegions = document.querySelectorAll('[part*="region comment-"]');
            const waveformRect = this.$refs.waveform.getBoundingClientRect();

            visibleRegions.forEach(regionElement => {
                // Extract comment ID from part attribute (e.g., "region comment-123" -> "123")
                const partAttr = regionElement.getAttribute('part');
                const commentId = partAttr.match(/comment-(.+)/)?.[1];

                if (commentId) {
                    // Find corresponding bubble
                    const bubble = bubbleOverlay.querySelector(`[data-comment-id="${commentId}"]`);
                    const regionRect = regionElement.getBoundingClientRect();
                    const leftPosition = regionRect.left - waveformRect.left;

                    if (bubble) {
                        // Update existing bubble position
                        bubble.style.left = `${leftPosition}px`;
                    } else {
                        // Create bubble for newly visible region
                        const comment = this.comments.find(c => c.commentId.toString() === commentId);
                        if (comment) {
                            this.createBubbleForComment(comment, leftPosition);
                        }
                    }
                }
            });

            // Remove bubbles for regions that are no longer visible
            const allBubbles = bubbleOverlay.querySelectorAll('.comment-bubble-overlay');
            allBubbles.forEach(bubble => {
                const commentId = bubble.getAttribute('data-comment-id');
                const correspondingRegion = document.querySelector(`[part*="comment-${commentId}"]`);
                if (!correspondingRegion) {
                    bubble.remove();
                }
            });
        },

        createBubbleForComment(comment, leftPosition) {
            const bubbleOverlay = this.$refs.bubbleOverlay;
            if (!bubbleOverlay) return;

            // Create bubble
            const bubble = document.createElement('div');
            bubble.className = 'comment-bubble-overlay';
            bubble.textContent = '💬';
            bubble.setAttribute('data-comment-id', comment.commentId);

            // Position bubble in overlay
            bubble.style.cssText = `
                position: absolute;
                top: 0;
                left: ${leftPosition}px;
                width: 20px;
                height: 20px;
                background: rgba(168, 85, 247, 0.9);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                border: 2px solid white;
                box-shadow: 0 2px 8px rgba(168, 85, 247, 0.3);
                z-index: 25;
                transition: all 0.2s ease-in-out;
                cursor: pointer;
                pointer-events: all;
            `;

            // Add bubble click handler
            bubble.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log('Bubble clicked for comment:', comment.commentId);

                // Pause playback when clicking on bubble
                if (this.isPlaying) {
                    this.wavesurfer.pause();
                }

                this.$dispatch('audio-annotation:view-comment', { commentId: comment.commentId });
            });

            // Add hover effects
            bubble.addEventListener('mouseenter', () => {
                bubble.style.transform = 'scale(1.1)';
                bubble.style.boxShadow = '0 4px 12px rgba(168, 85, 247, 0.5)';
            });

            bubble.addEventListener('mouseleave', () => {
                bubble.style.transform = 'scale(1)';
                bubble.style.boxShadow = '0 2px 8px rgba(168, 85, 247, 0.3)';
            });

            // Add to overlay
            bubbleOverlay.appendChild(bubble);
            console.log('Bubble created for comment during scroll:', comment.commentId, 'at position:', leftPosition);
        },

        activateCommentRegion(commentId) {
            // Remove active class from all regions
            document.querySelectorAll('[part*="region comment-"]').forEach(el => {
                el.classList.remove('region-active');
            });

            // Add active class to selected region
            const activeRegionElement = document.querySelector(`[part*="comment-${commentId}"]`);
            if (activeRegionElement) {
                activeRegionElement.classList.add('region-active');
            }

            // Set active comment
            this.activeCommentId = commentId;

            // Dispatch event for external handling
            const comment = this.comments.find(c => c.commentId === commentId);
            if (comment) {
                this.$dispatch('audio-annotation:comment-activated', { comment });
            }
        },

        handleRegionClicked(region) {
            if (region.commentData) {
                // Set as active region and play
                this.activeRegion = region;

                // Play the region with looping enabled
                region.play();

                // Activate the comment region visually
                this.activateCommentRegion(region.commentData.commentId);

                // Dispatch event for external handling
                this.$dispatch('audio-annotation:comment-clicked', { comment: region.commentData });

                console.log(`Playing region ${region.id} with loop: ${this.regionLoop}`);
            }
        },

        // Keyboard shortcuts
        handleKeydown(event) {
            if (!this.config.features.enableKeyboardShortcuts) return;

            switch (event.key) {
                case ' ':
                    event.preventDefault();
                    this.togglePlay();
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    this.seekBackward();
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    this.seekForward();
                    break;
                case 'c':
                    if (event.altKey || event.ctrlKey) {
                        event.preventDefault();
                        this.addComment({ timestamp: this.currentTime });
                    }
                    break;
            }
        },

        // Utility methods
        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            const milliseconds = Math.floor((seconds % 1) * 100);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}.${milliseconds.toString().padStart(2, '0')}`;
        },

        formatTimeRange(startSeconds, endSeconds) {
            return `${this.formatTime(startSeconds)} - ${this.formatTime(endSeconds)}`;
        },

        loadAudio(url) {
            if (!this.wavesurfer || !url) return;

            this.isLoaded = false;
            this.wavesurfer.load(url);
        },

        ensureAllRegionsHaveHandlers() {
            // Get all regions from the plugin
            const allRegions = this.regionsPlugin.getRegions();
            console.log(`Checking handlers for ${allRegions.length} regions`);

            allRegions.forEach(region => {
                if (region.id && region.id.startsWith('comment-') && region.commentData && !region._handlersSetup) {
                    console.log('Setting up missing handlers for region:', region.id);
                    this.scheduleRegionSetup(region, region.commentData);
                }
            });
        },

        clearAllBubbles() {
            const bubbleOverlay = this.$refs.bubbleOverlay;
            if (bubbleOverlay) {
                const bubbles = bubbleOverlay.querySelectorAll('.comment-bubble-overlay');
                if (bubbles.length > 0) {
                    bubbles.forEach(bubble => bubble.remove());
                    console.log(`Cleared ${bubbles.length} bubbles`);
                }
            }
        },

        // Cleanup
        destroy() {
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
                this.wavesurfer = null;
            }
        }
    };
}
