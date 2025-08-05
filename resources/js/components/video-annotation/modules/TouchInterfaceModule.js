import Hammer from 'hammerjs';

/**
 * TouchInterfaceModule - Handles touch gestures and mobile interactions
 * Provides unified touch and gesture handling using Hammer.js
 */
export class TouchInterfaceModule {
    constructor(core, sharedState, config) {
        this.core = core;
        this.sharedState = sharedState;
        this.config = config;
        
        // Hammer.js instance
        this.hammer = null;
        
        // Gesture state
        this.gestureInProgress = false;
        this.lastTapTime = 0;
        this.tapCount = 0;
        this.doubleTapDelay = 300;
        
        // Touch tracking
        this.activePointers = new Map();
        this.lastTouchTime = 0;
        
        // Volume gesture state
        this.volumeGestureActive = false;
        this.initialVolume = 1;
        this.volumeGestureStartY = 0;
        
        // Seek gesture state
        this.seekGestureActive = false;
        this.seekGestureStartX = 0;
        this.seekGestureStartTime = 0;
        
        // Brightness gesture state (for future implementation)
        this.brightnessGestureActive = false;
        
        // Gesture thresholds
        this.minSwipeDistance = 50;
        this.minSwipeVelocity = 0.3;
        this.volumeGestureSensitivity = 300; // pixels for full volume range
        this.seekGestureSensitivity = 120; // pixels per second
    }
    
    /**
     * Initialize touch interface
     */
    init($refs, $dispatch) {
        this.$refs = $refs;
        this.$dispatch = $dispatch;
        
        if (this.$refs.videoContainer) {
            this.setupHammer();
            this.setupTouchEventListeners();
        }
        
        return this;
    }
    
    /**
     * Setup Hammer.js gestures
     */
    setupHammer() {
        if (!this.$refs.videoContainer || this.hammer) return;
        
        const videoContainer = this.$refs.videoContainer;
        this.hammer = new Hammer(videoContainer);
        
        // Configure recognizers
        this.hammer.get('pan').set({ 
            direction: Hammer.DIRECTION_ALL,
            threshold: 10,
            pointers: 1
        });
        
        this.hammer.get('swipe').set({ 
            direction: Hammer.DIRECTION_ALL,
            threshold: this.minSwipeDistance,
            velocity: this.minSwipeVelocity
        });
        
        this.hammer.get('tap').set({ 
            taps: 1,
            interval: this.doubleTapDelay,
            threshold: 10
        });
        
        this.hammer.get('doubletap').set({ 
            taps: 2,
            interval: this.doubleTapDelay,
            threshold: 10
        });
        
        // Setup gesture handlers
        this.setupGestureHandlers();
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Hammer.js initialized');
        }
    }
    
    /**
     * Setup Hammer.js gesture handlers
     */
    setupGestureHandlers() {
        if (!this.hammer) return;
        
        // Single tap - play/pause
        this.hammer.on('tap', (event) => {
            if (this.handleTap(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        // Double tap - fullscreen toggle
        this.hammer.on('doubletap', (event) => {
            if (this.handleDoubleTap(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        // Pan gestures for volume/seek/brightness
        this.hammer.on('panstart', (event) => {
            if (this.handlePanStart(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        this.hammer.on('panmove', (event) => {
            if (this.handlePanMove(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        this.hammer.on('panend', (event) => {
            if (this.handlePanEnd(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        // Swipe gestures
        this.hammer.on('swipeleft', (event) => {
            if (this.handleSwipeLeft(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        this.hammer.on('swiperight', (event) => {
            if (this.handleSwipeRight(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        this.hammer.on('swipeup', (event) => {
            if (this.handleSwipeUp(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
        
        this.hammer.on('swipedown', (event) => {
            if (this.handleSwipeDown(event)) {
                event.preventDefault();
                event.srcEvent.preventDefault();
            }
        });
    }
    
    /**
     * Setup additional touch event listeners
     */
    setupTouchEventListeners() {
        if (!this.$refs.videoContainer) return;
        
        const videoContainer = this.$refs.videoContainer;
        
        // Track touch start/end for pointer management
        videoContainer.addEventListener('touchstart', (event) => {
            this.handleTouchStart(event);
        }, { passive: false });
        
        videoContainer.addEventListener('touchend', (event) => {
            this.handleTouchEnd(event);
        }, { passive: false });
    }
    
    /**
     * Handle single tap
     */
    handleTap(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        // Play/pause toggle
        this.core.togglePlayPause();
        
        // Trigger haptic feedback if enabled
        if (this.config.annotations?.enableHapticFeedback && navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        this.$dispatch('video-annotation:tap', { 
            event, 
            currentTime: this.sharedState.currentTime 
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Single tap - play/pause toggle');
        }
        
        return true;
    }
    
    /**
     * Handle double tap
     */
    handleDoubleTap(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        // Toggle fullscreen
        this.core.toggleFullscreen();
        
        // Trigger haptic feedback if enabled
        if (this.config.annotations?.enableHapticFeedback && navigator.vibrate) {
            navigator.vibrate([50, 50, 50]);
        }
        
        this.$dispatch('video-annotation:doubletap', { 
            event, 
            currentTime: this.sharedState.currentTime 
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Double tap - fullscreen toggle');
        }
        
        return true;
    }
    
    /**
     * Handle pan start
     */
    handlePanStart(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        const { center } = event;
        const containerRect = this.$refs.videoContainer.getBoundingClientRect();
        const relativeX = center.x - containerRect.left;
        const relativeY = center.y - containerRect.top;
        const containerWidth = containerRect.width;
        
        // Determine gesture type based on starting position
        if (relativeX < containerWidth * 0.3) {
            // Left side - brightness control (future feature)
            this.brightnessGestureActive = true;
        } else if (relativeX > containerWidth * 0.7) {
            // Right side - volume control
            this.volumeGestureActive = true;
            this.initialVolume = this.sharedState.volume;
            this.volumeGestureStartY = center.y;
        } else {
            // Center - seek control
            this.seekGestureActive = true;
            this.seekGestureStartX = center.x;
            this.seekGestureStartTime = this.sharedState.currentTime;
        }
        
        this.gestureInProgress = true;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Pan start - gesture type determined');
        }
        
        return true;
    }
    
    /**
     * Handle pan move
     */
    handlePanMove(event) {
        if (!this.gestureInProgress) return false;
        
        const { center, deltaX, deltaY } = event;
        
        if (this.volumeGestureActive) {
            this.handleVolumeGesture(center.y);
        } else if (this.seekGestureActive) {
            this.handleSeekGesture(deltaX);
        } else if (this.brightnessGestureActive) {
            // Future: Handle brightness gesture
        }
        
        return true;
    }
    
    /**
     * Handle pan end
     */
    handlePanEnd(event) {
        if (!this.gestureInProgress) return false;
        
        // Reset gesture states
        this.volumeGestureActive = false;
        this.seekGestureActive = false;
        this.brightnessGestureActive = false;
        this.gestureInProgress = false;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Pan end - gestures reset');
        }
        
        return true;
    }
    
    /**
     * Handle volume gesture
     */
    handleVolumeGesture(currentY) {
        const deltaY = this.volumeGestureStartY - currentY; // Inverted for natural feel
        const volumeChange = deltaY / this.volumeGestureSensitivity;
        const newVolume = Math.max(0, Math.min(1, this.initialVolume + volumeChange));
        
        this.core.setVolume(newVolume);
        
        this.$dispatch('video-annotation:volume-gesture', { 
            volume: newVolume,
            delta: volumeChange
        });
    }
    
    /**
     * Handle seek gesture
     */
    handleSeekGesture(deltaX) {
        const timeChange = deltaX / this.seekGestureSensitivity;
        const newTime = Math.max(0, Math.min(this.sharedState.duration, this.seekGestureStartTime + timeChange));
        
        this.core.seekTo(newTime);
        
        this.$dispatch('video-annotation:seek-gesture', { 
            time: newTime,
            delta: timeChange
        });
    }
    
    /**
     * Handle swipe left
     */
    handleSwipeLeft(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        // Seek backward
        const seekAmount = this.config.controls?.seekAmount || 10;
        const newTime = Math.max(0, this.sharedState.currentTime - seekAmount);
        this.core.seekTo(newTime);
        
        this.$dispatch('video-annotation:swipe-left', { 
            seekAmount,
            newTime
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[TouchInterface] Swipe left - seek backward ${seekAmount}s`);
        }
        
        return true;
    }
    
    /**
     * Handle swipe right
     */
    handleSwipeRight(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        // Seek forward
        const seekAmount = this.config.controls?.seekAmount || 10;
        const newTime = Math.min(this.sharedState.duration, this.sharedState.currentTime + seekAmount);
        this.core.seekTo(newTime);
        
        this.$dispatch('video-annotation:swipe-right', { 
            seekAmount,
            newTime
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[TouchInterface] Swipe right - seek forward ${seekAmount}s`);
        }
        
        return true;
    }
    
    /**
     * Handle swipe up
     */
    handleSwipeUp(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        // Increase volume
        const newVolume = Math.min(1, this.sharedState.volume + 0.1);
        this.core.setVolume(newVolume);
        
        this.$dispatch('video-annotation:swipe-up', { volume: newVolume });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Swipe up - volume increase');
        }
        
        return true;
    }
    
    /**
     * Handle swipe down
     */
    handleSwipeDown(event) {
        if (this.sharedState.touchInterface.mode !== 'NORMAL') return false;
        
        // Decrease volume
        const newVolume = Math.max(0, this.sharedState.volume - 0.1);
        this.core.setVolume(newVolume);
        
        this.$dispatch('video-annotation:swipe-down', { volume: newVolume });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Swipe down - volume decrease');
        }
        
        return true;
    }
    
    /**
     * Handle touch start
     */
    handleTouchStart(event) {
        this.sharedState.touchInterface.activePointer = 'touch';
        this.lastTouchTime = Date.now();
        
        // Track active touches
        Array.from(event.touches).forEach(touch => {
            this.activePointers.set(touch.identifier, {
                x: touch.clientX,
                y: touch.clientY,
                timestamp: this.lastTouchTime
            });
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[TouchInterface] Touch start - ${this.activePointers.size} active pointers`);
        }
    }
    
    /**
     * Handle touch end
     */
    handleTouchEnd(event) {
        // Remove ended touches
        Array.from(event.changedTouches).forEach(touch => {
            this.activePointers.delete(touch.identifier);
        });
        
        // Clear active pointer if no touches remain
        if (this.activePointers.size === 0) {
            this.sharedState.touchInterface.activePointer = null;
        }
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[TouchInterface] Touch end - ${this.activePointers.size} active pointers`);
        }
    }
    
    /**
     * Enable touch interface
     */
    enable() {
        this.sharedState.touchInterface.isEnabled = true;
        
        if (this.hammer) {
            this.hammer.set({ enable: true });
        }
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Touch interface enabled');
        }
    }
    
    /**
     * Disable touch interface
     */
    disable() {
        this.sharedState.touchInterface.isEnabled = false;
        
        if (this.hammer) {
            this.hammer.set({ enable: false });
        }
        
        // Reset all gesture states
        this.gestureInProgress = false;
        this.volumeGestureActive = false;
        this.seekGestureActive = false;
        this.brightnessGestureActive = false;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Touch interface disabled');
        }
    }
    
    /**
     * Check if device supports touch
     */
    isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }
    
    /**
     * Get touch interface status
     */
    getStatus() {
        return {
            enabled: this.sharedState.touchInterface.isEnabled,
            mode: this.sharedState.touchInterface.mode,
            activePointer: this.sharedState.touchInterface.activePointer,
            gestureInProgress: this.gestureInProgress,
            activePointers: this.activePointers.size,
            isTouchDevice: this.isTouchDevice()
        };
    }
    
    /**
     * Dispose of touch interface
     */
    dispose() {
        if (this.hammer) {
            this.hammer.destroy();
            this.hammer = null;
        }
        
        this.activePointers.clear();
        this.gestureInProgress = false;
        this.volumeGestureActive = false;
        this.seekGestureActive = false;
        this.brightnessGestureActive = false;
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[TouchInterface] Disposed');
        }
    }
    
    /**
     * Handle pointer start events (unified mouse/touch)
     */
    handlePointerStart(event, target = 'default') {
        const pointerType = event.pointerType || (event.touches ? 'touch' : 'mouse');
        this.sharedState.touchInterface.activePointer = pointerType;
        
        // Update pointer state
        if (!this.sharedState.pointerState) {
            this.sharedState.pointerState = {};
        }
        
        this.sharedState.pointerState.isDown = true;
        this.sharedState.pointerState.startTime = Date.now();
        this.sharedState.pointerState.startPos = {
            x: event.clientX || event.touches?.[0]?.clientX || 0,
            y: event.clientY || event.touches?.[0]?.clientY || 0
        };
        this.sharedState.pointerState.currentPos = { ...this.sharedState.pointerState.startPos };
        this.sharedState.pointerState.hasMoved = false;
        this.sharedState.pointerState.longPressTriggered = false;
        this.sharedState.pointerState.activePointer = pointerType;
        
        // Route to appropriate handler based on target
        switch (target) {
            case 'button':
                // Handle button pointer events
                break;
            case 'progress-bar':
                // Handle progress bar pointer events
                break;
            default:
                // General pointer handling
                break;
        }
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[TouchInterface] Pointer start - ${pointerType} on ${target}`);
        }
        
        return true;
    }
    
    /**
     * Handle pointer end events (unified mouse/touch)
     */
    handlePointerEnd(event, target = 'default') {
        // Reset pointer state
        if (this.sharedState.pointerState) {
            this.sharedState.pointerState.isDown = false;
            this.sharedState.pointerState.hasMoved = false;
            this.sharedState.pointerState.longPressTriggered = false;
        }
        
        // Clear active pointer after a short delay
        setTimeout(() => {
            if (this.sharedState.touchInterface) {
                this.sharedState.touchInterface.activePointer = null;
            }
        }, 100);
        
        // Route to appropriate handler based on target
        switch (target) {
            case 'button':
                // Handle button pointer end
                break;
            case 'progress-bar':
                // Handle progress bar pointer end
                break;
            default:
                // General pointer end handling
                break;
        }
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[TouchInterface] Pointer end on ${target}`);
        }
        
        return true;
    }

    /**
     * Public API for Alpine.js integration
     */
    getAlpineMethods() {
        return {
            // Touch interface control
            enableTouchInterface: this.enable.bind(this),
            disableTouchInterface: this.disable.bind(this),
            
            // Gesture handlers (if needed for manual triggering)
            handleTap: this.handleTap.bind(this),
            handleDoubleTap: this.handleDoubleTap.bind(this),
            
            // Pointer handlers
            handlePointerStart: this.handlePointerStart.bind(this),
            handlePointerEnd: this.handlePointerEnd.bind(this),
            
            // Status methods
            getTouchInterfaceStatus: this.getStatus.bind(this),
            isTouchDevice: this.isTouchDevice.bind(this),
            
            // Getters for reactive properties
            get touchInterfaceEnabled() { return this.sharedState.touchInterface.isEnabled; },
            get touchInterfaceMode() { return this.sharedState.touchInterface.mode; },
            get gestureInProgress() { return this.gestureInProgress; },
            get activePointer() { return this.sharedState.touchInterface.activePointer; }
        };
    }
}