/**
 * SharedState - Centralized reactive state management for video annotation components
 * This module manages all shared data between different video annotation modules
 */
export class SharedState {
    constructor(config, initialComments = []) {
        this.config = config;
        
        // Video player state
        this.videoLoaded = false;
        this.duration = 0;
        this.currentTime = 0;
        this.isPlaying = false;
        this.volume = 1;
        this.isMuted = false;
        this.isFullscreen = false;
        this.bufferedPercentage = 0;
        
        // Quality/resolution state
        this.qualitySources = [];
        this.currentResolution = null;
        this.currentResolutionSrc = null;
        
        // Progress bar state
        this.progressBarMode = config.ui?.progressBarMode || 'always';
        this.showProgressBar = this.progressBarMode === 'always';
        this.progressBarTimeout = null;
        this.progressBarWidth = 0;
        
        // Interaction state
        this.isDragging = false;
        this.hoverX = 0;
        this.hoverTime = 0;
        this.showHoverAdd = false;
        this.wasPlayingBeforeDrag = false;
        this.dragStartX = 0;
        this.dragCurrentTime = 0;
        this.showSeekCircle = false;
        
        // Touch/pointer state
        this.touchInterface = {
            mode: 'NORMAL', // 'NORMAL', 'REGION_CREATE', 'REGION_EDIT'
            isEnabled: true,
            enabled: true, // Duplicate for template compatibility
            hammer: null,
            gestureInProgress: false,
            lastTapTime: 0,
            tapCount: 0,
            activePointer: null, // 'mouse', 'touch', or null
            contextMenuVisible: false,
            unifiedTimelineVisible: false,
            actionModalVisible: false
        };
        
        // Pointer state for detailed tracking
        this.pointerState = {
            isDown: false,
            startTime: 0,
            startPos: { x: 0, y: 0 },
            currentPos: { x: 0, y: 0 },
            hasMoved: false,
            longPressTriggered: false,
            ghostClickPrevention: false,
            activePointer: null
        };
        
        // Tooltip state (legacy)
        this.showTooltip = false;
        this.tooltipTimeout = null;
        this.tooltipPosition = { left: 0, top: 0 };
        this.arrowPosition = 'center';
        
        // Comments state
        this.comments = initialComments || [];
        this.activeComments = []; // Multiple active comments for context system
        this.commentTimers = {}; // Individual timers for each comment auto-hide
        this.nearbyComments = []; // Comments within proximity threshold
        this.recentlyClickedComments = []; // Recently clicked comments for persistent timeline display
        this.commentDisplayTimers = {}; // Auto-hide timers for secondary context display
        
        // Region management state
        this.regions = [];
        this.activeRegion = null;
        this.regionCreationActive = false;
        this.regionStartTime = null;
        this.regionEndTime = null;
        this.showRegionTooltip = null;
        this.hiddenRegions = new Set();
        
        // UI Menu states
        this.showSpeedMenu = false;
        this.showSpeedModal = false;
        this.showVolumeModal = false;
        this.showVolumeSlider = false;
        this.showSettingsMenu = false;
        this.showResolutionMenu = false;
        this.mobileControlsExpanded = false;
        
        // Context menu state
        this.showContextMenu = false;
        this.contextMenuX = 0;
        this.contextMenuY = 0;
        this.contextMenuTime = 0;
        
        // Context display system state
        this.contextDisplay = {
            mode: 'time', // 'time' | 'comments' | 'combined'
            visible: false,
            autoHide: true,
            autoHideDelay: config.timing?.contextAutoHideDelay || 3000,
            timeout: null,
            debugMode: config.debug?.contextSystem || false,
            nearbyComments: [],
            currentDisplayComment: null,
            enabled: true, // Flag to disable old tooltip system when context is active
            position: { left: 0, top: 0 },
            content: ''
        };
        
        // Frame navigation state
        this.frameRate = 30; // Default, can be detected
        this.currentFrameNumber = 0;
        this.frameNavigationDirection = null; // 'forward', 'backward', 'seek', null
        this.showFrameHelpers = true;
        this.showPlayPauseOverlay = false;
        
        // Event system state
        this.eventListeners = new Map();
        this.globalEventListeners = new Map();
    }
    
    /**
     * Get current frame state for frame-aligned operations
     */
    get currentFrameState() {
        if (!this.duration) return { frameNumber: 1, totalFrames: 1, percentage: 0 };
        
        const totalFrames = Math.ceil(this.duration * this.frameRate);
        const frameNumber = Math.floor(this.currentTime * this.frameRate) + 1;
        const percentage = (frameNumber - 1) / totalFrames * 100;
        
        return {
            frameNumber: Math.max(1, Math.min(frameNumber, totalFrames)),
            totalFrames,
            percentage: Math.max(0, Math.min(100, percentage))
        };
    }
    
    /**
     * Get frame-aligned progress percentage
     */
    get frameAlignedProgressPercentage() {
        const frameState = this.currentFrameState;
        return frameState.percentage;
    }
    
    /**
     * Check if device is mobile
     */
    get isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    /**
     * Round time to nearest frame
     */
    roundToNearestFrame(time) {
        const frameDuration = 1 / this.frameRate;
        return Math.round(time / frameDuration) * frameDuration;
    }
    
    /**
     * Get frame number from time
     */
    getFrameNumber(time) {
        return Math.floor(time * this.frameRate) + 1;
    }
    
    /**
     * Convert time to progress bar pixel position
     */
    timeToPixel(time) {
        if (!this.duration || !this.progressBarWidth) return 0;
        return (time / this.duration) * this.progressBarWidth;
    }
    
    /**
     * Convert progress bar pixel position to time
     */
    pixelToTime(pixelX) {
        if (!this.duration || !this.progressBarWidth) return 0;
        const percentage = Math.max(0, Math.min(1, pixelX / this.progressBarWidth));
        return percentage * this.duration;
    }
    
    /**
     * Add a comment to the comments array
     */
    addComment(comment) {
        if (!comment || typeof comment.timestamp !== 'number') return;
        
        this.comments.push(comment);
        this.sortCommentsByTimestamp();
    }
    
    /**
     * Remove a comment from the comments array
     */
    removeComment(commentId) {
        this.comments = this.comments.filter(comment => comment.commentId !== commentId);
        this.activeComments = this.activeComments.filter(id => id !== commentId);
        
        // Clear timer for removed comment
        if (this.commentTimers[commentId]) {
            clearTimeout(this.commentTimers[commentId]);
            delete this.commentTimers[commentId];
        }
    }
    
    /**
     * Sort comments by timestamp
     */
    sortCommentsByTimestamp() {
        this.comments.sort((a, b) => (a.timestamp || 0) - (b.timestamp || 0));
    }
    
    /**
     * Get comment by ID
     */
    getComment(commentId) {
        return this.comments.find(comment => comment.commentId === commentId);
    }
    
    /**
     * Get comments within time range
     */
    getCommentsInRange(startTime, endTime) {
        return this.comments.filter(comment => 
            comment.timestamp >= startTime && comment.timestamp <= endTime
        );
    }
    
    /**
     * Add a region to the regions array
     */
    addRegion(region) {
        if (!region || typeof region.startTime !== 'number' || typeof region.endTime !== 'number') return;
        
        this.regions.push(region);
        this.sortRegionsByStartTime();
    }
    
    /**
     * Remove a region from the regions array
     */
    removeRegion(regionId) {
        this.regions = this.regions.filter(region => region.id !== regionId);
        this.hiddenRegions.delete(regionId);
        
        if (this.activeRegion?.id === regionId) {
            this.activeRegion = null;
        }
    }
    
    /**
     * Sort regions by start time
     */
    sortRegionsByStartTime() {
        this.regions.sort((a, b) => (a.startTime || 0) - (b.startTime || 0));
    }
    
    /**
     * Get region by ID
     */
    getRegion(regionId) {
        return this.regions.find(region => region.id === regionId);
    }
    
    /**
     * Get regions that overlap with current time
     */
    getActiveRegions() {
        return this.regions.filter(region => 
            this.currentTime >= region.startTime && this.currentTime <= region.endTime
        );
    }
    
    /**
     * Clear all timeouts
     */
    clearAllTimeouts() {
        if (this.progressBarTimeout) {
            clearTimeout(this.progressBarTimeout);
            this.progressBarTimeout = null;
        }
        
        if (this.tooltipTimeout) {
            clearTimeout(this.tooltipTimeout);
            this.tooltipTimeout = null;
        }
        
        if (this.contextDisplay.timeout) {
            clearTimeout(this.contextDisplay.timeout);
            this.contextDisplay.timeout = null;
        }
        
        // Clear all comment timers
        Object.values(this.commentTimers).forEach(timer => {
            if (timer) clearTimeout(timer);
        });
        this.commentTimers = {};
        
        // Clear comment display timers
        if (this.commentDisplayTimers) {
            Object.values(this.commentDisplayTimers).forEach(timer => {
                if (timer) clearTimeout(timer);
            });
            this.commentDisplayTimers = {};
        }
    }
    
    /**
     * Reset state to initial values
     */
    reset() {
        this.clearAllTimeouts();
        
        // Reset video state
        this.videoLoaded = false;
        this.duration = 0;
        this.currentTime = 0;
        this.isPlaying = false;
        this.bufferedPercentage = 0;
        
        // Reset interaction state
        this.isDragging = false;
        this.hoverX = 0;
        this.hoverTime = 0;
        this.showHoverAdd = false;
        this.showSeekCircle = false;
        
        // Reset touch interface
        this.touchInterface.mode = 'NORMAL';
        this.touchInterface.gestureInProgress = false;
        this.touchInterface.activePointer = null;
        
        // Reset tooltip state
        this.showTooltip = false;
        
        // Reset comments and regions
        this.activeComments = [];
        this.nearbyComments = [];
        this.activeRegion = null;
        this.regionCreationActive = false;
        
        // Reset context display
        this.contextDisplay.visible = false;
        this.contextDisplay.currentDisplayComment = null;
        this.contextDisplay.nearbyComments = [];
    }
    
    /**
     * Get all state as plain object for debugging
     */
    toJSON() {
        return {
            videoLoaded: this.videoLoaded,
            duration: this.duration,
            currentTime: this.currentTime,
            isPlaying: this.isPlaying,
            volume: this.volume,
            isMuted: this.isMuted,
            isFullscreen: this.isFullscreen,
            bufferedPercentage: this.bufferedPercentage,
            progressBarMode: this.progressBarMode,
            showProgressBar: this.showProgressBar,
            isDragging: this.isDragging,
            touchInterface: { ...this.touchInterface },
            comments: this.comments.length,
            activeComments: this.activeComments.length,
            regions: this.regions.length,
            contextDisplay: { ...this.contextDisplay },
            frameState: this.currentFrameState
        };
    }
}