/**
 * VideoEmbedResizableTune - EditorJS Block Tune for Resizable Video Embed Blocks
 * Allows users to resize video embed blocks by dragging corners
 */

class VideoEmbedResizableTune {
    static get isTune() {
        return true;
    }

    static get isInternal() {
        return false;
    }

    static get title() {
        return 'Resize Video';
    }

    constructor({ api, data, config, block }) {
        this.api = api;
        this.data = data || {};
        this.config = config || {};
        this.block = block;
        
        // Initialize resize data
        if (!this.data.resize) {
            this.data.resize = {
                width: null,
                height: null,
                maintainAspectRatio: true,
                maxWidth: '100%',
                minWidth: 300
            };
        }

        // Resize state
        this.isResizing = false;
        this.resizeHandles = null;
        this.originalDimensions = null;
        this.aspectRatio = null;

        // Bind methods
        this.startResize = this.startResize.bind(this);
        this.doResize = this.doResize.bind(this);
        this.stopResize = this.stopResize.bind(this);
        
        // Apply stored dimensions immediately when the tune is created
        setTimeout(() => this.applyStoredDimensions(), 200);
    }

    /**
     * Create tune menu button
     */
    render() {
        return {
            icon: this.getResizeIcon(),
            label: 'Resize',
            onActivate: () => {
                this.toggleResizeMode();
            },
            closeOnActivate: true
        };
    }

    /**
     * Get resize icon SVG
     */
    getResizeIcon() {
        return `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/>
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"/>
            </svg>
        `;
    }

    /**
     * Toggle resize mode on/off
     */
    toggleResizeMode() {
        const blockElement = this.block.holder;
        
        if (blockElement.classList.contains('resize-mode-active')) {
            this.disableResizeMode();
        } else {
            this.enableResizeMode();
        }
    }

    /**
     * Enable resize mode
     */
    enableResizeMode() {
        const blockElement = this.block.holder;
        blockElement.classList.add('resize-mode-active');
        
        // Apply any stored dimensions first
        this.applyStoredDimensions();
        
        // Add resize handles
        this.addResizeHandles();
    }

    /**
     * Disable resize mode
     */
    disableResizeMode() {
        const blockElement = this.block.holder;
        blockElement.classList.remove('resize-mode-active');
        
        // Remove resize handles
        this.removeResizeHandles();
    }

    /**
     * Add resize handles around the video embed container
     */
    addResizeHandles() {
        const blockElement = this.block.holder;
        // Target the video embed container (the wrapper around iframe/video)
        const videoContainer = blockElement.querySelector('.video-embed__video-wrapper') || 
                              blockElement.querySelector('.video-embed__container');
        
        if (!videoContainer) {
            console.warn('VideoEmbedResizableTune: Video container not found');
            return;
        }

        // Remove existing handles
        this.removeResizeHandles();

        // Don't create wrapper if already exists
        let resizeWrapper = blockElement.querySelector('.video-embed-resize-wrapper');
        if (!resizeWrapper) {
            // Create resize wrapper that will contain both video container and handles
            resizeWrapper = document.createElement('div');
            resizeWrapper.classList.add('video-embed-resize-wrapper');
            resizeWrapper.style.cssText = `
                position: relative;
                display: inline-block;
                width: 100%;
            `;

            // Move video container inside the wrapper
            const containerParent = videoContainer.parentNode;
            containerParent.insertBefore(resizeWrapper, videoContainer);
            resizeWrapper.appendChild(videoContainer);
        }

        // Create resize handles container
        const handlesContainer = document.createElement('div');
        handlesContainer.classList.add('video-embed-resize-handles');
        
        // Define handle positions - only corners for aspect ratio preservation
        const handles = ['nw', 'ne', 'sw', 'se'];

        handles.forEach(position => {
            const handle = document.createElement('div');
            handle.classList.add('video-embed-resize-handle', `resize-${position}`);
            handle.dataset.direction = position;
            
            // Add inline styling for smooth, visible handles with better interaction
            handle.style.cssText = `
                position: absolute;
                background: #3b82f6;
                border: 2px solid #ffffff;
                border-radius: 50%;
                pointer-events: auto;
                z-index: 11;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 8px;
                color: white;
                user-select: none;
                transition: none;
                cursor: grab;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            `;
            
            // Add hover effect
            handle.addEventListener('mouseenter', () => {
                handle.style.background = '#2563eb';
                handle.style.transform = 'scale(1.1)';
            });
            handle.addEventListener('mouseleave', () => {
                handle.style.background = '#3b82f6';
                handle.style.transform = 'scale(1)';
            });
            
            // Position the handle based on direction
            this.positionHandle(handle, position);
            
            // Add simple visual indicator for corner handles
            handle.innerHTML = '⋮⋮';
            
            handlesContainer.appendChild(handle);
        });

        // Position handles container to wrap around the video container
        handlesContainer.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        `;

        // Make handles draggable with better event handling
        handlesContainer.querySelectorAll('.video-embed-resize-handle').forEach(handle => {
            handle.style.pointerEvents = 'auto';
            handle.style.touchAction = 'none'; // Prevent touch scrolling
            
            // Add both mouse and touch events for better reliability
            handle.addEventListener('mousedown', this.startResize, { passive: false });
            handle.addEventListener('touchstart', this.startResize, { passive: false });
            
            // Prevent context menu on right click
            handle.addEventListener('contextmenu', (e) => e.preventDefault());
        });

        // Append handles to the wrapper (not inside video container)
        resizeWrapper.appendChild(handlesContainer);
        this.resizeHandles = handlesContainer;
    }

    /**
     * Position a corner resize handle
     */
    positionHandle(handle, direction) {
        // Position handles outside the video container for clear visibility and better interaction
        switch (direction) {
            case 'nw':
                handle.style.top = '-10px';
                handle.style.left = '-10px';
                handle.style.cursor = 'nw-resize';
                break;
            case 'ne':
                handle.style.top = '-10px';
                handle.style.right = '-10px';
                handle.style.cursor = 'ne-resize';
                break;
            case 'sw':
                handle.style.bottom = '-10px';
                handle.style.left = '-10px';
                handle.style.cursor = 'sw-resize';
                break;
            case 'se':
                handle.style.bottom = '-10px';
                handle.style.right = '-10px';
                handle.style.cursor = 'se-resize';
                break;
        }
    }

    /**
     * Remove resize handles
     */
    removeResizeHandles() {
        const blockElement = this.block.holder;
        
        // Only remove handles, not the wrapper
        if (this.resizeHandles) {
            this.resizeHandles.remove();
            this.resizeHandles = null;
        }
        
        // Also clean up any orphaned handles
        const orphanedHandles = blockElement.querySelectorAll('.video-embed-resize-handles');
        orphanedHandles.forEach(handle => handle.remove());
    }

    /**
     * Start resize operation
     */
    startResize(e) {
        e.preventDefault();
        e.stopPropagation();

        // Handle both mouse and touch events
        const clientX = e.clientX || (e.touches && e.touches[0] && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0] && e.touches[0].clientY);
        
        if (!clientX || !clientY) return;

        this.isResizing = true;
        const handle = e.target.closest('.video-embed-resize-handle');
        if (!handle) return;
        
        const direction = handle.dataset.direction;
        
        // Change cursor style during drag
        handle.style.cursor = 'grabbing';
        
        const blockElement = this.block.holder;
        const videoContainer = blockElement.querySelector('.video-embed__video-wrapper') || 
                              blockElement.querySelector('.video-embed__container');
        
        if (!videoContainer) return;

        // Get the actual computed size of the video container, not just the offset
        const computedStyle = window.getComputedStyle(videoContainer);
        const currentWidth = parseFloat(computedStyle.width) || videoContainer.offsetWidth;
        const currentHeight = parseFloat(computedStyle.height) || videoContainer.offsetHeight;
        
        console.log('Starting video embed resize from dimensions:', { currentWidth, currentHeight });
        
        this.originalDimensions = {
            width: currentWidth,
            height: currentHeight,
            startX: clientX,
            startY: clientY,
            direction: direction
        };

        // Calculate aspect ratio from current dimensions
        this.aspectRatio = currentWidth / currentHeight;

        // Add resize cursor to body
        document.body.classList.add('video-embed-resizing');
        document.body.style.cursor = this.getCursorForDirection(direction);

        // Add event listeners for resize - both mouse and touch
        document.addEventListener('mousemove', this.doResize, { passive: false });
        document.addEventListener('mouseup', this.stopResize, { passive: false });
        document.addEventListener('touchmove', this.doResize, { passive: false });
        document.addEventListener('touchend', this.stopResize, { passive: false });

        // Prevent text selection and scrolling during resize
        document.body.style.userSelect = 'none';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Perform resize operation
     */
    doResize(e) {
        if (!this.isResizing || !this.originalDimensions) return;

        e.preventDefault();

        // Handle both mouse and touch events
        const clientX = e.clientX || (e.touches && e.touches[0] && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0] && e.touches[0].clientY);
        
        if (!clientX || !clientY) return;

        const deltaX = clientX - this.originalDimensions.startX;
        const deltaY = clientY - this.originalDimensions.startY;
        const direction = this.originalDimensions.direction;

        let newWidth = this.originalDimensions.width;
        let newHeight = this.originalDimensions.height;

        // Calculate new dimensions based on corner handle direction
        // Always maintain aspect ratio for corner resizing
        switch (direction) {
            case 'se':
                // Bottom-right: drag right/down = bigger, left/up = smaller
                newWidth = this.originalDimensions.width + deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'sw':
                // Bottom-left: drag left/down = bigger, right/up = smaller
                newWidth = this.originalDimensions.width - deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'ne':
                // Top-right: drag right/up = bigger, left/down = smaller
                newWidth = this.originalDimensions.width + deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'nw':
                // Top-left: drag left/up = bigger, right/down = smaller
                newWidth = this.originalDimensions.width - deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
        }

        // Apply constraints while maintaining aspect ratio
        newWidth = Math.max(this.data.resize.minWidth, Math.min(newWidth, this.getMaxWidth()));
        newHeight = newWidth / this.aspectRatio;

        // Apply new dimensions immediately for smooth resizing
        this.applyDimensions(newWidth, newHeight);
        
        // Update resize wrapper to maintain handle positions
        this.updateResizeWrapper(newWidth, newHeight);
    }

    /**
     * Stop resize operation
     */
    stopResize() {
        if (!this.isResizing) return;

        this.isResizing = false;

        // Clean up all event listeners
        document.removeEventListener('mousemove', this.doResize);
        document.removeEventListener('mouseup', this.stopResize);
        document.removeEventListener('touchmove', this.doResize);
        document.removeEventListener('touchend', this.stopResize);
        document.body.classList.remove('video-embed-resizing');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        document.body.style.overflow = '';

        // Save current dimensions to data - this keeps the size permanent
        const blockElement = this.block.holder;
        const videoContainer = blockElement.querySelector('.video-embed__video-wrapper') || 
                              blockElement.querySelector('.video-embed__container');
        
        if (videoContainer) {
            const currentWidth = parseInt(videoContainer.style.width);
            const currentHeight = parseInt(videoContainer.style.height);
            
            this.data.resize.width = currentWidth;
            this.data.resize.height = currentHeight;
            
            // Make the size permanent using applyDimensions to ensure embedded content is properly sized
            this.applyDimensions(currentWidth, currentHeight);
        }

        // Hide resize handles after resizing
        this.disableResizeMode();

        this.originalDimensions = null;
    }

    /**
     * Apply dimensions to video container and force embedded content to fit
     */
    applyDimensions(width, height) {
        const blockElement = this.block.holder;
        const videoContainer = blockElement.querySelector('.video-embed__video-wrapper') || 
                              blockElement.querySelector('.video-embed__container');
        
        if (!videoContainer) return;

        // Apply dimensions to container
        videoContainer.style.width = `${width}px`;
        videoContainer.style.height = `${height}px`;
        videoContainer.style.maxWidth = 'none';
        
        // Force embedded iframe/video to take container size
        const iframe = videoContainer.querySelector('iframe');
        const video = videoContainer.querySelector('video');
        
        if (iframe) {
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.objectFit = 'contain';
        }
        
        if (video) {
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
        }
        
        // Force immediate layout update for smooth resize
        videoContainer.offsetHeight;
    }

    /**
     * Update resize wrapper dimensions to match video container
     */
    updateResizeWrapper(width, height) {
        const blockElement = this.block.holder;
        const resizeWrapper = blockElement.querySelector('.video-embed-resize-wrapper');
        
        if (!resizeWrapper) return;

        // Ensure wrapper matches video container dimensions
        resizeWrapper.style.width = `${width}px`;
        resizeWrapper.style.height = `${height}px`;
    }

    /**
     * Get cursor style for resize direction
     */
    getCursorForDirection(direction) {
        const cursors = {
            ne: 'ne-resize',
            nw: 'nw-resize',
            se: 'se-resize',
            sw: 'sw-resize'
        };
        return cursors[direction] || 'default';
    }

    /**
     * Get maximum width based on container
     */
    getMaxWidth() {
        const blockElement = this.block.holder;
        const parentWidth = blockElement.parentElement.offsetWidth;
        // Use full block width minus small margin to prevent overflow on mobile
        return parentWidth - 20; // Leave minimal margin for mobile
    }

    /**
     * Apply stored dimensions when block is rendered
     */
    applyStoredDimensions() {
        if (this.data.resize && (this.data.resize.width && this.data.resize.height)) {
            const blockElement = this.block.holder;
            
            // Wait for video to be properly rendered
            const checkAndApply = () => {
                const videoContainer = blockElement.querySelector('.video-embed__video-wrapper') || 
                                      blockElement.querySelector('.video-embed__container');
                
                if (videoContainer) {
                    // Ensure stored dimensions don't exceed block width (prevent mobile overflow)
                    const maxWidth = this.getMaxWidth();
                    let width = this.data.resize.width;
                    let height = this.data.resize.height;
                    
                    if (width > maxWidth) {
                        // Scale down proportionally to fit block width
                        const aspectRatio = width / height;
                        width = maxWidth;
                        height = width / aspectRatio;
                        
                        // Update stored data with corrected dimensions
                        this.data.resize.width = width;
                        this.data.resize.height = height;
                    }
                    
                    console.log('Applying stored video embed dimensions:', width, 'x', height);
                    
                    // Use the same applyDimensions method to ensure embedded content is sized correctly
                    this.applyDimensions(width, height);
                } else {
                    // Try again after a short delay if container not found
                    setTimeout(checkAndApply, 100);
                }
            };
            
            checkAndApply();
        } else {
            console.log('No stored video embed dimensions to apply');
        }
    }

    /**
     * Reset dimensions to original
     */
    resetDimensions() {
        const blockElement = this.block.holder;
        const videoContainer = blockElement.querySelector('.video-embed__video-wrapper') || 
                              blockElement.querySelector('.video-embed__container');
        
        if (videoContainer) {
            videoContainer.style.width = '';
            videoContainer.style.height = '';
            videoContainer.style.maxWidth = '';
        }

        // Clear stored dimensions
        this.data.resize = {
            width: null,
            height: null,
            maintainAspectRatio: true,
            maxWidth: '100%',
            minWidth: 300
        };

        // Update handles if visible
        if (this.resizeHandles) {
            this.updateResizeWrapper();
        }
    }

    /**
     * Save tune data
     */
    save() {
        return this.data;
    }
}

export default VideoEmbedResizableTune;