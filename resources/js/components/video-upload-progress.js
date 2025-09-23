/**
 * Video Upload Progress Alpine.js Component
 * Provides a modern UI for tracking video upload progress with phases, speed, and ETA
 */

export function videoUploadProgress() {
    return {
        // State management
        uploadState: {
            isActive: false,
            phase: 'single_upload', // single_upload, chunk_upload, video_processing, complete, error
            progress: 0,
            isComplete: false,
            hasError: false,
            errorMessage: ''
        },

        // Upload metrics for calculations
        uploadMetrics: {
            startTime: null,
            speed: 0, // bytes per second
            eta: 0, // seconds remaining
            uploaded: 0, // bytes uploaded
            lastProgress: 0,
            lastUpdateTime: null
        },

        // File information
        fileInfo: {
            name: '',
            size: 0,
            type: ''
        },

        // Phase configuration
        phases: [
            { key: 'upload', label: 'Upload' },
            { key: 'processing', label: 'Processing' },
            { key: 'complete', label: 'Complete' }
        ],

        // Phase status messages
        statusMessages: {
            single_upload: 'Preparing upload...',
            chunk_upload: 'Uploading video...',
            chunk_assembly: 'Assembling video chunks...',
            conversion: 'Converting video format...',
            metadata_extraction: 'Extracting metadata...',
            thumbnail_generation: 'Generating thumbnails...',
            video_processing: 'Processing video...',
            complete: 'Upload complete!',
            error: 'Upload failed'
        },

        init() {
            // Initialize component
            this.uploadMetrics.startTime = Date.now();
            this.uploadMetrics.lastUpdateTime = Date.now();
            
            // Listen for upload events
            this.$el.addEventListener('upload-cancelled', () => this.handleCancelUpload());
            this.$el.addEventListener('upload-retry', () => this.handleRetryUpload());
        },

        // Integration methods for SessionUploadStrategy
        startUpload(fileInfo) {
            this.uploadState.isActive = true;
            this.uploadState.phase = 'single_upload';
            this.uploadState.progress = 0;
            this.uploadState.isComplete = false;
            this.uploadState.hasError = false;
            this.uploadState.errorMessage = '';
            
            this.fileInfo = fileInfo;
            this.uploadMetrics.startTime = Date.now();
            this.uploadMetrics.lastUpdateTime = Date.now();
            this.uploadMetrics.uploaded = 0;
            this.uploadMetrics.lastProgress = 0;
            this.uploadMetrics.speed = 0;
            this.uploadMetrics.eta = 0;
        },

        updateProgress(phase, progress) {
            const now = Date.now();
            const timeDiff = (now - this.uploadMetrics.lastUpdateTime) / 1000; // seconds
            
            this.uploadState.phase = phase;
            
            // During processing phases, keep progress at 100% and ignore server progress percentage
            const isProcessingPhase = this.isProcessingPhase(phase);
            if (isProcessingPhase) {
                this.uploadState.progress = 100;
            } else {
                this.uploadState.progress = Math.min(100, Math.max(0, progress));
            }
            
            // Calculate upload metrics only during upload phases
            if ((phase === 'chunk_upload' || phase === 'single_upload') && timeDiff > 0) {
                this.calculateUploadMetrics(progress, now, timeDiff);
            }
            
            this.uploadMetrics.lastProgress = progress;
            this.uploadMetrics.lastUpdateTime = now;
        },

        updateProgressWithServerData(phase, progress, serverData) {
            this.updateProgress(phase, progress);
            
            // Store server metrics if available during upload phases
            if (serverData && !this.isProcessingPhase(phase)) {
                if (serverData.uploadSpeed !== undefined) {
                    this.uploadMetrics.speed = serverData.uploadSpeed;
                }
                if (serverData.eta !== undefined) {
                    this.uploadMetrics.eta = serverData.eta;
                }
                if (serverData.bytesUploaded !== undefined) {
                    this.uploadMetrics.uploaded = serverData.bytesUploaded;
                }
            }
        },

        isProcessingPhase(phase) {
            return [
                'chunk_assembly',
                'conversion', 
                'metadata_extraction',
                'thumbnail_generation',
                'video_processing'
            ].includes(phase);
        },

        calculateUploadMetrics(progress, now, timeDiff) {
            // Only calculate during upload phase (0-50% of total progress)
            const uploadProgress = Math.min(50, progress); // Cap at 50% for upload phase
            const currentUploaded = (uploadProgress / 50) * this.fileInfo.size;
            const bytesUploaded = currentUploaded - this.uploadMetrics.uploaded;
            
            // Calculate speed (bytes per second)
            if (bytesUploaded > 0 && timeDiff > 0) {
                const currentSpeed = bytesUploaded / timeDiff;
                // Smooth the speed calculation with exponential moving average
                this.uploadMetrics.speed = this.uploadMetrics.speed === 0 
                    ? currentSpeed 
                    : (this.uploadMetrics.speed * 0.7) + (currentSpeed * 0.3);
            }
            
            // Calculate ETA for upload phase only
            if (progress < 50) {
                const remainingBytes = this.fileInfo.size - currentUploaded;
                this.uploadMetrics.eta = this.uploadMetrics.speed > 0 
                    ? remainingBytes / this.uploadMetrics.speed 
                    : 0;
            } else {
                // During processing, don't show ETA
                this.uploadMetrics.eta = 0;
            }
            
            this.uploadMetrics.uploaded = currentUploaded;
        },

        completeUpload() {
            this.uploadState.phase = 'complete';
            this.uploadState.progress = 100;
            this.uploadState.isComplete = true;
            this.uploadMetrics.eta = 0;
        },

        errorUpload(errorMessage) {
            this.uploadState.phase = 'error';
            this.uploadState.hasError = true;
            this.uploadState.errorMessage = errorMessage || 'An unexpected error occurred during upload.';
        },

        // UI helper methods
        getPhaseIconClasses(phaseKey) {
            const currentPhaseIndex = this.phases.findIndex(p => p.key === this.getCurrentPhaseKey());
            const phaseIndex = this.phases.findIndex(p => p.key === phaseKey);
            
            if (this.uploadState.hasError) {
                return phaseIndex <= currentPhaseIndex 
                    ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500';
            }
            
            if (phaseIndex < currentPhaseIndex || this.uploadState.isComplete) {
                return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400';
            } else if (phaseIndex === currentPhaseIndex) {
                return 'bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 ring-2 ring-sky-200 dark:ring-sky-800';
            } else {
                return 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500';
            }
        },

        getPhaseTextClasses(phaseKey) {
            const currentPhaseIndex = this.phases.findIndex(p => p.key === this.getCurrentPhaseKey());
            const phaseIndex = this.phases.findIndex(p => p.key === phaseKey);
            
            if (this.uploadState.hasError) {
                return phaseIndex <= currentPhaseIndex 
                    ? 'text-red-600 dark:text-red-400'
                    : 'text-zinc-400 dark:text-zinc-500';
            }
            
            if (phaseIndex <= currentPhaseIndex || this.uploadState.isComplete) {
                return 'text-zinc-900 dark:text-zinc-100';
            } else {
                return 'text-zinc-400 dark:text-zinc-500';
            }
        },

        getConnectorClasses(phaseKey) {
            const currentPhaseIndex = this.phases.findIndex(p => p.key === this.getCurrentPhaseKey());
            const phaseIndex = this.phases.findIndex(p => p.key === phaseKey);
            
            if (this.uploadState.hasError) {
                return phaseIndex < currentPhaseIndex 
                    ? 'bg-red-200 dark:bg-red-800'
                    : 'bg-zinc-200 dark:bg-zinc-700';
            }
            
            if (phaseIndex < currentPhaseIndex || this.uploadState.isComplete) {
                return 'bg-emerald-200 dark:bg-emerald-800';
            } else {
                return 'bg-zinc-200 dark:bg-zinc-700';
            }
        },

        getCurrentPhaseKey() {
            if (this.uploadState.phase === 'single_upload' || this.uploadState.phase === 'chunk_upload') {
                return 'upload';
            } else if (this.isProcessingPhase(this.uploadState.phase)) {
                return 'processing';
            } else if (this.uploadState.phase === 'complete') {
                return 'complete';
            }
            return 'upload';
        },

        getStatusMessage() {
            return this.statusMessages[this.uploadState.phase] || 'Processing...';
        },

        // Formatting utilities
        formatSpeed(bytesPerSecond) {
            if (bytesPerSecond === 0 || !isFinite(bytesPerSecond)) return '0 MB/s';
            
            const mbps = bytesPerSecond / (1024 * 1024);
            if (mbps >= 1) {
                return `${mbps.toFixed(1)} MB/s`;
            } else {
                const kbps = bytesPerSecond / 1024;
                return `${kbps.toFixed(1)} KB/s`;
            }
        },

        formatETA(seconds) {
            if (seconds === 0 || !isFinite(seconds)) return '--';
            
            if (seconds < 60) {
                return `${Math.ceil(seconds)}s`;
            } else if (seconds < 3600) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.ceil(seconds % 60);
                return `${minutes}m ${remainingSeconds}s`;
            } else {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                return `${hours}h ${minutes}m`;
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            const size = bytes / Math.pow(1024, i);
            
            return `${size.toFixed(i === 0 ? 0 : 1)} ${sizes[i]}`;
        },

        // Action methods
        cancelUpload() {
            if (confirm('Are you sure you want to cancel the upload?')) {
                this.uploadState.isActive = false;
                // Dispatch event for SessionUploadStrategy
                this.$dispatch('upload-cancelled');
            }
        },

        retryUpload() {
            this.uploadState.hasError = false;
            this.uploadState.errorMessage = '';
            this.uploadState.phase = 'single_upload';
            this.uploadState.progress = 0;
            
            // Reset metrics
            this.uploadMetrics.startTime = Date.now();
            this.uploadMetrics.lastUpdateTime = Date.now();
            this.uploadMetrics.speed = 0;
            this.uploadMetrics.eta = 0;
            this.uploadMetrics.uploaded = 0;
            this.uploadMetrics.lastProgress = 0;
            // Reset server metrics
            this.uploadMetrics.serverSpeed = null;
            this.uploadMetrics.serverEta = null;
            this.uploadMetrics.serverBytesUploaded = null;
            this.uploadMetrics.serverTotalBytes = null;
            this.uploadMetrics.useServerData = false;
            
            // Dispatch event for SessionUploadStrategy
            this.$dispatch('upload-retry');
        },

        closeModal() {
            this.uploadState.isActive = false;
            this.$dispatch('upload-complete');
        },

        // Event handlers
        handleCancelUpload() {
            console.log('Upload cancelled via event');
        },

        handleRetryUpload() {
            console.log('Upload retry via event');
        }
    }
}

// Make it globally available for Alpine
if (typeof window !== 'undefined') {
    window.videoUploadProgress = videoUploadProgress;
}