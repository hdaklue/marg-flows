/**
 * EventHandlerModule - Unified event handling for all video annotation interactions
 * Manages pointer events, keyboard shortcuts, and custom event dispatching
 */
export class EventHandlerModule {
    constructor(core, sharedState, config) {
        this.core = core;
        this.sharedState = sharedState;
        this.config = config;
        
        // Event listeners registry
        this.eventListeners = new Map();
        this.globalEventListeners = new Map();
        
        // Keyboard shortcuts
        this.keyboardShortcuts = new Map();
        this.keyboardEnabled = true;
        
        // Pointer tracking
        this.lastPointerType = null;
        this.pointerTimeout = null;
        
        // Right-click context menu state
        this.contextMenuActive = false;
        this.contextMenuPosition = { x: 0, y: 0 };
        
        // Double-click detection
        this.lastClickTime = 0;
        this.doubleClickDelay = 300;
        this.clickCount = 0;
        this.clickTimer = null;
    }
    
    /**
     * Initialize event handling
     */
    init($refs, $dispatch) {
        this.$refs = $refs;
        this.$dispatch = $dispatch;
        
        this.setupKeyboardShortcuts();
        this.setupGlobalEventListeners();
        this.setupVideoContainerEvents();
        
        return this;
    }
    
    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        // Default keyboard shortcuts
        const shortcuts = {
            'Space': () => this.core.togglePlayPause(),
            'ArrowLeft': () => this.seekRelative(-5),
            'ArrowRight': () => this.seekRelative(5),
            'ArrowUp': () => this.adjustVolume(0.1),
            'ArrowDown': () => this.adjustVolume(-0.1),
            'KeyF': () => this.core.toggleFullscreen(),
            'KeyM': () => this.core.toggleMute(),
            'Comma': () => this.seekFrame(-1),
            'Period': () => this.seekFrame(1),
            'Home': () => this.core.seekTo(0),
            'End': () => this.core.seekTo(this.sharedState.duration),
            'Digit1': () => this.core.seekTo(this.sharedState.duration * 0.1),
            'Digit2': () => this.core.seekTo(this.sharedState.duration * 0.2),
            'Digit3': () => this.core.seekTo(this.sharedState.duration * 0.3),
            'Digit4': () => this.core.seekTo(this.sharedState.duration * 0.4),
            'Digit5': () => this.core.seekTo(this.sharedState.duration * 0.5),
            'Digit6': () => this.core.seekTo(this.sharedState.duration * 0.6),
            'Digit7': () => this.core.seekTo(this.sharedState.duration * 0.7),
            'Digit8': () => this.core.seekTo(this.sharedState.duration * 0.8),
            'Digit9': () => this.core.seekTo(this.sharedState.duration * 0.9),
            'Digit0': () => this.core.seekTo(0)
        };
        
        // Add custom shortcuts from config
        if (this.config.keyboard?.shortcuts) {
            Object.assign(shortcuts, this.config.keyboard.shortcuts);
        }
        
        // Register shortcuts
        Object.entries(shortcuts).forEach(([key, handler]) => {
            this.keyboardShortcuts.set(key, handler);
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[EventHandler] Registered ${this.keyboardShortcuts.size} keyboard shortcuts`);
        }
    }
    
    /**
     * Setup global event listeners
     */
    setupGlobalEventListeners() {
        // Keyboard events
        this.addGlobalEventListener('keydown', this.handleKeydown.bind(this));
        this.addGlobalEventListener('keyup', this.handleKeyup.bind(this));
        
        // Window events
        this.addGlobalEventListener('resize', this.handleWindowResize.bind(this), window);
        this.addGlobalEventListener('blur', this.handleWindowBlur.bind(this), window);
        this.addGlobalEventListener('visibilitychange', this.handleVisibilityChange.bind(this), document);
        
        // Mouse/pointer events for tracking
        this.addGlobalEventListener('mousedown', this.handleGlobalPointerEvent.bind(this), document);
        this.addGlobalEventListener('touchstart', this.handleGlobalPointerEvent.bind(this), document, { passive: true });
    }
    
    /**
     * Setup video container specific events
     */
    setupVideoContainerEvents() {
        if (!this.$refs.videoContainer) return;
        
        const container = this.$refs.videoContainer;
        
        // Mouse events
        this.addEventListener(container, 'click', this.handleContainerClick.bind(this));
        this.addEventListener(container, 'dblclick', this.handleContainerDoubleClick.bind(this));
        this.addEventListener(container, 'contextmenu', this.handleContextMenu.bind(this));
        this.addEventListener(container, 'mouseenter', this.handleContainerMouseEnter.bind(this));
        this.addEventListener(container, 'mouseleave', this.handleContainerMouseLeave.bind(this));
        this.addEventListener(container, 'mousemove', this.handleContainerMouseMove.bind(this));
        
        // Focus events
        this.addEventListener(container, 'focus', this.handleContainerFocus.bind(this));
        this.addEventListener(container, 'blur', this.handleContainerBlur.bind(this));
        
        // Make container focusable for keyboard events
        if (!container.hasAttribute('tabindex')) {
            container.setAttribute('tabindex', '0');
        }
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Video container events setup complete');
        }
    }
    
    /**
     * Handle keydown events
     */
    handleKeydown(event) {
        if (!this.keyboardEnabled || this.shouldIgnoreKeyboard(event)) {
            return;
        }
        
        const key = event.code || event.key;
        const handler = this.keyboardShortcuts.get(key);
        
        if (handler) {
            event.preventDefault();
            event.stopPropagation();
            
            try {
                handler(event);
                
                this.$dispatch('video-annotation:keyboard-shortcut', {
                    key,
                    event
                });
                
                if (this.sharedState.contextDisplay.debugMode) {
                    console.log(`[EventHandler] Keyboard shortcut executed: ${key}`);
                }
            } catch (error) {
                console.error(`[EventHandler] Error executing keyboard shortcut ${key}:`, error);
            }
        }
    }
    
    /**
     * Handle keyup events
     */
    handleKeyup(event) {
        // Can be used for key release actions if needed
        this.$dispatch('video-annotation:keyup', { event });
    }
    
    /**
     * Handle container click
     */
    handleContainerClick(event) {
        // Focus the container for keyboard events
        if (this.$refs.videoContainer && document.activeElement !== this.$refs.videoContainer) {
            this.$refs.videoContainer.focus();
        }
        
        // Track click for double-click detection
        this.trackClick(event);
        
        // Update pointer type
        this.updatePointerType('mouse');
        
        this.$dispatch('video-annotation:container-click', { event });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Container clicked');
        }
    }
    
    /**
     * Handle container double-click
     */
    handleContainerDoubleClick(event) {
        event.preventDefault();
        
        // Clear single-click timer if running
        if (this.clickTimer) {
            clearTimeout(this.clickTimer);
            this.clickTimer = null;
        }
        
        this.$dispatch('video-annotation:container-doubleclick', { event });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Container double-clicked');
        }
    }
    
    /**
     * Handle right-click context menu
     */
    handleContextMenu(event) {
        if (!this.config.ui?.enableContextMenu) {
            event.preventDefault();
            return;
        }
        
        event.preventDefault();
        
        this.contextMenuActive = true;
        this.contextMenuPosition = {
            x: event.clientX,
            y: event.clientY
        };
        
        this.$dispatch('video-annotation:context-menu', {
            x: event.clientX,
            y: event.clientY,
            event
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Context menu requested');
        }
    }
    
    /**
     * Handle container mouse enter
     */
    handleContainerMouseEnter(event) {
        this.updatePointerType('mouse');
        
        this.$dispatch('video-annotation:mouse-enter', { event });
    }
    
    /**
     * Handle container mouse leave
     */
    handleContainerMouseLeave(event) {
        this.$dispatch('video-annotation:mouse-leave', { event });
    }
    
    /**
     * Handle container mouse move
     */
    handleContainerMouseMove(event) {
        this.updatePointerType('mouse');
        
        // Hide context menu on mouse move
        if (this.contextMenuActive) {
            this.contextMenuActive = false;
        }
        
        this.$dispatch('video-annotation:mouse-move', { event });
    }
    
    /**
     * Handle container focus
     */
    handleContainerFocus(event) {
        this.$dispatch('video-annotation:focus', { event });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Container focused');
        }
    }
    
    /**
     * Handle container blur
     */
    handleContainerBlur(event) {
        this.$dispatch('video-annotation:blur', { event });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Container blurred');
        }
    }
    
    /**
     * Handle global pointer events
     */
    handleGlobalPointerEvent(event) {
        const pointerType = event.type.includes('touch') ? 'touch' : 'mouse';
        this.updatePointerType(pointerType);
    }
    
    /**
     * Handle window resize
     */
    handleWindowResize(event) {
        // Update progress bar width after resize
        if (this.$refs.progressBar) {
            this.sharedState.progressBarWidth = this.$refs.progressBar.getBoundingClientRect().width;
        }
        
        this.$dispatch('video-annotation:window-resize', { event });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Window resized');
        }
    }
    
    /**
     * Handle window blur
     */
    handleWindowBlur(event) {
        // Pause video when window loses focus (optional)
        if (this.config.behavior?.pauseOnBlur && this.sharedState.isPlaying) {
            this.core.togglePlayPause();
        }
        
        this.$dispatch('video-annotation:window-blur', { event });
    }
    
    /**
     * Handle visibility change
     */
    handleVisibilityChange(event) {
        if (document.hidden) {
            // Page is hidden
            if (this.config.behavior?.pauseOnHidden && this.sharedState.isPlaying) {
                this.core.togglePlayPause();
            }
        }
        
        this.$dispatch('video-annotation:visibility-change', { 
            hidden: document.hidden,
            event 
        });
    }
    
    /**
     * Track clicks for double-click detection
     */
    trackClick(event) {
        const now = Date.now();
        
        if (now - this.lastClickTime < this.doubleClickDelay) {
            this.clickCount++;
        } else {
            this.clickCount = 1;
        }
        
        this.lastClickTime = now;
        
        // Clear existing timer
        if (this.clickTimer) {
            clearTimeout(this.clickTimer);
        }
        
        // Set timer for single click
        this.clickTimer = setTimeout(() => {
            if (this.clickCount === 1) {
                this.$dispatch('video-annotation:single-click', { event });
            }
            this.clickCount = 0;
            this.clickTimer = null;
        }, this.doubleClickDelay);
    }
    
    /**
     * Update pointer type tracking
     */
    updatePointerType(type) {
        if (this.lastPointerType !== type) {
            this.lastPointerType = type;
            this.sharedState.touchInterface.activePointer = type;
            
            // Clear timeout for pointer type reset
            if (this.pointerTimeout) {
                clearTimeout(this.pointerTimeout);
            }
            
            // Reset pointer type after inactivity
            this.pointerTimeout = setTimeout(() => {
                this.sharedState.touchInterface.activePointer = null;
                this.lastPointerType = null;
            }, 5000);
        }
    }
    
    /**
     * Seek relative to current time
     */
    seekRelative(seconds) {
        const newTime = Math.max(0, Math.min(this.sharedState.duration, this.sharedState.currentTime + seconds));
        this.core.seekTo(newTime);
    }
    
    /**
     * Seek by frame
     */
    seekFrame(frames) {
        const frameDuration = 1 / this.sharedState.frameRate;
        const timeDelta = frames * frameDuration;
        this.seekRelative(timeDelta);
        
        // Update frame navigation direction for visual feedback
        if (frames > 0) {
            this.sharedState.frameNavigationDirection = 'forward';
        } else if (frames < 0) {
            this.sharedState.frameNavigationDirection = 'backward';
        }
        setTimeout(() => { this.sharedState.frameNavigationDirection = null; }, 500);
    }
    
    /**
     * Step forward by one frame
     */
    stepForward() {
        this.seekFrame(1);
    }
    
    /**
     * Step backward by one frame
     */
    stepBackward() {
        this.seekFrame(-1);
    }
    
    /**
     * Adjust volume relatively
     */
    adjustVolume(delta) {
        const newVolume = Math.max(0, Math.min(1, this.sharedState.volume + delta));
        this.core.setVolume(newVolume);
    }
    
    /**
     * Check if keyboard events should be ignored
     */
    shouldIgnoreKeyboard(event) {
        // Ignore if typing in input fields
        const activeElement = document.activeElement;
        if (activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.contentEditable === 'true'
        )) {
            return true;
        }
        
        // Ignore if modifier keys are pressed (except for specific combinations)
        if (event.ctrlKey || event.altKey || event.metaKey) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add event listener to element
     */
    addEventListener(element, type, handler, options = {}) {
        element.addEventListener(type, handler, options);
        
        // Store for cleanup
        if (!this.eventListeners.has(element)) {
            this.eventListeners.set(element, []);
        }
        
        this.eventListeners.get(element).push({ type, handler, options });
    }
    
    /**
     * Add global event listener
     */
    addGlobalEventListener(type, handler, target = document, options = {}) {
        // Validate that target is a valid DOM element with addEventListener
        if (!target || typeof target.addEventListener !== 'function') {
            console.error('[EventHandler] Invalid target for addEventListener:', target);
            return;
        }
        
        try {
            target.addEventListener(type, handler, options);
            
            // Store for cleanup
            if (!this.globalEventListeners.has(target)) {
                this.globalEventListeners.set(target, []);
            }
            
            this.globalEventListeners.get(target).push({ type, handler, options });
        } catch (error) {
            console.error(`[EventHandler] Failed to add event listener for ${type}:`, error);
        }
    }
    
    /**
     * Remove all event listeners
     */
    removeAllEventListeners() {
        // Remove element-specific listeners
        for (const [element, listeners] of this.eventListeners) {
            listeners.forEach(({ type, handler, options }) => {
                element.removeEventListener(type, handler, options);
            });
        }
        this.eventListeners.clear();
        
        // Remove global listeners
        for (const [target, listeners] of this.globalEventListeners) {
            listeners.forEach(({ type, handler, options }) => {
                target.removeEventListener(type, handler, options);
            });
        }
        this.globalEventListeners.clear();
    }
    
    /**
     * Enable keyboard shortcuts
     */
    enableKeyboard() {
        this.keyboardEnabled = true;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Keyboard shortcuts enabled');
        }
    }
    
    /**
     * Disable keyboard shortcuts
     */
    disableKeyboard() {
        this.keyboardEnabled = false;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Keyboard shortcuts disabled');
        }
    }
    
    /**
     * Add custom keyboard shortcut
     */
    addKeyboardShortcut(key, handler) {
        this.keyboardShortcuts.set(key, handler);
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[EventHandler] Added keyboard shortcut: ${key}`);
        }
    }
    
    /**
     * Remove keyboard shortcut
     */
    removeKeyboardShortcut(key) {
        this.keyboardShortcuts.delete(key);
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[EventHandler] Removed keyboard shortcut: ${key}`);
        }
    }
    
    /**
     * Dispose of event handler
     */
    dispose() {
        this.removeAllEventListeners();
        
        if (this.clickTimer) {
            clearTimeout(this.clickTimer);
            this.clickTimer = null;
        }
        
        if (this.pointerTimeout) {
            clearTimeout(this.pointerTimeout);
            this.pointerTimeout = null;
        }
        
        this.keyboardShortcuts.clear();
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[EventHandler] Disposed');
        }
    }
    
    /**
     * Public API for Alpine.js integration
     */
    getAlpineMethods() {
        return {
            // Keyboard control
            enableKeyboard: this.enableKeyboard.bind(this),
            disableKeyboard: this.disableKeyboard.bind(this),
            addKeyboardShortcut: this.addKeyboardShortcut.bind(this),
            removeKeyboardShortcut: this.removeKeyboardShortcut.bind(this),
            
            // Seeking methods
            seekRelative: this.seekRelative.bind(this),
            seekFrame: this.seekFrame.bind(this),
            stepForward: this.stepForward.bind(this),
            stepBackward: this.stepBackward.bind(this),
            
            // Volume control
            adjustVolume: this.adjustVolume.bind(this),
            
            // Event utilities
            updatePointerType: this.updatePointerType.bind(this),
            
            // Getters for reactive properties
            get keyboardEnabled() { return this.keyboardEnabled; },
            get lastPointerType() { return this.lastPointerType; },
            get contextMenuActive() { return this.contextMenuActive; }
        };
    }
}