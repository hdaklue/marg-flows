/**
 * Video Upload Progress Integration Helper
 * Bridges the SessionUploadStrategy with the Alpine.js progress component
 */

export class VideoUploadProgressIntegration {
    constructor(progressComponent) {
        this.progressComponent = progressComponent;
        this.isInitialized = false;
    }

    /**
     * Initialize the progress component with file information
     */
    initialize(file, uploadType = 'single') {
        if (!this.progressComponent) {
            console.error('Progress component not found');
            return;
        }

        // Set file information
        this.progressComponent.setFileInfo(file.name, file.size);
        this.progressComponent.setUploadType(uploadType);
        
        // Reset any previous state
        this.progressComponent.reset();
        
        this.isInitialized = true;
        
        return this;
    }

    /**
     * Create progress callback for SessionUploadStrategy
     */
    createProgressCallback() {
        return (progress) => {
            if (!this.isInitialized || !this.progressComponent) return;
            
            this.progressComponent.updateProgress(progress);
        };
    }

    /**
     * Create status callback for SessionUploadStrategy
     */
    createStatusCallback() {
        return (message, phase) => {
            if (!this.isInitialized || !this.progressComponent) return;
            
            this.progressComponent.updateStatus(message, phase);
        };
    }

    /**
     * Create error callback
     */
    createErrorCallback() {
        return (error) => {
            if (!this.isInitialized || !this.progressComponent) return;
            
            const errorMessage = typeof error === 'string' ? error : error.message || 'Upload failed';
            this.progressComponent.setError(errorMessage);
        };
    }

    /**
     * Set up event listeners for user interactions
     */
    setupEventListeners(callbacks = {}) {
        if (!this.progressComponent) return;

        // Set up callbacks
        if (callbacks.onCancel) {
            this.progressComponent.onCancel = callbacks.onCancel;
        }

        if (callbacks.onRetry) {
            this.progressComponent.onRetry = callbacks.onRetry;
        }

        if (callbacks.onComplete) {
            this.progressComponent.onComplete = callbacks.onComplete;
        }

        if (callbacks.onError) {
            this.progressComponent.onError = callbacks.onError;
        }

        return this;
    }

    /**
     * Show the progress component
     */
    show() {
        if (this.progressComponent && this.progressComponent.$refs.progressContainer) {
            this.progressComponent.$refs.progressContainer.style.display = 'block';
        }
        return this;
    }

    /**
     * Hide the progress component
     */
    hide() {
        if (this.progressComponent && this.progressComponent.$refs.progressContainer) {
            this.progressComponent.$refs.progressContainer.style.display = 'none';
        }
        return this;
    }

    /**
     * Get the current progress state
     */
    getState() {
        if (!this.progressComponent) return null;

        return {
            progress: this.progressComponent.progress,
            phase: this.progressComponent.currentPhase,
            isComplete: this.progressComponent.isComplete,
            hasError: this.progressComponent.hasError,
            uploadSpeed: this.progressComponent.uploadSpeed,
            estimatedTimeRemaining: this.progressComponent.estimatedTimeRemaining
        };
    }

    /**
     * Static method to create and integrate with SessionUploadStrategy
     */
    static integrateWithStrategy(progressComponent, strategy, file, uploadType = 'single') {
        const integration = new VideoUploadProgressIntegration(progressComponent);
        
        // Initialize with file info
        integration.initialize(file, uploadType);
        
        // Set up strategy callbacks
        strategy.setProgressCallback(integration.createProgressCallback());
        strategy.setStatusCallback(integration.createStatusCallback());
        
        return integration;
    }

    /**
     * Factory method to create integration from Alpine component
     */
    static fromAlpineComponent(alpineComponent) {
        return new VideoUploadProgressIntegration(alpineComponent);
    }

    /**
     * Create a complete upload flow with progress tracking
     */
    static async executeUploadWithProgress(strategy, file, progressContainer, options = {}) {
        // Find or create Alpine component
        let progressComponent = null;
        
        if (typeof progressContainer === 'string') {
            // Container selector provided
            const container = document.querySelector(progressContainer);
            if (container && container._x_dataStack) {
                progressComponent = container._x_dataStack[0];
            }
        } else if (progressContainer._x_dataStack) {
            // Direct container element provided
            progressComponent = progressContainer._x_dataStack[0];
        } else {
            // Assume it's already the Alpine component
            progressComponent = progressContainer;
        }

        if (!progressComponent) {
            throw new Error('Could not find Alpine.js progress component');
        }

        // Determine upload type based on file size
        const uploadType = file.size >= (strategy.config.maxSingleFileSize || 50 * 1024 * 1024) ? 'chunk' : 'single';
        
        // Create integration
        const integration = VideoUploadProgressIntegration.integrateWithStrategy(
            progressComponent, 
            strategy, 
            file, 
            uploadType
        );

        // Set up event listeners
        integration.setupEventListeners({
            onCancel: options.onCancel || (() => {
                console.log('Upload cancelled by user');
                if (strategy.cleanup) strategy.cleanup();
            }),
            onRetry: options.onRetry || (() => {
                console.log('Retrying upload...');
                return VideoUploadProgressIntegration.executeUploadWithProgress(
                    strategy, file, progressComponent, options
                );
            }),
            onComplete: options.onComplete || ((result) => {
                console.log('Upload completed successfully', result);
            }),
            onError: options.onError || ((error) => {
                console.error('Upload failed', error);
            })
        });

        // Show progress component
        integration.show();

        try {
            // Execute the upload
            const result = await strategy.execute(file);
            
            // Handle completion
            if (options.onComplete) {
                options.onComplete(result);
            }
            
            return { success: true, result, integration };
            
        } catch (error) {
            // Handle error
            integration.createErrorCallback()(error);
            
            if (options.onError) {
                options.onError(error);
            }
            
            return { success: false, error, integration };
        }
    }
}

/**
 * Global helper function for easy integration
 */
window.createVideoUploadProgress = function(progressContainer, callbacks = {}) {
    return VideoUploadProgressIntegration.fromAlpineComponent(progressContainer)
        .setupEventListeners(callbacks);
};

/**
 * Global helper for executing uploads with progress
 */
window.executeVideoUploadWithProgress = function(strategy, file, progressContainer, options = {}) {
    return VideoUploadProgressIntegration.executeUploadWithProgress(strategy, file, progressContainer, options);
};

export default VideoUploadProgressIntegration;