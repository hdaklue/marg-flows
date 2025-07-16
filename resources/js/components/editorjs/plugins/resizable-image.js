/**
 * ResizableImage Plugin for EditorJS
 * Allows image upload with drag-to-resize functionality
 */

// Import the plugin styles
import '../../../../css/components/editorjs/resizable-image.css';
class ResizableImage {
    static get toolbox() {
        return {
            title: 'Resizable Image',
            icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
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


    constructor({ data, config, api, readOnly }) {
        this.api = api;
        this.readOnly = readOnly;
        this.config = config || {};
        // Support both old single image format and new multiple images format
        this.data = data || {};
        
        // Convert old single image format to new array format
        if (this.data.file && !Array.isArray(this.data.files)) {
            this.data.files = [this.data.file];
            delete this.data.file; // Remove old format
        }
        
        // Initialize files array if not present
        if (!this.data.files) {
            this.data.files = [];
        }

        // Initialize state flags
        this.uploading = false;
        this.wrapper = null;
        this.fileInput = null;
        this.uploadContainer = null;
        this.eventHandlers = null;

        // Default configuration
        this.defaultConfig = {
            endpoints: {
                byFile: '/upload-image',
                byUrl: '/upload-image-by-url'
            },
            additionalRequestHeaders: {},
            field: 'image',
            types: 'image/*',
            captionPlaceholder: 'Enter image caption...',
            buttonContent: 'Select an image',
            uploader: null,
            actions: []
        };

        this.config = Object.assign(this.defaultConfig, this.config);

        // Plugin state
        this.isResizing = false;
        this.resizeHandle = null;
        this.startX = 0;
        this.startY = 0;
        this.startWidth = 0;
        this.startHeight = 0;
        this.aspectRatio = 1;
        this.currentImage = null;
        this.currentContainer = null;

        // Bind methods
        this.onUpload = this.onUpload.bind(this);
        this.onPaste = this.onPaste.bind(this);
        this.startResize = this.startResize.bind(this);
        this.onResize = this.onResize.bind(this);
        this.stopResize = this.stopResize.bind(this);
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('resizable-image');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Check if we have valid image data (support both old and new format)
        const hasImages = (this.data.files && this.data.files.length > 0) || 
                         (this.data.file && this.data.file.url && this.data.file.url.trim() !== '');
        
        if (hasImages) {
            // Always create upload interface (for adding more images)
            this.createUploadInterface(wrapper);
            
            // If we have images, render the gallery
            if (this.data.files && this.data.files.length > 0) {
                this.hideUploadContainer();
                this.renderGallery();
            } else {
                // Legacy single image - show old interface for now
                this.createImageElement(wrapper);
            }
        } else {
            // Always show upload interface for new blocks or invalid data
            this.createUploadInterface(wrapper);
        }

        return wrapper;
    }

    createUploadInterface(wrapper) {
        // Don't create upload interface in readonly mode
        if (this.readOnly) {
            return;
        }
        
        const uploadContainer = document.createElement('div');
        uploadContainer.classList.add('resizable-image__upload-container');

        // Create unique ID for the file input
        const inputId = `resizable-image-input-${Math.random().toString(36).substr(2, 9)}`;

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = this.config.types;
        fileInput.multiple = true; // Enable multiple file selection
        fileInput.classList.add('resizable-image__file-input');
        fileInput.id = inputId;

        // Create label that triggers the file input
        const uploadLabel = document.createElement('label');
        uploadLabel.setAttribute('for', inputId);
        uploadLabel.classList.add('resizable-image__upload-button');
        uploadLabel.innerHTML = `
      <div class="resizable-image__upload-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
      </div>
      <div class="resizable-image__upload-text">${this.config.buttonContent}</div>
    `;

        // Store references for cleanup
        this.fileInput = fileInput;
        this.uploadContainer = uploadContainer;
        this.uploadLabel = uploadLabel;

        const handleFileChange = (e) => {
            e.preventDefault();
            const files = Array.from(e.target.files);
            if (files.length > 0 && !this.uploading) {
                this.uploadFiles(files);
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

        // Drag and drop support with improved handling
        const handleDragOver = (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadContainer.classList.add('resizable-image__upload-container--dragover');
        };

        const handleDragLeave = (e) => {
            e.preventDefault();
            e.stopPropagation();
            // Only remove class if actually leaving the container
            if (!uploadContainer.contains(e.relatedTarget)) {
                uploadContainer.classList.remove('resizable-image__upload-container--dragover');
            }
        };

        const handleDrop = (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadContainer.classList.remove('resizable-image__upload-container--dragover');

            if (!this.readOnly && !this.uploading && e.dataTransfer.files.length) {
                const files = Array.from(e.dataTransfer.files).filter(file => 
                    file.type.startsWith('image/')
                );
                if (files.length > 0) {
                    this.uploadFiles(files);
                }
            }
        };

        uploadContainer.addEventListener('dragover', handleDragOver);
        uploadContainer.addEventListener('dragleave', handleDragLeave);
        uploadContainer.addEventListener('drop', handleDrop);

        // Store event handlers for cleanup
        this.eventHandlers = {
            handleFileChange,
            handleLabelClick,
            handleDragOver,
            handleDragLeave,
            handleDrop
        };

        uploadContainer.appendChild(uploadLabel);
        uploadContainer.appendChild(fileInput);
        wrapper.appendChild(uploadContainer);
    }

    createImageElement(wrapper) {
        const imageContainer = document.createElement('div');
        imageContainer.classList.add('resizable-image__container');

        const image = document.createElement('img');
        image.classList.add('resizable-image__img');
        image.src = this.data.file.url;
        image.alt = this.data.caption || '';

        // Set initial dimensions if available
        if (this.data.width) {
            image.style.width = this.data.width + 'px';
        }
        if (this.data.height) {
            image.style.height = this.data.height + 'px';
        }

        // Wait for image to load to get natural dimensions
        image.addEventListener('load', () => {
            this.aspectRatio = image.naturalWidth / image.naturalHeight;

            if (!this.data.width && !this.data.height) {
                // Set default width to container width, maintain aspect ratio
                const containerWidth = Math.min(image.naturalWidth, wrapper.parentElement?.offsetWidth || 600);
                image.style.width = containerWidth + 'px';
                image.style.height = (containerWidth / this.aspectRatio) + 'px';
            }
        });

        imageContainer.appendChild(image);

        // Store references to this specific block's elements
        this.currentImage = image;
        this.currentContainer = imageContainer;

        // Gallery styling applied automatically

        // Add resize handles if not read-only
        if (!this.readOnly) {
            this.addResizeHandles(imageContainer, image);
        }

        // Caption input
        const captionInput = document.createElement('input');
        captionInput.classList.add('resizable-image__caption');
        captionInput.placeholder = this.config.captionPlaceholder;
        captionInput.value = this.data.caption || '';
        captionInput.readOnly = this.readOnly;

        captionInput.addEventListener('input', () => {
            this.data.caption = captionInput.value;
        });

        wrapper.appendChild(imageContainer);
        wrapper.appendChild(captionInput);
    }

    addResizeHandles(container, image) {
        const handles = ['nw', 'ne', 'sw', 'se', 'n', 's', 'e', 'w'];

        handles.forEach(position => {
            const handle = document.createElement('div');
            handle.classList.add('resizable-image__handle', `resizable-image__handle--${position}`);
            handle.addEventListener('mousedown', (e) => this.startResize(e, position, image));
            container.appendChild(handle);
        });

        container.classList.add('resizable-image__container--resizable');
    }

    startResize(e, position, image) {
        e.preventDefault();
        this.isResizing = true;
        this.resizeHandle = position;
        this.startX = e.clientX;
        this.startY = e.clientY;
        this.startWidth = parseInt(getComputedStyle(image).width);
        this.startHeight = parseInt(getComputedStyle(image).height);

        document.addEventListener('mousemove', this.onResize);
        document.addEventListener('mouseup', this.stopResize);
        document.body.style.userSelect = 'none';
        document.body.style.cursor = getComputedStyle(e.target).cursor;

        // Prevent text selection and other default behaviors
        e.target.style.pointerEvents = 'none';
    }

    onResize(e) {
        if (!this.isResizing) return;

        const deltaX = e.clientX - this.startX;
        const deltaY = e.clientY - this.startY;
        const handle = this.resizeHandle;

        let newWidth = this.startWidth;
        let newHeight = this.startHeight;

        // Calculate new dimensions based on handle position
        switch (handle) {
            case 'se': // Southeast
                newWidth = this.startWidth + deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'sw': // Southwest
                newWidth = this.startWidth - deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'ne': // Northeast
                newWidth = this.startWidth + deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'nw': // Northwest
                newWidth = this.startWidth - deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'e': // East
                newWidth = this.startWidth + deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'w': // West
                newWidth = this.startWidth - deltaX;
                newHeight = newWidth / this.aspectRatio;
                break;
            case 'n': // North
                newHeight = this.startHeight - deltaY;
                newWidth = newHeight * this.aspectRatio;
                break;
            case 's': // South
                newHeight = this.startHeight + deltaY;
                newWidth = newHeight * this.aspectRatio;
                break;
        }

        // Apply minimum constraints
        const minWidth = 50;
        const minHeight = 50;

        if (newWidth < minWidth) {
            newWidth = minWidth;
            newHeight = newWidth / this.aspectRatio;
        }

        if (newHeight < minHeight) {
            newHeight = minHeight;
            newWidth = newHeight * this.aspectRatio;
        }

        // Apply the new dimensions to this specific image
        if (this.currentImage) {
            this.currentImage.style.width = newWidth + 'px';
            this.currentImage.style.height = newHeight + 'px';
        }
    }

    stopResize() {
        if (!this.isResizing) return;

        this.isResizing = false;
        this.resizeHandle = null;

        document.removeEventListener('mousemove', this.onResize);
        document.removeEventListener('mouseup', this.stopResize);
        document.body.style.userSelect = '';
        document.body.style.cursor = '';

        // Re-enable pointer events for handles in this container
        if (this.currentContainer) {
            this.currentContainer.querySelectorAll('.resizable-image__handle').forEach(handle => {
                handle.style.pointerEvents = '';
            });
        }

        // Save the new dimensions from this specific image
        if (this.currentImage) {
            this.data.width = parseInt(this.currentImage.style.width);
            this.data.height = parseInt(this.currentImage.style.height);
        }
    }

    onUpload(e) {
        const file = e.target.files[0];
        if (file) {
            this.uploadFile(file);
        }
    }

    onPaste(e) {
        // Handle paste events for images
        const items = e.clipboardData?.items;
        if (items) {
            for (let item of items) {
                if (item.type.indexOf('image') !== -1) {
                    const file = item.getAsFile();
                    if (file) {
                        this.uploadFile(file);
                        e.preventDefault();
                    }
                }
            }
        }
    }

    openFileSelection() {
        if (!this.readOnly && !this.uploading && this.fileInput) {
            this.fileInput.click();
        }
    }

    async uploadFiles(files) {
        // Prevent multiple uploads
        if (this.uploading) {
            return;
        }

        this.uploading = true;
        
        // Initialize upload tracking for each file
        this.uploadProgress = files.map((file, index) => ({
            id: `upload-${Date.now()}-${index}`,
            file,
            name: file.name,
            size: file.size,
            status: 'pending', // pending, uploading, success, error
            progress: 0,
            error: null,
            retryCount: 0
        }));

        // Show inline progress interface
        this.showInlineProgress();
        
        // Debug log
        console.log('Upload progress initialized:', this.uploadProgress);

        try {
            // Upload files sequentially to avoid overwhelming the server
            for (let i = 0; i < this.uploadProgress.length; i++) {
                await this.uploadSingleFileWithProgress(i);
            }
            
            // Hide progress and show gallery
            this.hideInlineProgress();
            this.hideUploadContainer();
            this.renderGallery();
        } catch (error) {
            // Don't hide progress on error - let user retry
            console.error('Upload error:', error);
        } finally {
            this.uploading = false;
        }
    }

    uploadFile(file) {
        // Legacy method - convert to use new uploadFiles method
        this.uploadFiles([file]);
    }

    async uploadSingleFileWithProgress(index) {
        const uploadItem = this.uploadProgress[index];
        if (!uploadItem) return;

        uploadItem.status = 'uploading';
        uploadItem.progress = 0;
        this.updateProgressItem(index);

        try {
            const response = await this.uploadSingleFile(uploadItem.file);
            
            // Add to data.files array
            this.data.files.push({
                url: response.url || response.file?.url,
                caption: response.caption || '',
                width: response.width || null,
                height: response.height || null
            });

            uploadItem.status = 'success';
            uploadItem.progress = 100;
            this.updateProgressItem(index);
            
        } catch (error) {
            uploadItem.status = 'error';
            uploadItem.error = error.message || 'Upload failed';
            this.updateProgressItem(index);
            throw error;
        }
    }

    async uploadSingleFile(file) {
        // Upload a single file and add to data.files array
        return new Promise((resolve, reject) => {
            if (this.config.uploader && typeof this.config.uploader.uploadByFile === 'function') {
                // Use custom uploader
                this.config.uploader.uploadByFile(file)
                    .then(resolve)
                    .catch(reject);
            } else {
                // Use default upload method
                this.defaultUpload(file)
                    .then(resolve)
                    .catch(reject);
            }
        });
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

    renderGallery() {
        // Remove existing gallery if any
        const existingGallery = this.wrapper.querySelector('.resizable-image__gallery');
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
        galleryContainer.classList.add('resizable-image__gallery');

        // Create thumbnail grid
        const thumbnailGrid = document.createElement('div');
        thumbnailGrid.classList.add('resizable-image__thumbnail-grid');

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
        thumbnail.classList.add('resizable-image__thumbnail');
        
        const img = document.createElement('img');
        img.src = fileData.url;
        img.alt = fileData.caption || `Image ${index + 1}`;
        img.classList.add('resizable-image__thumbnail-image');
        
        // Add click handler to open modal
        img.addEventListener('click', () => this.openModal(index));
        
        // Add remove button if not readonly
        if (!this.readOnly) {
            const removeBtn = document.createElement('button');
            removeBtn.classList.add('resizable-image__thumbnail-remove');
            removeBtn.innerHTML = `
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            `;
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeImage(index);
            });
            thumbnail.appendChild(removeBtn);
        }
        
        thumbnail.appendChild(img);
        return thumbnail;
    }

    createAddMoreButton() {
        // Don't create add more button in readonly mode
        if (this.readOnly) {
            return null;
        }
        
        const addButton = document.createElement('div');
        addButton.classList.add('resizable-image__add-more');
        
        addButton.innerHTML = `
            <div class="resizable-image__add-more-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="resizable-image__add-more-text">Add more</span>
        `;
        
        addButton.addEventListener('click', () => this.openFileSelection());
        
        return addButton;
    }

    createCaptionInput() {
        if (this.readOnly) {
            // In readonly mode, display caption as text if it exists
            if (this.data.caption && this.data.caption.trim() !== '') {
                const captionText = document.createElement('div');
                captionText.classList.add('resizable-image__caption', 'resizable-image__caption--readonly');
                captionText.textContent = this.data.caption;
                return captionText;
            }
            return null;
        }
        
        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.placeholder = 'Add a caption for this gallery...';
        captionInput.classList.add('resizable-image__caption');
        captionInput.value = this.data.caption || '';
        
        captionInput.addEventListener('input', (e) => {
            this.data.caption = e.target.value;
        });
        
        return captionInput;
    }

    removeImage(index) {
        if (this.data.files && this.data.files[index]) {
            this.data.files.splice(index, 1);
            
            // Re-render gallery
            this.renderGallery();
            
            // If no images left, show upload container
            if (this.data.files.length === 0) {
                this.showUploadContainer();
            }
        }
    }

    openModal(startIndex = 0) {
        if (!this.data.files || this.data.files.length === 0) return;
        
        this.currentModalIndex = Math.max(0, Math.min(startIndex, this.data.files.length - 1));
        
        // Create modal overlay
        const modal = document.createElement('div');
        modal.classList.add('resizable-image__modal');
        
        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.classList.add('resizable-image__modal-content');
        
        // Create image element
        const modalImage = document.createElement('img');
        modalImage.classList.add('resizable-image__modal-image');
        modalImage.src = this.data.files[this.currentModalIndex].url;
        modalImage.alt = this.data.files[this.currentModalIndex].caption || `Image ${this.currentModalIndex + 1}`;
        
        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.classList.add('resizable-image__modal-close');
        closeBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        `;
        
        // Create navigation buttons (only if more than 1 image)
        let prevBtn, nextBtn, counter;
        if (this.data.files.length > 1) {
            prevBtn = document.createElement('button');
            prevBtn.classList.add('resizable-image__modal-nav', 'resizable-image__modal-prev');
            prevBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
                </svg>
            `;
            
            nextBtn = document.createElement('button');
            nextBtn.classList.add('resizable-image__modal-nav', 'resizable-image__modal-next');
            nextBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 18l6-6-6-6"/>
                </svg>
            `;
            
            // Create counter
            counter = document.createElement('div');
            counter.classList.add('resizable-image__modal-counter');
            counter.textContent = `${this.currentModalIndex + 1} / ${this.data.files.length}`;
        }
        
        // Assemble modal
        modalContent.appendChild(modalImage);
        modalContent.appendChild(closeBtn);
        if (prevBtn && nextBtn) {
            modalContent.appendChild(prevBtn);
            modalContent.appendChild(nextBtn);
            modalContent.appendChild(counter);
        }
        modal.appendChild(modalContent);
        
        // Event handlers
        const closeModal = () => {
            modal.remove();
            document.removeEventListener('keydown', handleKeyDown);
        };
        
        const updateModalImage = () => {
            modalImage.src = this.data.files[this.currentModalIndex].url;
            modalImage.alt = this.data.files[this.currentModalIndex].caption || `Image ${this.currentModalIndex + 1}`;
            if (counter) {
                counter.textContent = `${this.currentModalIndex + 1} / ${this.data.files.length}`;
            }
        };
        
        const goToPrev = () => {
            this.currentModalIndex = this.currentModalIndex === 0 ? 
                this.data.files.length - 1 : this.currentModalIndex - 1;
            updateModalImage();
        };
        
        const goToNext = () => {
            this.currentModalIndex = this.currentModalIndex === this.data.files.length - 1 ? 
                0 : this.currentModalIndex + 1;
            updateModalImage();
        };
        
        const handleKeyDown = (e) => {
            switch (e.key) {
                case 'Escape':
                    closeModal();
                    break;
                case 'ArrowLeft':
                    if (this.data.files.length > 1) goToPrev();
                    break;
                case 'ArrowRight':
                    if (this.data.files.length > 1) goToNext();
                    break;
            }
        };
        
        // Attach event listeners
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', goToPrev);
            nextBtn.addEventListener('click', goToNext);
        }
        
        document.addEventListener('keydown', handleKeyDown);
        
        // Add to DOM
        document.body.appendChild(modal);
        
        // Focus for keyboard navigation
        modal.focus();
    }

    uploadFileOld(file) {
        // Prevent multiple uploads
        if (this.uploading) {
            return;
        }

        this.uploading = true;
        this.showProgress();

        if (this.config.uploader && typeof this.config.uploader.uploadByFile === 'function') {
            // Use custom uploader
            this.config.uploader.uploadByFile(file)
                .then(response => this.onUploadSuccess(response))
                .catch(error => this.onUploadError(error))
                .finally(() => {
                    this.hideProgress();
                    this.uploading = false;
                });
        } else {
            // Use default upload method
            this.defaultUpload(file)
                .then(response => this.onUploadSuccess(response))
                .catch(error => this.onUploadError(error))
                .finally(() => {
                    this.hideProgress();
                    this.uploading = false;
                });
        }
    }

    defaultUpload(file) {
        const formData = new FormData();
        formData.append(this.config.field, file);

        return fetch(this.config.endpoints.byFile, {
            method: 'POST',
            body: formData,
            headers: this.config.additionalRequestHeaders
        }).then(response => response.json());
    }

    onUploadSuccess(response) {
        this.data = {
            file: {
                url: response.url || response.file?.url
            },
            caption: response.caption || this.data.caption || '',
            width: response.width || null,
            height: response.height || null,
            // Gallery data only
        };

        // Find the wrapper element more reliably
        const wrapper = this.wrapper || document.querySelector('.resizable-image');
        if (wrapper) {
            // Clean up existing event listeners
            this.cleanupEventListeners();

            // Clear content and re-render
            wrapper.innerHTML = '';
            this.createImageElement(wrapper);

            // Store wrapper reference
            this.wrapper = wrapper;
        }
    }

    onUploadError(error) {
        console.error('Upload failed:', error);
        this.api.notifier.show({
            message: 'Image upload failed',
            style: 'error'
        });
    }

    showProgress() {
        if (this.wrapper) {
            // Remove existing progress if any
            const existingProgress = this.wrapper.querySelector('.resizable-image__progress');
            if (existingProgress) {
                existingProgress.remove();
            }
            
            // Create progress element
            const progressElement = document.createElement('div');
            progressElement.classList.add('resizable-image__progress');
            progressElement.innerHTML = `
                <div class="resizable-image__progress-bar">
                    <div class="resizable-image__progress-fill"></div>
                </div>
                <div class="resizable-image__progress-text">Uploading...</div>
            `;
            
            // Hide upload container if it exists
            if (this.uploadContainer) {
                this.uploadContainer.style.display = 'none';
            }
            
            // Add progress element to wrapper
            this.wrapper.appendChild(progressElement);
        }
    }

    hideProgress() {
        if (this.wrapper) {
            const progressElement = this.wrapper.querySelector('.resizable-image__progress');
            if (progressElement) {
                progressElement.remove();
            }
        }
    }

    showInlineProgress() {
        console.log('showInlineProgress called', {
            wrapper: !!this.wrapper,
            uploadProgress: this.uploadProgress?.length || 0
        });
        
        if (!this.wrapper || !this.uploadProgress) return;

        // Hide upload container
        if (this.uploadContainer) {
            this.uploadContainer.style.display = 'none';
        }

        // Remove existing progress
        const existingProgress = this.wrapper.querySelector('.resizable-image__inline-progress');
        if (existingProgress) {
            existingProgress.remove();
        }

        // Create thumbnail-style progress grid
        const progressContainer = document.createElement('div');
        progressContainer.classList.add('resizable-image__inline-progress');

        // Create thumbnail grid for progress
        const thumbnailGrid = document.createElement('div');
        thumbnailGrid.classList.add('resizable-image__progress-grid');

        // Create progress thumbnails
        this.uploadProgress.forEach((item, index) => {
            console.log('Creating progress thumbnail for:', item.name);
            const progressThumbnail = this.createProgressThumbnail(item, index);
            thumbnailGrid.appendChild(progressThumbnail);
        });

        progressContainer.appendChild(thumbnailGrid);
        this.wrapper.appendChild(progressContainer);
    }

    hideInlineProgress() {
        if (this.wrapper) {
            const progressContainer = this.wrapper.querySelector('.resizable-image__inline-progress');
            if (progressContainer) {
                progressContainer.remove();
            }
        }
    }

    createProgressThumbnail(uploadItem, index) {
        const thumbnail = document.createElement('div');
        thumbnail.classList.add('resizable-image__progress-thumbnail');
        thumbnail.setAttribute('data-index', index);

        // Create preview if it's an image
        let previewContent = '';
        if (uploadItem.file && uploadItem.file.type.startsWith('image/')) {
            const previewUrl = URL.createObjectURL(uploadItem.file);
            previewContent = `<img src="${previewUrl}" alt="${uploadItem.name}" class="resizable-image__progress-preview">`;
        } else {
            // Use file icon for non-images
            previewContent = `
                <div class="resizable-image__progress-file-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            `;
        }

        thumbnail.innerHTML = `
            <div class="resizable-image__progress-content">
                ${previewContent}
                <div class="resizable-image__progress-overlay">
                    <div class="resizable-image__progress-circle">
                        <svg class="resizable-image__progress-ring" width="40" height="40">
                            <circle class="resizable-image__progress-ring-bg" cx="20" cy="20" r="16" fill="none" stroke="currentColor" stroke-width="2"></circle>
                            <circle class="resizable-image__progress-ring-fill" cx="20" cy="20" r="16" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="100" stroke-dashoffset="100"></circle>
                        </svg>
                        <div class="resizable-image__progress-icon"></div>
                    </div>
                </div>
            </div>
            <div class="resizable-image__progress-info">
                <div class="resizable-image__progress-name">${uploadItem.name}</div>
                <div class="resizable-image__progress-status-text">${this.getStatusText(uploadItem.status)}</div>
            </div>
        `;

        return thumbnail;
    }

    updateProgressItem(index) {
        const uploadItem = this.uploadProgress[index];
        if (!uploadItem) return;

        const progressThumbnail = this.wrapper.querySelector(`.resizable-image__progress-thumbnail[data-index="${index}"]`);
        if (!progressThumbnail) return;

        const progressRing = progressThumbnail.querySelector('.resizable-image__progress-ring-fill');
        const statusText = progressThumbnail.querySelector('.resizable-image__progress-status-text');
        const progressIcon = progressThumbnail.querySelector('.resizable-image__progress-icon');
        const progressInfo = progressThumbnail.querySelector('.resizable-image__progress-info');

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

        // Handle retry for failed uploads
        if (uploadItem.status === 'error') {
            // Remove existing retry button if any
            const existingRetry = progressInfo.querySelector('.resizable-image__retry-btn');
            if (existingRetry) {
                existingRetry.remove();
            }

            const retryBtn = document.createElement('button');
            retryBtn.classList.add('resizable-image__retry-btn');
            retryBtn.innerHTML = `
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Retry
            `;
            
            retryBtn.addEventListener('click', () => this.retryUpload(index));
            progressInfo.appendChild(retryBtn);

            // Show error message
            if (uploadItem.error && statusText) {
                statusText.textContent = uploadItem.error;
                statusText.setAttribute('title', uploadItem.error);
            }
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

    async retryUpload(index) {
        const uploadItem = this.uploadProgress[index];
        if (!uploadItem) return;

        uploadItem.retryCount++;
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;
        uploadItem.error = null;

        this.updateProgressItem(index);

        try {
            await this.uploadSingleFileWithProgress(index);
            
            // Check if all uploads are complete
            const allComplete = this.uploadProgress.every(item => item.status === 'success');
            if (allComplete) {
                this.hideInlineProgress();
                this.hideUploadContainer();
                this.renderGallery();
            }
        } catch (error) {
            // Error handling is done in uploadSingleFileWithProgress
            console.error('Retry failed:', error);
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

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    save() {
        return this.data;
    }

    validate(savedData) {
        // Simple validation - check if we have valid data structure
        if (!savedData) return false;

        // Allow empty data for new blocks
        if (Object.keys(savedData).length === 0) return true;

        // For existing blocks, check if we have file data (new or old format)
        if (savedData.files && savedData.files.length > 0 && savedData.files[0].url) return true;
        if (savedData.file && savedData.file.url) return true;

        return false;
    }

    static get sanitize() {
        return {
            url: {},
            caption: {
                br: true,
            },
            width: {},
            height: {}
        };
    }

    // Tunes removed for gallery functionality

    // Center tune removed for gallery functionality

    // Settings removed for gallery functionality

    deleteFromServer(url) {
        // Extract file path from URL for deletion
        let filePath = url;
        if (filePath.includes('/storage/')) {
            const storageIndex = filePath.indexOf('/storage/');
            filePath = filePath.substring(storageIndex + '/storage/'.length);
        }

        // Call delete endpoint
        fetch(this.config.endpoints.delete || '/delete-image', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                ...this.config.additionalRequestHeaders
            },
            body: JSON.stringify({ path: filePath })
        }).catch(error => {
            console.warn('Failed to delete file from server:', error);
        });
    }

    cleanupEventListeners() {
        // Clean up upload interface event listeners
        if (this.fileInput && this.eventHandlers) {
            this.fileInput.removeEventListener('change', this.eventHandlers.handleFileChange);
        }

        if (this.uploadLabel && this.eventHandlers) {
            this.uploadLabel.removeEventListener('click', this.eventHandlers.handleLabelClick);
        }

        if (this.uploadContainer && this.eventHandlers) {
            this.uploadContainer.removeEventListener('dragover', this.eventHandlers.handleDragOver);
            this.uploadContainer.removeEventListener('dragleave', this.eventHandlers.handleDragLeave);
            this.uploadContainer.removeEventListener('drop', this.eventHandlers.handleDrop);
        }

        // Clean up resize event listeners
        if (this.isResizing) {
            this.stopResize();
        }
    }

    destroy() {
        // Cleanup all event listeners
        this.cleanupEventListeners();

        // Clear references
        this.fileInput = null;
        this.uploadLabel = null;
        this.uploadContainer = null;
        this.wrapper = null;
        this.eventHandlers = null;
    }
}

export default ResizableImage;
