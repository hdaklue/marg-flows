/**
 * ResizableImage Plugin for EditorJS
 * Allows image upload with drag-to-resize functionality
 */

// Import the plugin styles
import '../../../../css/components/editorjs/resizable-image.css';
class ResizableImage {
    static get toolbox() {
        return {
            title: 'Images',
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



    constructor({ data, config, api, readOnly, block }) {
        this.api = api;
        this.blockAPI = block;
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
                byUrl: '/upload-image-by-url',
                delete: '/delete-image'
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


        // Bind methods
        this.onUpload = this.onUpload.bind(this);
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('resizable-image');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Add drag-drop functionality to the entire block wrapper
        this.setupBlockDragDrop(wrapper);

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

    setupBlockDragDrop(wrapper) {
        if (this.readOnly) {
            return;
        }

        // Block-level drag and drop handlers
        const handleBlockDragOver = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.add('resizable-image--dragover');
        };

        const handleBlockDragLeave = (e) => {
            e.preventDefault();
            e.stopPropagation();
            // Only remove class if leaving the wrapper entirely
            if (!wrapper.contains(e.relatedTarget)) {
                wrapper.classList.remove('resizable-image--dragover');
            }
        };

        const handleBlockDrop = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.remove('resizable-image--dragover');

            if (!this.readOnly && !this.uploading && e.dataTransfer.files.length) {
                const files = Array.from(e.dataTransfer.files).filter(file =>
                    file.type.startsWith('image/')
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

        // Attach event listeners (drag-drop is now handled at block level)
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

    createImageElement(wrapper) {
        const imageContainer = document.createElement('div');
        imageContainer.classList.add('resizable-image__container');

        const image = document.createElement('img');
        image.classList.add('resizable-image__img');
        image.src = this.data.file.url;
        image.alt = this.data.caption || '';
        image.style.margin = '0';


        imageContainer.appendChild(image);

        // Gallery styling applied automatically


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





    onUpload(e) {
        const file = e.target.files[0];
        if (file) {
            this.handleUpload([file]);
        }
    }

    async handlePastedUrl(url) {
        if (!url || this.uploading || this.readOnly) {
            return;
        }

        // Validate URL format
        try {
            new URL(url);
        } catch (e) {
            console.error('Invalid URL format:', url);
            return;
        }

        // Create a mock file object for consistent handling
        const urlUpload = {
            id: `url-upload-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            url: url,
            name: url.split('/').pop() || 'pasted-image',
            status: 'pending',
            progress: 0,
            error: null,
            retryCount: 0
        };

        this.uploading = true;
        this.uploadProgress = [urlUpload];
        this.showInlineProgress();

        try {
            // Use the byUrl endpoint if available
            const uploadEndpoint = this.config.endpoints.byUrl || this.config.endpoints.byFile;
            const response = await this.executeUrlUploadRequest(url, uploadEndpoint);

            if (!response.url && !response.file?.url) {
                throw new Error('No URL in response');
            }

            // Add successful upload to data
            this.addUploadedFile(response);

            // Update progress
            urlUpload.status = 'success';
            urlUpload.progress = 100;
            this.updateProgressItem(0);

            // Complete the upload process
            this.completeUpload();

        } catch (error) {
            console.error('URL upload failed:', error);
            urlUpload.status = 'error';
            urlUpload.error = error.message || 'Failed to load image from URL';
            this.updateProgressItem(0);

            // Show error state
            this.completeUpload();
        } finally {
            this.uploading = false;
        }
    }

    async executeUrlUploadRequest(url, endpoint) {
        // Signal editor is busy during upload
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            const formData = new FormData();
            formData.append('url', url);

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: this.config.additionalRequestHeaders
            });

            let json;
            try {
                json = await response.json();
            } catch (parseError) {
                throw new Error(`Invalid response format: ${response.status}`);
            }

            if (json.success === 0 || json.success === false) {
                throw new Error(json.message || 'URL upload failed');
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return json;
        } catch (error) {
            throw error;
        }
    }

    openFileSelection() {
        if (!this.readOnly && !this.uploading && this.fileInput) {
            this.fileInput.click();
        }
    }

    /**
     * UNIFIED UPLOAD HANDLER - Single source of truth for all uploads
     */
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

            // Remove successful upload from DOM only, keep in uploadProgress for completeUpload()
            const progressThumbnail = this.wrapper.querySelector(`.resizable-image__progress-thumbnail[data-id="${uploadItem.id}"]`);
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

    addUploadedFile(response) {
        const fileData = {
            url: response.url || response.file?.url,
            caption: response.caption || '',
            width: response.width || null,
            height: response.height || null
        };
        this.data.files.push(fileData);
    }

    completeUpload() {
        // Check if any uploads failed
        const hasErrors = this.uploadProgress.some(item => item.status === 'error');
        const successCount = this.uploadProgress.filter(item => item.status === 'success').length;
        const errorCount = this.uploadProgress.filter(item => item.status === 'error').length;


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
            // Only show upload container if there are no existing images
            // If there are existing images, the "Add more" button handles new uploads
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

    notifyEditorChange() {
        console.log('ResizableImage - upload completed, triggering save to persist image data');
        
        // Use the standard EditorJS change notification
        if (this.blockAPI && this.blockAPI.dispatchChange) {
            this.blockAPI.dispatchChange();
        }
        
        // Signal that plugin operations are complete
        setTimeout(() => {
            document.dispatchEvent(new CustomEvent('editor:free'));
        }, 0);
    }

    // Legacy method support
    uploadFiles(files) {
        return this.handleUpload(files);
    }

    uploadFile(file) {
        return this.handleUpload([file]);
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
        img.style.margin = '0';

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
                this.handleDelete(index);
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

    /**
     * UNIFIED DELETE HANDLER - Single source of truth for all deletions
     */
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

    // Legacy method support
    async removeImage(index) {
        return this.handleDelete(index);
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
        modalImage.style.margin = '0';

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.classList.add('resizable-image__modal-close');
        closeBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        `;

        // Create delete button (only if not readonly)
        let deleteBtn;
        if (!this.readOnly) {
            deleteBtn = document.createElement('button');
            deleteBtn.classList.add('resizable-image__modal-delete');
            deleteBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c0-1 1-2 2-2v2m-6 5v6m4-6v6"/>
                </svg>
            `;
        }

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
        if (deleteBtn) {
            modalContent.appendChild(deleteBtn);
        }
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

        if (deleteBtn) {
            deleteBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    await this.handleDelete(this.currentModalIndex);
                    // If no files left, close modal
                    if (!this.data.files || this.data.files.length === 0) {
                        closeModal();
                    } else {
                        // Adjust index if needed and update modal
                        if (this.currentModalIndex >= this.data.files.length) {
                            this.currentModalIndex = this.data.files.length - 1;
                        }
                        updateModalImage();
                        // Update counter if exists
                        if (counter) {
                            counter.textContent = `${this.currentModalIndex + 1} / ${this.data.files.length}`;
                        }
                    }
                } catch (error) {
                    console.error('Failed to delete image from modal:', error);
                }
            });
        }

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

    removeProgressItem(itemId) {
        const progressItem = this.wrapper.querySelector(`.resizable-image__progress-thumbnail[data-id="${itemId}"]`);
        if (progressItem) {
            progressItem.remove();
        }

        // Remove from uploadProgress array
        this.uploadProgress = this.uploadProgress.filter(item => item.id !== itemId);

        // Check if progress container should be hidden
        const remainingItems = this.wrapper.querySelectorAll('.resizable-image__progress-thumbnail');
        if (remainingItems.length === 0) {
            this.hideInlineProgress();
        }
    }

    createProgressThumbnail(uploadItem, index) {
        const thumbnail = document.createElement('div');
        thumbnail.classList.add('resizable-image__progress-thumbnail');
        thumbnail.setAttribute('data-id', uploadItem.id);
        thumbnail.setAttribute('data-index', index); // Keep for backward compatibility

        // Create preview if it's an image
        let previewContent = '';
        if (uploadItem.file && uploadItem.file.type.startsWith('image/')) {
            // File upload - create blob URL for preview
            const previewUrl = URL.createObjectURL(uploadItem.file);
            previewContent = `<img src="${previewUrl}" alt="${uploadItem.name}" class="resizable-image__progress-preview" style="margin: 0;">`;
        } else if (uploadItem.url) {
            // URL upload - use the URL directly for preview
            previewContent = `<img src="${uploadItem.url}" alt="${uploadItem.name}" class="resizable-image__progress-preview" style="margin: 0;">`;
        } else {
            // Use generic image icon for unknown types
            previewContent = `
                <div class="resizable-image__progress-file-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
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
            // Remove existing buttons if any
            const existingRetry = progressInfo.querySelector('.resizable-image__retry-btn');
            const existingDiscard = progressInfo.querySelector('.resizable-image__discard-btn');
            if (existingRetry) existingRetry.remove();
            if (existingDiscard) existingDiscard.remove();

            // Create button container
            const buttonContainer = document.createElement('div');
            buttonContainer.classList.add('resizable-image__error-buttons');
            buttonContainer.style.cssText = 'display: flex; gap: 8px; margin-top: 4px;';

            // Retry button
            const retryBtn = document.createElement('button');
            retryBtn.classList.add('resizable-image__retry-btn');
            retryBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            `;
            retryBtn.addEventListener('click', () => this.retryUpload(uploadItem.id));

            // Discard button
            const discardBtn = document.createElement('button');
            discardBtn.classList.add('resizable-image__discard-btn');
            discardBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            `;
            discardBtn.addEventListener('click', () => this.discardUpload(uploadItem.id));

            buttonContainer.appendChild(retryBtn);
            buttonContainer.appendChild(discardBtn);
            progressInfo.appendChild(buttonContainer);

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

    async retryUpload(itemId) {
        const uploadIndex = this.uploadProgress.findIndex(item => item.id === itemId);
        const uploadItem = this.uploadProgress[uploadIndex];
        if (!uploadItem) return;

        uploadItem.retryCount++;
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;
        uploadItem.error = null;

        this.updateProgressItem(uploadIndex);

        try {
            await this.processFileUpload(uploadIndex);

            // Check if all uploads are complete
            const allComplete = this.uploadProgress.every(item => item.status === 'success');
            if (allComplete) {
                this.completeUpload();
            }
        } catch (error) {
            console.error('Retry failed:', error);
        }
    }

    discardUpload(itemId) {
        const uploadItem = this.uploadProgress.find(item => item.id === itemId);
        if (!uploadItem || uploadItem.status !== 'error') {
            return;
        }


        // Remove using the new method
        this.removeProgressItem(itemId);

        // Notify editor of changes
        this.notifyEditorChange();

        // Check if all remaining uploads are complete or if no uploads left
        if (this.uploadProgress.length === 0) {

            // Check if we have existing images in the gallery
            if (this.data.files && this.data.files.length > 0) {
                // Gallery already has "Add more" button, just ensure upload container is hidden
                this.hideUploadContainer();
            } else {
                this.showUploadContainer();
            }
        } else {
            const allComplete = this.uploadProgress.every(item => item.status === 'success');
            if (allComplete) {
                this.completeUpload();
            }
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

        // Allow empty files array (when all images are deleted)
        if (savedData.files && Array.isArray(savedData.files)) {
            return true; // Allow both empty and populated arrays
        }

        // Support legacy single file format
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

    async executeDeleteRequest(url) {
        // Extract file path from URL
        let filePath = url;
        if (filePath.includes('/storage/')) {
            const storageIndex = filePath.indexOf('/storage/');
            filePath = filePath.substring(storageIndex + '/storage/'.length);
        }

        // Ensure documents/ prefix
        if (!filePath.startsWith('documents/')) {
            filePath = 'documents/' + filePath;
        }

        try {
            const response = await fetch(this.config.endpoints.delete || '/delete-image', {
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

    // Legacy method support
    deleteFromServer(url) {
        return this.executeDeleteRequest(url);
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

        // Clean up resize event listeners
        if (this.isResizing) {
            this.stopResize();
        }
    }

    /**
     * Called when Block is removed from the page
     * Clean up images from server
     */
    async removed() {
        if (this.data.files && Array.isArray(this.data.files)) {
            // Signal editor is busy during cleanup
            document.dispatchEvent(new CustomEvent('editor:busy'));

            try {
                // Delete all images from server
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
                console.error('Failed to cleanup images on block removal:', error);
                // Signal editor is free even on error
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('editor:free'));
                }, 0);
            }
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
        this.blockDragHandlers = null;
    }
}

export default ResizableImage;
