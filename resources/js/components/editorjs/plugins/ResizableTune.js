/**
 * ResizableTune - EditorJS Block Tune for Resizable Video Blocks
 * Allows users to resize video blocks by dragging edges/corners
 */

class ResizableTune {
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
                minWidth: 200
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
        this.onBlockFocus = this.onBlockFocus.bind(this);
        this.onBlockBlur = this.onBlockBlur.bind(this);
        
        // Apply stored dimensions immediately when the tune is created
        setTimeout(() => this.applyStoredDimensions(), 100);
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
        
        // Add event listeners
        this.addEventListeners();
    }

    /**
     * Disable resize mode
     */
    disableResizeMode() {
        const blockElement = this.block.holder;
        blockElement.classList.remove('resize-mode-active');
        
        // Remove resize handles
        this.removeResizeHandles();
        
        // Remove event listeners
        this.removeEventListeners();
    }

    /**
     * Add resize handles around the video block
     */
    addResizeHandles() {
        const blockElement = this.block.holder;
        // Target specifically the thumbnail container (the actual video preview)
        const thumbnailContainer = blockElement.querySelector('.ce-video-upload__thumbnail-container');
        
        if (!thumbnailContainer) {
            return;
        }

        // Remove existing handles
        this.removeResizeHandles();

        // Create resize wrapper that will contain both thumbnail and handles
        const resizeWrapper = document.createElement('div');
        resizeWrapper.classList.add('video-resize-wrapper');
        resizeWrapper.style.cssText = `
            position: relative;
            display: inline-block;
        `;

        // Move thumbnail container inside the wrapper
        const thumbnailParent = thumbnailContainer.parentNode;
        thumbnailParent.insertBefore(resizeWrapper, thumbnailContainer);
        resizeWrapper.appendChild(thumbnailContainer);

        // Create resize handles container
        const handlesContainer = document.createElement('div');
        handlesContainer.classList.add('video-resize-handles');
        
        // Define handle positions - only corners for aspect ratio preservation
        const handles = ['nw', 'ne', 'sw', 'se'];

        handles.forEach(position => {
            const handle = document.createElement('div');
            handle.classList.add('video-resize-handle', `resize-${position}`);
            handle.dataset.direction = position;
            
            // Add inline styling for smooth, visible handles
            handle.style.cssText = `
                position: absolute;
                background: #3b82f6;
                border: 2px solid #ffffff;
                border-radius: 50%;
                pointer-events: auto;
                z-index: 11;
                width: 16px;
                height: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 8px;
                color: white;
                user-select: none;
                transition: none;
            `;
            
            // Position the handle based on direction
            this.positionHandle(handle, position);
            
            // Add simple visual indicator for corner handles
            handle.innerHTML = '⋮⋮';
            
            handlesContainer.appendChild(handle);
        });

        // Position handles container to wrap around the thumbnail
        handlesContainer.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        `;

        // Make handles draggable
        handlesContainer.querySelectorAll('.video-resize-handle').forEach(handle => {
            handle.style.pointerEvents = 'auto';
            handle.addEventListener('mousedown', this.startResize);
        });

        // Append handles to the wrapper (not inside thumbnail)
        resizeWrapper.appendChild(handlesContainer);
        this.resizeHandles = handlesContainer;
    }

    /**
     * Position a corner resize handle
     */
    positionHandle(handle, direction) {
        // Position handles outside the thumbnail container for clear visibility
        switch (direction) {
            case 'nw':
                handle.style.top = '-8px';
                handle.style.left = '-8px';
                handle.style.cursor = 'nw-resize';
                break;
            case 'ne':
                handle.style.top = '-8px';
                handle.style.right = '-8px';
                handle.style.cursor = 'ne-resize';
                break;
            case 'sw':
                handle.style.bottom = '-8px';
                handle.style.left = '-8px';
                handle.style.cursor = 'sw-resize';
                break;
            case 'se':
                handle.style.bottom = '-8px';
                handle.style.right = '-8px';
                handle.style.cursor = 'se-resize';
                break;
        }
    }

    /**
     * Remove resize handles
     */
    removeResizeHandles() {
        if (this.resizeHandles) {
            this.resizeHandles.remove();
            this.resizeHandles = null;
        }
    }

    /**
     * Start resize operation
     */
    startResize(e) {
        e.preventDefault();
        e.stopPropagation();

        this.isResizing = true;
        const handle = e.target.closest('.video-resize-handle');
        const direction = handle.dataset.direction;
        
        const blockElement = this.block.holder;
        const thumbnailContainer = blockElement.querySelector('.ce-video-upload__thumbnail-container');
        
        if (!thumbnailContainer) return;

        // Get the actual computed size of the thumbnail, not just the offset
        const computedStyle = window.getComputedStyle(thumbnailContainer);
        const currentWidth = parseFloat(computedStyle.width) || thumbnailContainer.offsetWidth;
        const currentHeight = parseFloat(computedStyle.height) || thumbnailContainer.offsetHeight;
        
        console.log('Starting resize from dimensions:', { currentWidth, currentHeight });
        
        this.originalDimensions = {
            width: currentWidth,
            height: currentHeight,
            startX: e.clientX,
            startY: e.clientY,
            direction: direction
        };

        // Calculate aspect ratio from current dimensions
        this.aspectRatio = currentWidth / currentHeight;

        // Add resize cursor to body
        document.body.classList.add('video-resizing');
        document.body.style.cursor = this.getCursorForDirection(direction);

        // Add event listeners for resize
        document.addEventListener('mousemove', this.doResize);
        document.addEventListener('mouseup', this.stopResize);

        // Prevent text selection during resize
        document.body.style.userSelect = 'none';
    }

    /**
     * Perform resize operation
     */
    doResize(e) {
        if (!this.isResizing || !this.originalDimensions) return;

        e.preventDefault();

        const deltaX = e.clientX - this.originalDimensions.startX;
        const deltaY = e.clientY - this.originalDimensions.startY;
        const direction = this.originalDimensions.direction;

        let newWidth = this.originalDimensions.width;
        let newHeight = this.originalDimensions.height;

        // Calculate new dimensions based on corner handle direction
        // Always maintain aspect ratio for corner resizing
        switch (direction) {
            case 'se':
                // Bottom-right: increase with both X and Y, use the larger delta
                const deltaMax = Math.max(deltaX, deltaY);
                newWidth = this.originalDimensions.width + deltaMax;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'sw':
                // Bottom-left: decrease width, increase height
                newWidth = this.originalDimensions.width - deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'ne':
                // Top-right: increase width, decrease height
                newWidth = this.originalDimensions.width + deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'nw':
                // Top-left: decrease both
                const deltaNW = Math.max(-deltaX, -deltaY);
                newWidth = this.originalDimensions.width + deltaNW;
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

        // Clean up
        document.removeEventListener('mousemove', this.doResize);
        document.removeEventListener('mouseup', this.stopResize);
        document.body.classList.remove('video-resizing');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';

        // Save current dimensions to data - this keeps the size permanent
        const blockElement = this.block.holder;
        const thumbnailContainer = blockElement.querySelector('.ce-video-upload__thumbnail-container');
        
        if (thumbnailContainer) {
            const currentWidth = parseInt(thumbnailContainer.style.width);
            const currentHeight = parseInt(thumbnailContainer.style.height);
            
            this.data.resize.width = currentWidth;
            this.data.resize.height = currentHeight;
            
            // Make the size permanent by setting it as inline style
            thumbnailContainer.style.width = `${currentWidth}px`;
            thumbnailContainer.style.height = `${currentHeight}px`;
            thumbnailContainer.style.maxWidth = 'none';
        }

        // Hide resize handles after resizing
        this.disableResizeMode();

        this.originalDimensions = null;
    }

    /**
     * Apply dimensions to thumbnail container
     */
    applyDimensions(width, height) {
        const blockElement = this.block.holder;
        const thumbnailContainer = blockElement.querySelector('.ce-video-upload__thumbnail-container');
        
        if (!thumbnailContainer) return;

        // Apply dimensions smoothly without any delays
        thumbnailContainer.style.width = `${width}px`;
        thumbnailContainer.style.height = `${height}px`;
        thumbnailContainer.style.maxWidth = 'none';
        
        // Force immediate layout update for smooth resize
        thumbnailContainer.offsetHeight;
    }

    /**
     * Update resize wrapper dimensions to match thumbnail
     */
    updateResizeWrapper(width, height) {
        const blockElement = this.block.holder;
        const resizeWrapper = blockElement.querySelector('.video-resize-wrapper');
        
        if (!resizeWrapper) return;

        // Ensure wrapper matches thumbnail dimensions
        resizeWrapper.style.width = `${width}px`;
        resizeWrapper.style.height = `${height}px`;
    }

    /**
     * Update handles position to match video container
     */
    updateHandlesPosition() {
        if (!this.resizeHandles) return;

        // Since handles are now direct children of videoContainer,
        // they automatically maintain correct positioning
        this.resizeHandles.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        `;
    }

    /**
     * Get cursor style for resize direction
     */
    getCursorForDirection(direction) {
        const cursors = {
            n: 'n-resize',
            s: 's-resize',
            e: 'e-resize',
            w: 'w-resize',
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
        return Math.min(parentWidth - 40, 800); // Leave some margin
    }

    /**
     * Add event listeners for block focus/blur
     */
    addEventListeners() {
        // Listen for block selection changes
        this.api.blocks.getCurrentBlockIndex(); // Trigger focus tracking
    }

    /**
     * Remove event listeners
     */
    removeEventListeners() {
        // Clean up any remaining listeners
    }

    /**
     * Handle block focus
     */
    onBlockFocus() {
        // Block is focused, keep handles visible
    }

    /**
     * Handle block blur
     */
    onBlockBlur() {
        // Block lost focus, you might want to hide handles here
    }

    /**
     * Apply stored dimensions when block is rendered
     */
    applyStoredDimensions() {
        if (this.data.resize && (this.data.resize.width && this.data.resize.height)) {
            const blockElement = this.block.holder;
            const thumbnailContainer = blockElement.querySelector('.ce-video-upload__thumbnail-container');
            
            if (thumbnailContainer) {
                console.log('Applying stored dimensions:', this.data.resize.width, 'x', this.data.resize.height);
                thumbnailContainer.style.width = `${this.data.resize.width}px`;
                thumbnailContainer.style.height = `${this.data.resize.height}px`;
                thumbnailContainer.style.maxWidth = 'none';
            }
        } else {
            console.log('No stored dimensions to apply');
        }
    }

    /**
     * Reset dimensions to original
     */
    resetDimensions() {
        const blockElement = this.block.holder;
        const thumbnailContainer = blockElement.querySelector('.ce-video-upload__thumbnail-container');
        
        if (thumbnailContainer) {
            thumbnailContainer.style.width = '';
            thumbnailContainer.style.height = '';
            thumbnailContainer.style.maxWidth = '';
        }

        // Clear stored dimensions
        this.data.resize = {
            width: null,
            height: null,
            maintainAspectRatio: true,
            maxWidth: '100%',
            minWidth: 200
        };

        // Update handles if visible
        if (this.resizeHandles) {
            this.updateHandlesPosition();
        }
    }

    /**
     * Save tune data
     */
    save() {
        return this.data;
    }
}

export default ResizableTune;