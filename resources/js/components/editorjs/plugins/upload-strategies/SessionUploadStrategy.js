/**
 * Session-based Upload Strategy for VideoUpload Plugin
 * Handles both single and chunked uploads using session polling
 */

export default class SessionUploadStrategy {
    constructor(config, t) {
        this.config = config;
        this.t = t;
        this.sessionId = null;
        this.pollingInterval = null;
        this.progressCallback = null;
        this.statusCallback = null;
        this.progressComponent = null;
        this.uploadStartTime = null;
        this.lastProgressUpdate = null;
        this.lastProgress = 0;
        this.lastChunksUploaded = 0;
    }

    /**
     * Set progress callback function
     */
    setProgressCallback(callback) {
        this.progressCallback = callback;
        return this;
    }

    /**
     * Set status callback function to update UI with user-friendly messages
     */
    setStatusCallback(callback) {
        this.statusCallback = callback;
        return this;
    }

    /**
     * Initialize progress component integration
     */
    initializeProgressComponent() {
        // Get reference to the Alpine component if it exists
        const progressElement = document.querySelector('[x-data*="videoUploadProgress"]');
        if (progressElement && window.Alpine) {
            this.progressComponent = window.Alpine.$data(progressElement);
        }
    }

    /**
     * Execute session-based file upload
     */
    async execute(file) {
        try {
            this.uploadStartTime = Date.now();
            this.lastProgressUpdate = Date.now();
            
            // Initialize progress UI
            this.initializeProgressComponent();
            if (this.progressComponent) {
                this.progressComponent.startUpload({
                    name: file.name,
                    size: file.size,
                    type: file.type
                });
            }
            
            // Step 1: Create upload session
            const sessionData = await this.createSession(file);
            this.sessionId = sessionData.data.session_id;
            
            this.logToBrowser('session', `Created upload session: ${this.sessionId} (${sessionData.data.upload_type})`, {
                upload_type: sessionData.data.upload_type,
                chunks_total: sessionData.data.chunks_total,
                file_name: file.name,
                file_size: file.size
            });
            
            // Step 2: Start actual upload based on type
            if (sessionData.data.upload_type === 'single') {
                await this.uploadSingle(file);
            } else {
                await this.uploadChunked(file, sessionData.data.chunks_total);
            }
            
            // Step 3: Start polling for status updates
            return await this.pollForCompletion();
            
        } catch (error) {
            this.handleUploadError(error);
            this.cleanup();
            throw error;
        }
    }

    /**
     * Create upload session
     */
    async createSession(file) {
        const formData = new FormData();
        formData.append('file_size', file.size.toString());
        formData.append('original_filename', file.name);
        formData.append('max_single_file_size', this.config.maxSingleFileSize.toString());
        
        // Calculate chunks if file is large
        if (file.size >= this.config.maxSingleFileSize) {
            const chunksTotal = Math.ceil(file.size / this.config.chunkSize);
            formData.append('chunks_total', chunksTotal.toString());
        }

        const response = await fetch(this.config.endpoints.createSession, {
            method: 'POST',
            body: formData,
            headers: this.config.additionalRequestHeaders
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Failed to create upload session');
        }

        return json;
    }

    /**
     * Upload single file
     */
    async uploadSingle(file) {
        const formData = new FormData();
        formData.append(this.config.field, file);
        formData.append('session_id', this.sessionId);

        // Signal editor is busy
        document.dispatchEvent(new CustomEvent('editor:busy'));

        const response = await fetch(this.config.endpoints.single, {
            method: 'POST',
            body: formData,
            headers: this.config.additionalRequestHeaders
        });

        if (!response.ok) {
            const json = await response.json();
            throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
        }
    }

    /**
     * Upload file in chunks
     */
    async uploadChunked(file, chunksTotal) {
        const chunkSize = this.config.chunkSize;
        
        // Signal editor is busy
        document.dispatchEvent(new CustomEvent('editor:busy'));

        for (let chunkIndex = 0; chunkIndex < chunksTotal; chunkIndex++) {
            const start = chunkIndex * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);

            const chunkFile = new File([chunk], file.name, { type: file.type });
            await this.uploadChunk(chunkFile, chunkIndex, chunksTotal);
        }
    }

    /**
     * Upload a single chunk
     */
    async uploadChunk(chunkFile, chunkIndex, totalChunks) {
        const formData = new FormData();
        formData.append(this.config.field, chunkFile);
        formData.append('session_id', this.sessionId);
        formData.append('fileName', chunkFile.name);
        formData.append('fileKey', this.sessionId); // Use session ID as file key for chunk uploads
        formData.append('chunk', chunkIndex.toString());
        formData.append('chunks', totalChunks.toString());
        
        // console.log('Uploading chunk with session_id:', this.sessionId, 'chunk:', chunkIndex, 'total:', totalChunks);

        const response = await fetch(this.config.endpoints.chunk, {
            method: 'POST',
            body: formData,
            headers: this.config.additionalRequestHeaders
        });

        if (!response.ok) {
            const json = await response.json();
            throw new Error(json.message || `Failed to upload chunk ${chunkIndex}`);
        }
    }

    /**
     * Poll for upload completion with user-friendly status updates
     */
    async pollForCompletion() {
        return new Promise((resolve, reject) => {
            let lastPhase = null;
            
            this.pollingInterval = setInterval(async () => {
                try {
                    const status = await this.getSessionStatus();
                    
                    // Log to Laravel browser logs for user visibility
        this.logToBrowser('polling', `Status: ${status.status}, Phase: ${status.phase}, Progress: ${status.data?.upload_progress || 0}%`, {
            chunks: status.data?.chunks_uploaded || 0,
            total: status.data?.chunks_total || 0,
            fileSize: status.data?.file_size || 0
        });
                    
                    // Update UI with user-friendly status message
                    if (status.phase !== lastPhase) {
                        this.updateUserStatus(status);
                        lastPhase = status.phase;
                    }
                    
                    // Update progress - this should trigger the modal updates
                    this.updateProgress(status);
                    
                    // Check for completion
                    if (status.status === 'completed' && status.phase === 'complete') {
                        this.cleanup();
                        resolve(this.buildVideoResponse(status.data));
                    } else if (status.status === 'failed') {
                        this.cleanup();
                        reject(new Error(status.data.error_message || 'Upload failed'));
                    }
                    
                } catch (error) {
                    console.error('Polling error:', error); // Debug log
                    this.cleanup();
                    reject(error);
                }
            }, 500); // Poll every 500ms
        });
    }

    /**
     * Get current session status
     */
    async getSessionStatus() {
        const url = `${this.config.endpoints.sessionStatus}/${this.sessionId}/status`;
        // console.log('Polling session status at:', url); // Debug log
        
        const response = await fetch(url, {
            method: 'GET',
            headers: this.config.additionalRequestHeaders
        });

        if (!response.ok) {
            console.error('Session status request failed:', response.status, response.statusText);
            throw new Error(`Failed to get session status: ${response.status}`);
        }

        const result = await response.json();
        // console.log('Session status response:', result); // Debug log
        return result;
    }

    /**
     * Update UI with user-friendly status messages
     */
    updateUserStatus(status) {
        if (!this.statusCallback) return;

        let message = '';
        switch (status.phase) {
            case 'single_upload':
                message = this.t.statusUploading || 'Uploading video...';
                break;
            case 'chunk_upload':
                message = this.t.statusUploadingChunks || 'Uploading video in chunks...';
                break;
            case 'video_processing':
                message = this.t.statusProcessing || 'Processing video...';
                break;
            case 'complete':
                message = this.t.statusComplete || 'Video uploaded successfully!';
                break;
            case 'error':
                message = this.t.statusError || 'Upload failed';
                break;
            default:
                message = this.t.statusUploading || 'Uploading...';
        }

        this.statusCallback(message, status.phase);
    }

    /**
     * Update progress based on current phase with enhanced calculations
     */
    updateProgress(status) {
        let progress = 0;
        const currentTime = Date.now();
        
        switch (status.phase) {
            case 'single_upload':
            case 'chunk_upload':
                // Show actual chunk-by-chunk progress (0-90% for upload phase)
                const chunksUploaded = status.data.chunks_uploaded || 0;
                const chunksTotal = status.data.chunks_total || 1;
                const chunkProgress = chunksTotal > 0 ? (chunksUploaded / chunksTotal) : 0;
                progress = Math.round(Math.min(chunkProgress * 90, 90));
                break;
            case 'video_processing':
                // Processing phase goes from 90% to 100%
                const processingProgress = status.data.processing_progress || 0;
                progress = Math.round(90 + (processingProgress * 0.1));
                break;
            case 'complete':
                progress = 100;
                break;
            case 'error':
                // Keep last known progress
                return; // Don't update progress on error
        }

        // Calculate upload speed and ETA for chunk uploads
        let uploadSpeed = null;
        let eta = null;
        
        if (status.phase === 'chunk_upload') {
            const timeElapsed = (currentTime - this.uploadStartTime) / 1000; // seconds
            const chunksUploaded = status.data.chunks_uploaded || 0;
            const chunksTotal = status.data.chunks_total || 1;
            const fileSize = status.data.file_size || 0;
            
            if (timeElapsed > 0 && chunksUploaded > 0) {
                // Calculate chunks per second
                const chunksPerSecond = chunksUploaded / timeElapsed;
                const remainingChunks = chunksTotal - chunksUploaded;
                
                // Estimate time remaining
                if (chunksPerSecond > 0 && remainingChunks > 0) {
                    eta = Math.round(remainingChunks / chunksPerSecond);
                }
                
                // Calculate actual data speed based on bytes uploaded
                const avgChunkSize = fileSize / chunksTotal;
                const bytesUploaded = chunksUploaded * avgChunkSize;
                const bytesPerSecond = bytesUploaded / timeElapsed;
                uploadSpeed = Math.round((bytesPerSecond / 1024 / 1024) * 100) / 100; // MB/s
                
                // Update last known values
                this.lastChunksUploaded = chunksUploaded;
            }
        }

        // Log detailed progress to Laravel browser logs
        this.logToBrowser('progress', `${progress}% (phase: ${status.phase}, chunks: ${status.data.chunks_uploaded}/${status.data.chunks_total}, speed: ${uploadSpeed}MB/s, ETA: ${eta}s)`, {
            progress,
            phase: status.phase,
            chunks_uploaded: status.data.chunks_uploaded,
            chunks_total: status.data.chunks_total,
            upload_speed: uploadSpeed,
            eta: eta,
            status_data: status.data
        });

        // Update legacy progress callback
        if (this.progressCallback && typeof this.progressCallback === 'function') {
            this.progressCallback(progress);
        }
        
        // Update status callback with enhanced info
        if (this.statusCallback && typeof this.statusCallback === 'function') {
            let message = '';
            switch (status.phase) {
                case 'single_upload':
                    message = 'Preparing upload...';
                    break;
                case 'chunk_upload':
                    let speedText = uploadSpeed && uploadSpeed > 0 ? ` (${uploadSpeed} MB/s)` : '';
                    let etaText = eta && eta > 0 ? ` - ${eta}s remaining` : '';
                    message = `Uploading video${speedText}${etaText}`;
                    break;
                case 'video_processing':
                    message = 'Processing video...';
                    break;
                case 'complete':
                    message = 'Upload complete!';
                    break;
                case 'error':
                    message = 'Upload failed';
                    break;
                default:
                    message = 'Processing...';
            }
            this.statusCallback(message, status.phase);
        }
        
        // Update modern progress component (Alpine.js)
        if (this.progressComponent) {
            this.updateProgressComponent(status, progress, { uploadSpeed, eta });
        }
        
        this.lastProgress = progress;
        this.lastProgressUpdate = currentTime;
    }

    /**
     * Build video response compatible with existing plugin
     */
    buildVideoResponse(data) {
        return {
            success: true,
            completed: true,
            file: {
                filename: data.final_filename,
                thumbnail: data.thumbnail_filename
            },
            width: data.video_metadata?.width || null,
            height: data.video_metadata?.height || null,
            duration: data.video_metadata?.duration || null,
            size: data.file_size,
            format: data.video_metadata?.format || null,
            aspect_ratio: data.video_metadata?.aspect_ratio || '16:9',
            aspect_ratio_data: data.video_metadata?.aspect_ratio_data || null,
            message: 'Video uploaded and processed successfully.',
            processing: false,
        };
    }

    /**
     * Update the progress component with session data
     */
    updateProgressComponent(status, progress, metrics = {}) {
        const phaseMapping = {
            'single_upload': 'single_upload',
            'chunk_upload': 'chunk_upload',
            'video_processing': 'video_processing',
            'complete': 'complete',
            'error': 'error'
        };

        const mappedPhase = phaseMapping[status.phase] || status.phase;
        
        // Update Alpine component if available
        if (this.progressComponent) {
            if (status.phase === 'error') {
                this.progressComponent.errorUpload(status.data?.error_message || 'Upload failed');
            } else if (status.phase === 'complete') {
                this.progressComponent.completeUpload();
            } else {
                // Pass additional metrics if the component supports them
                if (typeof this.progressComponent.updateProgressWithMetrics === 'function') {
                    this.progressComponent.updateProgressWithMetrics(mappedPhase, progress, {
                        uploadSpeed: metrics.uploadSpeed,
                        eta: metrics.eta,
                        chunksUploaded: status.data.chunks_uploaded,
                        chunksTotal: status.data.chunks_total
                    });
                } else {
                    this.progressComponent.updateProgress(mappedPhase, progress);
                }
            }
        }
    }

    /**
     * Handle upload errors
     */
    handleUploadError(error) {
        console.error('Upload failed:', error);
        
        if (this.progressComponent) {
            this.progressComponent.errorUpload(error.message || 'Upload failed');
        }
    }

    /**
     * Cancel upload process and cleanup session
     */
    async cancelUpload() {
        if (!this.sessionId) {
            console.log('No session to cancel');
            return;
        }

        try {
            // Call the cancel endpoint to cleanup server-side
            const cancelUrl = `/video-upload-sessions/${this.sessionId}/cancel`;
            const response = await fetch(cancelUrl, {
                method: 'DELETE',
                headers: this.config.additionalRequestHeaders
            });

            if (response.ok) {
                console.log('Upload session cancelled successfully');
            } else {
                console.warn('Failed to cancel session on server, but cleaning up locally');
            }
        } catch (error) {
            console.error('Error cancelling upload session:', error);
        } finally {
            // Always cleanup local state regardless of server response
            this.cleanup();
        }
    }

    /**
     * Retry upload process
     */
    retryUpload() {
        // Reset state and restart upload
        this.sessionId = null;
        this.uploadStartTime = Date.now();
        this.lastProgressUpdate = Date.now();
        
        console.log('Retrying upload...');
        // The actual retry will be handled by the calling component
    }

    /**
     * Cleanup polling and resources
     */
    cleanup() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        document.dispatchEvent(new CustomEvent('editor:free'));
    }

    /**
     * Log messages for debugging and user visibility
     */
    logToBrowser(type, message, data = {}) {
        // Use console.log with enhanced formatting for user visibility
        const sessionInfo = this.sessionId ? ` [Session: ${this.sessionId}]` : '';
        console.log(`[VideoUpload-${type}]${sessionInfo} ${message}`, data);
    }

    /**
     * Get strategy name for logging/debugging
     */
    getName() {
        return 'session';
    }
}