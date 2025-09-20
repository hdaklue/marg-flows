/**
 * Enhanced Video Upload Plugin for EditorJS with Modern Progress Interface
 * Extends the existing VideoUpload plugin with our new progress component
 */

import VideoUpload from './video-upload.js';
import VideoUploadProgressIntegration from '../../VideoUploadProgressIntegration.js';

class VideoUploadEnhanced extends VideoUpload {
    constructor(config) {
        super(config);
        
        // Progress integration
        this.progressIntegration = null;
        this.progressContainer = null;
    }

    /**
     * Enhanced progress display using our new component
     */
    showInlineProgress() {
        if (!this.wrapper || !this.uploadProgress) return;

        // Hide upload container
        if (this.uploadContainer) {
            this.uploadContainer.style.display = 'none';
        }

        // Remove existing progress
        const existingProgress = this.wrapper.querySelector('.ce-video-upload__uploading');
        if (existingProgress) {
            existingProgress.remove();
        }

        // Remove existing modern progress
        const existingModernProgress = this.wrapper.querySelector('.video-upload-progress');
        if (existingModernProgress) {
            existingModernProgress.remove();
        }

        // Create modern progress container
        const progressContainer = document.createElement('div');
        progressContainer.innerHTML = `
            <div 
                x-data="videoUploadProgress()" 
                x-ref="progressContainer"
                class="video-upload-progress"
                x-cloak
            >
                <!-- Progress Card -->
                <div class="video-upload-progress__card">
                    <!-- Header with File Info -->
                    <div class="video-upload-progress__header">
                        <div class="video-upload-progress__file-info">
                            <div class="video-upload-progress__file-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                                </svg>
                            </div>
                            <div class="video-upload-progress__file-details">
                                <div class="video-upload-progress__file-name" x-text="fileName"></div>
                                <div class="video-upload-progress__file-size" x-text="formatFileSize(fileSize)"></div>
                            </div>
                        </div>
                        
                        <!-- Cancel Button -->
                        <button 
                            @click="cancelUpload()"
                            x-show="!isComplete && !hasError"
                            class="video-upload-progress__cancel-btn"
                            type="button"
                            title="Cancel upload"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="video-upload-progress__progress-container">
                        <div class="video-upload-progress__progress-bar">
                            <div 
                                class="video-upload-progress__progress-fill"
                                :style="'width: ' + progress + '%'"
                                :class="{
                                    'progress-processing': currentPhase === 'video_processing',
                                    'progress-complete': isComplete,
                                    'progress-error': hasError
                                }"
                            ></div>
                        </div>
                        <div class="video-upload-progress__progress-percentage" x-text="progress + '%'"></div>
                    </div>

                    <!-- Phase Indicators -->
                    <div class="video-upload-progress__phases">
                        <!-- Single Upload Phase -->
                        <div 
                            class="video-upload-progress__phase"
                            :class="{
                                'phase-active': currentPhase === 'single_upload',
                                'phase-complete': phaseComplete('single_upload'),
                                'phase-error': hasError && currentPhase === 'single_upload'
                            }"
                            x-show="uploadType === 'single'"
                        >
                            <div class="video-upload-progress__phase-icon">
                                <template x-if="phaseComplete('single_upload')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                    </svg>
                                </template>
                                <template x-if="!phaseComplete('single_upload')">
                                    <div 
                                        class="video-upload-progress__spinner" 
                                        x-show="currentPhase === 'single_upload'"
                                    ></div>
                                    <div 
                                        class="video-upload-progress__phase-number" 
                                        x-show="currentPhase !== 'single_upload'"
                                        x-text="1"
                                    ></div>
                                </template>
                            </div>
                            <span class="video-upload-progress__phase-label">Upload</span>
                        </div>

                        <!-- Chunk Upload Phase -->
                        <div 
                            class="video-upload-progress__phase"
                            :class="{
                                'phase-active': currentPhase === 'chunk_upload',
                                'phase-complete': phaseComplete('chunk_upload'),
                                'phase-error': hasError && currentPhase === 'chunk_upload'
                            }"
                            x-show="uploadType === 'chunk'"
                        >
                            <div class="video-upload-progress__phase-icon">
                                <template x-if="phaseComplete('chunk_upload')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                    </svg>
                                </template>
                                <template x-if="!phaseComplete('chunk_upload')">
                                    <div 
                                        class="video-upload-progress__spinner" 
                                        x-show="currentPhase === 'chunk_upload'"
                                    ></div>
                                    <div 
                                        class="video-upload-progress__phase-number" 
                                        x-show="currentPhase !== 'chunk_upload'"
                                        x-text="1"
                                    ></div>
                                </template>
                            </div>
                            <span class="video-upload-progress__phase-label">Upload Chunks</span>
                        </div>

                        <!-- Processing Phase -->
                        <div 
                            class="video-upload-progress__phase"
                            :class="{
                                'phase-active': currentPhase === 'video_processing',
                                'phase-complete': phaseComplete('video_processing'),
                                'phase-error': hasError && currentPhase === 'video_processing'
                            }"
                        >
                            <div class="video-upload-progress__phase-icon">
                                <template x-if="phaseComplete('video_processing')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                    </svg>
                                </template>
                                <template x-if="!phaseComplete('video_processing')">
                                    <div 
                                        class="video-upload-progress__spinner" 
                                        x-show="currentPhase === 'video_processing'"
                                    ></div>
                                    <div 
                                        class="video-upload-progress__phase-number" 
                                        x-show="currentPhase !== 'video_processing'"
                                        x-text="2"
                                    ></div>
                                </template>
                            </div>
                            <span class="video-upload-progress__phase-label">Processing</span>
                        </div>

                        <!-- Complete Phase -->
                        <div 
                            class="video-upload-progress__phase"
                            :class="{
                                'phase-active': currentPhase === 'complete',
                                'phase-complete': phaseComplete('complete')
                            }"
                        >
                            <div class="video-upload-progress__phase-icon">
                                <template x-if="phaseComplete('complete')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                    </svg>
                                </template>
                                <template x-if="!phaseComplete('complete')">
                                    <div class="video-upload-progress__phase-number" x-text="3"></div>
                                </template>
                            </div>
                            <span class="video-upload-progress__phase-label">Complete</span>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div class="video-upload-progress__status">
                        <div 
                            class="video-upload-progress__status-message"
                            :class="{
                                'status-error': hasError,
                                'status-complete': isComplete
                            }"
                            x-text="statusMessage"
                        ></div>
                    </div>

                    <!-- Upload Metrics -->
                    <div 
                        class="video-upload-progress__metrics"
                        x-show="!hasError && showMetrics"
                    >
                        <div class="video-upload-progress__metric">
                            <span class="video-upload-progress__metric-label">Speed:</span>
                            <span class="video-upload-progress__metric-value" x-text="formatSpeed(uploadSpeed)"></span>
                        </div>
                        <div class="video-upload-progress__metric" x-show="estimatedTimeRemaining > 0">
                            <span class="video-upload-progress__metric-label">ETA:</span>
                            <span class="video-upload-progress__metric-value" x-text="formatTimeRemaining(estimatedTimeRemaining)"></span>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div 
                        class="video-upload-progress__error"
                        x-show="hasError"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                    >
                        <div class="video-upload-progress__error-content">
                            <div class="video-upload-progress__error-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                    <path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="video-upload-progress__error-details">
                                <div class="video-upload-progress__error-title">Upload Failed</div>
                                <div class="video-upload-progress__error-message" x-text="errorMessage"></div>
                            </div>
                        </div>
                        <div class="video-upload-progress__error-actions">
                            <button 
                                @click="retryUpload()"
                                class="video-upload-progress__retry-btn"
                                type="button"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M1 4v6h6M23 20v-6h-6"/>
                                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                                </svg>
                                Retry Upload
                            </button>
                            <button 
                                @click="dismissError()"
                                class="video-upload-progress__dismiss-btn"
                                type="button"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>

                    <!-- Success State -->
                    <div 
                        class="video-upload-progress__success"
                        x-show="isComplete && !hasError"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                    >
                        <div class="video-upload-progress__success-content">
                            <div class="video-upload-progress__success-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                </svg>
                            </div>
                            <div class="video-upload-progress__success-message">
                                Video uploaded and processed successfully!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.wrapper.appendChild(progressContainer);
        this.progressContainer = progressContainer;

        // Initialize Alpine.js if not already done
        if (window.Alpine && !progressContainer.querySelector('[x-data]')._x_dataStack) {
            window.Alpine.initTree(progressContainer);
        }
    }

    /**
     * Enhanced upload handling with modern progress integration
     */
    async performUpload(file) {
        // Get the progress component
        const progressElement = this.progressContainer?.querySelector('[x-data]');
        let progressComponent = null;
        
        if (progressElement && progressElement._x_dataStack) {
            progressComponent = progressElement._x_dataStack[0];
        }

        // Determine upload type
        const uploadType = file.size >= this.config.maxSingleFileSize ? 'chunk' : 'single';

        // Set up progress integration if component is available
        if (progressComponent) {
            this.progressIntegration = VideoUploadProgressIntegration.integrateWithStrategy(
                progressComponent,
                this.sessionUploadStrategy,
                file,
                uploadType
            );

            // Set up event callbacks
            this.progressIntegration.setupEventListeners({
                onCancel: () => {
                    this.sessionUploadStrategy.cleanup();
                    this.uploading = false;
                    this.hideInlineProgress();
                    this.showUploadContainer();
                },
                onRetry: () => {
                    return this.performUpload(file);
                },
                onComplete: (result) => {
                    // Auto-hide progress after a delay
                    setTimeout(() => {
                        this.hideInlineProgress();
                    }, 2000);
                },
                onError: (error) => {
                    console.error('Upload error:', error);
                }
            });
        } else {
            // Fallback to original progress callbacks
            this.sessionUploadStrategy.setProgressCallback((progress) => {
                this.updateUploadProgress(progress);
            });

            this.sessionUploadStrategy.setStatusCallback((message, phase) => {
                this.updateUploadStatus(message, phase);
            });
        }

        console.log(`Using ${this.sessionUploadStrategy.getName()} upload strategy for file: ${file.name} (${Math.round(file.size / (1024 * 1024))}MB)`);
        
        return await this.sessionUploadStrategy.execute(file);
    }

    /**
     * Enhanced progress hiding
     */
    hideInlineProgress() {
        if (this.wrapper) {
            // Hide old-style progress
            const uploadingContainer = this.wrapper.querySelector('.ce-video-upload__uploading');
            if (uploadingContainer) {
                uploadingContainer.remove();
            }

            // Hide modern progress
            const modernProgress = this.wrapper.querySelector('.video-upload-progress');
            if (modernProgress) {
                modernProgress.remove();
            }
        }
        
        this.progressContainer = null;
        this.progressIntegration = null;
    }

    /**
     * Enhanced completion handling
     */
    completeUpload() {
        // Call parent method
        super.completeUpload();

        // If we have modern progress integration, let it handle the completion
        if (this.progressIntegration) {
            // The integration will auto-hide after the success message
            return;
        }
    }

    /**
     * Enhanced cleanup
     */
    destroy() {
        // Clean up progress integration
        if (this.progressIntegration) {
            this.progressIntegration.hide();
            this.progressIntegration = null;
        }

        if (this.progressContainer) {
            this.progressContainer.remove();
            this.progressContainer = null;
        }

        // Call parent cleanup
        super.destroy();
    }
}

export default VideoUploadEnhanced;