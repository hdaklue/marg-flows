/**
 * VideoUpload Plugin for EditorJS
 * Mimics ResizableImage structure for video file uploads with Video.js rendering
 */

// Import Video.js for rendering
import videojs from 'video.js';
import 'videojs-youtube';

// Import the plugin styles
import '../../../../css/components/editorjs/video-upload.css';

class VideoUpload {
    static get toolbox() {
        return {
            title: 'Upload Video',
            icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
            </svg>`
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get enableLineBreaks() {
        return true;
    }

    static get tunes() {
        return [];
    }

    constructor({ data, config, api, readOnly, block }) {
        this.api = api;
        this.blockAPI = block;
        this.readOnly = readOnly;
        this.config = config || {};
        this.data = data || {};

        // Initialize single file data
        if (!this.data.file) {
            this.data.file = null;
        }

        // Initialize state flags
        this.uploading = false;
        this.wrapper = null;
        this.fileInput = null;
        this.uploadContainer = null;
        this.eventHandlers = null;
        this.currentVideoId = null;

        // Default configuration
        this.defaultConfig = {
            endpoints: {
                byFile: '/upload-video',
                delete: '/delete-video'
            },
            additionalRequestHeaders: {},
            field: 'video',
            types: 'video/*',
            captionPlaceholder: 'Enter video caption...',
            buttonContent: 'Select a video',
            uploader: null,
            actions: []
        };

        this.config = Object.assign(this.defaultConfig, this.config);

        // Bind methods
        this.onUpload = this.onUpload.bind(this);
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('video-upload');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Add drag-drop functionality to the entire block wrapper
        this.setupBlockDragDrop(wrapper);

        // Check if we have a video
        const hasVideo = this.data.file && this.data.file.url && this.data.file.url.trim() !== '';

        if (hasVideo) {
            // Show the video player
            this.createVideoElement(wrapper);
        } else {
            // No video, show upload interface
            this.createUploadInterface(wrapper);
        }

        return wrapper;
    }

    setupBlockDragDrop(wrapper) {
        if (this.readOnly) {
            return;
        }

        // Block-level drag and drop handlers
        const handleBlockDragOver = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.add('video-upload--dragover');
        };

        const handleBlockDragLeave = (e) => {
            e.preventDefault();
            e.stopPropagation();
            // Only remove class if leaving the wrapper entirely
            if (!wrapper.contains(e.relatedTarget)) {
                wrapper.classList.remove('video-upload--dragover');
            }
        };

        const handleBlockDrop = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.remove('video-upload--dragover');

            if (!this.readOnly && !this.uploading && e.dataTransfer.files.length) {
                const files = Array.from(e.dataTransfer.files).filter(file =>
                    file.type.startsWith('video/')
                );
                if (files.length > 0) {
                    this.handleUpload(files);
                }
            }
        };

        // Add event listeners to the wrapper
        wrapper.addEventListener('dragover', handleBlockDragOver);
        wrapper.addEventListener('dragleave', handleBlockDragLeave);
        wrapper.addEventListener('drop', handleBlockDrop);

        // Store handlers for cleanup
        this.blockDragHandlers = {
            handleBlockDragOver,
            handleBlockDragLeave,
            handleBlockDrop
        };
    }

    createUploadInterface(wrapper) {
        // Don't create upload interface in readonly mode
        if (this.readOnly) {
            return;
        }

        const uploadContainer = document.createElement('div');
        uploadContainer.classList.add('video-upload__upload-container');

        // Create unique ID for the file input
        const inputId = `video-upload-input-${Math.random().toString(36).substr(2, 9)}`;

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = this.config.types;
        fileInput.multiple = true; // Enable multiple file selection
        fileInput.classList.add('video-upload__file-input');
        fileInput.id = inputId;

        // Create label that triggers the file input
        const uploadLabel = document.createElement('label');
        uploadLabel.setAttribute('for', inputId);
        uploadLabel.classList.add('video-upload__upload-button');
        uploadLabel.innerHTML = `
            <div class="video-upload__upload-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                </svg>
            </div>
            <div class="video-upload__upload-text">${this.config.buttonContent}</div>
        `;

        // Store references for cleanup
        this.fileInput = fileInput;
        this.uploadContainer = uploadContainer;
        this.uploadLabel = uploadLabel;

        const handleFileChange = (e) => {
            e.preventDefault();
            const files = Array.from(e.target.files);
            if (files.length > 0 && !this.uploading) {
                this.handleUpload(files);
            }
            // Clear input to allow same file selection
            e.target.value = '';
        };

        const handleLabelClick = (e) => {
            e.preventDefault();
            if (!this.readOnly && !this.uploading) {
                fileInput.click();
            }
        };

        // Attach event listeners
        fileInput.addEventListener('change', handleFileChange);
        uploadLabel.addEventListener('click', handleLabelClick);

        // Store event handlers for cleanup
        this.eventHandlers = {
            handleFileChange,
            handleLabelClick
        };

        uploadContainer.appendChild(uploadLabel);
        uploadContainer.appendChild(fileInput);
        wrapper.appendChild(uploadContainer);
    }

    createVideoElement(wrapper) {
        const videoContainer = document.createElement('div');
        videoContainer.classList.add('ce-video-upload__container');

        // Create thumbnail video element (no controls, just for preview)
        const thumbnail = document.createElement('video');
        thumbnail.classList.add('ce-video-upload__thumbnail');
        thumbnail.preload = 'metadata';
        thumbnail.muted = true;
        thumbnail.style.width = '200px';
        thumbnail.style.height = '120px';
        thumbnail.style.objectFit = 'cover';
        thumbnail.style.cursor = 'pointer';
        thumbnail.style.borderRadius = '8px';

        // Set video source
        const source = document.createElement('source');
        source.src = this.data.file.url;
        source.type = this.getVideoMimeType(this.data.file.url);
        thumbnail.appendChild(source);

        // Add click handler to open modal
        thumbnail.addEventListener('click', () => this.openModal());

        // Add play icon overlay
        const playIcon = document.createElement('div');
        playIcon.classList.add('ce-video-upload__play-icon');
        playIcon.innerHTML = `
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" fill="rgba(0,0,0,0.7)"/>
                <path d="M10 8l6 4-6 4V8z" fill="white"/>
            </svg>
        `;
        playIcon.style.position = 'absolute';
        playIcon.style.top = '50%';
        playIcon.style.left = '50%';
        playIcon.style.transform = 'translate(-50%, -50%)';
        playIcon.style.cursor = 'pointer';
        playIcon.addEventListener('click', () => this.openModal());

        // Wrapper for thumbnail and play icon
        const thumbnailWrapper = document.createElement('div');
        thumbnailWrapper.style.position = 'relative';
        thumbnailWrapper.style.display = 'inline-block';
        thumbnailWrapper.appendChild(thumbnail);
        thumbnailWrapper.appendChild(playIcon);

        // Add delete button if not readonly
        if (!this.readOnly) {
            const deleteBtn = document.createElement('button');
            deleteBtn.classList.add('ce-video-upload__delete-btn');
            deleteBtn.innerHTML = `
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            `;
            deleteBtn.addEventListener('click', () => this.deleteVideo());
            thumbnailWrapper.appendChild(deleteBtn);
        }

        videoContainer.appendChild(thumbnailWrapper);

        // Caption input
        const captionInput = document.createElement('input');
        captionInput.classList.add('ce-video-upload__caption');
        captionInput.placeholder = this.config.captionPlaceholder || 'Enter video caption...';
        captionInput.value = this.data.caption || '';
        captionInput.readOnly = this.readOnly;

        captionInput.addEventListener('input', () => {
            this.data.caption = captionInput.value;
        });

        wrapper.appendChild(videoContainer);
        wrapper.appendChild(captionInput);
    }

    deleteVideo() {
        if (this.readOnly) return;

        // Delete from server if needed
        if (this.data.file && this.data.file.url) {
            this.handleDeleteFile(this.data.file.url);
        }

        // Clear the data
        this.data.file = null;
        this.data.caption = '';

        // Re-render to show upload interface
        this.wrapper.innerHTML = '';
        this.createUploadInterface(this.wrapper);
    }

    renderGallery() {
        // Remove existing gallery if any
        const existingGallery = this.wrapper.querySelector('.video-upload__gallery');
        if (existingGallery) {
            existingGallery.remove();
        }

        // Don't render gallery if no files
        if (!this.data.files || this.data.files.length === 0) {
            if (!this.readOnly) {
                this.showUploadContainer();
            }
            return;
        }

        // Create gallery container
        const galleryContainer = document.createElement('div');
        galleryContainer.classList.add('video-upload__gallery');

        // Create thumbnail grid
        const thumbnailGrid = document.createElement('div');
        thumbnailGrid.classList.add('video-upload__thumbnail-grid');

        this.data.files.forEach((fileData, index) => {
            const thumbnailItem = this.createThumbnail(fileData, index);
            thumbnailGrid.appendChild(thumbnailItem);
        });

        // Add "Add more" button (only if not readonly)
        const addMoreButton = this.createAddMoreButton();
        if (addMoreButton) {
            thumbnailGrid.appendChild(addMoreButton);
        }

        galleryContainer.appendChild(thumbnailGrid);

        // Add caption input or display caption text
        const captionElement = this.createCaptionInput();
        if (captionElement) {
            galleryContainer.appendChild(captionElement);
        }

        // Insert gallery after upload container
        this.wrapper.appendChild(galleryContainer);
    }

    createThumbnail(fileData, index) {
        const thumbnail = document.createElement('div');
        thumbnail.classList.add('video-upload__thumbnail');

        // Create video preview element
        const video = document.createElement('video');
        video.src = fileData.url;
        video.classList.add('video-upload__thumbnail-video');
        video.style.margin = '0';
        video.preload = 'metadata';
        video.muted = true; // Required for autoplay

        // Add click handler to open modal
        video.addEventListener('click', () => this.openModal(index));

        // Add remove button if not readonly
        if (!this.readOnly) {
            const removeBtn = document.createElement('button');
            removeBtn.classList.add('video-upload__thumbnail-remove');
            removeBtn.innerHTML = `
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            `;
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleDelete(index);
            });
            thumbnail.appendChild(removeBtn);
        }

        thumbnail.appendChild(video);
        return thumbnail;
    }

    createAddMoreButton() {
        // Don't create add more button in readonly mode
        if (this.readOnly) {
            return null;
        }

        const addButton = document.createElement('div');
        addButton.classList.add('video-upload__add-more');

        addButton.innerHTML = `
            <div class="video-upload__add-more-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="video-upload__add-more-text">Add more</span>
        `;

        addButton.addEventListener('click', () => this.openFileSelection());

        return addButton;
    }

    createCaptionInput() {
        if (this.readOnly) {
            // In readonly mode, display caption as text if it exists
            if (this.data.caption && this.data.caption.trim() !== '') {
                const captionText = document.createElement('div');
                captionText.classList.add('video-upload__caption', 'video-upload__caption--readonly');
                captionText.textContent = this.data.caption;
                return captionText;
            }
            return null;
        }

        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.placeholder = 'Add a caption for this video gallery...';
        captionInput.classList.add('video-upload__caption');
        captionInput.value = this.data.caption || '';

        captionInput.addEventListener('input', (e) => {
            this.data.caption = e.target.value;
        });

        return captionInput;
    }

    openModal() {
        if (!this.data.file || !this.data.file.url) return;

        // Create modal overlay
        const modal = document.createElement('div');
        modal.classList.add('ce-video-upload__modal');

        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.classList.add('ce-video-upload__modal-content');

        // Get aspect ratio from server-calculated data
        const aspectRatio = (this.data.file && this.data.file.aspect_ratio)
            ? this.data.file.aspect_ratio
            : '16:9';

        // Create responsive video container with proper aspect ratio
        const aspectRatioContainer = document.createElement('div');
        const [width, height] = aspectRatio.split(':');
        const aspectRatioValue = parseFloat(width) / parseFloat(height);
        
        // Calculate responsive dimensions
        const maxWidth = Math.min(window.innerWidth * 0.9, window.innerHeight * 0.8 * aspectRatioValue);
        const maxHeight = Math.min(window.innerHeight * 0.8, window.innerWidth * 0.9 / aspectRatioValue);
        
        // Apply responsive dimensions directly via style
        aspectRatioContainer.style.cssText = `
            position: relative;
            width: ${maxWidth}px;
            height: ${maxHeight}px;
            margin: 0 auto;
            aspect-ratio: ${width} / ${height};
        `;
        
        // Fallback for browsers that don't support aspect-ratio
        if (!CSS.supports('aspect-ratio', '1')) {
            const paddingBottom = (parseFloat(height) / parseFloat(width) * 100).toFixed(2);
            aspectRatioContainer.style.paddingBottom = `${paddingBottom}%`;
            aspectRatioContainer.style.height = '0';
        }

        // Create Video.js video element for modal
        const modalVideo = document.createElement('video');
        modalVideo.classList.add('video-js', 'vjs-default-skin');
        
        // Position video to fill the aspect ratio container
        modalVideo.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        `;

        modalVideo.controls = true;
        modalVideo.preload = 'metadata';

        const uniqueId = `modal-video-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        modalVideo.id = uniqueId;

        console.log('Video element classes:', modalVideo.className);
        console.log('Aspect ratio container classes:', aspectRatioContainer.className);
        console.log('Video element ID:', uniqueId);

        // Set video source
        const source = document.createElement('source');
        source.src = this.data.file.url;
        source.type = this.getVideoMimeType(this.data.file.url);
        modalVideo.appendChild(source);

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.classList.add('ce-video-upload__modal-close');
        closeBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        `;

        // Create delete button (only if not readonly)
        let deleteBtn;
        if (!this.readOnly) {
            deleteBtn = document.createElement('button');
            deleteBtn.classList.add('video-upload__modal-delete');
            deleteBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c0-1 1-2 2-2v2m-6 5v6m4-6v6"/>
                </svg>
            `;
        }

        // No navigation needed for single video

        // Nest video inside aspect ratio container
        aspectRatioContainer.appendChild(modalVideo);
        
        // Assemble modal
        modalContent.appendChild(aspectRatioContainer);
        modalContent.appendChild(closeBtn);
        if (deleteBtn) {
            modalContent.appendChild(deleteBtn);
        }
        modal.appendChild(modalContent);

        // Initialize Video.js after DOM insertion
        requestAnimationFrame(() => {
            try {
                const player = videojs(uniqueId, {
                    responsive: true,
                    preload: 'metadata',
                    controls: true,
                });

                player.ready(() => {
                    console.log('Modal Video.js player ready with aspect ratio:', aspectRatio);
                    // Auto-play the video when modal opens
                    try {
                        player.play().catch(error => {
                            console.log('Autoplay was prevented:', error);
                            // If autoplay fails, show play button is ready to be clicked
                        });
                    } catch (error) {
                        console.log('Play error:', error);
                    }
                });
            } catch (error) {
                console.warn('Modal Video.js initialization failed:', error);
            }
        });

        // Event handlers
        const closeModal = () => {
            // Dispose Video.js player before removing modal
            try {
                const player = videojs.getPlayer(uniqueId);
                if (player) {
                    player.dispose();
                }
            } catch (error) {
                console.warn('Error disposing Video.js player:', error);
            }

            modal.remove();
            document.removeEventListener('keydown', handleKeyDown);
        };

        const updateModalVideo = () => {
            // Dispose current player
            try {
                const player = videojs.getPlayer(uniqueId);
                if (player) {
                    player.dispose();
                }
            } catch (error) {
                console.warn('Error disposing Video.js player:', error);
            }

            // Update source
            source.src = this.data.files[this.currentModalIndex].url;
            source.type = this.getVideoMimeType(this.data.files[this.currentModalIndex].url);

            // Reinitialize Video.js
            requestAnimationFrame(() => {
                try {
                    const player = videojs(uniqueId, {
                        // fluid: true,
                        responsive: true,
                        // fill: true,
                        preload: 'metadata'
                    });

                    player.ready(() => {
                        // Auto-play when switching videos
                        try {
                            player.play().catch(error => {
                                console.log('Autoplay was prevented:', error);
                            });
                        } catch (error) {
                            console.log('Play error:', error);
                        }
                    });
                } catch (error) {
                    console.warn('Video.js reinitialization failed:', error);
                }
            });

            if (counter) {
                counter.textContent = `${this.currentModalIndex + 1} / ${this.data.files.length}`;
            }
        };

        const goToPrev = () => {
            this.currentModalIndex = this.currentModalIndex === 0 ?
                this.data.files.length - 1 : this.currentModalIndex - 1;
            updateModalVideo();
        };

        const goToNext = () => {
            this.currentModalIndex = this.currentModalIndex === this.data.files.length - 1 ?
                0 : this.currentModalIndex + 1;
            updateModalVideo();
        };

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        };

        // Attach event listeners
        closeBtn.addEventListener('click', closeModal);

        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteVideo();
                closeModal();
            });
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', handleKeyDown);

        // Add to DOM
        document.body.appendChild(modal);

        // Focus for keyboard navigation
        modal.focus();
    }

    onUpload(e) {
        const file = e.target.files[0];
        if (file) {
            this.handleUpload([file]);
        }
    }

    openFileSelection() {
        if (!this.readOnly && !this.uploading && this.fileInput) {
            this.fileInput.click();
        }
    }

    async handleUpload(files) {
        // Convert single file to array for unified processing
        const fileArray = Array.isArray(files) ? files : [files];

        // Prevent multiple uploads
        if (this.uploading) {
            return;
        }

        this.uploading = true;

        // Initialize upload tracking for each file with unique IDs
        this.uploadProgress = fileArray.map((file, index) => ({
            id: `upload-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            file,
            name: file.name,
            size: file.size,
            status: 'pending',
            progress: 0,
            error: null,
            retryCount: 0
        }));

        // Show progress interface
        this.showInlineProgress();

        try {
            // Process each file sequentially
            for (let i = 0; i < this.uploadProgress.length; i++) {
                await this.processFileUpload(i);
            }

            // Complete upload process (handles both success and error cases)
            this.completeUpload();
        } catch (error) {
            console.error('Upload process error:', error);
        } finally {
            this.uploading = false;
        }
    }

    async processFileUpload(index) {
        const uploadItem = this.uploadProgress[index];
        if (!uploadItem) return;

        // Update status to uploading
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;
        this.updateProgressItem(index);

        try {
            // Perform the actual upload
            const response = await this.performUpload(uploadItem.file);

            // Validate response
            if (!response.url && !response.file?.url) {
                throw new Error('No URL in response');
            }

            // Add successful upload to data
            this.addUploadedFile(response);

            // Update progress item
            uploadItem.status = 'success';
            uploadItem.progress = 100;
            this.updateProgressItem(index);

            // Remove successful upload from DOM only
            const progressThumbnail = this.wrapper.querySelector(`.video-upload__progress-thumbnail[data-id="${uploadItem.id}"]`);
            if (progressThumbnail) {
                progressThumbnail.remove();
            }

        } catch (error) {
            // Handle upload error
            uploadItem.status = 'error';
            uploadItem.error = error.message || 'Upload failed';
            this.updateProgressItem(index);

            console.error(`Upload failed for file ${index}:`, error);
        }
    }

    async performUpload(file) {
        // Single method for actual upload logic
        if (this.config.uploader && typeof this.config.uploader.uploadByFile === 'function') {
            return await this.config.uploader.uploadByFile(file);
        } else {
            return await this.executeUploadRequest(file);
        }
    }

    async executeUploadRequest(file) {
        const formData = new FormData();
        formData.append(this.config.field, file);

        // Signal editor is busy during upload
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            const response = await fetch(this.config.endpoints.byFile, {
                method: 'POST',
                body: formData,
                headers: this.config.additionalRequestHeaders
            });

            // Parse JSON response
            let json;
            try {
                json = await response.json();
            } catch (parseError) {
                throw new Error(`Invalid response format: ${response.status}`);
            }

            // Validate response success
            if (json.success === 0 || json.success === false) {
                throw new Error(json.message || 'Upload failed');
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return json;
        } catch (error) {
            throw error;
        }
    }

    addUploadedFile(response) {
        const fileData = {
            url: response.url || response.file?.url,
            caption: response.caption || '',
            width: response.width || null,
            height: response.height || null,
            duration: response.duration || null,
            size: response.size || null,
            format: response.format || null,
            aspect_ratio: response.aspect_ratio || '16:9',
            aspect_ratio_data: response.aspect_ratio_data || null
        };
        this.data.file = fileData;
    }

    completeUpload() {
        // Re-render the component to show the uploaded video
        this.wrapper.innerHTML = '';

        // Check if we have a video and render accordingly
        const hasVideo = this.data.file && this.data.file.url && this.data.file.url.trim() !== '';
        if (hasVideo) {
            this.createVideoElement(this.wrapper);
        } else {
            this.createUploadInterface(this.wrapper);
        }

        // Check if any uploads failed
        const hasErrors = this.uploadProgress.some(item => item.status === 'error');
        const successCount = this.uploadProgress.filter(item => item.status === 'success').length;

        // Always render gallery if there are successful uploads
        if (successCount > 0) {
            this.renderGallery();
        }

        // Only hide upload container if ALL uploads completed successfully (no errors)
        if (!hasErrors && successCount > 0) {
            this.hideUploadContainer();
            this.hideInlineProgress();
            // Clear upload progress after successful completion
            this.uploadProgress = [];
            // Notify editor of changes only when all uploads are successful
            this.notifyEditorChange();
        } else if (hasErrors) {
            // Only show upload container if there are no existing videos
            if (!this.data.files || this.data.files.length === 0) {
                this.showUploadContainer();
            } else {
                this.hideUploadContainer();
            }
            // Remove only successful items from uploadProgress, keep failed ones for retry
            this.uploadProgress = this.uploadProgress.filter(item => item.status === 'error');
            // Still notify editor of any successful uploads
            if (successCount > 0) {
                this.notifyEditorChange();
            }
            return;
        } else {
            // No successful uploads, show upload container
            this.showUploadContainer();
            this.hideInlineProgress();
            // Clear upload progress
            this.uploadProgress = [];
        }
    }

    async handleDelete(index) {
        if (!this.data.files || !this.data.files[index]) {
            return;
        }

        const file = this.data.files[index];

        // Signal editor is busy
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            // Delete from server if URL exists
            if (file.url) {
                await this.executeDeleteRequest(file.url);
            }

            // Remove from data array
            this.removeFileFromData(index);

            // Update UI
            this.updateAfterDelete();

            // Notify editor
            this.notifyEditorChange();

        } catch (error) {
            console.error('Delete operation failed:', error);
            setTimeout(() => {
                document.dispatchEvent(new CustomEvent('editor:free'));
            }, 0);
        }
    }

    async executeDeleteRequest(url) {
        // Extract file path from URL
        let filePath = url;
        if (filePath.includes('/storage/')) {
            const storageIndex = filePath.indexOf('/storage/');
            filePath = filePath.substring(storageIndex + '/storage/'.length);
        }

        // Ensure documents/videos/ prefix
        if (!filePath.startsWith('documents/videos/')) {
            filePath = 'documents/videos/' + filePath.replace('documents/', '');
        }

        try {
            const response = await fetch(this.config.endpoints.delete || '/delete-video', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.config.additionalRequestHeaders
                },
                body: JSON.stringify({ path: filePath })
            });

            if (!response.ok) {
                throw new Error(`Delete failed: ${response.status}`);
            }

            return response;
        } catch (error) {
            throw error;
        }
    }

    removeFileFromData(index) {
        this.data.files.splice(index, 1);

        // Ensure valid data structure
        if (this.data.files.length === 0) {
            this.data.files = [];
            if (!this.data.caption) {
                this.data.caption = '';
            }
        }
    }

    updateAfterDelete() {
        this.renderGallery();

        if (this.data.files.length === 0) {
            this.showUploadContainer();
        }
    }

    hideUploadContainer() {
        if (this.uploadContainer) {
            this.uploadContainer.style.display = 'none';
        }
    }

    showUploadContainer() {
        if (this.uploadContainer) {
            this.uploadContainer.style.display = 'flex';
        }
    }

    showInlineProgress() {
        if (!this.wrapper || !this.uploadProgress) return;

        // Hide upload container
        if (this.uploadContainer) {
            this.uploadContainer.style.display = 'none';
        }

        // Remove existing progress
        const existingProgress = this.wrapper.querySelector('.video-upload__inline-progress');
        if (existingProgress) {
            existingProgress.remove();
        }

        // Create thumbnail-style progress grid
        const progressContainer = document.createElement('div');
        progressContainer.classList.add('video-upload__inline-progress');

        // Create thumbnail grid for progress
        const thumbnailGrid = document.createElement('div');
        thumbnailGrid.classList.add('video-upload__progress-grid');

        // Create progress thumbnails
        this.uploadProgress.forEach((item, index) => {
            const progressThumbnail = this.createProgressThumbnail(item, index);
            thumbnailGrid.appendChild(progressThumbnail);
        });

        progressContainer.appendChild(thumbnailGrid);
        this.wrapper.appendChild(progressContainer);
    }

    hideInlineProgress() {
        if (this.wrapper) {
            const progressContainer = this.wrapper.querySelector('.video-upload__inline-progress');
            if (progressContainer) {
                progressContainer.remove();
            }
        }
    }

    createProgressThumbnail(uploadItem, index) {
        const thumbnail = document.createElement('div');
        thumbnail.classList.add('video-upload__progress-thumbnail');
        thumbnail.setAttribute('data-id', uploadItem.id);
        thumbnail.setAttribute('data-index', index);

        // Create preview if it's a video
        let previewContent = '';
        if (uploadItem.file && uploadItem.file.type.startsWith('video/')) {
            // File upload - create blob URL for preview
            const previewUrl = URL.createObjectURL(uploadItem.file);
            previewContent = `<video src="${previewUrl}" class="video-upload__progress-preview" style="margin: 0;" muted></video>`;
        } else {
            // Use generic video icon for unknown types
            previewContent = `
                <div class="video-upload__progress-file-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                    </svg>
                </div>
            `;
        }

        thumbnail.innerHTML = `
            <div class="video-upload__progress-content">
                ${previewContent}
                <div class="video-upload__progress-overlay">
                    <div class="video-upload__progress-circle">
                        <svg class="video-upload__progress-ring" width="40" height="40">
                            <circle class="video-upload__progress-ring-bg" cx="20" cy="20" r="16" fill="none" stroke="currentColor" stroke-width="2"></circle>
                            <circle class="video-upload__progress-ring-fill" cx="20" cy="20" r="16" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="100" stroke-dashoffset="100"></circle>
                        </svg>
                        <div class="video-upload__progress-icon"></div>
                    </div>
                </div>
            </div>
            <div class="video-upload__progress-info">
                <div class="video-upload__progress-name">${uploadItem.name}</div>
                <div class="video-upload__progress-status-text">${this.getStatusText(uploadItem.status)}</div>
            </div>
        `;

        return thumbnail;
    }

    updateProgressItem(index) {
        const uploadItem = this.uploadProgress[index];
        if (!uploadItem) return;

        const progressThumbnail = this.wrapper.querySelector(`.video-upload__progress-thumbnail[data-index="${index}"]`);
        if (!progressThumbnail) return;

        const progressRing = progressThumbnail.querySelector('.video-upload__progress-ring-fill');
        const statusText = progressThumbnail.querySelector('.video-upload__progress-status-text');
        const progressIcon = progressThumbnail.querySelector('.video-upload__progress-icon');

        // Update circular progress ring
        if (progressRing) {
            const circumference = 2 * Math.PI * 16; // r=16
            const strokeDashoffset = circumference - (uploadItem.progress / 100) * circumference;
            progressRing.style.strokeDashoffset = strokeDashoffset;
        }

        // Update status text
        if (statusText) {
            statusText.textContent = this.getStatusText(uploadItem.status);
        }

        // Update thumbnail styling based on status
        progressThumbnail.classList.remove('status-pending', 'status-uploading', 'status-success', 'status-error');
        progressThumbnail.classList.add(`status-${uploadItem.status}`);

        // Update progress icon based on status
        if (progressIcon) {
            progressIcon.innerHTML = this.getProgressIcon(uploadItem.status);
        }
    }

    getProgressIcon(status) {
        switch (status) {
            case 'pending':
                return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>`;
            case 'uploading':
                return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-spin">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>`;
            case 'success':
                return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>`;
            case 'error':
                return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>`;
            default:
                return '';
        }
    }

    getStatusText(status) {
        switch (status) {
            case 'pending': return 'Pending';
            case 'uploading': return 'Uploading...';
            case 'success': return 'Complete';
            case 'error': return 'Failed';
            default: return 'Unknown';
        }
    }

    getVideoMimeType(url) {
        const extension = url.split('.').pop().toLowerCase();
        const types = {
            'mp4': 'video/mp4',
            'webm': 'video/webm',
            'ogv': 'video/ogg',
            'ogg': 'video/ogg',
            'mov': 'video/quicktime',
            'avi': 'video/x-msvideo',
            'wmv': 'video/x-ms-wmv',
            'flv': 'video/x-flv',
            'mkv': 'video/x-matroska',
            'm4v': 'video/mp4'
        };
        return types[extension] || 'video/mp4';
    }

    notifyEditorChange() {
        if (this.blockAPI && this.blockAPI.dispatchChange) {
            this.blockAPI.dispatchChange();
        }
        setTimeout(() => {
            document.dispatchEvent(new CustomEvent('editor:free'));
        }, 0);
    }

    save() {
        return this.data;
    }

    validate(savedData) {
        // Simple validation - check if we have valid data structure
        if (!savedData) return false;

        // Allow empty data for new blocks
        if (Object.keys(savedData).length === 0) return true;

        // Check if we have a valid file or empty data
        if (savedData.file && savedData.file.url) {
            return true; // Valid file
        }

        // Allow empty file (when video is deleted)
        if (!savedData.file || savedData.file === null) {
            return true;
        }

        return false;
    }

    static get sanitize() {
        return {
            url: {},
            caption: {
                br: true,
            },
            width: {},
            height: {},
            duration: {},
            size: {},
            format: {}
        };
    }

    cleanupEventListeners() {
        // Clean up upload interface event listeners
        if (this.fileInput && this.eventHandlers) {
            this.fileInput.removeEventListener('change', this.eventHandlers.handleFileChange);
        }

        if (this.uploadLabel && this.eventHandlers) {
            this.uploadLabel.removeEventListener('click', this.eventHandlers.handleLabelClick);
        }

        // Clean up block-level drag-drop listeners
        if (this.wrapper && this.blockDragHandlers) {
            this.wrapper.removeEventListener('dragover', this.blockDragHandlers.handleBlockDragOver);
            this.wrapper.removeEventListener('dragleave', this.blockDragHandlers.handleBlockDragLeave);
            this.wrapper.removeEventListener('drop', this.blockDragHandlers.handleBlockDrop);
        }
    }

    async removed() {
        if (this.data.files && Array.isArray(this.data.files)) {
            // Signal editor is busy during cleanup
            document.dispatchEvent(new CustomEvent('editor:busy'));

            try {
                // Delete all videos from server
                const deletePromises = this.data.files.map(file => {
                    if (file.url) {
                        return this.executeDeleteRequest(file.url);
                    }
                    return Promise.resolve();
                });

                await Promise.all(deletePromises);

                // Signal editor is free after cleanup
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('editor:free'));
                }, 0);

            } catch (error) {
                console.error('Failed to cleanup videos on block removal:', error);
                // Signal editor is free even on error
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('editor:free'));
                }, 0);
            }
        }
    }

    destroy() {
        // Dispose any Video.js players
        if (this.currentVideoId) {
            try {
                const player = videojs.getPlayer(this.currentVideoId);
                if (player) {
                    player.dispose();
                }
            } catch (error) {
                console.warn('Error disposing Video.js player:', error);
            }
        }

        // Cleanup all event listeners
        this.cleanupEventListeners();

        // Clear references
        this.fileInput = null;
        this.uploadLabel = null;
        this.uploadContainer = null;
        this.wrapper = null;
        this.eventHandlers = null;
        this.blockDragHandlers = null;
        this.currentVideoId = null;
    }
}

export default VideoUpload;
