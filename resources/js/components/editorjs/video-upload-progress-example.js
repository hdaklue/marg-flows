/**
 * Example Integration: Video Upload Progress with EditorJS
 * 
 * This file demonstrates how to integrate the new video upload progress interface
 * with the existing EditorJS video upload plugin and SessionUploadStrategy.
 */

import VideoUploadEnhanced from './plugins/VideoUploadEnhanced.js';
import SessionUploadStrategy from './plugins/upload-strategies/SessionUploadStrategy.js';
import VideoUploadProgressIntegration from '../VideoUploadProgressIntegration.js';

// Make sure the CSS is loaded
import '../../../css/components/video-upload-progress.css';

/**
 * Enhanced EditorJS Configuration with Modern Video Upload Progress
 */
export function createEditorWithModernVideoUpload(containerId, config = {}) {
    // Default video upload configuration
    const videoUploadConfig = {
        endpoints: {
            createSession: '/api/video-upload/create-session',
            single: '/api/video-upload/single',
            chunk: '/api/video-upload/chunk',
            sessionStatus: '/api/video-upload/session',
            delete: '/api/video-upload/delete'
        },
        additionalRequestHeaders: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        field: 'video',
        types: 'video/*',
        chunkSize: 5 * 1024 * 1024,         // 5MB chunks
        maxFileSize: 100 * 1024 * 1024,     // 100MB max
        maxSingleFileSize: 50 * 1024 * 1024, // 50MB single upload threshold
        useChunkedUpload: true,
        secureFileEndpoint: '/secure-files',
        ...config.videoUpload
    };

    // Create EditorJS instance with enhanced video upload
    const editor = new EditorJS({
        holder: containerId,
        tools: {
            video: {
                class: VideoUploadEnhanced,
                config: videoUploadConfig
            },
            // ... other tools
        },
        ...config
    });

    return editor;
}

/**
 * Standalone Video Upload with Progress (outside EditorJS)
 */
export async function uploadVideoWithProgress(file, progressContainerSelector, config = {}) {
    // Default configuration
    const uploadConfig = {
        endpoints: {
            createSession: '/api/video-upload/create-session',
            single: '/api/video-upload/single',
            chunk: '/api/video-upload/chunk',
            sessionStatus: '/api/video-upload/session'
        },
        additionalRequestHeaders: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        field: 'video',
        chunkSize: 5 * 1024 * 1024,
        maxFileSize: 100 * 1024 * 1024,
        maxSingleFileSize: 50 * 1024 * 1024,
        ...config
    };

    // Create upload strategy
    const strategy = new SessionUploadStrategy(uploadConfig, {
        statusUploading: 'Uploading video...',
        statusUploadingChunks: 'Uploading video in chunks...',
        statusProcessing: 'Processing video...',
        statusComplete: 'Video uploaded successfully!',
        statusError: 'Upload failed'
    });

    // Find progress container
    const progressContainer = document.querySelector(progressContainerSelector);
    if (!progressContainer) {
        throw new Error(`Progress container not found: ${progressContainerSelector}`);
    }

    // Execute upload with progress
    try {
        const result = await VideoUploadProgressIntegration.executeUploadWithProgress(
            strategy,
            file,
            progressContainer,
            {
                onCancel: () => {
                    console.log('Upload cancelled by user');
                },
                onRetry: () => {
                    console.log('Retrying upload...');
                },
                onComplete: (result) => {
                    console.log('Upload completed successfully:', result);
                    
                    // Example: Update UI with uploaded video info
                    if (result.file && result.file.filename) {
                        displayUploadedVideo(result);
                    }
                },
                onError: (error) => {
                    console.error('Upload failed:', error);
                    
                    // Example: Show user-friendly error
                    showErrorNotification('Upload failed: ' + error.message);
                }
            }
        );

        return result;
    } catch (error) {
        console.error('Upload initialization failed:', error);
        throw error;
    }
}

/**
 * Manual Progress Component Integration (for custom implementations)
 */
export function createManualVideoUploadProgress(file, callbacks = {}) {
    // Create progress container
    const progressContainer = document.createElement('div');
    progressContainer.innerHTML = `
        <div 
            x-data="videoUploadProgress()" 
            x-ref="progressContainer"
            class="video-upload-progress"
            x-init="setFileInfo('${file.name}', ${file.size})"
        >
            <!-- Include the full progress component HTML here -->
            <!-- ... (same as in the Blade component) ... -->
        </div>
    `;

    // Initialize Alpine.js
    if (window.Alpine) {
        window.Alpine.initTree(progressContainer);
    }

    // Get Alpine component
    const alpineComponent = progressContainer.querySelector('[x-data]')._x_dataStack[0];
    
    // Create integration
    const integration = VideoUploadProgressIntegration.fromAlpineComponent(alpineComponent);
    integration.setupEventListeners(callbacks);

    return {
        container: progressContainer,
        integration: integration,
        component: alpineComponent
    };
}

/**
 * Example: Upload Multiple Videos with Individual Progress Tracking
 */
export async function uploadMultipleVideos(files, containerSelector, config = {}) {
    const container = document.querySelector(containerSelector);
    if (!container) {
        throw new Error(`Container not found: ${containerSelector}`);
    }

    const results = [];
    
    for (const file of files) {
        // Create individual progress component for each file
        const { container: progressContainer, integration } = createManualVideoUploadProgress(file, {
            onComplete: (result) => {
                console.log(`Completed upload for ${file.name}:`, result);
                results.push({ file: file.name, success: true, result });
            },
            onError: (error) => {
                console.error(`Failed upload for ${file.name}:`, error);
                results.push({ file: file.name, success: false, error });
            }
        });

        // Add to container
        container.appendChild(progressContainer);

        // Create strategy and start upload
        const strategy = new SessionUploadStrategy(config, {});
        
        try {
            await VideoUploadProgressIntegration.executeUploadWithProgress(
                strategy,
                file,
                integration.progressComponent
            );
        } catch (error) {
            console.error(`Upload failed for ${file.name}:`, error);
        }
    }

    return results;
}

/**
 * Example Usage in a typical application
 */
export function initializeVideoUploadFeatures() {
    // 1. Initialize EditorJS with enhanced video upload
    const editor = createEditorWithModernVideoUpload('editor-container', {
        placeholder: 'Start writing your document...',
        videoUpload: {
            // Custom configuration for this instance
            maxFileSize: 50 * 1024 * 1024, // 50MB for this editor
        }
    });

    // 2. Set up drag-and-drop upload area
    const dropZone = document.getElementById('video-drop-zone');
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', async (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');

            const files = Array.from(e.dataTransfer.files).filter(file => 
                file.type.startsWith('video/')
            );

            if (files.length > 0) {
                // Show progress for the first video file
                const progressContainer = document.getElementById('upload-progress-container');
                if (progressContainer) {
                    await uploadVideoWithProgress(files[0], '#upload-progress-container');
                }
            }
        });
    }

    // 3. Set up file input
    const fileInput = document.getElementById('video-file-input');
    if (fileInput) {
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (file && file.type.startsWith('video/')) {
                const progressContainer = document.getElementById('upload-progress-container');
                if (progressContainer) {
                    await uploadVideoWithProgress(file, '#upload-progress-container');
                }
            }
        });
    }

    return editor;
}

/**
 * Utility Functions
 */
function displayUploadedVideo(result) {
    // Example: Create a simple video display
    const videoContainer = document.getElementById('uploaded-videos');
    if (videoContainer && result.file) {
        const videoElement = document.createElement('div');
        videoElement.innerHTML = `
            <div class="uploaded-video-item">
                <video controls width="300">
                    <source src="/secure-files/videos/${result.file.filename}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <p><strong>File:</strong> ${result.file.filename}</p>
                <p><strong>Size:</strong> ${formatFileSize(result.size || 0)}</p>
                ${result.duration ? `<p><strong>Duration:</strong> ${formatDuration(result.duration)}</p>` : ''}
            </div>
        `;
        videoContainer.appendChild(videoElement);
    }
}

function showErrorNotification(message) {
    // Example: Simple error notification
    const notification = document.createElement('div');
    notification.className = 'error-notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        z-index: 9999;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Export for global usage
window.VideoUploadProgress = {
    createEditor: createEditorWithModernVideoUpload,
    uploadWithProgress: uploadVideoWithProgress,
    createManualProgress: createManualVideoUploadProgress,
    uploadMultiple: uploadMultipleVideos,
    initialize: initializeVideoUploadFeatures
};