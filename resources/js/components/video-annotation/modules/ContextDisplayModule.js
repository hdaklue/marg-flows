/**
 * ContextDisplayModule - Handles progressive context system for comments and time display
 * Replaces the old tooltip system with a more advanced context display
 */
export class ContextDisplayModule {
    constructor(core, sharedState, config) {
        this.core = core;
        this.sharedState = sharedState;
        this.config = config;
        
        // Context display elements
        this.contextElement = null;
        this.contextContent = '';
        this.contextPosition = { left: 0, top: 0 };
        
        // Auto-hide management
        this.autoHideEnabled = true;
        this.autoHideDelay = config.timing?.contextAutoHideDelay || 3000;
        this.autoHideTimer = null;
        
        // Context modes
        this.availableModes = ['time', 'comments', 'combined'];
        this.currentMode = 'combined';
        
        // Animation state
        this.isAnimating = false;
        this.animationDuration = 200;
    }
    
    /**
     * Initialize context display system
     */
    init($refs, $dispatch) {
        this.$refs = $refs;
        this.$dispatch = $dispatch;
        
        // Create context element if it doesn't exist
        this.createContextElement();
        
        // Setup timeline hit detection for comments
        this.setupTimelineHitDetection();
        
        return this;
    }
    
    /**
     * Create context display element
     */
    createContextElement() {
        if (this.contextElement || !this.$refs.videoContainer) return;
        
        this.contextElement = document.createElement('div');
        this.contextElement.className = 'video-annotation-context-display';
        this.contextElement.style.cssText = `
            position: absolute;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.4;
            max-width: 300px;
            word-wrap: break-word;
            pointer-events: none;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity ${this.animationDuration}ms ease, transform ${this.animationDuration}ms ease;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        `;
        
        this.$refs.videoContainer.appendChild(this.contextElement);
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[ContextDisplay] Created context element');
        }
    }
    
    /**
     * Show context display
     */
    show(content, position, options = {}) {
        if (!this.sharedState.contextDisplay.enabled || !this.contextElement) return;
        
        const {
            mode = this.currentMode,
            autoHide = this.autoHideEnabled,
            immediate = false
        } = options;
        
        // Update content and position
        this.updateContent(content, mode);
        this.updatePosition(position);
        
        // Show the element
        if (immediate) {
            this.contextElement.style.transition = 'none';
        }
        
        this.contextElement.style.opacity = '1';
        this.contextElement.style.transform = 'translateY(0)';
        
        if (immediate) {
            // Restore transition after immediate show
            setTimeout(() => {
                if (this.contextElement) {
                    this.contextElement.style.transition = `opacity ${this.animationDuration}ms ease, transform ${this.animationDuration}ms ease`;
                }
            }, 0);
        }
        
        this.sharedState.contextDisplay.visible = true;
        
        // Set auto-hide timer
        if (autoHide) {
            this.setAutoHideTimer();
        }
        
        this.$dispatch('video-annotation:context-shown', {
            content,
            position,
            mode
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[ContextDisplay] Shown with mode: ${mode}`);
        }
    }
    
    /**
     * Hide context display
     */
    hide(immediate = false) {
        if (!this.contextElement) return;
        
        // Clear auto-hide timer
        this.clearAutoHideTimer();
        
        if (immediate) {
            this.contextElement.style.transition = 'none';
            this.contextElement.style.opacity = '0';
            this.contextElement.style.transform = 'translateY(10px)';
            
            // Restore transition
            setTimeout(() => {
                if (this.contextElement) {
                    this.contextElement.style.transition = `opacity ${this.animationDuration}ms ease, transform ${this.animationDuration}ms ease`;
                }
            }, 0);
        } else {
            this.contextElement.style.opacity = '0';
            this.contextElement.style.transform = 'translateY(10px)';
        }
        
        this.sharedState.contextDisplay.visible = false;
        
        this.$dispatch('video-annotation:context-hidden');
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[ContextDisplay] Hidden');
        }
    }
    
    /**
     * Update context content
     */
    updateContent(content, mode = this.currentMode) {
        if (!this.contextElement) return;
        
        this.contextContent = content;
        this.currentMode = mode;
        
        // Format content based on mode
        let formattedContent = '';
        
        switch (mode) {
            case 'time':
                formattedContent = this.formatTimeContent(content);
                break;
            case 'comments':
                formattedContent = this.formatCommentsContent(content);
                break;
            case 'combined':
                formattedContent = this.formatCombinedContent(content);
                break;
            default:
                formattedContent = content;
        }
        
        this.contextElement.innerHTML = formattedContent;
        
        // Update shared state
        this.sharedState.contextDisplay.content = formattedContent;
        this.sharedState.contextDisplay.mode = mode;
    }
    
    /**
     * Update context position
     */
    updatePosition(position) {
        if (!this.contextElement || !position) return;
        
        const { left, top } = position;
        
        // Calculate position relative to video container
        const containerRect = this.$refs.videoContainer.getBoundingClientRect();
        const contextRect = this.contextElement.getBoundingClientRect();
        
        let finalLeft = left;
        let finalTop = top - contextRect.height - 10; // Position above the reference point
        
        // Boundary checks
        if (finalLeft + contextRect.width > containerRect.width) {
            finalLeft = containerRect.width - contextRect.width - 10;
        }
        
        if (finalLeft < 10) {
            finalLeft = 10;
        }
        
        if (finalTop < 10) {
            finalTop = top + 30; // Position below if not enough space above
        }
        
        this.contextElement.style.left = `${finalLeft}px`;
        this.contextElement.style.top = `${finalTop}px`;
        
        // Update shared state
        this.sharedState.contextDisplay.position = { left: finalLeft, top: finalTop };
    }
    
    /**
     * Format time-based content
     */
    formatTimeContent(content) {
        if (typeof content === 'object' && content.time !== undefined) {
            const timestamp = this.formatTimestamp(content.time);
            const frameNumber = this.sharedState.getFrameNumber(content.time);
            
            return `
                <div class="context-time">
                    <div class="context-timestamp">${timestamp}</div>
                    <div class="context-frame">Frame ${frameNumber}</div>
                </div>
            `;
        }
        
        return content;
    }
    
    /**
     * Format comments-based content
     */
    formatCommentsContent(content) {
        if (Array.isArray(content)) {
            // Multiple comments
            return content.map(comment => this.formatSingleComment(comment)).join('');
        } else if (typeof content === 'object' && content.commentId) {
            // Single comment
            return this.formatSingleComment(content);
        }
        
        return content;
    }
    
    /**
     * Format single comment for display
     */
    formatSingleComment(comment) {
        const timestamp = this.formatTimestamp(comment.timestamp);
        const author = comment.name || 'Anonymous';
        const body = comment.body || '';
        const avatar = comment.avatar || '';
        
        return `
            <div class="context-comment" data-comment-id="${comment.commentId}">
                <div class="context-comment-header">
                    ${avatar ? `<img src="${avatar}" class="context-comment-avatar" alt="${author}" />` : ''}
                    <span class="context-comment-author">${author}</span>
                    <span class="context-comment-time">${timestamp}</span>
                </div>
                <div class="context-comment-body">${body}</div>
            </div>
        `;
    }
    
    /**
     * Format combined content (time + comments)
     */
    formatCombinedContent(content) {
        if (typeof content === 'object' && (content.time !== undefined || content.comments)) {
            let html = '';
            
            // Add time information
            if (content.time !== undefined) {
                html += this.formatTimeContent({ time: content.time });
            }
            
            // Add comments information
            if (content.comments && content.comments.length > 0) {
                if (html) html += '<hr class="context-divider" />';
                html += this.formatCommentsContent(content.comments);
            }
            
            return html;
        }
        
        return content;
    }
    
    /**
     * Show context for current time
     */
    showTimeContext(time = null, position = null) {
        const currentTime = time !== null ? time : this.sharedState.currentTime;
        const content = { time: currentTime };
        
        this.show(content, position, { mode: 'time' });
    }
    
    /**
     * Show context for comments
     */
    showCommentsContext(comments, position = null) {
        if (!Array.isArray(comments) || comments.length === 0) return;
        
        this.show(comments, position, { mode: 'comments' });
    }
    
    /**
     * Show combined context (time + comments)
     */
    showCombinedContext(time = null, comments = null, position = null) {
        const currentTime = time !== null ? time : this.sharedState.currentTime;
        const activeComments = comments || this.getActiveComments();
        
        const content = {
            time: currentTime,
            comments: activeComments
        };
        
        this.show(content, position, { mode: 'combined' });
    }
    
    /**
     * Get active comments for current context
     */
    getActiveComments() {
        return this.sharedState.activeComments
            .map(commentId => this.sharedState.getComment(commentId))
            .filter(comment => comment !== undefined);
    }
    
    /**
     * Set auto-hide timer
     */
    setAutoHideTimer() {
        this.clearAutoHideTimer();
        
        if (this.autoHideEnabled) {
            this.autoHideTimer = setTimeout(() => {
                this.hide();
            }, this.autoHideDelay);
        }
    }
    
    /**
     * Clear auto-hide timer
     */
    clearAutoHideTimer() {
        if (this.autoHideTimer) {
            clearTimeout(this.autoHideTimer);
            this.autoHideTimer = null;
        }
    }
    
    /**
     * Enable context display system
     */
    enable() {
        this.sharedState.contextDisplay.enabled = true;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[ContextDisplay] Enabled');
        }
    }
    
    /**
     * Disable context display system
     */
    disable() {
        this.sharedState.contextDisplay.enabled = false;
        this.hide(true);
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[ContextDisplay] Disabled');
        }
    }
    
    /**
     * Toggle context display system
     */
    toggle() {
        if (this.sharedState.contextDisplay.enabled) {
            this.disable();
        } else {
            this.enable();
        }
    }
    
    /**
     * Set context mode
     */
    setMode(mode) {
        if (this.availableModes.includes(mode)) {
            this.currentMode = mode;
            this.sharedState.contextDisplay.mode = mode;
            
            if (this.sharedState.contextDisplay.debugMode) {
                console.log(`[ContextDisplay] Mode set to: ${mode}`);
            }
        }
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
     * Get context display status
     */
    getStatus() {
        return {
            enabled: this.sharedState.contextDisplay.enabled,
            visible: this.sharedState.contextDisplay.visible,
            mode: this.currentMode,
            autoHide: this.autoHideEnabled,
            hasContent: this.contextContent.length > 0
        };
    }
    
    /**
     * Dispose of context display
     */
    dispose() {
        this.clearAutoHideTimer();
        
        if (this.contextElement && this.contextElement.parentNode) {
            this.contextElement.parentNode.removeChild(this.contextElement);
            this.contextElement = null;
        }
        
        this.sharedState.contextDisplay.visible = false;
        this.sharedState.contextDisplay.content = '';
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[ContextDisplay] Disposed');
        }
    }
    
    /**
     * Get display content for Alpine.js template (secondary context text)
     * Simple real-time comment display without auto-hide complications
     */
    getDisplayContent() {
        const currentTime = this.sharedState.currentTime;
        const proximityThreshold = 2; // seconds - how close to comment to trigger display
        
        // Find comments that are currently being hit by timeline
        const hitComments = this.sharedState.comments.filter(comment => {
            if (!comment.timestamp) return false;
            const distance = Math.abs(comment.timestamp - currentTime);
            return distance <= proximityThreshold;
        });
        
        // Base content with current time
        const baseContent = {
            primary: this.formatTimestamp(currentTime),
            secondary: null,
            showCommentCount: false,
            comment: null,
            isVisible: false
        };
        
        // If comments are being hit, show the first one
        if (hitComments.length > 0) {
            const comment = hitComments[0];
            const author = comment.name || 'Anonymous';
            const commentTime = this.formatTimestamp(comment.timestamp);
            const body = comment.body || '';
            
            return {
                ...baseContent,
                secondary: `${author} • ${commentTime} - ${body}`,
                comment: comment,
                showCommentCount: hitComments.length > 1,
                commentCount: hitComments.length,
                isVisible: true
            };
        }
        
        // No comments hit - return basic display
        return baseContent;
    }
    
    /**
     * Get recently active comments that are still in auto-hide period  
     */
    getRecentlyActiveComments() {
        if (!this.sharedState.commentDisplayTimers) return [];
        
        const activeCommentIds = Object.keys(this.sharedState.commentDisplayTimers);
        return activeCommentIds
            .map(id => this.sharedState.comments.find(c => c.commentId == id))
            .filter(c => c)
            .sort((a, b) => b.timestamp - a.timestamp); // Most recent first
    }
    
    /**
     * Set auto-hide timer for a specific comment
     */
    setCommentAutoHideTimer(commentId) {
        if (!this.sharedState.commentDisplayTimers) {
            this.sharedState.commentDisplayTimers = {};
        }
        
        // Clear existing timer
        if (this.sharedState.commentDisplayTimers[commentId]) {
            clearTimeout(this.sharedState.commentDisplayTimers[commentId]);
        }
        
        // Set new auto-hide timer (3 seconds)
        this.sharedState.commentDisplayTimers[commentId] = setTimeout(() => {
            this.hideCommentFromSecondaryContext(commentId);
            
            // Trigger Alpine.js reactivity update
            if (typeof document !== 'undefined') {
                document.dispatchEvent(new CustomEvent('video-context-updated', {
                    detail: { action: 'comment-auto-hidden', commentId }
                }));
            }
            
            console.log('[ContextDisplay] Auto-hid comment:', commentId);
        }, 3000);
        
        console.log('[ContextDisplay] Set auto-hide timer for comment:', commentId);
    }
    
    /**
     * Setup timeline hit detection for comments
     */
    setupTimelineHitDetection() {
        // Listen for time updates to detect when timeline hits comments
        if (this.core.player) {
            this.core.player.on('timeupdate', () => {
                this.updateTimelineHitDetection();
            });
        }
        
        // Also listen for Alpine.js video time update events
        if (typeof document !== 'undefined') {
            document.addEventListener('video-time-updated', (event) => {
                this.updateTimelineHitDetection();
            });
        }
    }
    
    /**
     * Update timeline hit detection and manage auto-hide timers
     */
    updateTimelineHitDetection() {
        const currentTime = this.sharedState.currentTime;
        const proximityThreshold = 2; // seconds
        
        // Find comments currently being hit
        const hitComments = this.sharedState.comments.filter(comment => {
            if (!comment.timestamp) return false;
            const distance = Math.abs(comment.timestamp - currentTime);
            return distance <= proximityThreshold;
        });
        
        // Update context display if comments are being hit
        if (hitComments.length > 0) {
            // Set auto-hide timers for hit comments
            hitComments.forEach(comment => {
                if (!this.sharedState.commentDisplayTimers) {
                    this.sharedState.commentDisplayTimers = {};
                }
                
                // Clear existing timer for this comment
                if (this.sharedState.commentDisplayTimers[comment.commentId]) {
                    clearTimeout(this.sharedState.commentDisplayTimers[comment.commentId]);
                }
                
                // Set new auto-hide timer (3 seconds after timeline passes)
                this.sharedState.commentDisplayTimers[comment.commentId] = setTimeout(() => {
                    this.hideCommentFromSecondaryContext(comment.commentId);
                }, 3000);
            });
            
            // Update nearby comments for context
            this.sharedState.contextDisplay.nearbyComments = hitComments.map(comment => ({
                comment,
                distance: Math.abs(comment.timestamp - currentTime)
            }));
        } else {
            // Clear nearby comments when nothing is being hit
            this.sharedState.contextDisplay.nearbyComments = [];
        }
    }
    
    /**
     * Hide specific comment from secondary context
     */
    hideCommentFromSecondaryContext(commentId) {
        // Remove from nearby comments
        if (this.sharedState.contextDisplay.nearbyComments) {
            this.sharedState.contextDisplay.nearbyComments = this.sharedState.contextDisplay.nearbyComments.filter(
                nearby => nearby.comment.commentId !== commentId
            );
        }
        
        // Clear timer
        if (this.sharedState.commentDisplayTimers && this.sharedState.commentDisplayTimers[commentId]) {
            clearTimeout(this.sharedState.commentDisplayTimers[commentId]);
            delete this.sharedState.commentDisplayTimers[commentId];
        }
    }

    /**
     * Public API for Alpine.js integration
     */
    getAlpineMethods() {
        return {
            // Core context methods
            showContext: this.show.bind(this),
            hideContext: this.hide.bind(this),
            
            // Specific context types
            showTimeContext: this.showTimeContext.bind(this),
            showCommentsContext: this.showCommentsContext.bind(this),
            showCombinedContext: this.showCombinedContext.bind(this),
            
            // Display content for templates
            getDisplayContent: this.getDisplayContent.bind(this),
            
            // Context control
            enableContext: this.enable.bind(this),
            disableContext: this.disable.bind(this),
            toggleContext: this.toggle.bind(this),
            
            // Context configuration
            setContextMode: this.setMode.bind(this),
            clearContextAutoHide: this.clearAutoHideTimer.bind(this),
            
            // Utility methods
            getContextStatus: this.getStatus.bind(this),
            formatTimestamp: this.formatTimestamp.bind(this),
            
            // Getters for reactive properties
            get contextVisible() { return this.sharedState.contextDisplay.visible; },
            get contextEnabled() { return this.sharedState.contextDisplay.enabled; },
            get contextMode() { return this.currentMode; },
            get contextContent() { return this.contextContent; }
        };
    }
}