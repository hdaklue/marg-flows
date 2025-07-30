export default function designAnnotationApp(imageUrl, existingComments = [], imageWidth = null, imageHeight = null, metadata = null) {
    // Utility functions
    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0,
                v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function debounce(func, wait) {
        let timeout;
        const debouncedFunction = function executedFunction(...args) {
            const later = () => {
                timeout = null;
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };

        debouncedFunction.cancel = () => {
            clearTimeout(timeout);
            timeout = null;
        };

        return debouncedFunction;
    }

    function throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    return {

        testWrapper: null,
        imageUrl: '',
        imageReady: true,
        initFinished: false,
        comments: [],
        isSelecting: false,
        isDragging: false,
        allCommentsHidden: false,
        selectionStart: {
            x: 0,
            y: 0,
            xPx: null,
            yPx: null
        },
        selectionBox: {
            x: 0,
            y: 0,
            width: 0,
            height: 0
        },
        selectedCommentIds: [],
        showCommentFilter: false,
        visibleComments: [],
        filterMode: false,
        touchStartTime: 0,
        longPressTimer: null,
        touchMoved: false,
        isMobile: false,
        // Responsive zoom system
        zoomLevel: 1,
        minZoom: 1,
        maxZoom: 9,
        mainContainerWidth: 0,
        mainContainerHeight: 0,
        innerWrapperWidth: 0,
        innerWrapperHeight: 0,
        // Pan properties for keyboard navigation
        panX: 0,
        panY: 0,
        arrowKeyStep: 50, // Pixels to move per arrow key press
        callbacks: {
            onDeleteComment: null,
            onCommentClick: null
        },
        // Image metadata from server
        metadata: {},
        imageNaturalWidth: 0,
        imageNaturalHeight: 0,
        // Viewport dimensions
        viewportWidth: 0,
        viewportHeight: 0,

        get hasActiveFilter() {
            if (this.allCommentsHidden) return false;
            return this.selectedCommentIds.length < this.comments.length;
        },

        get canZoomIn() {
            return this.zoomLevel < this.maxZoom;
        },

        get canZoomOut() {
            return this.zoomLevel > this.minZoom;
        },

        get isZoomed() {
            return this.zoomLevel > 1;
        },

        get imageDisplayWidth() {
            // Use cached dimensions when available for better performance
            return this.innerWrapperWidth || (this.imageNaturalWidth * this.zoomLevel);
        },

        get imageDisplayHeight() {
            // Use cached dimensions when available for better performance
            return this.innerWrapperHeight || (this.imageNaturalHeight * this.zoomLevel);
        },

        // Responsive zoom step based on viewport size (Tailwind breakpoints)
        get currentZoomStep() {
            const containerWidth = this.mainContainerWidth || 400;
            const viewportWidth = window.innerWidth;

            // Cached viewport size categories for performance
            if (viewportWidth < 640) {
                return Math.max(100, containerWidth * 0.15);
            } else if (viewportWidth < 768) {
                return Math.max(120, containerWidth * 0.18);
            } else if (viewportWidth < 1024) {
                return Math.max(150, containerWidth * 0.20);
            } else if (viewportWidth < 1280) {
                return Math.max(180, containerWidth * 0.22);
            } else {
                return Math.max(200, containerWidth * 0.25);
            }
        },

        // Responsive max zoom based on viewport
        get responsiveMaxZoom() {
            const viewportWidth = window.innerWidth;

            // Optimized zoom levels for different screen sizes
            if (viewportWidth < 640) return 4;
            if (viewportWidth < 768) return 5;
            if (viewportWidth < 1024) return 6;
            return 8;
        },

        init() {
            // Initialize core properties immediately to prevent layout shifts
            this.isMobile = this.detectMobileDevice();
            this.imageUrl = imageUrl;
            this.comments = existingComments;
            this.selectedCommentIds = this.comments.map(c => c.id);
            this.metadata = metadata || {};


            // Set up optimized throttled selection handler
            this.throttledUpdateSelection = throttle(this.updateSelection.bind(this), 16);

            // ZERO UI SHIFT: Use metadata immediately for instant dimension calculation
            this.initializeWithFallback(imageWidth, imageHeight);
            if (metadata && this.isValidMetadata(metadata)) {
                this.initializeWithMetadata(metadata);
            } else {
                this.initializeWithFallback(imageWidth, imageHeight);
            }

            // Set up reactive watches after initial state is established
            this.setupReactiveWatchers();

            // Initialize visibility state
            this.updateVisibleComments();

            // Initialize gesture handling
            if (this.isMobile) {
                // Mobile: Use Hammer.js with sequential gestures
                this.$nextTick(() => {
                    this.initializeHammer();
                });
            } else {
                // Desktop: Set up keyboard listeners for mode switching
                this.setupDesktopModeHandlers();
            }
        },

        /**
         * Check if metadata is valid and complete for immediate use
         */
        isValidMetadata(metadata) {
            return metadata &&
                metadata.exists &&
                !metadata.hasError &&
                metadata.dimensions &&
                metadata.dimensions.width > 0 &&
                metadata.dimensions.height > 0;
        },

        /**
         * Initialize component using comprehensive metadata (ZERO UI SHIFT)
         */
        initializeWithMetadata(metadata) {
            // Set natural image dimensions
            this.imageNaturalWidth = metadata.dimensions.width;
            this.imageNaturalHeight = metadata.dimensions.height;

            // Get viewport dimensions
            this.updateViewportDimensions();

            // Calculate container dimensions based on viewport and image
            const containerDimensions = this.calculateContainerDimensions(
                this.imageNaturalWidth,
                this.imageNaturalHeight,
                this.viewportWidth,
                this.viewportHeight
            );

            // Set container dimensions immediately - no async waiting
            this.mainContainerWidth = containerDimensions.width;
            this.mainContainerHeight = containerDimensions.height;
            this.innerWrapperWidth = containerDimensions.width;
            this.innerWrapperHeight = containerDimensions.height;

            // Mark as ready immediately - ZERO delay
            this.imageReady = true;
            this.initFinished = true;

            // Initialize zoom state with known dimensions
            this.initializeZoomState();
        },

        /**
         * Fallback initialization for backward compatibility
         */
        initializeWithFallback(width, height) {
            // Use provided dimensions if available
            if (width && height) {
                this.imageNaturalWidth = parseInt(width) || 0;
                this.imageNaturalHeight = parseInt(height) || 0;

                // Try to calculate reasonable container dimensions
                if (this.imageNaturalWidth > 0 && this.imageNaturalHeight > 0) {
                    // Get viewport dimensions
                    this.updateViewportDimensions();

                    const containerDimensions = this.calculateContainerDimensions(
                        this.imageNaturalWidth,
                        this.imageNaturalHeight,
                        this.viewportWidth,
                        this.viewportHeight
                    );

                    this.mainContainerWidth = containerDimensions.width;
                    this.mainContainerHeight = containerDimensions.height;
                    this.innerWrapperWidth = containerDimensions.width;
                    this.innerWrapperHeight = containerDimensions.height;

                    this.imageReady = true;
                    this.initFinished = true;
                    this.initializeZoomState();
                    return;
                }
            }

            // Final fallback - requires async dimension calculation
            this.imageReady = false;
            this.initFinished = false;

            this.$nextTick(() => {
                this.updateImageDimensions();
                this.resetZoomState();
                // Minimal delay for DOM readiness
                setTimeout(() => this.initFinished = true, 100);
            });
        },

        /**
         * Set up reactive watchers after initial state
         */
        setupReactiveWatchers() {
            this.$watch('initFinished', (value) => this.imageReady = value);
            this.$watch('selectedCommentIds', () => {
                if (this.filterMode) {
                    this.updateVisibleComments();
                }
            });
        },

        /**
         * Initialize zoom state with known dimensions
         */
        initializeZoomState() {
            this.zoomLevel = 1;
            this.panX = 0;
            this.panY = 0;
            // No need to call resetZoomState() as we're setting optimal values
        },

        /**
         * Update viewport dimensions from the container
         */
        updateViewportDimensions() {
            // Use 95vw and 90vh as the responsive container sizing from the blade template
            this.viewportWidth = Math.round(window.innerWidth * 0.95);
            this.viewportHeight = Math.round(window.innerHeight * 0.90);
        },

        /**
         * Calculate container dimensions with responsive logic and percentage precision
         */
        calculateContainerDimensions(imageWidth, imageHeight, containerWidth, containerHeight) {
            if (imageWidth <= 0 || imageHeight <= 0) {
                return {
                    width: containerWidth,
                    height: containerHeight,
                    widthPercent: this.toPercentage(containerWidth, containerWidth),
                    heightPercent: this.toPercentage(containerHeight, containerHeight)
                };
            }

            const imageAspectRatio = imageWidth / imageHeight;

            // If image dimensions are smaller than component dimensions, use image dimensions
            if (imageWidth <= containerWidth && imageHeight <= containerHeight) {
                return {
                    width: imageWidth,
                    height: imageHeight,
                    widthPercent: this.toPercentage(imageWidth, containerWidth),
                    heightPercent: this.toPercentage(imageHeight, containerHeight)
                };
            }

            // Use container width, calculate height respecting aspect ratio
            const calculatedHeight = Math.round(containerWidth / imageAspectRatio);

            // If calculated height fits within container height, use it
            if (calculatedHeight <= containerHeight) {
                return {
                    width: containerWidth,
                    height: calculatedHeight,
                    widthPercent: this.toPercentage(containerWidth, containerWidth),
                    heightPercent: this.toPercentage(calculatedHeight, containerHeight)
                };
            }

            // Otherwise, use container height and calculate width
            const calculatedWidth = Math.round(containerHeight * imageAspectRatio);
            return {
                width: calculatedWidth,
                height: containerHeight,
                widthPercent: this.toPercentage(calculatedWidth, containerWidth),
                heightPercent: this.toPercentage(containerHeight, containerHeight)
            };
        },

        /**
         * Convert value to percentage with 9 decimal precision
         */
        toPercentage(value, total) {
            if (total === 0) return 0.000000000;
            return parseFloat(((value / total) * 100).toFixed(9));
        },


        destroy() {
            // Destroy Hammer.js instance
            if (this.hammerInstance) {
                this.hammerInstance.destroy();
                this.hammerInstance = null;
            }

            // Remove desktop keyboard listeners
            if (!this.isMobile) {
                this.removeDesktopModeHandlers();
            }

            // Clear selection timeout
            if (this.selectionTimeout) {
                clearTimeout(this.selectionTimeout);
                this.selectionTimeout = null;
            }

            // Clear all timers and handlers
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }

            // Clear debounce timeout if exists
            if (this.updateSelection && this.updateSelection.cancel) {
                this.updateSelection.cancel();
            }

            // Reset zoom state
            this.resetZoomState();

            // Clear all references to prevent memory leaks
            this.comments = [];
            this.visibleComments = [];
            this.selectedCommentIds = [];
            this.callbacks = {};
        },

        handleEscape() {
            if (this.showingComment) {
                this.closeComment();
                return;
            }
            this.handleClose();
        },

        updateVisibleComments() {
            this.visibleComments = this.filterMode ?
                this.comments.filter(c => this.selectedCommentIds.includes(c.id)) :
                this.comments.filter(c => c.text?.trim());
        },

        toggleCommentFilter() {
            this.showCommentFilter = !this.showCommentFilter;
            this.filterMode = !this.filterMode;
        },

        toggleAllComments() {
            this.allCommentsHidden ? this.showAllComments() : this.hideAllComments();
        },

        hideAllComments() {
            this.selectedCommentIds = [];
            this.visibleComments = [];
            this.allCommentsHidden = true;
        },

        showAllComments() {
            this.visibleComments = this.comments;
            this.selectedCommentIds = this.comments.map(c => c.id);
            this.allCommentsHidden = false;
        },

        reset() {
            this.comments = [];
            this.selectedCommentIds = [];
            this.activeCommentId = null;
            this.isSelecting = false;
            this.isDragging = false;
            this.allCommentsHidden = false;
            this.visibleComments = [];
            this.filterMode = false;
            this.showCommentFilter = false;
            this.resetZoomState();

            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
        },

        resetZoomState() {
            this.zoomLevel = 1;
            this.mainContainerWidth = 0;
            this.mainContainerHeight = 0;
            this.innerWrapperWidth = 0;
            this.innerWrapperHeight = 0;
            this.panX = 0;
            this.panY = 0;
        },

        resetPan() {
            this.panX = 0;
            this.panY = 0;
        },

        // Hammer.js instance for touch gestures
        hammerInstance: null,
        initialPinchScale: 1,
        lastPinchScale: 1,
        pinchCenter: { x: 0, y: 0 },

        // Enhanced gesture state management
        gestureSequence: [],
        currentGesture: null,
        lastGestureEnd: 0,
        gestureTransitionTime: 150, // ms between gestures
        showSelectionMode: false,
        selectionTimeout: null,

        // Pan state for navigation
        initialImagePan: { x: 0, y: 0 },
        panStartPoint: { x: 0, y: 0 },

        // Desktop interaction mode
        isSpacePressed: false,
        isShiftPressed: false,
        currentMode: 'select', // 'select' or 'pan'
        showModeIndicator: false,


        detectMobileDevice() {
            return (
                'ontouchstart' in window ||
                navigator.maxTouchPoints > 0 ||
                /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                window.innerWidth < 768
            );
        },

        /**
         * Initialize Hammer.js for mobile touch gestures with sequential pattern support
         */
        initializeHammer() {
            if (!this.isMobile || !window.Hammer || !this.$refs.innerWrapper) {
                return;
            }

            // Create Hammer manager instance with optimized recognizers
            this.hammerInstance = new Hammer.Manager(this.$refs.innerWrapper, {
                recognizers: [
                    // Pinch (highest priority - can interrupt others)
                    [Hammer.Pinch, { enable: true }],

                    // Pan (works with pinch simultaneously)
                    [Hammer.Pan, {
                        direction: Hammer.DIRECTION_ALL,
                        threshold: 5,
                        pointers: 1 // Single finger only
                    }],

                    // Tap (for starting selection)
                    [Hammer.Tap, {
                        taps: 1,
                        threshold: 10,
                        time: 150
                    }],

                    // Press (force selection mode)
                    [Hammer.Press, { time: 250 }]
                ]
            });

            // Set up recognizer relationships
            this.hammerInstance.get('pinch').recognizeWith('pan');
            this.hammerInstance.get('tap').requireFailure('pan');

            // Bind enhanced gesture events
            this.hammerInstance.on('panstart', this.handleHammerPanStart.bind(this));
            this.hammerInstance.on('panmove', this.handleHammerPanMove.bind(this));
            this.hammerInstance.on('panend', this.handleHammerPanEnd.bind(this));
            this.hammerInstance.on('tap', this.handleHammerTap.bind(this));
            this.hammerInstance.on('press', this.handleHammerPress.bind(this));
            this.hammerInstance.on('pinchstart', this.handleHammerPinchStart.bind(this));
            this.hammerInstance.on('pinchmove', this.handleHammerPinchMove.bind(this));
            this.hammerInstance.on('pinchend', this.handleHammerPinchEnd.bind(this));
        },

        /**
         * Handle Hammer.js pan start with context awareness
         */
        handleHammerPanStart(event) {
            const timeSinceLastGesture = Date.now() - this.lastGestureEnd;

            // Don't start pan immediately after pinch
            if (timeSinceLastGesture < this.gestureTransitionTime) {
                return;
            }

            // Determine pan context
            if (this.currentGesture === 'selecting') {
                // Continue selection
                return;
            }

            this.currentGesture = 'panning';
            this.gestureSequence.push('pan');

            // Store initial positions for image navigation
            this.initialImagePan = { x: this.panX, y: this.panY };
            this.panStartPoint = { x: event.center.x, y: event.center.y };

            this.disableSmoothTransitions();
        },

        /**
         * Handle Hammer.js pan move with context-aware behavior
         */
        handleHammerPanMove(event) {
            if (this.currentGesture === 'selecting') {
                this.updateSelectionArea(event);
            } else if (this.currentGesture === 'panning' && this.isZoomed) {
                this.handleImagePanning(event);
            }
        },

        /**
         * Handle Hammer.js pan end with gesture completion
         */
        handleHammerPanEnd(event) {
            if (this.currentGesture === 'selecting') {
                this.completeSelection();
            } else if (this.currentGesture === 'panning') {
                this.currentGesture = null;
                this.lastGestureEnd = Date.now();
                this.enableSmoothTransitions();
            }
        },

        /**
         * Handle Hammer.js tap events with selection mode detection
         */
        handleHammerTap(event) {
            const timeSinceLastGesture = Date.now() - this.lastGestureEnd;

            // Ignore taps immediately after other gestures
            if (timeSinceLastGesture < this.gestureTransitionTime) {
                return;
            }

            // Check if this should start a selection
            if (this.shouldStartSelection(event)) {
                this.startSelectionMode(event);
            } else {
                // Regular tap - create point comment
                const rect = this.$refs.innerWrapper.getBoundingClientRect();
                this.handleClick(event.center.x, event.center.y, rect);
            }
        },

        /**
         * Handle Hammer.js press events (force selection mode)
         */
        handleHammerPress(event) {
            // Force selection mode regardless of context
            this.startSelectionMode(event, true);
        },

        /**
         * Handle Hammer.js pinch start for zoom (highest priority)
         */
        handleHammerPinchStart(event) {
            this.currentGesture = 'pinching';
            this.gestureSequence.push('pinch');

            // Cancel any selection in progress
            this.cancelSelection();

            this.initialPinchScale = event.scale;
            this.lastPinchScale = this.zoomLevel;
            this.pinchCenter = { x: event.center.x, y: event.center.y };

            this.disableSmoothTransitions();
        },

        /**
         * Handle Hammer.js pinch move for zoom
         */
        handleHammerPinchMove(event) {
            if (this.currentGesture !== 'pinching') return;

            const scaleChange = event.scale / this.initialPinchScale;
            const newZoom = Math.max(this.minZoom,
                Math.min(this.responsiveMaxZoom, this.lastPinchScale * scaleChange));

            this.zoomLevel = newZoom;
            this.updateImageDimensionsAndCenter();
        },

        /**
         * Handle Hammer.js pinch end
         */
        handleHammerPinchEnd(event) {
            this.currentGesture = null;
            this.lastGestureEnd = Date.now();
            this.enableSmoothTransitions();

            // Reset pinch state
            this.initialPinchScale = 1;
            this.lastPinchScale = this.zoomLevel;
        },

        /**
         * Determine if tap should start selection mode
         */
        shouldStartSelection(event) {
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const coords = this.getImageCoordinates(event.center.x, event.center.y);

            // Check if tap is on existing comment
            const clickedComment = this.findCommentAtPoint(coords.x, coords.y, 2.5);
            if (clickedComment) return false;

            // Check recent gesture context
            const recentGestures = this.gestureSequence.slice(-3);
            const hasRecentZoomOrPan = recentGestures.includes('pinch') ||
                recentGestures.includes('pan');

            return this.isZoomed && hasRecentZoomOrPan;
        },

        /**
         * Start selection mode with visual feedback
         */
        startSelectionMode(event, forced = false) {
            this.currentGesture = 'selecting';
            this.gestureSequence.push('select');

            // Visual feedback
            this.showSelectionMode = true;

            // Haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate(forced ? [10, 50, 10] : [10, 30, 10]);
            }

            // Store selection start with 9 decimal precision
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const coords = this.getImageCoordinates(event.center.x, event.center.y);

            // Convert to 9 decimal precision percentages
            const startX = parseFloat(coords.x.toFixed(9));
            const startY = parseFloat(coords.y.toFixed(9));

            this.selectionStart = {
                x: startX,
                y: startY,
                xPx: event.center.x - rect.left,
                yPx: event.center.y - rect.top
            };

            // Initialize selection box at press position to prevent flicker
            this.selectionBox = {
                x: startX,
                y: startY,
                width: parseFloat((0).toFixed(9)),
                height: parseFloat((0).toFixed(9))
            };

            this.isSelecting = true;
            this.isDragging = true;

            // Set timeout to prevent getting stuck in selection mode
            this.selectionTimeout = setTimeout(() => {
                if (this.currentGesture === 'selecting') {
                    this.completeSelection();
                }
            }, 5000); // 5 second selection timeout
        },

        /**
         * Handle image panning for zoomed content
         */
        handleImagePanning(event) {
            if (!this.isZoomed) return;

            // Calculate pan delta
            const deltaX = event.center.x - this.panStartPoint.x;
            const deltaY = event.center.y - this.panStartPoint.y;

            // Apply new pan position with constraints
            this.updatePanPosition(
                this.initialImagePan.x + deltaX,
                this.initialImagePan.y + deltaY
            );
        },

        /**
         * Update selection area during drag with 9 decimal precision
         */
        updateSelectionArea(event) {
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const coords = this.getImageCoordinates(event.center.x, event.center.y);

            // Ensure 9 decimal precision for all calculations
            const currentX = parseFloat(coords.x.toFixed(9));
            const currentY = parseFloat(coords.y.toFixed(9));
            const startX = this.selectionStart.x;
            const startY = this.selectionStart.y;

            this.selectionBox = {
                x: parseFloat(Math.min(startX, currentX).toFixed(9)),
                y: parseFloat(Math.min(startY, currentY).toFixed(9)),
                width: parseFloat(Math.abs(currentX - startX).toFixed(9)),
                height: parseFloat(Math.abs(currentY - startY).toFixed(9))
            };
        },

        /**
         * Update pan position with constraints
         */
        updatePanPosition(newX, newY) {
            const maxPanX = Math.max(0, (this.innerWrapperWidth - this.mainContainerWidth) / 2);
            const maxPanY = Math.max(0, (this.innerWrapperHeight - this.mainContainerHeight) / 2);

            this.panX = Math.max(-maxPanX, Math.min(maxPanX, newX));
            this.panY = Math.max(-maxPanY, Math.min(maxPanY, newY));
        },

        /**
         * Handle desktop mouse panning
         */
        handleDesktopPanning(event) {
            if (!this.isZoomed) return;

            const deltaX = event.clientX - this.panStartPoint.x;
            const deltaY = event.clientY - this.panStartPoint.y;

            this.updatePanPosition(
                this.initialImagePan.x + deltaX,
                this.initialImagePan.y + deltaY
            );
        },

        /**
         * Cancel selection mode
         */
        cancelSelection() {
            this.isSelecting = false;
            this.isDragging = false;
            this.showSelectionMode = false;
            this.currentGesture = null;
            this.resetSelectionState();

            if (this.selectionTimeout) {
                clearTimeout(this.selectionTimeout);
                this.selectionTimeout = null;
            }
        },

        /**
         * Complete selection and create comment
         */
        completeSelection() {
            if (this.selectionBox.width > 1 && this.selectionBox.height > 1) {
                this.createAreaComment();
            }
            this.cancelSelection();
        },

        /**
         * Setup desktop mode switching with keyboard
         */
        setupDesktopModeHandlers() {
            // Bind keyboard event handlers
            this.handleKeyDown = this.handleDesktopKeyDown.bind(this);
            this.handleKeyUp = this.handleDesktopKeyUp.bind(this);

            window.addEventListener('keydown', this.handleKeyDown);
            window.addEventListener('keyup', this.handleKeyUp);
        },

        /**
         * Remove desktop keyboard handlers
         */
        removeDesktopModeHandlers() {
            if (this.handleKeyDown) {
                window.removeEventListener('keydown', this.handleKeyDown);
                window.removeEventListener('keyup', this.handleKeyUp);
            }
        },



        /**
         * Handle desktop keyboard for mode switching
         */
        handleDesktopKeyDown(event) {
            if (event.code === 'Space' && !this.isSpacePressed) {
                event.preventDefault();
                this.isSpacePressed = true;
                this.currentMode = 'pan';
                this.showModeIndicator = true;

                // Hide indicator after delay if not used
                setTimeout(() => {
                    if (!this.isDragging) {
                        this.showModeIndicator = false;
                    }
                }, 2000);
            }

            if (event.code === 'ShiftLeft' || event.code === 'ShiftRight') {
                this.isShiftPressed = true;
                this.currentMode = 'select';
                this.showModeIndicator = true;

                setTimeout(() => {
                    if (!this.isDragging) {
                        this.showModeIndicator = false;
                    }
                }, 2000);
            }
        },

        /**
         * Handle desktop key release
         */
        handleDesktopKeyUp(event) {
            if (event.code === 'Space') {
                this.isSpacePressed = false;
                if (!this.isDragging) {
                    this.currentMode = 'select';
                    this.showModeIndicator = false;
                }
            }

            if (event.code === 'ShiftLeft' || event.code === 'ShiftRight') {
                this.isShiftPressed = false;
                if (!this.isDragging) {
                    this.currentMode = 'select';
                    this.showModeIndicator = false;
                }
            }
        },

        /**
         * Get current interaction mode for desktop
         */
        getDesktopMode() {
            if (!this.isZoomed) return 'select'; // Can't pan when not zoomed

            if (this.isSpacePressed) return 'pan';
            if (this.isShiftPressed) return 'select';

            return this.currentMode;
        },

        handleClose() {
            if (this.showCommentFilter) {
                this.showCommentFilter = false;
                this.filterMode = false;
                return;
            }
        },

        handleBackdropClick(event) {
            if (event.target === event.currentTarget) this.handleClose();
        },

        // Desktop Mouse Events with Mode Awareness
        startSelection(event) {
            if (event.button !== 0) return;

            // Disable transitions during interaction for responsiveness
            this.disableSmoothTransitions();

            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            // Determine mode for desktop
            if (!this.isMobile) {
                const mode = this.getDesktopMode();

                if (mode === 'pan' && this.isZoomed) {
                    // Start panning mode
                    this.currentGesture = 'panning';
                    this.initialImagePan = { x: this.panX, y: this.panY };
                    this.panStartPoint = { x: event.clientX, y: event.clientY };
                    this.isDragging = true;
                    return;
                }
            }

            // Start selection mode (default) with 9 decimal precision
            const coords = this.getImageCoordinates(event.clientX, event.clientY);
            this.selectionStart = {
                x: parseFloat(coords.x.toFixed(9)),
                y: parseFloat(coords.y.toFixed(9)),
                xPx: x,
                yPx: y
            };
            this.isDragging = true;
            this.isSelecting = false;
        },

        updateSelection(event) {
            if (!this.isDragging) return;

            // Handle desktop panning mode
            if (!this.isMobile && this.currentGesture === 'panning') {
                this.handleDesktopPanning(event);
                return;
            }

            // Handle selection mode
            if (!this.selectionStart.xPx) return;

            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = Math.max(0, Math.min(event.clientX - rect.left, rect.width));
            const y = Math.max(0, Math.min(event.clientY - rect.top, rect.height));
            const distance = Math.sqrt(
                Math.pow(x - this.selectionStart.xPx, 2) +
                Math.pow(y - this.selectionStart.yPx, 2)
            );

            if (distance > (this.isMobile ? 8 : 5)) {
                this.isSelecting = true;
            }

            if (!this.isSelecting) return;

            const coords = this.getImageCoordinates(event.clientX, event.clientY);

            // Ensure 9 decimal precision for desktop mouse events
            const currentX = parseFloat(coords.x.toFixed(9));
            const currentY = parseFloat(coords.y.toFixed(9));
            const startX = this.selectionStart.x;
            const startY = this.selectionStart.y;

            this.selectionBox = {
                x: parseFloat(Math.min(startX, currentX).toFixed(9)),
                y: parseFloat(Math.min(startY, currentY).toFixed(9)),
                width: parseFloat(Math.abs(currentX - startX).toFixed(9)),
                height: parseFloat(Math.abs(currentY - startY).toFixed(9))
            };
        },

        endSelection(event) {
            if (!this.isDragging) return;

            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            // Handle desktop panning end
            if (!this.isMobile && this.currentGesture === 'panning') {
                this.currentGesture = null;
                this.isDragging = false;
                this.enableSmoothTransitions();
                this.showModeIndicator = false;
                return;
            }

            // Handle selection end
            this.isDragging = false;

            // Re-enable transitions after interaction
            this.enableSmoothTransitions();

            if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
                this.createAreaComment();
            } else if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
                this.handleClick(event.clientX, event.clientY, rect);
            }

            this.resetSelectionState();
            this.showModeIndicator = false;
        },

        // Touch Events (fallback for non-mobile or when Hammer.js is not available)
        handleTouchStart(event) {
            // Use Hammer.js for mobile devices if available
            if (this.isMobile && this.hammerInstance) {
                return;
            }

            // Fallback touch handling for desktop or when Hammer.js is not available
            this.disableSmoothTransitions();

            const touch = event.touches[0];
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;

            this.touchStartTime = Date.now();
            this.touchMoved = false;

            const coords = this.getImageCoordinates(touch.clientX, touch.clientY);
            this.selectionStart = {
                x: parseFloat(coords.x.toFixed(9)),
                y: parseFloat(coords.y.toFixed(9)),
                xPx: x,
                yPx: y
            };
            this.isDragging = true;
            this.isSelecting = false;

            // Optimized long press for area selection
            this.longPressTimer = setTimeout(() => {
                if (!this.touchMoved && this.isDragging) {
                    if (navigator.vibrate) {
                        navigator.vibrate([10, 50, 10]);
                    }
                    this.isSelecting = true;
                }
            }, 300);
        },

        handleTouchMove(event) {
            // Use Hammer.js for mobile devices if available
            if (this.isMobile && this.hammerInstance) {
                return;
            }

            if (!this.isDragging) return;

            const touch = event.touches[0];
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = Math.max(0, Math.min(touch.clientX - rect.left, rect.width));
            const y = Math.max(0, Math.min(touch.clientY - rect.top, rect.height));

            const distance = Math.sqrt(
                Math.pow(x - this.selectionStart.xPx, 2) +
                Math.pow(y - this.selectionStart.yPx, 2)
            );

            if (distance > 12) {
                this.touchMoved = true;
                if (this.longPressTimer) {
                    clearTimeout(this.longPressTimer);
                    this.longPressTimer = null;
                }
                this.isSelecting = true;
            }

            if (!this.isSelecting) return;

            const coords = this.getImageCoordinates(touch.clientX, touch.clientY);

            // Ensure 9 decimal precision for touch events
            const currentX = parseFloat(coords.x.toFixed(9));
            const currentY = parseFloat(coords.y.toFixed(9));
            const startX = this.selectionStart.x;
            const startY = this.selectionStart.y;

            this.selectionBox = {
                x: parseFloat(Math.min(startX, currentX).toFixed(9)),
                y: parseFloat(Math.min(startY, currentY).toFixed(9)),
                width: parseFloat(Math.abs(currentX - startX).toFixed(9)),
                height: parseFloat(Math.abs(currentY - startY).toFixed(9))
            };
        },

        handleTouchEnd(event) {
            // Use Hammer.js for mobile devices if available
            if (this.isMobile && this.hammerInstance) {
                return;
            }

            if (!this.isDragging) return;

            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }

            this.enableSmoothTransitions();

            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const touchDuration = Date.now() - this.touchStartTime;

            if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
                this.createAreaComment();
            } else if (!this.touchMoved && touchDuration < 300) {
                const touch = event.changedTouches[0];
                this.handleClick(touch.clientX, touch.clientY, rect);
            }

            this.isDragging = false;
            this.resetSelectionState();
        },

        // Common handlers
        handleClick(clientX, clientY, rect) {
            // Skip comment detection when zoomed (comments are hidden)
            if (!this.isZoomed) {
                const coords = this.getImageCoordinates(clientX, clientY);
                const searchRadius = this.isMobile ? 2.5 : 1.5;
                const clickedComment = this.findCommentAtPoint(coords.x, coords.y, searchRadius);

                if (clickedComment) {
                    return this.selectComment(clickedComment);
                }
            }

            // Calculate coordinates for comment creation with 9 decimal precision
            const coords = this.getImageCoordinates(clientX, clientY);
            const commentSize = parseFloat((this.isMobile ? 3 : 2).toFixed(9));
            const offset = parseFloat((commentSize / 2).toFixed(9));

            // Ensure point comment stays within bounds with 9 decimal precision
            const maxX = parseFloat((100 - commentSize).toFixed(9));
            const maxY = parseFloat((100 - commentSize).toFixed(9));

            const commentData = {
                x: parseFloat(Math.max(0, Math.min(maxX, coords.x - offset)).toFixed(9)),
                y: parseFloat(Math.max(0, Math.min(maxY, coords.y - offset)).toFixed(9)),
                width: commentSize,
                height: commentSize,
                type: 'point',
                imageUrl: this.imageUrl,
                isMobile: this.isMobile,
                zoomLevel: this.zoomLevel
            };

            this.dispatchCommentEvent(commentData);
        },

        createAreaComment() {
            // Ensure all values are properly bounded and maintain 9 decimal precision
            const boundedX = parseFloat(Math.max(0, Math.min(100, this.selectionBox.x)).toFixed(9));
            const boundedY = parseFloat(Math.max(0, Math.min(100, this.selectionBox.y)).toFixed(9));

            // Calculate width and height with edge case protection
            const maxWidth = parseFloat((100 - boundedX).toFixed(9));
            const maxHeight = parseFloat((100 - boundedY).toFixed(9));

            const boundedWidth = parseFloat(Math.max(0, Math.min(this.selectionBox.width, maxWidth)).toFixed(9));
            const boundedHeight = parseFloat(Math.max(0, Math.min(this.selectionBox.height, maxHeight)).toFixed(9));

            const commentData = {
                x: boundedX,
                y: boundedY,
                width: boundedWidth,
                height: boundedHeight,
                type: 'area',
                imageUrl: this.imageUrl,
                isMobile: this.isMobile,
                zoomLevel: this.zoomLevel
            };

            this.dispatchCommentEvent(commentData);
        },

        dispatchCommentEvent(commentData) {
            window.dispatchEvent(new CustomEvent('open-comment-modal', {
                detail: commentData,
                bubbles: true
            }));
        },

        resetSelectionState() {
            this.selectionStart = {
                x: parseFloat((0).toFixed(9)),
                y: parseFloat((0).toFixed(9)),
                xPx: null,
                yPx: null
            };
            this.selectionBox = {
                x: parseFloat((0).toFixed(9)),
                y: parseFloat((0).toFixed(9)),
                width: parseFloat((0).toFixed(9)),
                height: parseFloat((0).toFixed(9))
            };
            this.isSelecting = false;
        },

        findCommentAtPoint(x, y, searchRadius = 0) {
            return this.visibleComments.find(c => {
                const expandedLeft = c.x - searchRadius;
                const expandedRight = c.x + c.width + searchRadius;
                const expandedTop = c.y - searchRadius;
                const expandedBottom = c.y + c.height + searchRadius;

                return x >= expandedLeft && x <= expandedRight &&
                    y >= expandedTop && y <= expandedBottom;
            });
        },

        selectComment(comment) {
            // For now, just log comment selection - this could be used for a separate comment viewer modal
            console.log('Comment selected:', comment);

            if (this.callbacks.onCommentClick) {
                this.callbacks.onCommentClick(comment);
            }
        },

        // Public API methods
        setCallbacks(callbacks = {}) {
            const validCallbacks = ['onDeleteComment', 'onCommentClick'];

            Object.keys(callbacks).forEach(key => {
                if (validCallbacks.includes(key) && typeof callbacks[key] === 'function') {
                    this.callbacks[key] = callbacks[key];
                }
            });
        },

        getComments() {
            return this.comments;
        },

        addComment(comment) {
            // Ensure comment has a valid ID before adding
            const commentWithId = {
                id: comment.id || uuidv4(),
                ...comment
            };

            // Check for duplicates before adding
            if (!this.comments.find(c => c.id === commentWithId.id)) {
                this.comments.push(commentWithId);
                if (!this.selectedCommentIds.includes(commentWithId.id)) {
                    this.selectedCommentIds.push(commentWithId.id);
                }
                this.updateVisibleComments();
            }
        },

        async removeComment(commentId) {
            try {
                // Remove from local state first
                const originalComments = [...this.comments];
                const originalSelectedIds = [...this.selectedCommentIds];

                this.comments = this.comments.filter(c => c.id !== commentId);
                this.selectedCommentIds = this.selectedCommentIds.filter(id => id !== commentId);
                this.updateVisibleComments();

                // Call the async callback if it exists
                if (this.callbacks.onDeleteComment) {
                    await this.callbacks.onDeleteComment(commentId);
                }

            } catch (error) {
                console.error('Error deleting comment:', error);

                // Rollback on error
                this.comments = originalComments;
                this.selectedCommentIds = originalSelectedIds;
                this.updateVisibleComments();

                alert('Failed to delete comment. Please try again.');
            }
        },

        // Enhanced zoom methods using wrapper dimensions
        zoomIn(factor = 1.2) {
            this.enableSmoothTransitions();
            const newZoom = Math.min(this.zoomLevel * factor, this.maxZoom);
            this.setZoom(newZoom);
        },

        zoomOut(factor = 1.2) {
            this.enableSmoothTransitions();
            const newZoom = Math.max(this.zoomLevel / factor, this.minZoom);
            this.setZoom(newZoom);
        },

        resetZoom() {
            this.setZoom(1);
            this.resetPan();
        },

        setZoom(newZoom) {
            this.zoomLevel = newZoom;
            this.updateImageDimensionsAndCenter();
        },

        centerImage() {
            // Center the image within the viewport
            if (this.innerWrapperWidth <= this.mainContainerWidth) {
                this.panX = 0;
            } else {
                // Keep current pan position but constrain it
                const maxPanX = Math.max(0, (this.innerWrapperWidth - this.mainContainerWidth) / 2);
                this.panX = Math.max(-maxPanX, Math.min(maxPanX, this.panX));
            }

            if (this.innerWrapperHeight <= this.mainContainerHeight) {
                this.panY = 0;
            } else {
                // Keep current pan position but constrain it
                const maxPanY = Math.max(0, (this.innerWrapperHeight - this.mainContainerHeight) / 2);
                this.panY = Math.max(-maxPanY, Math.min(maxPanY, this.panY));
            }
        },

        enableSmoothTransitions() {
            // Ultra smooth transitions with natural easing
            if (this.$refs.innerWrapper) {
                this.$refs.innerWrapper.style.transition = 'transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), width 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), height 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            }
        },

        disableSmoothTransitions() {
            // Disable transitions for responsive interactions
            if (this.$refs.innerWrapper) {
                this.$refs.innerWrapper.style.transition = 'none';
            }
        },

        updateImageDimensionsAndCenter() {
            // Early return if no container dimensions set
            if (this.mainContainerWidth === 0 || this.mainContainerHeight === 0) {
                this.updateImageDimensions();
                return;
            }

            // Calculate new dimensions based on zoom level
            this.updateInnerWrapperDimensions();

            // Center and constrain pan position
            this.constrainPanPosition();
        },

        /**
         * Constrain pan position within bounds after dimension changes
         */
        constrainPanPosition() {
            // Calculate pan constraints
            const maxPanX = Math.max(0, (this.innerWrapperWidth - this.mainContainerWidth) / 2);
            const maxPanY = Math.max(0, (this.innerWrapperHeight - this.mainContainerHeight) / 2);

            // Reset pan if content fits within container
            if (this.innerWrapperWidth <= this.mainContainerWidth) {
                this.panX = 0;
            } else {
                this.panX = Math.max(-maxPanX, Math.min(maxPanX, this.panX));
            }

            if (this.innerWrapperHeight <= this.mainContainerHeight) {
                this.panY = 0;
            } else {
                this.panY = Math.max(-maxPanY, Math.min(maxPanY, this.panY));
            }
        },

        updateImageDimensions() {
            this.$nextTick(() => {
                const viewport = this.$refs.scrollContainer;
                const mainContainer = this.$refs.mainContainer;
                const innerWrapper = this.$refs.innerWrapper;
                const image = this.$refs.image;

                if (!viewport || !mainContainer || !innerWrapper) {
                    return;
                }

                // If main container dimensions not set, calculate from viewport
                if (this.mainContainerWidth === 0) {
                    this.calculateContainerDimensionsFromViewport(viewport, image);
                    return;
                }

                // Update inner wrapper size based on current zoom level
                this.updateInnerWrapperDimensions();
            });
        },

        /**
         * Calculate container dimensions from viewport when no metadata available
         */
        calculateContainerDimensionsFromViewport(viewport, image) {
            const maxWidth = viewport.clientWidth;
            const maxHeight = viewport.clientHeight;

            // If we have natural dimensions from image, use them
            let imageWidth = 0;
            let imageHeight = 0;

            if (this.imageNaturalWidth > 0 && this.imageNaturalHeight > 0) {
                imageWidth = this.imageNaturalWidth;
                imageHeight = this.imageNaturalHeight;
            } else if (image && image.naturalWidth > 0 && image.naturalHeight > 0) {
                imageWidth = image.naturalWidth;
                imageHeight = image.naturalHeight;
                this.imageNaturalWidth = imageWidth;
                this.imageNaturalHeight = imageHeight;
            } else {
                // Last resort: use viewport dimensions with 4:3 aspect ratio
                if (maxWidth / maxHeight > 4 / 3) {
                    imageWidth = Math.round(maxHeight * 4 / 3);
                    imageHeight = maxHeight;
                } else {
                    imageWidth = maxWidth;
                    imageHeight = Math.round(maxWidth * 3 / 4);
                }
                this.imageNaturalWidth = imageWidth;
                this.imageNaturalHeight = imageHeight;
            }

            // Update viewport dimensions
            this.updateViewportDimensions();

            // Calculate container dimensions
            const containerDimensions = this.calculateContainerDimensions(
                imageWidth,
                imageHeight,
                maxWidth,
                maxHeight
            );

            this.mainContainerWidth = containerDimensions.width;
            this.mainContainerHeight = containerDimensions.height;
            this.innerWrapperWidth = containerDimensions.width;
            this.innerWrapperHeight = containerDimensions.height;
        },

        /**
         * Update inner wrapper dimensions based on current zoom level
         */
        updateInnerWrapperDimensions() {
            if (this.mainContainerWidth === 0 || this.mainContainerHeight === 0) {
                return;
            }

            const additionalPixels = (this.zoomLevel - 1) * this.currentZoomStep;
            const scaleFactor = 1 + (additionalPixels / Math.max(this.mainContainerWidth, this.mainContainerHeight));

            this.innerWrapperWidth = Math.round(this.mainContainerWidth * scaleFactor);
            this.innerWrapperHeight = Math.round(this.mainContainerHeight * scaleFactor);
        },

        get currentMainContainerWidth() {
            return this.mainContainerWidth || 'auto';
        },

        get currentMainContainerHeight() {
            return this.mainContainerHeight || 'auto';
        },

        get currentInnerWrapperWidth() {
            return this.innerWrapperWidth || this.mainContainerWidth || 0;
        },

        get currentInnerWrapperHeight() {
            return this.innerWrapperHeight || this.mainContainerHeight || 0;
        },

        handleWheel(event) {
            event.preventDefault();
            const delta = event.deltaY;
            const zoomFactor = 1.1;

            if (delta < 0 && this.canZoomIn) {
                this.zoomIn(zoomFactor);
            } else if (delta > 0 && this.canZoomOut) {
                this.zoomOut(zoomFactor);
            }
        },

        handleArrowKeys(event) {
            // Only handle arrow keys when zoomed in
            if (!this.isZoomed) return;

            event.preventDefault();
            const step = this.arrowKeyStep;

            // Calculate movement bounds
            const maxPanX = Math.max(0, (this.innerWrapperWidth - this.mainContainerWidth) / 2);
            const maxPanY = Math.max(0, (this.innerWrapperHeight - this.mainContainerHeight) / 2);

            switch (event.key) {
                case 'ArrowUp':
                    this.panY = Math.min(maxPanY, this.panY + step);
                    break;
                case 'ArrowDown':
                    this.panY = Math.max(-maxPanY, this.panY - step);
                    break;
                case 'ArrowLeft':
                    this.panX = Math.min(maxPanX, this.panX + step);
                    break;
                case 'ArrowRight':
                    this.panX = Math.max(-maxPanX, this.panX - step);
                    break;
            }

        },

        // Enhanced coordinate calculation with edge case protection and 9 decimal precision
        getImageCoordinates(clientX, clientY) {
            const innerWrapper = this.$refs.innerWrapper;
            const image = this.$refs.image;

            if (!innerWrapper || !image) {
                return {
                    x: parseFloat((0).toFixed(9)),
                    y: parseFloat((0).toFixed(9))
                };
            }

            const wrapperRect = innerWrapper.getBoundingClientRect();
            const imageRect = image.getBoundingClientRect();

            // Edge case: Prevent division by zero
            if (imageRect.width <= 0 || imageRect.height <= 0) {
                return {
                    x: parseFloat((0).toFixed(9)),
                    y: parseFloat((0).toFixed(9))
                };
            }

            // Calculate relative position within the visible image area
            const x = clientX - imageRect.left;
            const y = clientY - imageRect.top;

            // Convert to percentage of the actual image dimensions with safe math
            const xPercent = (x / imageRect.width) * 100;
            const yPercent = (y / imageRect.height) * 100;

            // Ensure values are within bounds and maintain 9 decimal precision
            return {
                x: parseFloat(Math.max(0, Math.min(100, xPercent)).toFixed(9)),
                y: parseFloat(Math.max(0, Math.min(100, yPercent)).toFixed(9))
            };
        },

        onImageLoad() {
            // Only use this for supplementary information, not critical layout
            // Critical layout should already be handled by metadata or fallback dimensions

            const img = this.$refs.image;
            if (img && img.naturalWidth > 0 && img.naturalHeight > 0) {
                // Update natural dimensions if they weren't already set
                if (!this.imageNaturalWidth || !this.imageNaturalHeight) {
                    this.imageNaturalWidth = img.naturalWidth;
                    this.imageNaturalHeight = img.naturalHeight;

                    // Only recalculate dimensions if we don't have them yet
                    if (this.mainContainerWidth === 0) {
                        this.$nextTick(() => {
                            this.updateImageDimensions();
                        });
                    }
                }
            }

            // Ensure image is marked as ready (may have been set earlier by metadata)
            if (!this.imageReady || !this.initFinished) {
                this.imageReady = true;
                this.initFinished = true;
            }
        }

    };
}
