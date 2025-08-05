import { CommentSystemModule } from './modules/CommentSystemModule.js';
import { ContextDisplayModule } from './modules/ContextDisplayModule.js';
import { EventHandlerModule } from './modules/EventHandlerModule.js';
import { ProgressBarModule } from './modules/ProgressBarModule.js';
import { RegionManagementModule } from './modules/RegionManagementModule.js';
import { SharedState } from './modules/SharedState.js';
import { TouchInterfaceModule } from './modules/TouchInterfaceModule.js';
import { VideoPlayerCore } from './modules/VideoPlayerCore.js';

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

/**
 * Video Annotation Component - ES6 Modular Architecture
 *
 * This is the main entry point for the video annotation component.
 * It creates and orchestrates all the individual modules to provide
 * a unified Alpine.js interface.
 *
 * @param {Object} userConfig - User configuration options
 * @param {Array} initialComments - Initial comments array
 * @returns {Object} Alpine.js component object
 */
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
            progressBarMode: 'always', // 'always', 'auto-hide', 'hover'
            showControls: true,
            helpTooltipLimit: 3,
            theme: 'auto',
            enableContextMenu: true
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
            contextAutoHideDelay: 3000,
            longPressDuration: 500,
            playPauseOverlayDuration: 800,
            helpTooltipDuration: 3000
        },
        controls: {
            seekAmount: 10, // seconds
            volumeStep: 0.1,
            enableKeyboard: true
        },
        behavior: {
            pauseOnBlur: false,
            pauseOnHidden: true,
            autoHideControls: false
        },
        keyboard: {
            shortcuts: {
                // Additional custom shortcuts can be added here
            }
        },
        callbacks: {
            onPlay: null,
            onPause: null,
            onSeek: null,
            onVolumeChange: null,
            onResolutionChange: null,
            onFullscreenChange: null,
            onCommentAdded: null,
            onCommentRemoved: null,
            onRegionCreated: null,
            onRegionDeleted: null
        },
        debug: {
            contextSystem: false,
            touchInterface: false,
            regions: false
        }
    };

    // Handle config gracefully - use defaults if null/undefined
    const config = userConfig ? mergeDeep(defaultConfig, userConfig) : defaultConfig;

    // Create shared state instance
    const sharedState = new SharedState(config, initialComments);

    // Module instances
    let videoCore = null;
    let progressBar = null;
    let commentSystem = null;
    let regionManagement = null;
    let touchInterface = null;
    let contextDisplay = null;
    let eventHandler = null;

    // Alpine.js component return object
    return {
        // Shared state properties (reactive)
        get player() { return videoCore?.player; },
        get videoElement() { return videoCore?.videoElement; },
        get videoLoaded() { return sharedState.videoLoaded; },
        get durationFromState() { return sharedState.duration; }, // For debugging
        get currentTimeFromState() { return sharedState.currentTime; }, // For debugging
        get isPlayingFromState() { return sharedState.isPlaying; }, // For debugging
        get volume() { return sharedState.volume; },
        get isMuted() { return sharedState.isMuted; },
        get isFullscreen() { return sharedState.isFullscreen; },
        get bufferedPercentage() { return sharedState.bufferedPercentage; },

        // Progress bar state
        get progressBarMode() { return sharedState.progressBarMode; },
        get showProgressBar() { return sharedState.showProgressBar; },
        get progressBarWidth() { return sharedState.progressBarWidth; },
        get isDragging() { return sharedState.isDragging; },
        get hoverX() { return sharedState.hoverX; },
        get hoverTime() { return sharedState.hoverTime; },
        get showHoverAdd() { return sharedState.showHoverAdd; },
        get showSeekCircle() { return sharedState.showSeekCircle; },

        // Touch interface state
        get touchInterfaceMode() { return sharedState.touchInterface.mode; },
        get touchInterfaceEnabled() { return sharedState.touchInterface.isEnabled; },
        get activePointer() { return sharedState.touchInterface.activePointer; },
        get gestureInProgress() { return sharedState.touchInterface.gestureInProgress; },
        get isMobile() { return sharedState.isMobile; },

        // Comments state
        get comments() { return sharedState.comments; },
        get activeComments() { return sharedState.activeComments; },
        get nearbyComments() { return sharedState.nearbyComments; },

        // Regions state
        get regions() { return sharedState.regions; },
        get activeRegion() { return sharedState.activeRegion; },
        get regionCreationActive() { return sharedState.regionCreationActive; },
        get hiddenRegions() { return sharedState.hiddenRegions; },

        // Context display state
        get contextVisible() { return sharedState.contextDisplay?.visible || false; },
        get contextEnabled() { return sharedState.contextDisplay?.enabled || false; },
        get contextMode() { return sharedState.contextDisplay?.mode || 'time'; },
        get contextContent() { return sharedState.contextDisplay?.content || null; },
        get contextDisplay() { 
            return {
                visible: sharedState.contextDisplay?.visible || false,
                enabled: sharedState.contextDisplay?.enabled || false,
                mode: sharedState.contextDisplay?.mode || 'time',
                content: sharedState.contextDisplay?.content || null,
                nearbyComments: sharedState.contextDisplay?.nearbyComments || [],
                autoHideTimer: sharedState.contextDisplay?.autoHideTimer || null
            };
        },

        // Frame navigation state
        get frameRate() { return sharedState.frameRate; },
        get currentFrameState() { return sharedState.currentFrameState; },

        // Configuration access
        get config() { return config; },

        // Legacy tooltip state (for backward compatibility)
        get showTooltip() { return !sharedState.contextDisplay.enabled && sharedState.showTooltip; },

        // Additional properties used in Blade template
        get showPlayPauseOverlay() { return sharedState.showPlayPauseOverlay || false; },
        get frameNavigationDirection() { return sharedState.frameNavigationDirection || null; },
        get showFrameHelpers() { return sharedState.showFrameHelpers !== false; }, // Default true
        get currentFrameNumber() { return sharedState.currentFrameNumber || 0; },
        // Menu states - reactive properties for Alpine.js
        showResolutionMenu: true, // Show by default
        showSettingsMenu: false,
        showVolumeModal: false,
        showVolumeSlider: false,
        showSpeedMenu: false,
        showSpeedModal: false,
        
        // Video state - reactive properties for Alpine.js
        isPlaying: false,
        currentTime: 0,
        duration: 0,
        bufferedPercentage: 0,
        volume: 1,
        isMuted: false,
        
        // Test method to verify Alpine reactivity
        testMenuToggle() {
            console.log('[VideoAnnotation] Testing menu toggle...');
            console.log('[VideoAnnotation] Quality sources:', this.qualitySources);
            console.log('[VideoAnnotation] Quality sources length:', this.qualitySources.length);
            console.log('[VideoAnnotation] Current resolution:', this.currentResolution);
            console.log('[VideoAnnotation] Config enableResolutionSelector:', this.config.features.enableResolutionSelector);
            this.showResolutionMenu = !this.showResolutionMenu;
            console.log('[VideoAnnotation] showResolutionMenu is now:', this.showResolutionMenu);
        },

        // Menu control methods with debug logging
        toggleResolutionMenu() {
            console.log('[VideoAnnotation] Toggling resolution menu, current state:', this.showResolutionMenu);
            this.showResolutionMenu = !this.showResolutionMenu;
            console.log('[VideoAnnotation] Resolution menu is now:', this.showResolutionMenu);
        },
        
        toggleSettingsMenu() {
            console.log('[VideoAnnotation] Toggling settings menu, current state:', this.showSettingsMenu);
            this.showSettingsMenu = !this.showSettingsMenu;
            console.log('[VideoAnnotation] Settings menu is now:', this.showSettingsMenu);
        },
        
        toggleVolumeModal() {
            console.log('[VideoAnnotation] Toggling volume modal, current state:', this.showVolumeModal);
            this.showVolumeModal = !this.showVolumeModal;
            console.log('[VideoAnnotation] Volume modal is now:', this.showVolumeModal);
        },
        
        closeAllMenus() {
            console.log('[VideoAnnotation] Closing all menus');
            this.showResolutionMenu = false;
            this.showSettingsMenu = false;
            this.showVolumeModal = false;
            this.showVolumeSlider = false;
            this.showSpeedMenu = false;
            this.showSpeedModal = false;
        },
        
        // Settings methods
        toggleCommentsOnProgressBar() {
            console.log('[VideoAnnotation] Toggling comments on progress bar');
            if (sharedState) {
                sharedState.showCommentsOnProgressBar = !sharedState.showCommentsOnProgressBar;
                console.log('[VideoAnnotation] Show comments on progress bar:', sharedState.showCommentsOnProgressBar);
            }
        },
        
        toggleProgressBarMode() {
            console.log('[VideoAnnotation] Toggling progress bar mode');
            if (sharedState) {
                const currentMode = sharedState.progressBarMode;
                sharedState.progressBarMode = currentMode === 'always' ? 'auto-hide' : 'always';
                sharedState.showProgressBar = sharedState.progressBarMode === 'always';
                console.log('[VideoAnnotation] Progress bar mode:', sharedState.progressBarMode);
            }
        },
        // Quality sources as reactive property (updated from SharedState)
        qualitySources: [],
        get qualitySourcesFromState() { return sharedState.qualitySources || []; },
        get currentResolution() { return sharedState.currentResolution || null; },
        get currentResolutionSrc() { return sharedState.currentResolutionSrc || null; },
        get playbackRate() { return sharedState.playbackRate || 1.0; },
        get showCommentsOnProgressBar() { return sharedState.showCommentsOnProgressBar !== false; }, // Default true
        get showRegionBar() { return sharedState.showRegionBar !== false; }, // Default true
        get regionBarWidth() { return sharedState.regionBarWidth || 0; },
        get isCreatingRegion() { return sharedState.isCreatingRegion || false; },
        get regionCreationStart() { return sharedState.regionCreationStart || null; },
        get regionCreationEnd() { return sharedState.regionCreationEnd || null; },
        get showRegionToolbar() { return sharedState.showRegionToolbar || false; },
        get showRegionTooltip() { return sharedState.showRegionTooltip || null; },
        get mobileControlsExpanded() { return sharedState.mobileControlsExpanded || false; },
        get windowWidth() { return sharedState.windowWidth || window.innerWidth; },
        get dragCurrentTime() { return sharedState.dragCurrentTime || 0; },
        get wasPlayingBeforeDrag() { return sharedState.wasPlayingBeforeDrag || false; },
        
        // Touch interface properties
        get touchInterface() { 
            return {
                enabled: sharedState.touchInterface?.enabled || false,
                mode: sharedState.touchInterface?.mode || 'NORMAL',
                contextMenuVisible: sharedState.touchInterface?.contextMenuVisible || false,
                unifiedTimelineVisible: sharedState.touchInterface?.unifiedTimelineVisible || false,
                actionModalVisible: sharedState.touchInterface?.actionModalVisible || false
            };
        },

        // Context menu properties
        get showContextMenu() { return sharedState.showContextMenu || false; },
        get contextMenuX() { return sharedState.contextMenuX || 0; },
        get contextMenuY() { return sharedState.contextMenuY || 0; },
        get contextMenuTime() { return sharedState.contextMenuTime || 0; },

        // Pointer state properties
        get pointerState() {
            return {
                isDown: sharedState.pointerState?.isDown || false,
                startTime: sharedState.pointerState?.startTime || 0,
                startPos: sharedState.pointerState?.startPos || { x: 0, y: 0 },
                currentPos: sharedState.pointerState?.currentPos || { x: 0, y: 0 },
                hasMoved: sharedState.pointerState?.hasMoved || false,
                longPressTriggered: sharedState.pointerState?.longPressTriggered || false,
                ghostClickPrevention: sharedState.pointerState?.ghostClickPrevention || false,
                activePointer: sharedState.pointerState?.activePointer || null
            };
        },

        // Event handler module access
        get eventHandler() {
            return eventHandler?.getAlpineMethods() || {};
        },

        // Computed properties
        get progressPercentage() {
            return progressBar ? progressBar.getProgressPercentage() : (
                this.duration && this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0
            );
        },

        get bufferedProgress() {
            return progressBar ? progressBar.getBufferedPercentage() : this.bufferedPercentage;
        },

        get frameAlignedProgressPercentage() {
            // Use reactive properties for better Alpine.js integration
            return this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
        },

        get isSafari() {
            return videoCore?.isSafari || false;
        },

        /**
         * Initialize the video annotation component
         */
        init() {
            // Initialize shared state
            if (!sharedState.comments || sharedState.comments.length === 0) {
                sharedState.comments = initialComments || [];
            }

            // Set up default values for properties used in template
            this.detectBrowser(); // Set browser detection early
            sharedState.windowWidth = window.innerWidth;
            sharedState.showFrameHelpers = true;
            sharedState.showCommentsOnProgressBar = config.annotations?.showCommentsOnProgressBar !== false;
            sharedState.showRegionBar = true;
            
            // Initialize menu states explicitly
            this.showResolutionMenu = false;
            this.showSettingsMenu = false;
            this.showVolumeModal = false;
            this.showVolumeSlider = false;
            this.showSpeedMenu = false;
            this.showSpeedModal = false;
            
            // Initialize context menu state
            sharedState.showContextMenu = false;
            sharedState.contextMenuX = 0;
            sharedState.contextMenuY = 0;
            sharedState.contextMenuTime = 0;
            
            // Initialize window resize handler
            const handleResize = () => {
                sharedState.windowWidth = window.innerWidth;
                if (progressBar) {
                    this.updateProgressBarWidth();
                }
            };
            window.addEventListener('resize', handleResize);

            // Use $nextTick to ensure DOM elements are ready
            this.$nextTick(() => {
                try {
                    // Get video element reference with multiple fallbacks
                    const videoElement = this.$refs.videoPlayer || 
                                       this.$refs.video || 
                                       this.$el.querySelector('video') ||
                                       this.$el.querySelector('[x-ref="videoPlayer"]');
                    
                    if (!videoElement) {
                        console.error('[VideoAnnotation] Video element not found. Available refs:', Object.keys(this.$refs || {}));
                        return;
                    }

                    // Validate that DOM elements exist before creating modules
                    const requiredRefs = {
                        progressBar: this.$refs.progressBar,
                        toolBar: this.$refs.toolBar || this.$refs['tool-bar-container']
                    };

                    const missingRefs = Object.entries(requiredRefs)
                        .filter(([name, ref]) => !ref)
                        .map(([name]) => name);

                    if (missingRefs.length > 0) {
                        console.warn('[VideoAnnotation] Missing DOM refs:', missingRefs, 'Available refs:', Object.keys(this.$refs || {}));
                    }

                    // Create module instances
                    videoCore = new VideoPlayerCore(config, sharedState);
                    progressBar = new ProgressBarModule(videoCore, sharedState, config);
                    commentSystem = new CommentSystemModule(videoCore, sharedState, config);
                    regionManagement = new RegionManagementModule(videoCore, sharedState, config);
                    touchInterface = new TouchInterfaceModule(videoCore, sharedState, config);
                    contextDisplay = new ContextDisplayModule(videoCore, sharedState, config);
                    eventHandler = new EventHandlerModule(videoCore, sharedState, config);

                    // Initialize VideoCore with the video element
                    if (videoElement) {
                        if (config.debug?.contextSystem) {
                            console.log('[VideoAnnotation] Initializing VideoCore with element:', videoElement);
                            console.log('[VideoAnnotation] Video element src:', videoElement.src || 'No src');
                            console.log('[VideoAnnotation] Video element sources:', videoElement.querySelectorAll('source').length);
                        }
                        videoCore.init(videoElement);
                    } else {
                        console.error('[VideoAnnotation] VideoCore initialization failed - no video element');
                    }

                    // Initialize modules with error handling
                    this.initializeModulesWithErrorHandling(videoElement);

                    // Setup inter-module communication
                    this.setupModuleCommunication();

                    // Update progress bar width initially
                    this.$nextTick(() => {
                        this.updateProgressBarWidth();
                    });

                    if (config.debug?.contextSystem) {
                        console.log('[VideoAnnotation] Initialized with modular architecture');
                        console.log('[VideoAnnotation] Modules:', {
                            videoCore: !!videoCore,
                            progressBar: !!progressBar,
                            commentSystem: !!commentSystem,
                            regionManagement: !!regionManagement,
                            touchInterface: !!touchInterface,
                            contextDisplay: !!contextDisplay,
                            eventHandler: !!eventHandler
                        });
                    }

                } catch (error) {
                    console.error('[VideoAnnotation] Initialization error:', error);
                    console.error('[VideoAnnotation] Available $refs:', Object.keys(this.$refs || {}));
                    console.error('[VideoAnnotation] $el:', this.$el);
                }
            });
        },

        /**
         * Initialize modules with proper error handling
         */
        initializeModulesWithErrorHandling(videoElement) {
            const modules = [
                // VideoCore is already initialized above
                { name: 'progressBar', instance: progressBar, initArgs: [this.$refs, this.$dispatch] },
                { name: 'commentSystem', instance: commentSystem, initArgs: [this.$refs, this.$dispatch] },
                { name: 'regionManagement', instance: regionManagement, initArgs: [this.$refs, this.$dispatch] },
                { name: 'touchInterface', instance: touchInterface, initArgs: [this.$refs, this.$dispatch] },
                { name: 'contextDisplay', instance: contextDisplay, initArgs: [this.$refs, this.$dispatch] },
                { name: 'eventHandler', instance: eventHandler, initArgs: [this.$refs, this.$dispatch] }
            ];

            modules.forEach(({ name, instance, initArgs }) => {
                try {
                    if (instance && typeof instance.init === 'function') {
                        instance.init(...initArgs);
                        
                        // Special handling for comment system
                        if (name === 'commentSystem' && typeof instance.enableTimeUpdates === 'function') {
                            instance.enableTimeUpdates();
                        }
                    } else {
                        console.warn(`[VideoAnnotation] ${name} module not available or missing init method`);
                    }
                } catch (error) {
                    console.error(`[VideoAnnotation] Failed to initialize ${name} module:`, error);
                }
            });
        },

        /**
         * Setup inter-module communication
         */
        setupModuleCommunication() {
            // Validate that $el exists and has addEventListener method
            if (!this.$el || typeof this.$el.addEventListener !== 'function') {
                console.error('[VideoAnnotation] $el is not a valid DOM element or missing addEventListener');
                return;
            }

            try {
                // Listen for custom events and route them to appropriate modules
                this.$el.addEventListener('video-annotation:seek', (event) => {
                    try {
                        if (event.detail?.timestamp !== undefined && videoCore) {
                            videoCore.seekTo(event.detail.timestamp);
                        }
                    } catch (error) {
                        console.error('[VideoAnnotation] Error handling seek event:', error);
                    }
                });

                this.$el.addEventListener('video-annotation:add-comment', (event) => {
                    try {
                        if (config.callbacks?.onCommentAdded) {
                            config.callbacks.onCommentAdded(event.detail);
                        }
                    } catch (error) {
                        console.error('[VideoAnnotation] Error handling add-comment event:', error);
                    }
                });

                this.$el.addEventListener('video-annotation:region-created', (event) => {
                    try {
                        if (config.callbacks?.onRegionCreated) {
                            config.callbacks.onRegionCreated(event.detail);
                        }
                    } catch (error) {
                        console.error('[VideoAnnotation] Error handling region-created event:', error);
                    }
                });

                // Listen for comment-related events
                this.$el.addEventListener('video-annotation:view-comment', (event) => {
                    try {
                        if (config.callbacks?.onCommentViewed) {
                            config.callbacks.onCommentViewed(event.detail);
                        }
                    } catch (error) {
                        console.error('[VideoAnnotation] Error handling view-comment event:', error);
                    }
                });

                // Listen for seek-comment events (when clicking comment dots)
                this.$el.addEventListener('video-annotation:seek-comment', (event) => {
                    try {
                        if (event.detail?.timestamp !== undefined && videoCore) {
                            videoCore.seekTo(event.detail.timestamp);
                        }
                        if (event.detail?.commentId && commentSystem) {
                            commentSystem.showCommentContext(event.detail.commentId);
                        }
                    } catch (error) {
                        console.error('[VideoAnnotation] Error handling seek-comment event:', error);
                    }
                });

            } catch (error) {
                console.error('[VideoAnnotation] Error setting up module communication:', error);
            }
            
            // Listen for quality sources updates from VideoCore
            document.addEventListener('quality-sources-updated', (event) => {
                // Update the reactive property
                this.qualitySources = event.detail.qualitySources || [];
            });
            
            // Listen for play state changes from VideoCore
            document.addEventListener('video-play-state-changed', (event) => {
                // Update the reactive property
                this.isPlaying = event.detail.isPlaying || false;
            });
            
            // Listen for duration changes from VideoCore  
            document.addEventListener('video-duration-changed', (event) => {
                this.duration = event.detail.duration || 0;
            });
            
            // Listen for time updates from VideoCore
            document.addEventListener('video-time-updated', (event) => {
                this.currentTime = event.detail.currentTime || 0;
                // We could also store progressPercentage if needed
                // this.progressPercentage = event.detail.progressPercentage || 0;
            });
            
            // Listen for buffered updates from VideoCore
            document.addEventListener('video-buffered-updated', (event) => {
                this.bufferedPercentage = event.detail.bufferedPercentage || 0;
            });
            
            // Listen for volume updates from VideoCore
            document.addEventListener('video-volume-changed', (event) => {
                // Update reactive properties to force Alpine.js template updates
                this.volume = event.detail.volume || 0;
                this.isMuted = event.detail.isMuted || false;
                console.log('[VideoAnnotation] Volume changed:', this.volume, 'Muted:', this.isMuted);
            });
        },

        /**
         * Update progress bar width
         */
        updateProgressBarWidth() {
            if (progressBar) {
                progressBar.updateProgressBarWidth();
            }
        },

        /**
         * Clean up resources
         */
        cleanup() {
            // Dispose of all modules
            if (commentSystem) commentSystem.dispose();
            if (regionManagement) regionManagement.dispose();
            if (touchInterface) touchInterface.dispose();
            if (contextDisplay) contextDisplay.dispose();
            if (eventHandler) eventHandler.dispose();
            if (videoCore) videoCore.dispose();

            // Clear shared state
            sharedState.reset();

            if (config.debug?.contextSystem) {
                console.log('[VideoAnnotation] Cleaned up resources');
            }
        },

        // === PUBLIC API METHODS ===
        // These methods are exposed for external use and combine functionality from multiple modules

        // Video control methods
        togglePlayPause() { return videoCore?.togglePlayPause(); },
        togglePlay() { return videoCore?.togglePlayPause(); }, // Alias for backward compatibility
        seekTo(time) { return videoCore?.seekTo(time); },
        setVolume(volume) { return videoCore?.setVolume(volume); },
        toggleMute() { return videoCore?.toggleMute(); },
        toggleFullscreen() { return videoCore?.toggleFullscreen(); },
        
        // Frame navigation methods
        stepForward() {
            if (videoCore?.player && sharedState?.duration > 0) {
                const currentTime = videoCore.player.currentTime() || 0;
                const frameRate = sharedState.frameRate || 30;
                const frameDuration = 1 / frameRate;
                const newTime = Math.min(currentTime + frameDuration, sharedState.duration);
                videoCore.seekTo(newTime);
                
                // Update frame navigation direction for visual feedback
                sharedState.frameNavigationDirection = 'forward';
                setTimeout(() => { sharedState.frameNavigationDirection = null; }, 500);
            }
        },
        
        stepBackward() {
            if (videoCore?.player && sharedState) {
                const currentTime = videoCore.player.currentTime() || 0;
                const frameRate = sharedState.frameRate || 30;
                const frameDuration = 1 / frameRate;
                const newTime = Math.max(currentTime - frameDuration, 0);
                videoCore.seekTo(newTime);
                
                // Update frame navigation direction for visual feedback
                sharedState.frameNavigationDirection = 'backward';
                setTimeout(() => { sharedState.frameNavigationDirection = null; }, 500);
            }
        },
        
        // Resolution control methods
        changeResolution(source) {
            console.log('[VideoAnnotation] Changing resolution to:', source);
            if (videoCore?.changeResolution) {
                videoCore.changeResolution(source);
                
                // Update the selected state in qualitySources array
                this.qualitySources = this.qualitySources.map(s => ({
                    ...s,
                    selected: s.src === source.src
                }));
                
                // Update SharedState to keep it in sync
                if (sharedState) {
                    sharedState.qualitySources = this.qualitySources;
                    sharedState.currentResolution = source;
                    sharedState.currentResolutionSrc = source.src;
                }
            }
        },

        // Progress bar methods
        handleProgressBarClick(event, action = 'click') {
            return progressBar?.handleProgressBarClick(event, action);
        },
        handleProgressBarDragStart(event) {
            return progressBar?.handleProgressBarDragStart(event);
        },
        handleProgressBarHover(event) {
            return progressBar?.handleProgressBarHover(event);
        },
        handleProgressBarLeave() {
            return progressBar?.handleProgressBarLeave();
        },

        // Comment system methods
        handleCommentClick(comment, event) {
            return commentSystem?.handleCommentClick(comment, event);
        },
        loadComment(commentId) {
            return commentSystem?.loadComment(commentId);
        },
        addComment(comment) {
            return commentSystem?.addComment(comment);
        },
        removeComment(commentId) {
            return commentSystem?.removeComment(commentId);
        },
        renderCommentMarkers() {
            return commentSystem?.renderCommentMarkers() || [];
        },
        getCommentBubblePosition(comment, index) {
            return commentSystem?.getCommentBubblePosition(comment, index) || { left: 0, zIndex: 100 };
        },
        calculateCommentOffset(comment, index) {
            return commentSystem?.calculateCommentOffset(comment, index) || { left: 0, top: 0 };
        },
        showCommentContext(commentId) {
            return commentSystem?.showCommentContext(commentId);
        },
        hideCommentContext(commentId) {
            return commentSystem?.hideCommentContext(commentId);
        },
        clearActiveComments() {
            return commentSystem?.clearActiveComments();
        },

        // Region management methods
        startRegionCreation() {
            return regionManagement?.startRegionCreation();
        },
        finishRegionCreation() {
            return regionManagement?.finishRegionCreation();
        },
        cancelRegionCreation() {
            return regionManagement?.cancelRegionCreation();
        },
        selectRegion(regionId) {
            return regionManagement?.selectRegion(regionId);
        },
        deleteRegion(regionId) {
            return regionManagement?.deleteRegion(regionId);
        },
        handleRegionClick(regionId, event) {
            return regionManagement?.handleRegionClick(regionId, event);
        },
        getVisibleRegions() {
            return regionManagement?.getVisibleRegions() || [];
        },
        getRegionPosition(region) {
            return regionManagement?.getRegionPosition(region) || { left: 0, width: 0 };
        },

        // Touch interface methods
        enableTouchInterface() {
            return touchInterface?.enable();
        },
        disableTouchInterface() {
            return touchInterface?.disable();
        },
        isTouchDevice() {
            return touchInterface?.isTouchDevice() || false;
        },

        // Context display methods
        showContext(content, position, options) {
            return contextDisplay?.show(content, position, options);
        },
        hideContext(immediate = false) {
            return contextDisplay?.hide(immediate);
        },
        showTimeContext(time, position) {
            return contextDisplay?.showTimeContext(time, position);
        },
        showCommentsContext(comments, position) {
            return contextDisplay?.showCommentsContext(comments, position);
        },
        showCombinedContext(time, comments, position) {
            return contextDisplay?.showCombinedContext(time, comments, position);
        },
        setContextMode(mode) {
            return contextDisplay?.setMode(mode);
        },
        enableContext() {
            return contextDisplay?.enable();
        },
        disableContext() {
            return contextDisplay?.disable();
        },
        clearContextAutoHide() {
            return contextDisplay?.clearAutoHideTimer();
        },

        // Event handler methods
        enableKeyboard() {
            return eventHandler?.enableKeyboard();
        },
        disableKeyboard() {
            return eventHandler?.disableKeyboard();
        },
        addKeyboardShortcut(key, handler) {
            return eventHandler?.addKeyboardShortcut(key, handler);
        },
        seekRelative(seconds) {
            return eventHandler?.seekRelative(seconds);
        },
        seekFrame(frames) {
            return eventHandler?.seekFrame(frames);
        },

        // Utility methods
        formatTimestamp(seconds) {
            return commentSystem?.formatTimestamp(seconds) || this.formatTime(seconds);
        },
        formatTime(seconds) {
            if (!seconds || seconds < 0) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        },
        roundToNearestFrame(time) {
            return sharedState.roundToNearestFrame ? sharedState.roundToNearestFrame(time) : time;
        },
        getFrameNumber(time) {
            return sharedState.getFrameNumber ? sharedState.getFrameNumber(time) : Math.floor(time * (this.frameRate || 30));
        },
        timeToPixel(time) {
            return sharedState.timeToPixel ? sharedState.timeToPixel(time) : 0;
        },
        pixelToTime(pixelX) {
            return sharedState.pixelToTime ? sharedState.pixelToTime(pixelX) : 0;
        },

        // Additional methods used in Blade template
        toggleFrameHelpers() {
            sharedState.showFrameHelpers = !sharedState.showFrameHelpers;
        },
        stepForward() {
            return eventHandler?.stepForward() || videoCore?.stepForward();
        },
        stepBackward() {
            return eventHandler?.stepBackward() || videoCore?.stepBackward();
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
        setPlaybackRate(rate) {
            return videoCore?.setPlaybackRate(rate);
        },
        toggleSpeedMenu() {
            sharedState.showSpeedMenu = !sharedState.showSpeedMenu;
        },
        toggleSpeedModal() {
            sharedState.showSpeedModal = !sharedState.showSpeedModal;
        },
        toggleVolumeModal() {
            sharedState.showVolumeModal = !sharedState.showVolumeModal;
        },
        toggleVolumeSlider() {
            sharedState.showVolumeSlider = !sharedState.showVolumeSlider;
        },
        toggleSettingsMenu() {
            sharedState.showSettingsMenu = !sharedState.showSettingsMenu;
        },
        toggleResolutionMenu() {
            sharedState.showResolutionMenu = !sharedState.showResolutionMenu;
        },
        toggleMobileControls() {
            sharedState.mobileControlsExpanded = !sharedState.mobileControlsExpanded;
        },
        getVolumePercentage() {
            return Math.round((this.volume || 0) * 100);
        },
        detectBrowser() {
            // Browser detection (fallback if videoCore not available)
            if (!videoCore) {
                sharedState.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                sharedState.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
                sharedState.isAndroid = /Android/.test(navigator.userAgent);
                sharedState.isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
                sharedState.isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
            }
        },

        // Context menu methods
        showContextMenu(event, time) {
            if (!config.annotations?.enableContextMenu) return;
            
            sharedState.showContextMenu = true;
            sharedState.contextMenuX = event.clientX || event.pageX || 0;
            sharedState.contextMenuY = event.clientY || event.pageY || 0;
            sharedState.contextMenuTime = time || this.currentTime;
            
            // Prevent default context menu
            if (event.preventDefault) event.preventDefault();
            if (event.stopPropagation) event.stopPropagation();
        },
        hideContextMenu() {
            sharedState.showContextMenu = false;
            sharedState.contextMenuX = 0;
            sharedState.contextMenuY = 0;
            sharedState.contextMenuTime = 0;
        },
        handleContextMenuAction(action) {
            const time = sharedState.contextMenuTime;
            this.hideContextMenu();
            
            switch (action) {
                case 'add-comment':
                    if (commentSystem) {
                        commentSystem.addCommentAtTime(time);
                    }
                    break;
                case 'create-region':
                    if (regionManagement) {
                        regionManagement.startRegionCreationAtTime(time);
                    }
                    break;
                default:
                    console.warn(`Unknown context menu action: ${action}`);
            }
        },

        // Context display methods
        getContextDisplayContent() {
            if (!contextDisplay) {
                return {
                    primary: this.formatTime(this.currentTime),
                    secondary: null,
                    showCommentCount: false,
                    comment: null
                };
            }
            
            if (contextDisplay.getDisplayContent) {
                const content = contextDisplay.getDisplayContent();
                // Only log when there are secondary comments to show
                if (content.secondary) {
                    console.log('[VideoAnnotation] Showing secondary context:', content.secondary);
                }
                return content;
            } else {
                return {
                    primary: this.formatTime(this.currentTime),
                    secondary: null,
                    showCommentCount: false,
                    comment: null
                };
            }
        },

        // Comment timeline display methods
        shouldShowTimelineDisplay(comment) {
            if (!comment || !commentSystem) return false;
            return commentSystem.shouldShowTimelineDisplay ? commentSystem.shouldShowTimelineDisplay(comment) : false;
        },
        
        // Expose commentSystem methods for template access
        get commentSystem() {
            return commentSystem;
        },
        
        handleTimelineCommentClick(comment) {
            if (commentSystem && comment) {
                commentSystem.handleTimelineCommentClick(comment);
            }
        },

        handleCommentClick(event, comment) {
            if (comment) {
                // Seek to comment timestamp
                if (comment.timestamp !== undefined && videoCore) {
                    videoCore.seekTo(comment.timestamp);
                }
                
                // Fire simple flying comment event
                this.$dispatch('flying-comment-show', {
                    commentId: comment.commentId,
                    name: comment.name,
                    timestamp: comment.timestamp,
                    body: comment.body
                });
                
                console.log('[VideoAnnotation] Flying comment shown:', comment.name);
            }
        },

        // Region toolbar methods
        initRegionToolbarDrag(event) {
            if (regionManagement) {
                return regionManagement.initRegionToolbarDrag ? regionManagement.initRegionToolbarDrag(event) : null;
            }
        },

        startRegionCreationAtCurrentFrame() {
            if (regionManagement) {
                return regionManagement.startRegionCreationAtCurrentFrame ? regionManagement.startRegionCreationAtCurrentFrame() : null;
            }
        },

        // Progress bar event handlers
        onProgressBarMouseEnterWithContext(event) {
            if (contextDisplay) {
                return contextDisplay.onProgressBarMouseEnter ? contextDisplay.onProgressBarMouseEnter(event) : null;
            }
        },

        onProgressBarMouseMoveWithContext(event) {
            if (contextDisplay) {
                return contextDisplay.onProgressBarMouseMove ? contextDisplay.onProgressBarMouseMove(event) : null;
            }
        },

        onProgressBarMouseLeaveWithContext() {
            if (contextDisplay) {
                return contextDisplay.onProgressBarMouseLeave ? contextDisplay.onProgressBarMouseLeave() : null;
            }
        },

        handleProgressBarPointer(event, action) {
            if (progressBar) {
                return progressBar.handleProgressBarPointer ? progressBar.handleProgressBarPointer(event, action) : null;
            }
        },

        // Video interaction methods
        handleVideoClick(event) {
            if (videoCore) {
                return videoCore.handleVideoClick ? videoCore.handleVideoClick(event) : this.togglePlayPause();
            }
            return this.togglePlayPause();
        },

        handleVideoHover(event) {
            // Handle video hover events - could be used for showing controls or other interactions
            if (touchInterface && touchInterface.handleVideoHover) {
                return touchInterface.handleVideoHover(event);
            }
            // Default behavior - could show/hide controls based on hover
            return null;
        },

        handleVideoLeave(event) {
            // Handle video mouse leave events - could be used for hiding controls or other interactions
            if (touchInterface && touchInterface.handleVideoLeave) {
                return touchInterface.handleVideoLeave(event);
            }
            // Default behavior - could hide controls or reset hover states
            return null;
        },

        // Comment tooltip methods
        hideCommentTooltip() {
            // Hide any active comment tooltips
            if (commentSystem && commentSystem.hideCommentTooltip) {
                return commentSystem.hideCommentTooltip();
            }
            // Fallback - hide any tooltip-related state
            sharedState.showTooltip = false;
            return null;
        },

        showCommentTooltip(commentId) {
            // Show comment tooltip
            if (commentSystem && commentSystem.showCommentTooltip) {
                return commentSystem.showCommentTooltip(commentId);
            }
            return null;
        },

        handleVideoDoubleClick(event) {
            if (videoCore) {
                return videoCore.handleVideoDoubleClick ? videoCore.handleVideoDoubleClick(event) : this.toggleFullscreen();
            }
            return this.toggleFullscreen();
        },

        handleVideoRightClick(event) {
            // Handle right-click context menu on video
            if (!config.annotations?.enableContextMenu) {
                return;
            }

            // Prevent default browser context menu
            if (event.preventDefault) event.preventDefault();
            if (event.stopPropagation) event.stopPropagation();

            // Show custom context menu at current time
            const currentTime = this.currentTime || 0;
            
            // Set context menu state directly
            sharedState.showContextMenu = true;
            sharedState.contextMenuX = event.clientX || event.pageX || 0;
            sharedState.contextMenuY = event.clientY || event.pageY || 0;
            sharedState.contextMenuTime = currentTime;

            // Route to touch interface if available
            if (touchInterface && touchInterface.handleVideoRightClick) {
                return touchInterface.handleVideoRightClick(event);
            }

            return false; // Prevent default context menu
        },

        // Pointer event handlers for unified touch/mouse support
        handlePointerStart(event, target = 'default') {
            if (touchInterface && touchInterface.handlePointerStart) {
                return touchInterface.handlePointerStart(event, target);
            }
            
            // Fallback - track pointer state
            if (!sharedState.pointerState) {
                sharedState.pointerState = {};
            }
            
            sharedState.pointerState.isDown = true;
            sharedState.pointerState.startTime = Date.now();
            sharedState.pointerState.startPos = {
                x: event.clientX || event.touches?.[0]?.clientX || 0,
                y: event.clientY || event.touches?.[0]?.clientY || 0
            };
            sharedState.pointerState.currentPos = { ...sharedState.pointerState.startPos };
            sharedState.pointerState.hasMoved = false;
            sharedState.pointerState.longPressTriggered = false;
            sharedState.pointerState.activePointer = event.pointerType || (event.touches ? 'touch' : 'mouse');
            
            return true;
        },

        handlePointerEnd(event, target = 'default') {
            if (touchInterface && touchInterface.handlePointerEnd) {
                return touchInterface.handlePointerEnd(event, target);
            }
            
            // Fallback - reset pointer state
            if (sharedState.pointerState) {
                sharedState.pointerState.isDown = false;
                sharedState.pointerState.hasMoved = false;
                sharedState.pointerState.longPressTriggered = false;
                
                // Clear active pointer after a short delay
                setTimeout(() => {
                    if (sharedState.pointerState) {
                        sharedState.pointerState.activePointer = null;
                    }
                }, 100);
            }
            
            return true;
        },

        // Missing methods used in Blade template
        jumpFrames(frameCount) {
            return eventHandler?.seekFrame ? eventHandler.seekFrame(frameCount) : videoCore?.seekFrame ? videoCore.seekFrame(frameCount) : null;
        },
        
        // Progress bar pointer/click handlers
        handleProgressBarPointer(event, action = 'click') {
            if (progressBar && progressBar.handleProgressBarPointer) {
                return progressBar.handleProgressBarPointer(event, action);
            }
            // Fallback to standard click handler
            return this.handleProgressBarClick(event, action);
        },
        
        // Context menu handlers
        addCommentAtCurrentFrame() {
            if (commentSystem) {
                return commentSystem.addCommentAtTime ? commentSystem.addCommentAtTime(this.currentTime) : null;
            }
            this.$dispatch('video-annotation:add-comment', { 
                timestamp: this.currentTime,
                frame: this.getFrameNumber(this.currentTime)
            });
        },
        
        startSimpleRegionCreation() {
            if (regionManagement) {
                return regionManagement.startRegionCreation();
            }
            return null;
        },
        
        exitRegionCreationMode() {
            if (regionManagement) {
                return regionManagement.cancelRegionCreation();
            }
            return null;
        },
        
        confirmRegionCreation() {
            if (regionManagement) {
                return regionManagement.finishRegionCreation();
            }
            return null;
        },
        
        setRegionStart() {
            if (regionManagement && regionManagement.setRegionStart) {
                return regionManagement.setRegionStart(this.currentTime);
            }
            // Store region start state
            sharedState.regionCreationStart = {
                time: this.currentTime,
                frame: this.getFrameNumber(this.currentTime)
            };
        },
        
        setRegionEnd() {
            if (regionManagement && regionManagement.setRegionEnd) {
                return regionManagement.setRegionEnd(this.currentTime);
            }
            // Store region end state
            sharedState.regionCreationEnd = {
                time: this.currentTime,
                frame: this.getFrameNumber(this.currentTime)
            };
        },
        
        // Settings and UI toggles
        toggleCommentsOnProgressBar() {
            sharedState.showCommentsOnProgressBar = !sharedState.showCommentsOnProgressBar;
            this.$dispatch('video-annotation:comments-visibility-toggled', {
                visible: sharedState.showCommentsOnProgressBar
            });
        },
        
        toggleProgressBarMode() {
            const modes = ['always-visible', 'auto-hide'];
            const currentIndex = modes.indexOf(sharedState.progressBarMode || 'always-visible');
            const nextIndex = (currentIndex + 1) % modes.length;
            sharedState.progressBarMode = modes[nextIndex];
            
            // Update showProgressBar based on mode
            sharedState.showProgressBar = sharedState.progressBarMode === 'always-visible';
            
            this.$dispatch('video-annotation:progress-bar-mode-changed', {
                mode: sharedState.progressBarMode
            });
        },
        
        changeResolution(source) {
            if (videoCore && videoCore.changeResolution) {
                return videoCore.changeResolution(source);
            }
            
            // Fallback - update shared state
            sharedState.currentResolution = source;
            sharedState.currentResolutionSrc = source.src;
            
            this.$dispatch('video-annotation:resolution-changed', { 
                resolution: source 
            });
        },
        
        // Touch interface handlers
        hideTouchContextMenu() {
            if (touchInterface) {
                touchInterface.hideContextMenu();
            }
            sharedState.touchInterface.contextMenuVisible = false;
        },
        
        handleTouchStart(event) {
            if (touchInterface && touchInterface.handleTouchStart) {
                return touchInterface.handleTouchStart(event);
            }
            
            // Fallback touch handling
            sharedState.touchInterface.activePointer = 'touch';
            return true;
        },
        
        handleTouchEnd(event) {
            if (touchInterface && touchInterface.handleTouchEnd) {
                return touchInterface.handleTouchEnd(event);
            }
            
            // Fallback touch handling
            setTimeout(() => {
                if (sharedState.touchInterface) {
                    sharedState.touchInterface.activePointer = null;
                }
            }, 100);
            return true;
        },
        
        addCommentFromContextMenu() {
            const currentTime = this.currentTime || 0;
            
            // Hide context menu
            this.hideContextMenu();
            
            // Add comment at current time
            if (commentSystem && commentSystem.addCommentAtTime) {
                commentSystem.addCommentAtTime(currentTime);
            } else {
                // Fallback - dispatch event
                this.$dispatch('video-annotation:add-comment', {
                    timestamp: currentTime,
                    frame: this.getFrameNumber(currentTime)
                });
            }
        },
        
        // Test method to verify Alpine reactivity
        testMenuToggle() {
            console.log('Menu toggle test - current showResolutionMenu:', this.showResolutionMenu);
            this.showResolutionMenu = !this.showResolutionMenu;
            console.log('Menu toggle test - new showResolutionMenu:', this.showResolutionMenu);
        },

        // Debug methods
        getSharedState() {
            return config.debug ? sharedState : null;
        },
        getModuleStatus() {
            if (!config.debug) return null;

            return {
                videoCore: videoCore?.publicAPI ? 'ready' : 'not initialized',
                progressBar: progressBar?.publicAPI ? 'ready' : 'not initialized',
                commentSystem: !!commentSystem,
                regionManagement: !!regionManagement,
                touchInterface: touchInterface?.getStatus() || 'not initialized',
                contextDisplay: contextDisplay?.getStatus() || 'not initialized',
                eventHandler: !!eventHandler,
                sharedState: sharedState.toJSON()
            };
        }
    };
}
