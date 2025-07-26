
export default function designReviewApp() {
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
        isOpen: false,
        currentImage: '',
        designId: null,
        comments: [],
        showingComment: false,
        activeCommentId: null,
        isSelecting: false,
        isDragging: false,
        allCommentsHidden: false,
        _openedViaEvent: false,
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
            onCommentClick: null,
            onModalOpen: null,
            onModalClose: null
        },


        get hasActiveFilter() {
            if (this.allCommentsHidden) return false;
            return this.selectedCommentIds.length < this.comments.length;
        },


        get canZoomIn() {
            return this.zoomLevel < this.responsiveMaxZoom;
        },

        get canZoomOut() {
            return this.zoomLevel > this.minZoom;
        },

        get isZoomed() {
            return this.zoomLevel > 1;
        },

        get imageDisplayWidth() {
            return this.imageNaturalWidth * this.zoomLevel;
        },

        get imageDisplayHeight() {
            return this.imageNaturalHeight * this.zoomLevel;
        },

        // Responsive zoom step based on viewport size (Tailwind breakpoints)
        get currentZoomStep() {
            const width = window.innerWidth;

            // Tailwind breakpoints: sm(640), md(768), lg(1024), xl(1280), 2xl(1536)
            if (width < 640) {
                // Mobile: smaller steps for precise control
                return Math.max(100, this.mainContainerWidth * 0.15);
            } else if (width < 768) {
                // Small tablet: moderate steps
                return Math.max(120, this.mainContainerWidth * 0.18);
            } else if (width < 1024) {
                // Tablet: balanced steps
                return Math.max(150, this.mainContainerWidth * 0.20);
            } else if (width < 1280) {
                // Large screens: bigger steps
                return Math.max(180, this.mainContainerWidth * 0.22);
            } else {
                // XL+ screens: largest steps
                return Math.max(200, this.mainContainerWidth * 0.25);
            }
        },

        // Responsive max zoom based on viewport
        get responsiveMaxZoom() {
            const width = window.innerWidth;

            if (width < 640) {
                return 4; // Mobile: less zoom to avoid performance issues
            } else if (width < 768) {
                return 5; // Small tablet
            } else if (width < 1024) {
                return 6; // Tablet
            } else {
                return 8; // Desktop: more zoom capability
            }
        },

        init(callbacks = {}) {
            // Detect mobile device with better accuracy
            this.isMobile = this.detectMobileDevice();

            // Throttled update function for performance
            this.throttledUpdateSelection = throttle(this.updateSelection.bind(this), 16);

            if (callbacks && Object.keys(callbacks).length > 0) {
                this.setCallbacks(callbacks);
            }
            this.$watch('selectedCommentIds', () => {
                if (this.filterMode) {
                    this.updateVisibleComments();
                }
            });

            // Create bound event handler for proper cleanup
            this.handleOpenEvent = (event) => {
                this._openedViaEvent = true;
                const {
                    imageUrl,
                    comments,
                    designId
                } = event.detail;

                this.openModal(imageUrl, comments || [], designId);

                // Set up callbacks for this specific design
                this.setCallbacks({
                    onDeleteComment: async (commentId) => {
                        if (window.Livewire) {
                            return new Promise((resolve, reject) => {
                                Livewire.emit('deleteComment', {
                                    designId: designId,
                                    commentId: commentId
                                });
                                resolve();
                            });
                        }
                    },
                    onCommentClick: (comment) => {
                        console.log('Comment clicked:', comment);
                    }
                });
            };

            // Register event listener for opening modal
            window.addEventListener('open-design-review', this.handleOpenEvent);




        },
        destroy() {
            // Remove event listeners
            if (this.handleOpenEvent) {
                window.removeEventListener('open-design-review', this.handleOpenEvent);
                this.handleOpenEvent = null;
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

        openModal(imageUrl, existingComments = [], designId) {
            if (this._openedViaEvent) {
                console.warn(
                    'Design Review: Modal opened via both direct method and event. Using direct method parameters.'
                );
                this._openedViaEvent = false;
            }

            this.currentImage = imageUrl;
            this.designId = designId;

            this.comments = existingComments;
            this.selectedCommentIds = this.comments.map(c => c.id);


            this.isOpen = true;
            this.updateVisibleComments();

            // Initialize dimensions after modal is open
            this.$nextTick(() => {
                this.updateImageDimensions();
            });

            if (this.callbacks.onModalOpen) {
                this.callbacks.onModalOpen(this.currentImage);
            }
        },

        closeModal() {
            this.isOpen = false;
            this.reset();
            if (this.callbacks.onModalClose) {
                this.callbacks.onModalClose();
            }
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

        // Touch navigation methods
        moveUp() {
            if (!this.isZoomed) return;
            const maxPanY = Math.max(0, (this.innerWrapperHeight - this.mainContainerHeight) / 2);
            this.panY = Math.min(maxPanY, this.panY + this.arrowKeyStep);
        },

        moveDown() {
            if (!this.isZoomed) return;
            const maxPanY = Math.max(0, (this.innerWrapperHeight - this.mainContainerHeight) / 2);
            this.panY = Math.max(-maxPanY, this.panY - this.arrowKeyStep);
        },

        moveLeft() {
            if (!this.isZoomed) return;
            const maxPanX = Math.max(0, (this.innerWrapperWidth - this.mainContainerWidth) / 2);
            this.panX = Math.min(maxPanX, this.panX + this.arrowKeyStep);
        },

        moveRight() {
            if (!this.isZoomed) return;
            const maxPanX = Math.max(0, (this.innerWrapperWidth - this.mainContainerWidth) / 2);
            this.panX = Math.max(-maxPanX, this.panX - this.arrowKeyStep);
        },

        detectMobileDevice() {
            return (
                'ontouchstart' in window ||
                navigator.maxTouchPoints > 0 ||
                /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                window.innerWidth < 768
            );
        },
        handleClose() {
            if (this.showCommentFilter) {
                this.showCommentFilter = false;
                this.filterMode = false;
                return;
            }

            this.closeModal();
        },


        handleBackdropClick(event) {
            if (event.target === event.currentTarget) this.handleClose();
        },

        // Mouse Events
        startSelection(event) {
            if (event.button !== 0) return;

            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            const coords = this.getImageCoordinates(event.clientX, event.clientY);

            this.selectionStart = {
                x: coords.x,
                y: coords.y,
                xPx: x,
                yPx: y
            };
            this.isDragging = true;
            this.isSelecting = false;
        },

        updateSelection(event) {
            if (!this.isDragging || !this.selectionStart.xPx) return;

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
            this.selectionBox = {
                x: Math.min(this.selectionStart.x, coords.x),
                y: Math.min(this.selectionStart.y, coords.y),
                width: Math.abs(coords.x - this.selectionStart.x),
                height: Math.abs(coords.y - this.selectionStart.y)
            };
        },

        endSelection(event) {
            if (!this.isDragging) return;

            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            this.isDragging = false;

            if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
                this.createAreaComment();
            } else if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
                this.handleClick(event.clientX, event.clientY, rect);
            }

            this.resetSelectionState();
        },

        // Touch Events
        handleTouchStart(event) {
            const touch = event.touches[0];
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;

            this.touchStartTime = Date.now();
            this.touchMoved = false;

            const coords = this.getImageCoordinates(touch.clientX, touch.clientY);
            this.selectionStart = {
                x: coords.x,
                y: coords.y,
                xPx: x,
                yPx: y
            };
            this.isDragging = true;
            this.isSelecting = false;

            // Optimized long press for area selection
            this.longPressTimer = setTimeout(() => {
                if (!this.touchMoved && this.isDragging) {
                    // Enhanced haptic feedback
                    if (navigator.vibrate) {
                        navigator.vibrate([10, 50, 10]);
                    }
                    this.isSelecting = true;
                }
            }, 300); // Further reduced for better responsiveness
        },

        handleTouchMove(event) {
            if (!this.isDragging) return;

            const touch = event.touches[0];
            const rect = this.$refs.innerWrapper.getBoundingClientRect();
            const x = Math.max(0, Math.min(touch.clientX - rect.left, rect.width));
            const y = Math.max(0, Math.min(touch.clientY - rect.top, rect.height));

            const distance = Math.sqrt(
                Math.pow(x - this.selectionStart.xPx, 2) +
                Math.pow(y - this.selectionStart.yPx, 2)
            );

            // Enhanced movement detection for mobile
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
            this.selectionBox = {
                x: Math.min(this.selectionStart.x, coords.x),
                y: Math.min(this.selectionStart.y, coords.y),
                width: Math.abs(coords.x - this.selectionStart.x),
                height: Math.abs(coords.y - this.selectionStart.y)
            };
        },

        handleTouchEnd(event) {
            if (!this.isDragging) return;

            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }

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

            // Calculate coordinates for comment creation
            const coords = this.getImageCoordinates(clientX, clientY);
            const commentSize = this.isMobile ? 3 : 2;
            const offset = commentSize / 2;

            const commentData = {
                x: Math.max(0, Math.min(100 - commentSize, coords.x - offset)),
                y: Math.max(0, Math.min(100 - commentSize, coords.y - offset)),
                width: commentSize,
                height: commentSize,
                type: 'point',
                designId: this.designId,
                imageUrl: this.currentImage,
                isMobile: this.isMobile,
                zoomLevel: this.zoomLevel
            };

            this.dispatchCommentEvent(commentData);
        },

        createAreaComment() {
            const commentData = {
                x: Math.max(0, Math.min(100, this.selectionBox.x)),
                y: Math.max(0, Math.min(100, this.selectionBox.y)),
                width: Math.min(this.selectionBox.width, 100 - this.selectionBox.x),
                height: Math.min(this.selectionBox.height, 100 - this.selectionBox.y),
                type: 'area',
                designId: this.designId,
                imageUrl: this.currentImage,
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

        cancelSelection() {
            this.isDragging = false;
            this.isSelecting = false;
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            this.resetSelectionState();
        },

        resetSelectionState() {
            this.selectionStart = {
                x: 0,
                y: 0,
                xPx: null,
                yPx: null
            };
            this.selectionBox = {
                x: 0,
                y: 0,
                width: 0,
                height: 0
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

            this.showingComment = true;
            this.activeCommentId = comment.id;
            this.$wire.set('activeCommentId', comment.id);

        },

        closeComment() {
            this.showingComment = false;
            this.activeCommentId = null;
            this.$wire.set('activeCommentId', null);
        },

        // Public API methods
        setCallbacks(callbacks = {}) {
            const validCallbacks = ['onDeleteComment', 'onCommentClick', 'onModalOpen', 'onModalClose'];

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
            const newZoom = Math.min(this.zoomLevel * factor, this.maxZoom);
            this.setZoom(newZoom);
        },

        zoomOut(factor = 1.2) {
            const newZoom = Math.max(this.zoomLevel / factor, this.minZoom);
            this.setZoom(newZoom);
        },

        resetZoom() {
            this.setZoom(1);
            this.resetPan();
        },

        setZoom(newZoom) {
            this.zoomLevel = newZoom;
            this.updateImageDimensions();
        },

        updateImageDimensions() {
            this.$nextTick(() => {
                const viewport = this.$refs.scrollContainer;
                const mainContainer = this.$refs.mainContainer;
                const innerWrapper = this.$refs.innerWrapper;
                const image = this.$refs.image;

                if (!viewport || !mainContainer || !innerWrapper || !image) {
                    console.log('Missing refs:', { viewport: !!viewport, mainContainer: !!mainContainer, innerWrapper: !!innerWrapper, image: !!image });
                    return;
                }

                // Wait for image to load and get natural dimensions
                if (image.naturalWidth === 0 || image.naturalHeight === 0) {
                    console.log('Image not loaded yet, waiting...');
                    return;
                }

                // Initialize main container size once (based on image + viewport constraints)
                if (this.mainContainerWidth === 0) {
                    const maxWidth = viewport.clientWidth;
                    const maxHeight = viewport.clientHeight;
                    const imageAspectRatio = image.naturalWidth / image.naturalHeight;

                    // Use full viewport dimensions with image aspect ratio
                    let containerWidth, containerHeight;

                    // Try to use full viewport width first
                    containerWidth = maxWidth;
                    containerHeight = maxWidth / imageAspectRatio;

                    // If height exceeds viewport, use full viewport height instead
                    if (containerHeight > maxHeight) {
                        containerHeight = maxHeight;
                        containerWidth = maxHeight * imageAspectRatio;
                    }

                    // This ensures we use maximum possible viewport space while maintaining aspect ratio

                    this.mainContainerWidth = containerWidth;
                    this.mainContainerHeight = containerHeight;

                    // Inner wrapper starts at same size as main container (zoom level 1)
                    this.innerWrapperWidth = containerWidth;
                    this.innerWrapperHeight = containerHeight;

                    console.log('Container dimensions set:', {
                        viewport: { width: maxWidth, height: maxHeight },
                        mainContainer: { width: this.mainContainerWidth, height: this.mainContainerHeight },
                        innerWrapper: { width: this.innerWrapperWidth, height: this.innerWrapperHeight },
                        aspectRatio: imageAspectRatio,
                        expectedAspectRatio: image.naturalWidth / image.naturalHeight,
                        calculatedAspectRatio: containerWidth / containerHeight,
                        naturalSize: { width: image.naturalWidth, height: image.naturalHeight },
                        calculations: {
                            fitWidth: maxWidth,
                            fitHeight: maxWidth / imageAspectRatio,
                            finalWidth: containerWidth,
                            finalHeight: containerHeight
                        }
                    });

                    // Force update to show the image
                    this.$nextTick(() => {
                        console.log('Image should be visible now');
                        console.log('DOM elements:', {
                            viewport: {
                                width: viewport.offsetWidth,
                                height: viewport.offsetHeight,
                                display: getComputedStyle(viewport).display
                            },
                            mainContainer: {
                                width: this.$refs.mainContainer.offsetWidth,
                                height: this.$refs.mainContainer.offsetHeight,
                                display: getComputedStyle(this.$refs.mainContainer).display
                            },
                            innerWrapper: {
                                width: this.$refs.innerWrapper.offsetWidth,
                                height: this.$refs.innerWrapper.offsetHeight,
                                display: getComputedStyle(this.$refs.innerWrapper).display
                            },
                            image: {
                                width: image.offsetWidth,
                                height: image.offsetHeight,
                                naturalWidth: image.naturalWidth,
                                naturalHeight: image.naturalHeight,
                                display: getComputedStyle(image).display
                            }
                        });
                    });
                    return;
                }

                // Update inner wrapper size based on zoom level (main container stays fixed)
                const additionalPixels = (this.zoomLevel - 1) * this.currentZoomStep;

                // Scale both dimensions proportionally by the same factor
                const scaleFactor = 1 + (additionalPixels / Math.max(this.mainContainerWidth, this.mainContainerHeight));
                this.innerWrapperWidth = this.mainContainerWidth * scaleFactor;
                this.innerWrapperHeight = this.mainContainerHeight * scaleFactor;

                console.log('Inner wrapper updated:', {
                    zoomLevel: this.zoomLevel,
                    scaleFactor: scaleFactor,
                    additionalPixels: additionalPixels,
                    mainContainer: { width: this.mainContainerWidth, height: this.mainContainerHeight },
                    innerWrapper: { width: this.innerWrapperWidth, height: this.innerWrapperHeight }
                });
            });
        },

        get currentMainContainerWidth() {
            console.log('Getting main container width:', this.mainContainerWidth);
            return this.mainContainerWidth || 'auto';
        },

        get currentMainContainerHeight() {
            console.log('Getting main container height:', this.mainContainerHeight);
            return this.mainContainerHeight || 'auto';
        },

        get currentInnerWrapperWidth() {
            const width = this.innerWrapperWidth || this.mainContainerWidth;
            console.log('Getting inner wrapper width:', width);
            return width;
        },

        get currentInnerWrapperHeight() {
            const height = this.innerWrapperHeight || this.mainContainerHeight;
            console.log('Getting inner wrapper height:', height);
            return height;
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

            console.log('Pan updated:', { panX: this.panX, panY: this.panY, maxPanX, maxPanY });
        },

        // Enhanced coordinate calculation for improved zoom system
        getImageCoordinates(clientX, clientY) {
            const innerWrapper = this.$refs.innerWrapper;
            const image = this.$refs.image;

            if (!innerWrapper || !image) {
                return { x: 0, y: 0 };
            }

            const wrapperRect = innerWrapper.getBoundingClientRect();
            const imageRect = image.getBoundingClientRect();

            // Calculate relative position within the visible image area
            const x = clientX - imageRect.left;
            const y = clientY - imageRect.top;

            // Convert to percentage of the actual image dimensions
            const xPercent = (x / imageRect.width) * 100;
            const yPercent = (y / imageRect.height) * 100;

            return {
                x: Math.max(0, Math.min(100, xPercent)),
                y: Math.max(0, Math.min(100, yPercent))
            };
        },

        // Image load handler to set up dimensions
        onImageLoad() {
            this.updateImageDimensions();
        },

    };
}
