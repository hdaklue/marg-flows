/**
 * ProgressBarModule - Handles progress bar interactions, seeking, and drag operations
 */
export class ProgressBarModule {
    constructor(core, sharedState, config) {
        this.core = core;
        this.sharedState = sharedState;
        this.config = config;
        
        // Progress bar specific state
        this.isDragging = false;
        this.wasPlayingBeforeDrag = false;
        this.dragStartX = 0;
        this.dragCurrentTime = 0;
        this.showSeekCircle = false;
        
        // Bound methods for event handling
        this.boundHandleDragMove = null;
        this.boundEndDrag = null;
    }

    /**
     * Initialize progress bar functionality
     */
    init($refs, $dispatch) {
        this.$refs = $refs;
        this.$dispatch = $dispatch;
        return this;
    }

    /**
     * Handle progress bar pointer events (unified mouse/touch handling)
     */
    handleProgressBarPointer(event, action = 'click') {
        if (!this.$refs.progressBar || !this.sharedState.duration) return;

        const rect = this.$refs.progressBar.getBoundingClientRect();
        const isTouch = event.type.includes('touch') || event.pointerType === 'touch';
        const clientX = isTouch ? (event.touches?.[0]?.clientX || event.clientX) : event.clientX;
        const clickX = clientX - rect.left;
        const percentage = Math.max(0, Math.min(1, clickX / rect.width));
        const targetTime = percentage * this.sharedState.duration;

        // Seek to the clicked position
        if (this.core && this.core.seekTo) {
            this.core.seekTo(targetTime);
        }

        // Store hover position for other modules
        this.sharedState.hoverX = clickX;
        this.dragCurrentTime = targetTime;

        // Handle different actions
        if (action === 'doubleclick') {
            // Handle double click/tap - add comment
            if (this.config.annotations?.enableProgressBarComments) {
                const frameAlignedTime = this.core.roundToNearestFrame ? this.core.roundToNearestFrame(targetTime) : targetTime;
                const frameNumber = this.core.getFrameNumber ? this.core.getFrameNumber(frameAlignedTime) : Math.floor(frameAlignedTime * 30);
                this.$dispatch('video-annotation:add-comment', {
                    timestamp: frameAlignedTime,
                    currentTime: frameAlignedTime,
                    frameNumber: frameNumber,
                    frameRate: this.core.getFrameRate ? this.core.getFrameRate() : 30
                });
            }
        }

        // Dispatch seek event
        this.$dispatch('video-annotation:seek', {
            timestamp: targetTime,
            percentage: percentage,
            action: action
        });

        return true;
    }

    /**
     * Handle progress bar click/tap for seeking
     */
    handleProgressBarClick(event, action = 'click') {
        return this.handleProgressBarPointer(event, action);
    }

    /**
     * Start progress bar drag operation
     */
    handleProgressBarDragStart(event) {
        if (!this.handlePointerStart(event, 'progressbar-drag')) return;

        // Remember if video was playing
        this.wasPlayingBeforeDrag = this.sharedState.isPlaying;

        this.isDragging = true;
        this.showSeekCircle = true;
        this.sharedState.showProgressBar = true;

        // Clear progress bar auto-hide timeout
        if (this.sharedState.progressBarTimeout) {
            clearTimeout(this.sharedState.progressBarTimeout);
            this.sharedState.progressBarTimeout = null;
        }

        const progressBar = this.$refs.progressBar;
        if (!progressBar) return;

        const rect = progressBar.getBoundingClientRect();
        const isTouch = event.type.includes('touch');
        const clientX = isTouch ? event.touches[0].clientX : event.clientX;

        this.dragStartX = clientX - rect.left;
        const percentage = this.dragStartX / rect.width;
        this.dragCurrentTime = percentage * this.sharedState.duration;

        // Add global listeners for unified drag handling
        const moveEvent = isTouch ? 'touchmove' : 'mousemove';
        const endEvent = isTouch ? 'touchend' : 'mouseup';

        this.boundHandleDragMove = this.handleProgressBarDragMove.bind(this);
        this.boundEndDrag = this.handleProgressBarDragEnd.bind(this);

        document.addEventListener(moveEvent, this.boundHandleDragMove, { passive: false });
        document.addEventListener(endEvent, this.boundEndDrag);
    }

    /**
     * Handle progress bar drag movement
     */
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

        this.sharedState.hoverX = newX;
        const percentage = newX / rect.width;
        this.dragCurrentTime = percentage * this.sharedState.duration;
    }

    /**
     * End progress bar drag operation
     */
    handleProgressBarDragEnd(event) {
        if (!this.isDragging) return;

        this.handlePointerEnd(event, 'progressbar-drag');

        // Seek to final position
        if (this.dragCurrentTime >= 0 && this.dragCurrentTime <= this.sharedState.duration) {
            this.core.seekTo(this.dragCurrentTime);
        }

        // Resume playback if it was playing before drag
        if (this.wasPlayingBeforeDrag && this.core.player) {
            this.core.player.play();
        }

        // Clean up drag state
        this.isDragging = false;
        this.showSeekCircle = false;
        this.dragCurrentTime = 0;

        // Remove global listeners
        const moveEvent = event.type.includes('touch') ? 'touchmove' : 'mousemove';
        const endEvent = event.type.includes('touch') ? 'touchend' : 'mouseup';

        if (this.boundHandleDragMove) {
            document.removeEventListener(moveEvent, this.boundHandleDragMove);
            this.boundHandleDragMove = null;
        }
        
        if (this.boundEndDrag) {
            document.removeEventListener(endEvent, this.boundEndDrag);
            this.boundEndDrag = null;
        }
    }

    /**
     * Handle progress bar hover for desktop
     */
    handleProgressBarHover(event) {
        if (!this.$refs.progressBar || this.isDragging) return;

        const rect = this.$refs.progressBar.getBoundingClientRect();
        this.sharedState.hoverX = event.clientX - rect.left;
        
        // Calculate hover time for tooltips/context
        const percentage = this.sharedState.hoverX / rect.width;
        const hoverTime = percentage * this.sharedState.duration;
        
        // Store hover information for other modules
        this.sharedState.hoverTime = hoverTime;
    }

    /**
     * Handle progress bar mouse leave
     */
    handleProgressBarLeave() {
        if (!this.isDragging) {
            this.sharedState.showHoverAdd = false;
        }
    }

    /**
     * Handle pointer start (unified touch/mouse)
     */
    handlePointerStart(event, source) {
        // Prevent default behavior for touch events
        if (event.type.includes('touch')) {
            event.preventDefault();
        }
        return true;
    }

    /**
     * Handle pointer move (unified touch/mouse)
     */
    handlePointerMove(event) {
        // Prevent default behavior for touch events
        if (event.type.includes('touch')) {
            event.preventDefault();
        }
    }

    /**
     * Handle pointer end (unified touch/mouse)
     */
    handlePointerEnd(event, source) {
        // Can be extended for specific end handling
        return true;
    }

    /**
     * Get current progress percentage
     */
    getProgressPercentage() {
        if (!this.sharedState.duration) return 0;
        return (this.sharedState.currentTime / this.sharedState.duration) * 100;
    }

    /**
     * Get buffered percentage
     */
    getBufferedPercentage() {
        return this.sharedState.bufferedPercentage || 0;
    }

    /**
     * Convert pixel position to time
     */
    pixelToTime(pixelX) {
        if (!this.$refs.progressBar || !this.sharedState.duration) return 0;
        
        const rect = this.$refs.progressBar.getBoundingClientRect();
        const percentage = Math.max(0, Math.min(1, pixelX / rect.width));
        return percentage * this.sharedState.duration;
    }

    /**
     * Convert time to pixel position
     */
    timeToPixel(time) {
        if (!this.$refs.progressBar || !this.sharedState.duration) return 0;
        
        const rect = this.$refs.progressBar.getBoundingClientRect();
        const percentage = time / this.sharedState.duration;
        return percentage * rect.width;
    }

    /**
     * Update progress bar width for other modules
     */
    updateProgressBarWidth() {
        if (this.$refs.progressBar) {
            this.sharedState.progressBarWidth = this.$refs.progressBar.getBoundingClientRect().width;
        }
    }

    /**
     * Public API for external access
     */
    get publicAPI() {
        return {
            handleProgressBarClick: this.handleProgressBarClick.bind(this),
            handleProgressBarDragStart: this.handleProgressBarDragStart.bind(this),
            handleProgressBarHover: this.handleProgressBarHover.bind(this),
            handleProgressBarLeave: this.handleProgressBarLeave.bind(this),
            getProgressPercentage: this.getProgressPercentage.bind(this),
            getBufferedPercentage: this.getBufferedPercentage.bind(this),
            pixelToTime: this.pixelToTime.bind(this),
            timeToPixel: this.timeToPixel.bind(this),
            updateProgressBarWidth: this.updateProgressBarWidth.bind(this),
            isDragging: () => this.isDragging,
            dragCurrentTime: () => this.dragCurrentTime
        };
    }
}