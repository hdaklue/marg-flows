/**
 * CommentSystemModule - Handles comment rendering, interactions, and context display system
 * Implements the new comment context system with timeline hit detection and multiple active comments
 */
export class CommentSystemModule {
    constructor(core, sharedState, config) {
        this.core = core;
        this.sharedState = sharedState;
        this.config = config;
        
        // Comment interaction state
        this.lastCommentHitTime = 0;
        this.commentHitThreshold = 0.5; // seconds
        this.proximityThreshold = 2; // seconds for nearby comments
        
        // Bound methods for event handling
        this.boundTimeUpdateHandler = null;
    }
    
    /**
     * Initialize comment system
     */
    init($refs, $dispatch) {
        this.$refs = $refs;
        this.$dispatch = $dispatch;
        
        // Set up time update handler for timeline hit detection
        this.boundTimeUpdateHandler = this.handleTimelineUpdate.bind(this);
        
        return this;
    }
    
    /**
     * Handle video time updates for comment timeline hit detection
     */
    handleTimelineUpdate() {
        if (!this.sharedState.comments.length) return;
        
        const currentTime = this.sharedState.currentTime;
        const previousTime = this.lastCommentHitTime;
        
        // Only check for hits if time has advanced (not seeking backwards)
        if (currentTime > previousTime) {
            this.detectCommentTimelineHits(previousTime, currentTime);
        }
        
        this.lastCommentHitTime = currentTime;
        
        // Update nearby comments for context system
        this.updateNearbyComments();
        
        // Auto-hide individual comments based on their timers
        this.checkCommentAutoHide();
    }
    
    /**
     * Detect when timeline crosses comment timestamps
     */
    detectCommentTimelineHits(fromTime, toTime) {
        const hitComments = this.sharedState.comments.filter(comment => {
            const timestamp = comment.timestamp || 0;
            return timestamp > fromTime && timestamp <= toTime;
        });
        
        // Add hit comments to active comments
        hitComments.forEach(comment => {
            this.showCommentContext(comment.commentId);
        });
    }
    
    /**
     * Update nearby comments for context display
     */
    updateNearbyComments() {
        const currentTime = this.sharedState.currentTime;
        
        this.sharedState.nearbyComments = this.sharedState.comments.filter(comment => {
            const timestamp = comment.timestamp || 0;
            return Math.abs(timestamp - currentTime) <= this.proximityThreshold;
        });
    }
    
    /**
     * Show comment context (add to active comments)
     */
    showCommentContext(commentId) {
        if (!this.sharedState.activeComments.includes(commentId)) {
            this.sharedState.activeComments.push(commentId);
            
            // Set individual auto-hide timer for this comment
            this.setCommentAutoHideTimer(commentId);
            
            if (this.sharedState.contextDisplay.debugMode) {
                console.log(`[CommentSystem] Added comment ${commentId} to active context`);
            }
        }
    }
    
    /**
     * Hide comment context (remove from active comments)
     */
    hideCommentContext(commentId) {
        const index = this.sharedState.activeComments.indexOf(commentId);
        if (index > -1) {
            this.sharedState.activeComments.splice(index, 1);
            
            // Clear the timer for this comment
            if (this.sharedState.commentTimers[commentId]) {
                clearTimeout(this.sharedState.commentTimers[commentId]);
                delete this.sharedState.commentTimers[commentId];
            }
            
            if (this.sharedState.contextDisplay.debugMode) {
                console.log(`[CommentSystem] Removed comment ${commentId} from active context`);
            }
        }
    }
    
    /**
     * Set individual auto-hide timer for a comment
     */
    setCommentAutoHideTimer(commentId) {
        // Clear existing timer if any
        if (this.sharedState.commentTimers[commentId]) {
            clearTimeout(this.sharedState.commentTimers[commentId]);
        }
        
        // Set new timer
        this.sharedState.commentTimers[commentId] = setTimeout(() => {
            this.hideCommentContext(commentId);
        }, this.sharedState.contextDisplay.autoHideDelay);
    }
    
    /**
     * Check and process comment auto-hide timers
     */
    checkCommentAutoHide() {
        // This method can be used for additional auto-hide logic if needed
        // The main auto-hide is handled by individual timers in setCommentAutoHideTimer
    }
    
    /**
     * Handle comment click - seek to timestamp and show context
     */
    handleCommentClick(comment, event) {
        if (event) {
            event.stopPropagation();
        }
        
        if (!comment || typeof comment.timestamp !== 'number') return;
        
        // Seek to comment timestamp
        this.core.seekTo(comment.timestamp);
        
        // Show comment context immediately
        this.showCommentContext(comment.commentId);
        
        // Dispatch custom event
        this.$dispatch('video-annotation:comment-clicked', {
            comment,
            timestamp: comment.timestamp
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[CommentSystem] Comment clicked: ${comment.commentId} at ${comment.timestamp}s`);
        }
    }
    
    /**
     * Handle comment double-click - add new comment at timestamp
     */
    handleCommentDoubleClick(timestamp, event) {
        if (event) {
            event.stopPropagation();
        }
        
        if (!this.config.annotations?.enableProgressBarComments) return;
        
        const frameAlignedTime = this.sharedState.roundToNearestFrame(timestamp);
        const frameNumber = this.sharedState.getFrameNumber(frameAlignedTime);
        
        this.$dispatch('video-annotation:add-comment', {
            timestamp: frameAlignedTime,
            currentTime: frameAlignedTime,
            frameNumber: frameNumber,
            frameRate: this.sharedState.frameRate
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[CommentSystem] Double-click add comment at ${frameAlignedTime}s (frame ${frameNumber})`);
        }
    }
    
    /**
     * Get comment bubble position on progress bar
     */
    getCommentBubblePosition(comment, index) {
        if (!comment.timestamp || !this.sharedState.duration) {
            return { left: 0, zIndex: 100 };
        }
        
        const percentage = (comment.timestamp / this.sharedState.duration) * 100;
        const left = Math.max(0, Math.min(100, percentage));
        
        // Calculate z-index based on active state and proximity to current time
        const isActive = this.sharedState.activeComments.includes(comment.commentId);
        const distanceFromCurrent = Math.abs(comment.timestamp - this.sharedState.currentTime);
        
        let zIndex = 100;
        if (isActive) {
            zIndex = 200; // Active comments on top
        } else if (distanceFromCurrent <= this.proximityThreshold) {
            zIndex = 150; // Nearby comments higher
        }
        
        return { left, zIndex };
    }
    
    /**
     * Calculate comment offset to prevent overlap
     */
    calculateCommentOffset(comment, index) {
        if (!this.sharedState.comments || this.sharedState.comments.length <= 1) {
            return { left: 0, top: 0 };
        }
        
        const currentTimestamp = comment.timestamp || 0;
        const pixelPosition = this.sharedState.timeToPixel(currentTimestamp);
        const bubbleWidth = 24; // Width of comment bubble
        const minDistance = bubbleWidth + 4; // Minimum distance between bubbles
        
        let offsetLeft = 0;
        let offsetTop = 0;
        
        // Check for overlaps with previous comments
        for (let i = 0; i < index; i++) {
            const otherComment = this.sharedState.comments[i];
            const otherTimestamp = otherComment.timestamp || 0;
            const otherPixelPosition = this.sharedState.timeToPixel(otherTimestamp);
            
            const distance = Math.abs(pixelPosition - otherPixelPosition);
            
            if (distance < minDistance) {
                // Comments are too close, offset this one
                offsetTop += 8; // Stack vertically
                
                // If stacking gets too high, offset horizontally
                if (offsetTop > 24) {
                    offsetLeft = minDistance - distance;
                    offsetTop = 0;
                }
            }
        }
        
        return { left: offsetLeft, top: offsetTop };
    }
    
    /**
     * Render comment markers on progress bar
     */
    renderCommentMarkers() {
        if (!this.$refs.progressBar || !this.sharedState.comments.length) return;
        
        // This method provides data for template rendering
        // The actual DOM rendering is handled by Alpine.js templates
        return this.sharedState.comments.map((comment, index) => {
            const position = this.getCommentBubblePosition(comment, index);
            const offset = this.calculateCommentOffset(comment, index);
            const isActive = this.sharedState.activeComments.includes(comment.commentId);
            const isNearby = this.sharedState.nearbyComments.some(nearby => nearby.commentId === comment.commentId);
            
            return {
                comment,
                index,
                position,
                offset,
                isActive,
                isNearby,
                style: {
                    left: `${position.left}%`,
                    zIndex: position.zIndex,
                    transform: `translate(${offset.left}px, ${offset.top}px)`
                }
            };
        });
    }
    
    /**
     * Get comment context content for display
     */
    getCommentContextContent(commentId) {
        const comment = this.sharedState.getComment(commentId);
        if (!comment) return '';
        
        // Format: "Author • Time - Comment text"
        const timestamp = this.formatTimestamp(comment.timestamp);
        const author = comment.name || 'Anonymous';
        const body = comment.body || '';
        
        return `${author} • ${timestamp} - ${body}`;
    }
    
    /**
     * Format timestamp for display
     */
    formatTimestamp(seconds) {
        if (typeof seconds !== 'number' || seconds < 0) return '0:00';
        
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    /**
     * Clear all comment timers
     */
    clearAllCommentTimers() {
        Object.values(this.sharedState.commentTimers).forEach(timer => {
            if (timer) clearTimeout(timer);
        });
        this.sharedState.commentTimers = {};
    }
    
    /**
     * Clear active comments context
     */
    clearActiveComments() {
        this.clearAllCommentTimers();
        this.sharedState.activeComments = [];
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[CommentSystem] Cleared all active comments');
        }
    }
    
    /**
     * Load comment by ID and show context
     */
    loadComment(commentId) {
        const comment = this.sharedState.getComment(commentId);
        if (!comment) return;
        
        // Seek to comment timestamp
        this.core.seekTo(comment.timestamp);
        
        // Show comment context
        this.showCommentContext(commentId);
    }
    
    /**
     * Add comment to the system
     */
    addComment(comment) {
        this.sharedState.addComment(comment);
        
        // Show context for newly added comment
        if (comment.commentId) {
            this.showCommentContext(comment.commentId);
        }
        
        this.$dispatch('video-annotation:comment-added', { comment });
    }
    
    /**
     * Add comment at specific time
     */
    addCommentAtTime(timestamp) {
        const frameRate = this.sharedState.frameRate || 30;
        const frameNumber = Math.floor(timestamp * frameRate) + 1;
        
        // Generate new comment ID
        const commentId = this.sharedState.comments.length + 1;
        
        const newComment = {
            commentId: commentId,
            avatar: 'https://ui-avatars.com/api/?name=User&background=8b5cf6&color=fff',
            name: 'User',
            body: `New comment at ${this.formatTimestamp(timestamp)}`,
            timestamp: timestamp,
            frameNumber: frameNumber,
            frameRate: frameRate
        };
        
        // Add to shared state
        this.addComment(newComment);
        
        // Dispatch event for Livewire integration
        this.$dispatch('video-annotation:add-comment', {
            timestamp: timestamp,
            frameNumber: frameNumber,
            frameRate: frameRate
        });
        
        console.log('[CommentSystem] Added comment at time:', timestamp, 'frame:', frameNumber);
        
        return newComment;
    }
    
    /**
     * Remove comment from the system
     */
    removeComment(commentId) {
        this.sharedState.removeComment(commentId);
        this.$dispatch('video-annotation:comment-removed', { commentId });
    }
    
    /**
     * Enable time update handling
     */
    enableTimeUpdates() {
        if (this.boundTimeUpdateHandler && this.core.player) {
            this.core.player.on('timeupdate', this.boundTimeUpdateHandler);
        }
    }
    
    /**
     * Disable time update handling
     */
    disableTimeUpdates() {
        if (this.boundTimeUpdateHandler && this.core.player) {
            this.core.player.off('timeupdate', this.boundTimeUpdateHandler);
        }
    }
    
    /**
     * Dispose of the comment system
     */
    dispose() {
        this.disableTimeUpdates();
        this.clearAllCommentTimers();
        this.sharedState.activeComments = [];
        this.sharedState.nearbyComments = [];
    }
    
    /**
     * Check if timeline display should be shown for a comment
     * @param {Object} comment - Comment object
     * @returns {boolean} Whether to show timeline display
     */
    shouldShowTimelineDisplay(comment) {
        if (!comment || !comment.commentId) return false;
        
        // Show timeline display if:
        // 1. Comment is in active comments (being hovered/clicked)
        // 2. Timeline is being hit by current playback time
        // 3. Comment was recently clicked
        
        const isActive = this.sharedState.activeComments.includes(comment.commentId);
        const isNearCurrentTime = Math.abs(comment.timestamp - this.sharedState.currentTime) <= this.proximityThreshold;
        const wasRecentlyClicked = this.sharedState.recentlyClickedComments?.includes(comment.commentId);
        
        return isActive || (isNearCurrentTime && this.sharedState.isPlaying) || wasRecentlyClicked;
    }
    
    /**
     * Handle timeline comment click (when clicking on the timeline display bubble)
     * @param {Object} comment - Comment object
     */
    handleTimelineCommentClick(comment) {
        if (!comment || !comment.commentId) return;
        
        // Seek to comment timestamp
        this.core.seekTo(comment.timestamp);
        
        // Add to recently clicked comments for persistent display
        if (!this.sharedState.recentlyClickedComments) {
            this.sharedState.recentlyClickedComments = [];
        }
        
        if (!this.sharedState.recentlyClickedComments.includes(comment.commentId)) {
            this.sharedState.recentlyClickedComments.push(comment.commentId);
        }
        
        // Set auto-hide timer for this specific comment
        this.setCommentAutoHideTimer(comment.commentId, this.config.timing.contextAutoHideDelay || 5000);
        
        // Dispatch event for full comment view
        this.$dispatch('video-annotation:show-comment-details', { 
            comment,
            source: 'timeline-display' 
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[CommentSystem] Timeline comment clicked:', comment.commentId);
        }
    }
    
    /**
     * Set auto-hide timer for individual comment
     * @param {string|number} commentId - Comment ID
     * @param {number} delay - Delay in milliseconds
     */
    setCommentAutoHideTimer(commentId, delay = 3000) {
        // Clear existing timer for this comment
        if (this.sharedState.commentTimers[commentId]) {
            clearTimeout(this.sharedState.commentTimers[commentId]);
        }
        
        // Set new timer
        this.sharedState.commentTimers[commentId] = setTimeout(() => {
            this.hideIndividualComment(commentId);
        }, delay);
    }
    
    /**
     * Hide individual comment from timeline display
     * @param {string|number} commentId - Comment ID
     */
    hideIndividualComment(commentId) {
        // Remove from recently clicked comments
        if (this.sharedState.recentlyClickedComments) {
            const index = this.sharedState.recentlyClickedComments.indexOf(commentId);
            if (index > -1) {
                this.sharedState.recentlyClickedComments.splice(index, 1);
            }
        }
        
        // Remove from active comments
        const activeIndex = this.sharedState.activeComments.indexOf(commentId);
        if (activeIndex > -1) {
            this.sharedState.activeComments.splice(activeIndex, 1);
        }
        
        // Clear timer
        if (this.sharedState.commentTimers[commentId]) {
            clearTimeout(this.sharedState.commentTimers[commentId]);
            delete this.sharedState.commentTimers[commentId];
        }
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[CommentSystem] Hidden individual comment:', commentId);
        }
    }

    /**
     * Public API for Alpine.js integration
     */
    getAlpineMethods() {
        return {
            // Comment interaction methods
            handleCommentClick: this.handleCommentClick.bind(this),
            handleCommentDoubleClick: this.handleCommentDoubleClick.bind(this),
            handleTimelineCommentClick: this.handleTimelineCommentClick.bind(this),
            loadComment: this.loadComment.bind(this),
            
            // Comment management methods
            addComment: this.addComment.bind(this),
            removeComment: this.removeComment.bind(this),
            
            // Context methods
            showCommentContext: this.showCommentContext.bind(this),
            hideCommentContext: this.hideCommentContext.bind(this),
            clearActiveComments: this.clearActiveComments.bind(this),
            
            // Timeline display methods
            shouldShowTimelineDisplay: this.shouldShowTimelineDisplay.bind(this),
            setCommentAutoHideTimer: this.setCommentAutoHideTimer.bind(this),
            hideIndividualComment: this.hideIndividualComment.bind(this),
            
            // Rendering methods
            renderCommentMarkers: this.renderCommentMarkers.bind(this),
            getCommentContextContent: this.getCommentContextContent.bind(this),
            getCommentBubblePosition: this.getCommentBubblePosition.bind(this),
            calculateCommentOffset: this.calculateCommentOffset.bind(this),
            
            // Utility methods
            formatTimestamp: this.formatTimestamp.bind(this),
            
            // Getters for reactive properties
            get activeComments() { return this.sharedState.activeComments; },
            get nearbyComments() { return this.sharedState.nearbyComments; },
            get comments() { return this.sharedState.comments; }
        };
    }
}