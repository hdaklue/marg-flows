/**
 * VideoUpload Plugin for EditorJS
 * Mimics ResizableImage structure for video file uploads with Video.js rendering
 * Only supports Video.js compatible formats (MP4, WebM, OGG)
 */

// Import validation utilities (for format validation and display)
import { 
    VIDEO_VALIDATION_CONFIG, 
    isVideoFormatSupported 
} from '../video-validation.js';

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

    static get pasteConfig() {
        return {
            files: {
                mimeTypes: [
                    'video/*'  // Accept all video types, let our validation handle the filtering
                ]
            }
        };
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

        // Initialize localization
        this.t = this.initializeLocalization();

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

        // Default configuration (fallback values, will be overridden by server config)
        this.defaultConfig = {
            endpoints: {
                byFile: '/upload-video',
                delete: '/delete-video'
            },
            additionalRequestHeaders: {},
            field: 'video',
            types: 'video/*',
            captionPlaceholder: this.t.captionPlaceholder,
            buttonContent: this.t.buttonContent,
            uploader: null,
            actions: [],
            // Fallback chunk settings (will be overridden by server config)
            chunkSize: 5 * 1024 * 1024,     // 5MB default chunk size
            maxFileSize: 100 * 1024 * 1024, // 100MB default max file size  
            useChunkedUpload: true
        };

        this.config = Object.assign(this.defaultConfig, this.config);

        // Bind methods
        this.onUpload = this.onUpload.bind(this);
        this.onPaste = this.onPaste.bind(this);
    }

    initializeLocalization() {
        // Detect current locale from HTML lang attribute or other sources
        const htmlElement = document.documentElement;
        const currentLocale = htmlElement.lang || 'en';
        const locale = currentLocale.split('-')[0]; // Get base locale (e.g., 'en' from 'en-US')
        
        // Define translations for VideoUpload plugin
        const translations = {
            'en': {
                captionPlaceholder: 'Enter video caption...',
                buttonContent: 'Select a video',
                uploadTitle: 'Upload a video',
                uploadSubtitle: 'Drag, paste, or click to select',
                addCaption: 'Add caption...',
                galleryCaptionPlaceholder: 'Add a caption for this video gallery...',
                addMore: 'Add more',
                processingTitle: 'Processing video...',
                processingSubtitle: 'Converting and uploading',
                replacementConfirm: 'This will replace the current video. Continue?',
                status: {
                    pending: 'Pending',
                    converting: 'Converting...',
                    uploading: 'Uploading...',
                    complete: 'Complete',
                    failed: 'Failed',
                    unknown: 'Unknown'
                },
                errors: {
                    invalidFormat: 'Invalid file format. Please select a video file.',
                    unsupportedFormat: 'Unsupported video format. Please use MP4, WebM, or OGV format for best compatibility.',
                    fileTooLarge: 'File is too large ({fileSize}MB). Maximum size allowed is {maxSize}MB.',
                    uploadFailed: 'Upload failed',
                    pasteProcessFailed: 'Failed to process pasted video file.',
                    unknownError: 'Upload failed',
                    videoNotSupported: 'Video Format Not Supported',
                    browserCompatibility: 'This video format cannot be played in your browser.',
                    formatSuggestion: 'Try converting it to MP4 or WebM format for better compatibility.',
                    downloadOriginal: 'Download Original File'
                },
                ui: {
                    uploadFailed: 'Upload Failed',
                    filesFailed: '{count} file(s) failed to upload',
                    retryFailedUploads: 'Retry Failed Uploads',
                    dismiss: 'Dismiss'
                }
            },
            'ar': {
                captionPlaceholder: 'أدخل تسمية توضيحية للفيديو...',
                buttonContent: 'اختر فيديو',
                uploadTitle: 'رفع فيديو',
                uploadSubtitle: 'اسحب أو الصق أو انقر للاختيار',
                addCaption: 'إضافة تسمية توضيحية...',
                galleryCaptionPlaceholder: 'أضف تسمية توضيحية لمعرض الفيديو...',
                addMore: 'إضافة المزيد',
                processingTitle: 'معالجة الفيديو...',
                processingSubtitle: 'التحويل والرفع',
                replacementConfirm: 'سيؤدي هذا إلى استبدال الفيديو الحالي. الاستمرار؟',
                status: {
                    pending: 'في الانتظار',
                    converting: 'جاري التحويل...',
                    uploading: 'جاري الرفع...',
                    complete: 'مكتمل',
                    failed: 'فشل',
                    unknown: 'غير معروف'
                },
                errors: {
                    invalidFormat: 'تنسيق ملف غير صالح. يرجى اختيار ملف فيديو.',
                    unsupportedFormat: 'تنسيق فيديو غير مدعوم. يرجى استخدام تنسيق MP4 أو WebM أو OGV للحصول على أفضل توافق.',
                    fileTooLarge: 'الملف كبير جداً ({fileSize} ميغابايت). الحد الأقصى المسموح هو {maxSize} ميغابايت.',
                    uploadFailed: 'فشل الرفع',
                    pasteProcessFailed: 'فشل في معالجة ملف الفيديو المُلصق.',
                    unknownError: 'فشل الرفع',
                    videoNotSupported: 'تنسيق الفيديو غير مدعوم',
                    browserCompatibility: 'لا يمكن تشغيل هذا التنسيق من الفيديو في متصفحك.',
                    formatSuggestion: 'جرب تحويله إلى تنسيق MP4 أو WebM للحصول على توافق أفضل.',
                    downloadOriginal: 'تحميل الملف الأصلي'
                },
                ui: {
                    uploadFailed: 'فشل الرفع',
                    filesFailed: 'فشل رفع {count} ملف',
                    retryFailedUploads: 'إعادة محاولة الملفات الفاشلة',
                    dismiss: 'إغلاق'
                }
            }
        };
        
        return translations[locale] || translations['en'];
    }

    /**
     * Validate video file using dynamic configuration from server
     */
    validateVideoFile(file) {
        const errors = [];

        // Check if it's a video file
        if (!file.type.startsWith('video/')) {
            errors.push(this.t.errors.invalidFormat);
            return { isValid: false, errors };
        }

        // Check format compatibility (use static config for supported formats)
        if (!isVideoFormatSupported(file)) {
            errors.push(this.t.errors.unsupportedFormat);
            return { isValid: false, errors };
        }

        // Check file size using dynamic config from server
        if (file.size > this.config.maxFileSize) {
            const fileSizeMB = Math.round(file.size / (1024 * 1024));
            const maxSizeMB = Math.round(this.config.maxFileSize / (1024 * 1024));
            const errorMessage = this.t.errors.fileTooLarge
                .replace('{fileSize}', fileSizeMB)
                .replace('{maxSize}', maxSizeMB);
            errors.push(errorMessage);
            return { isValid: false, errors };
        }

        return { isValid: true, errors: [] };
    }



    /**
     * Handle pasted files from clipboard (EditorJS built-in paste handling)
     */
    onPaste(event) {
        switch (event.type) {
            case 'file':
                // EditorJS passes the file in event.detail.file
                const file = event.detail.file;
                if (file) {
                    return this.handlePastedFile(file);
                }
                break;
        }
        return false;
    }

    async handlePastedFile(file) {
        if (this.readOnly) {
            return false;
        }

        // Check if it's a video file
        if (!file.type.startsWith('video/')) {
            return false;
        }

        // Validate file using dynamic config from server
        const validation = this.validateVideoFile(file);
        if (!validation.isValid) {
            validation.errors.forEach(error => {
                this.showErrorMessage(error, file.name);
            });
            return false;
        }

        // Check if we already have a video (single video only)
        if (this.data.file && (this.data.file.url || this.data.file.filename)) {
            // Show notification that video will be replaced
            if (!confirm(this.t.replacementConfirm)) {
                return false;
            }
        }

        try {
            // Handle the pasted video file
            await this.handleUpload([file]);
            return true;
        } catch (error) {
            console.error('Paste upload failed:', error);
            this.showErrorMessage(this.t.errors.pasteProcessFailed, file.name);
            return false;
        }
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('video-upload');

        // Store wrapper reference for later use
        this.wrapper = wrapper;

        // Add drag-drop functionality to the entire block wrapper
        this.setupBlockDragDrop(wrapper);

        // Add paste functionality
        this.setupPasteHandling(wrapper);

        // Check if we have a video (check for filename since we now store filenames)
        const hasVideo = this.data.file && (this.data.file.url || this.data.file.filename);

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
            const uploadArea = wrapper.querySelector('.ce-video-upload__upload-area');
            if (uploadArea) {
                uploadArea.classList.add('drag-over');
            }
        };

        const handleBlockDragLeave = (e) => {
            e.preventDefault();
            e.stopPropagation();
            // Only remove class if leaving the wrapper entirely
            if (!wrapper.contains(e.relatedTarget)) {
                const uploadArea = wrapper.querySelector('.ce-video-upload__upload-area');
                if (uploadArea) {
                    uploadArea.classList.remove('drag-over');
                }
            }
        };

        const handleBlockDrop = (e) => {
            e.preventDefault();
            e.stopPropagation();
            const uploadArea = wrapper.querySelector('.ce-video-upload__upload-area');
            if (uploadArea) {
                uploadArea.classList.remove('drag-over');
            }

            if (!this.readOnly && !this.uploading && e.dataTransfer.files.length) {
                const videoFiles = Array.from(e.dataTransfer.files).filter(file =>
                    file.type.startsWith('video/')
                );

                // Validate files using dynamic config from server
                const validFiles = [];
                videoFiles.forEach(file => {
                    const validation = this.validateVideoFile(file);
                    if (validation.isValid) {
                        validFiles.push(file);
                    } else {
                        validation.errors.forEach(error => {
                            this.showErrorMessage(error, file.name);
                        });
                    }
                });

                if (validFiles.length > 0) {
                    this.handleUpload(validFiles);
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

    setupPasteHandling(wrapper) {
        if (this.readOnly) {
            return;
        }

        const handlePaste = async (e) => {
            // Only handle paste if this block is focused/active
            if (!wrapper.contains(document.activeElement) &&
                !wrapper.classList.contains('ce-block--focused')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const items = e.clipboardData?.items;
            if (!items) return;

            const videoFiles = [];

            // Check clipboard items for video files
            Array.from(items).forEach(item => {
                if (item.kind === 'file' && item.type.startsWith('video/')) {
                    const file = item.getAsFile();
                    if (file) {
                        videoFiles.push(file);
                    }
                }
            });

            if (videoFiles.length > 0) {
                // Validate files using single source of truth (checks both format AND size)
                const validFiles = [];
                const invalidFiles = [];

                videoFiles.forEach(file => {
                    const validation = this.validateVideoFile(file);
                    if (validation.isValid) {
                        validFiles.push(file);
                    } else {
                        validation.errors.forEach(error => {
                            invalidFiles.push({ file, error });
                        });
                    }
                });

                // Show errors for invalid files
                invalidFiles.forEach(({ file, error }) => {
                    this.showErrorMessage(error, file.name);
                });

                if (validFiles.length > 0) {
                    // Check if we already have a video (single video only)
                    if (this.data.file && (this.data.file.url || this.data.file.filename)) {
                        if (!confirm(this.t.replacementConfirm)) {
                            return;
                        }
                    }

                    // Handle the first valid video file (single video plugin)
                    await this.handleUpload([validFiles[0]]);
                }
            }
        };

        // Add paste event listener
        document.addEventListener('paste', handlePaste);

        // Store handler for cleanup
        this.pasteHandler = handlePaste;

        // Make wrapper focusable for better paste targeting
        if (!wrapper.hasAttribute('tabindex')) {
            wrapper.setAttribute('tabindex', '-1');
        }
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
        fileInput.classList.add('ce-video-upload__file-input');
        fileInput.id = inputId;

        // Create modern upload area
        const uploadArea = document.createElement('div');
        uploadArea.classList.add('ce-video-upload__upload-area');
        uploadArea.setAttribute('for', inputId);

        uploadArea.innerHTML = `
            <div class="ce-video-upload__upload-content">
                <div class="ce-video-upload__upload-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                    </svg>
                </div>
                <div class="ce-video-upload__upload-text">
                    <div class="ce-video-upload__upload-title">${this.t.uploadTitle}</div>
                    <div class="ce-video-upload__upload-subtitle">${this.t.uploadSubtitle}</div>
                </div>
                <div class="ce-video-upload__upload-formats">${VIDEO_VALIDATION_CONFIG.supportedExtensions.map(ext => ext.toUpperCase()).join(', ')} up to ${Math.round(this.config.maxFileSize / (1024 * 1024))}MB</div>
            </div>
        `;

        // Store references for cleanup
        this.fileInput = fileInput;
        this.uploadContainer = uploadContainer;
        this.uploadArea = uploadArea;

        const handleFileChange = (e) => {
            e.preventDefault();
            const files = Array.from(e.target.files);
            if (files.length > 0 && !this.uploading) {
                this.handleUpload(files);
            }
            // Clear input to allow same file selection
            e.target.value = '';
        };

        const handleAreaClick = (e) => {
            e.preventDefault();
            if (!this.readOnly && !this.uploading) {
                fileInput.click();
            }
        };

        // Attach event listeners
        fileInput.addEventListener('change', handleFileChange);
        uploadArea.addEventListener('click', handleAreaClick);

        // Store event handlers for cleanup
        this.eventHandlers = {
            handleFileChange,
            handleAreaClick
        };

        uploadContainer.appendChild(uploadArea);
        uploadContainer.appendChild(fileInput);
        wrapper.appendChild(uploadContainer);
    }

    createVideoElement(wrapper) {
        const videoContainer = document.createElement('div');
        videoContainer.classList.add('ce-video-upload__container');

        // Create thumbnail container with aspect ratio
        const thumbnailContainer = document.createElement('div');
        thumbnailContainer.classList.add('ce-video-upload__thumbnail-container');

        // Get aspect ratio from data
        const aspectRatio = this.data.file.aspect_ratio || '16:9';
        const [width, height] = aspectRatio.split(':');
        const aspectRatioValue = parseFloat(width) / parseFloat(height);

        // Set container dimensions based on aspect ratio but keep consistent max size
        const maxWidth = 200;
        const maxHeight = 120;

        let containerWidth, containerHeight;
        if (aspectRatioValue > maxWidth / maxHeight) {
            // Wide video - constrain by width
            containerWidth = maxWidth;
            containerHeight = maxWidth / aspectRatioValue;
        } else {
            // Tall video - constrain by height
            containerHeight = maxHeight;
            containerWidth = maxHeight * aspectRatioValue;
        }

        thumbnailContainer.style.cssText = `
            width: ${containerWidth}px;
            height: ${containerHeight}px;
            position: relative;
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            background: #f3f4f6;
        `;

        // Create thumbnail image element
        const thumbnail = document.createElement('img');
        thumbnail.classList.add('ce-video-upload__thumbnail');
        thumbnail.style.cssText = `
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        `;
        thumbnail.loading = 'lazy';

        // Use thumbnail from server or fallback to video icon
        if (this.data.file.thumbnail) {
            thumbnail.src = this.data.file.thumbnail;
            thumbnail.alt = 'Video thumbnail';
            thumbnailContainer.appendChild(thumbnail);
        } else {
            // Fallback: use a default video icon
            thumbnailContainer.style.display = 'flex';
            thumbnailContainer.style.alignItems = 'center';
            thumbnailContainer.style.justifyContent = 'center';
            thumbnailContainer.innerHTML = `
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="#9ca3af" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                </svg>
            `;
        }

        // Add play icon overlay
        const playIcon = document.createElement('div');
        playIcon.classList.add('ce-video-upload__play-icon');
        playIcon.innerHTML = `
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" fill="rgba(0,0,0,0.7)"/>
                <path d="M10 8l6 4-6 4V8z" fill="white"/>
            </svg>
        `;
        playIcon.style.cssText = `
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            cursor: pointer;
            opacity: 0.9;
            transition: opacity 0.2s ease;
            pointer-events: none;
        `;
        // Note: No click handler on play icon - it's just visual

        // Add click handler to open modal (only on container)
        thumbnailContainer.addEventListener('click', () => this.openModal());

        // Add play icon to thumbnail container
        if (this.data.file.thumbnail) {
            thumbnailContainer.appendChild(playIcon);
        }

        // Wrapper for thumbnail container and delete button
        const thumbnailWrapper = document.createElement('div');
        thumbnailWrapper.style.cssText = `
            position: relative;
            display: inline-block;
        `;
        thumbnailWrapper.appendChild(thumbnailContainer);

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

        // Caption input (compact for document editing)
        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.classList.add('ce-video-upload__caption');
        captionInput.placeholder = this.t.addCaption;
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
        if (this.data.file && (this.data.file.url || this.data.file.filename)) {
            const videoUrl = this.data.file.url || this.resolveVideoUrl(this.data.file.filename);
            this.handleDeleteFile(videoUrl);
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
            <span class="video-upload__add-more-text">${this.t.addMore}</span>
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
        captionInput.placeholder = this.t.galleryCaptionPlaceholder;
        captionInput.classList.add('video-upload__caption');
        captionInput.value = this.data.caption || '';

        captionInput.addEventListener('input', (e) => {
            this.data.caption = e.target.value;
        });

        return captionInput;
    }

    openModal() {
        if (!this.data.file || (!this.data.file.url && !this.data.file.filename)) return;
        
        // Get the video URL (resolve from filename if needed)
        const videoUrl = this.data.file.url || this.resolveVideoUrl(this.data.file.filename);
        if (!videoUrl) return;

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
        source.src = videoUrl;
        source.type = this.getVideoMimeType(videoUrl);
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

        // Lazy load and initialize Video.js after DOM insertion
        requestAnimationFrame(async () => {
            try {
                // Check if Video.js is already loaded
                if (typeof window.videojs === 'undefined') {
                    console.log('Lazy loading Video.js...');
                    // Dynamically import Video.js
                    const { default: videojs } = await import('video.js');
                    window.videojs = videojs;
                }

                const player = window.videojs(uniqueId, {
                    responsive: true,
                    preload: 'metadata',
                    controls: true,
                    html5: {
                        vhs: {
                            overrideNative: !window.videojs.browser.IS_SAFARI
                        },
                        nativeVideoTracks: false,
                        nativeAudioTracks: false,
                        nativeTextTracks: false
                    }
                });

                // Handle Video.js errors
                player.ready(() => {
                    console.log('Modal Video.js player ready with aspect ratio:', aspectRatio);

                    // Add error handler
                    player.on('error', () => {
                        const error = player.error();
                        console.warn('Video.js playback error:', error);

                        if (error && error.code === 4) {
                            // Media source not supported - show helpful message
                            this.showVideoCompatibilityError(modalVideo, aspectRatioContainer);
                        }
                    });

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
                // Fallback: use native video controls
                modalVideo.controls = true;
            }
        });

        // Event handlers
        const closeModal = () => {
            // Dispose Video.js player before removing modal
            try {
                if (window.videojs && window.videojs.getPlayer) {
                    const player = window.videojs.getPlayer(uniqueId);
                    if (player) {
                        player.dispose();
                    }
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

        // Client-side validation using single source of truth
        const validFiles = [];
        const invalidFiles = [];

        // Validate files client-side using dynamic config from server
        for (const file of fileArray) {
            const validation = this.validateVideoFile(file);
            if (validation.isValid) {
                validFiles.push(file);
            } else {
                validation.errors.forEach(error => {
                    invalidFiles.push({ file, error });
                });
            }
        }

        // Show errors for invalid files immediately
        if (invalidFiles.length > 0) {
            invalidFiles.forEach(({ file, error }) => {
                this.showErrorMessage(error, file.name);
            });
        }

        // If no valid files, don't proceed
        if (validFiles.length === 0) {
            return;
        }

        this.uploading = true;

        // Initialize upload tracking for valid files only
        this.uploadProgress = validFiles.map((file, index) => ({
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
            // No conversion needed - only Video.js compatible formats are allowed
            const fileToUpload = uploadItem.file;

            // Perform the actual upload
            const response = await this.performUpload(fileToUpload);

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

            // Show user-friendly error message (auto-dismiss server errors)
            this.showErrorMessage(error.message || this.t.errors.uploadFailed, uploadItem.file.name, true);
        }
    }

    async performUpload(file) {
        // Single method for actual upload logic
        if (this.config.uploader && typeof this.config.uploader.uploadByFile === 'function') {
            return await this.config.uploader.uploadByFile(file);
        } else {
            // Check if we should use chunked upload
            if (this.config.useChunkedUpload && file.size > this.config.chunkSize) {
                return await this.executeChunkedUpload(file);
            } else {
                return await this.executeUploadRequest(file);
            }
        }
    }

    /**
     * Execute chunked upload for large files
     */
    async executeChunkedUpload(file) {
        const fileKey = this.generateFileKey();
        const fileName = file.name;
        const chunkSize = this.config.chunkSize;
        const totalChunks = Math.ceil(file.size / chunkSize);

        console.log(`Starting chunked upload: ${fileName} (${totalChunks} chunks)`);

        // Signal editor is busy during upload
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            // Upload each chunk sequentially
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const start = chunkIndex * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);

                console.log(`Uploading chunk ${chunkIndex + 1}/${totalChunks}`);

                const chunkFile = new File([chunk], fileName, { type: file.type });
                const response = await this.uploadChunk(chunkFile, fileKey, fileName, chunkIndex, totalChunks);

                // Update progress based on chunk completion
                const progress = Math.round(((chunkIndex + 1) / totalChunks) * 100);
                this.updateUploadProgress(progress);

                // If this was the last chunk and upload is complete
                if (response.completed) {
                    console.log('Chunked upload completed successfully');
                    return response;
                }
            }

            throw new Error('Chunked upload completed but no final response received');

        } catch (error) {
            console.error('Chunked upload failed:', error);
            throw error;
        }
    }

    /**
     * Upload a single chunk
     */
    async uploadChunk(chunkFile, fileKey, fileName, chunkIndex, totalChunks) {
        const formData = new FormData();
        formData.append(this.config.field, chunkFile);
        formData.append('fileKey', fileKey);
        formData.append('fileName', fileName);
        formData.append('chunk', chunkIndex.toString());
        formData.append('chunks', totalChunks.toString());

        const response = await fetch(this.config.endpoints.byFile, {
            method: 'POST',
            body: formData,
            headers: {
                ...this.config.additionalRequestHeaders,
                // Don't set Content-Type for FormData, let browser set it with boundary
            }
        });

        // Parse JSON response
        let json;
        try {
            json = await response.json();
        } catch (parseError) {
            throw new Error(`Invalid response format for chunk ${chunkIndex}: ${response.status}`);
        }

        // Validate response success
        if (json.success === false || !response.ok) {
            throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
        }

        return json;
    }

    /**
     * Generate a unique file key for chunked uploads
     */
    generateFileKey() {
        return `video_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Update upload progress (can be overridden for custom progress handling)
     */
    updateUploadProgress(progress) {
        // This can be used to update progress bars or other UI elements
        console.log(`Upload progress: ${progress}%`);
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

    extractFilenameFromUrl(url) {
        if (!url) return null;
        
        // Handle both full URLs and paths
        let path = url;
        
        // If it's a full URL, extract the path part
        if (url.includes('://')) {
            try {
                const urlObj = new URL(url);
                path = urlObj.pathname;
            } catch (e) {
                // If URL parsing fails, use the original string
                path = url;
            }
        }
        
        // Remove leading slash if present
        if (path.startsWith('/')) {
            path = path.substring(1);
        }
        
        // Extract just the filename from paths like 'storage/documents/videos/tenant_id/document_id/filename.mp4'
        const segments = path.split('/');
        return segments[segments.length - 1];
    }
    
    resolveVideoUrl(filename) {
        if (!filename) return null;
        
        // If it's already a full URL (backward compatibility), return as-is
        if (filename.includes('://') || filename.startsWith('/')) {
            return filename;
        }
        
        // Build URL using config baseDirectory
        if (this.config.baseDirectory) {
            return `${this.config.baseDirectory}/${filename}`;
        }
        
        // Fallback to default pattern
        return `/storage/documents/videos/${filename}`;
    }

    addUploadedFile(response) {
        // Extract filename from the full URL for storage
        const fullUrl = response.url || response.file?.url;
        const filename = this.extractFilenameFromUrl(fullUrl);
        
        const fileData = {
            filename: filename,  // Store only filename
            thumbnail: response.thumbnail || response.file?.thumbnail || null,
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

        // Check if we have a video and render accordingly (check for filename since we now store filenames)
        const hasVideo = this.data.file && (this.data.file.url || this.data.file.filename);
        if (hasVideo) {
            this.createVideoElement(this.wrapper);
        } else {
            this.createUploadInterface(this.wrapper);
        }

        // Check if any uploads failed
        const hasErrors = this.uploadProgress.some(item => item.status === 'error');
        const successCount = this.uploadProgress.filter(item => item.status === 'success').length;
        const errorItems = this.uploadProgress.filter(item => item.status === 'error');

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
            // Show error state with retry options
            this.showErrorState(errorItems);
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
            const fileUrl = file.url || this.resolveVideoUrl(file.filename);
            if (fileUrl) {
                await this.executeDeleteRequest(fileUrl);
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
        const existingProgress = this.wrapper.querySelector('.ce-video-upload__uploading');
        if (existingProgress) {
            existingProgress.remove();
        }

        // Create clean uploading UI
        const uploadingContainer = document.createElement('div');
        uploadingContainer.classList.add('ce-video-upload__uploading');

        uploadingContainer.innerHTML = `
            <div class="ce-video-upload__uploading-spinner">
                <svg class="ce-video-upload__spinner" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                        <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                        <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                    </svg>
                </div>
                <div class="ce-video-upload__uploading-text">
                    <div class="ce-video-upload__uploading-title">${this.t.processingTitle}</div>
                    <div class="ce-video-upload__uploading-subtitle">${this.t.processingSubtitle}</div>
                </div>
            </div>
        `;

        this.wrapper.appendChild(uploadingContainer);
    }

    hideInlineProgress() {
        if (this.wrapper) {
            const uploadingContainer = this.wrapper.querySelector('.ce-video-upload__uploading');
            if (uploadingContainer) {
                uploadingContainer.remove();
            }
        }
    }

    showErrorMessage(message, fileName, autoDismiss = false) {
        // Create or update error notification
        let errorContainer = this.wrapper.querySelector('.ce-video-upload__error-notification');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.classList.add('ce-video-upload__error-notification');
            this.wrapper.appendChild(errorContainer);
        }

        errorContainer.innerHTML = `
            <div class="ce-video-upload__error-content">
                <div class="ce-video-upload__error-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                        <path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="ce-video-upload__error-details">
                    <div class="ce-video-upload__error-title">${this.t.ui.uploadFailed}</div>
                    <div class="ce-video-upload__error-message">${message}</div>
                    ${fileName ? `<div class="ce-video-upload__error-file">File: ${fileName}</div>` : ''}
                </div>
                <button class="ce-video-upload__error-dismiss" type="button" title="Dismiss error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

        // Add dismiss button functionality
        const dismissBtn = errorContainer.querySelector('.ce-video-upload__error-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                if (errorContainer && errorContainer.parentNode) {
                    errorContainer.remove();
                }
            });
        }

        // Only auto-dismiss if explicitly requested (for server errors)
        if (autoDismiss) {
            setTimeout(() => {
                if (errorContainer && errorContainer.parentNode) {
                    errorContainer.remove();
                }
            }, 5000);
        }
    }


    showVideoCompatibilityError(videoElement, container) {
        // Replace video player with error message
        const errorMessage = document.createElement('div');
        errorMessage.classList.add('ce-video-upload__video-error');
        errorMessage.innerHTML = `
            <div class="ce-video-upload__video-error-content">
                <div class="ce-video-upload__video-error-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="ce-video-upload__video-error-details">
                    <div class="ce-video-upload__video-error-title">${this.t.errors.videoNotSupported}</div>
                    <div class="ce-video-upload__video-error-message">
                        ${this.t.errors.browserCompatibility}
                        <br>${this.t.errors.formatSuggestion}
                    </div>
                    <a href="${videoUrl}" download class="ce-video-upload__video-error-download">
                        ${this.t.errors.downloadOriginal}
                    </a>
                </div>
            </div>
        `;

        // Replace the video element with error message
        container.innerHTML = '';
        container.appendChild(errorMessage);
    }

    showErrorState(errorItems) {
        this.hideInlineProgress();

        const errorContainer = document.createElement('div');
        errorContainer.classList.add('ce-video-upload__error-state');

        errorContainer.innerHTML = `
            <div class="ce-video-upload__error-header">
                <div class="ce-video-upload__error-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                        <path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="ce-video-upload__error-info">
                    <div class="ce-video-upload__error-title">${this.t.ui.uploadFailed}</div>
                    <div class="ce-video-upload__error-subtitle">${this.t.ui.filesFailed.replace('{count}', errorItems.length)}</div>
                </div>
            </div>
            <div class="ce-video-upload__error-list">
                ${errorItems.map(item => `
                    <div class="ce-video-upload__error-item">
                        <div class="ce-video-upload__error-file-info">
                            <div class="ce-video-upload__error-file-name">${item.name}</div>
                            <div class="ce-video-upload__error-file-message">${item.error}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="ce-video-upload__error-actions">
                <button class="ce-video-upload__retry-btn" type="button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M1 4v6h6M23 20v-6h-6"/>
                        <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                    </svg>
                    ${this.t.ui.retryFailedUploads}
                </button>
                <button class="ce-video-upload__dismiss-btn" type="button">${this.t.ui.dismiss}</button>
            </div>
        `;

        this.wrapper.appendChild(errorContainer);

        // Add event listeners
        const retryBtn = errorContainer.querySelector('.ce-video-upload__retry-btn');
        const dismissBtn = errorContainer.querySelector('.ce-video-upload__dismiss-btn');

        retryBtn.addEventListener('click', () => {
            errorContainer.remove();
            this.retryFailedUploads();
        });

        dismissBtn.addEventListener('click', () => {
            errorContainer.remove();
            this.uploadProgress = [];
            this.showUploadContainer();
        });
    }

    async retryFailedUploads() {
        const failedItems = this.uploadProgress.filter(item => item.status === 'error');
        if (failedItems.length === 0) return;

        // Reset failed items to pending
        failedItems.forEach(item => {
            item.status = 'pending';
            item.progress = 0;
            item.error = null;
            item.retryCount = (item.retryCount || 0) + 1;
        });

        // Show progress and retry uploads
        this.showInlineProgress();

        try {
            for (let i = 0; i < this.uploadProgress.length; i++) {
                if (this.uploadProgress[i].status === 'pending') {
                    await this.processFileUpload(i);
                }
            }
            this.completeUpload();
        } catch (error) {
            console.error('Retry upload process error:', error);
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
        progressThumbnail.classList.remove('status-pending', 'status-converting', 'status-uploading', 'status-success', 'status-error');
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
            case 'converting':
                return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-spin">
                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
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
            case 'pending': return this.t.status.pending;
            case 'converting': return this.t.status.converting;
            case 'uploading': return this.t.status.uploading;
            case 'success': return this.t.status.complete;
            case 'error': return this.t.status.failed;
            default: return this.t.status.unknown;
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
        if (savedData.file && (savedData.file.url || savedData.file.filename)) {
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

        if (this.uploadArea && this.eventHandlers) {
            this.uploadArea.removeEventListener('click', this.eventHandlers.handleAreaClick);
        }

        // Clean up block-level drag-drop listeners
        if (this.wrapper && this.blockDragHandlers) {
            this.wrapper.removeEventListener('dragover', this.blockDragHandlers.handleBlockDragOver);
            this.wrapper.removeEventListener('dragleave', this.blockDragHandlers.handleBlockDragLeave);
            this.wrapper.removeEventListener('drop', this.blockDragHandlers.handleBlockDrop);
        }

        // Clean up paste handler
        if (this.pasteHandler) {
            document.removeEventListener('paste', this.pasteHandler);
        }
    }

    async removed() {
        if (this.data.files && Array.isArray(this.data.files)) {
            // Signal editor is busy during cleanup
            document.dispatchEvent(new CustomEvent('editor:busy'));

            try {
                // Delete all videos from server
                const deletePromises = this.data.files.map(file => {
                    const fileUrl = file.url || this.resolveVideoUrl(file.filename);
                    if (fileUrl) {
                        return this.executeDeleteRequest(fileUrl);
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
                if (window.videojs && window.videojs.getPlayer) {
                    const player = window.videojs.getPlayer(this.currentVideoId);
                    if (player) {
                        player.dispose();
                    }
                }
            } catch (error) {
                console.warn('Error disposing Video.js player:', error);
            }
        }

        // Cleanup all event listeners
        this.cleanupEventListeners();

        // Clear references
        this.fileInput = null;
        this.uploadArea = null;
        this.uploadContainer = null;
        this.wrapper = null;
        this.eventHandlers = null;
        this.blockDragHandlers = null;
        this.currentVideoId = null;
    }
}

export default VideoUpload;
