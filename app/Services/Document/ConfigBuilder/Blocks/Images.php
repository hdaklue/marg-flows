<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\ImagesConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;
use App\Services\Upload\ChunkConfigManager;
use App\Support\FileSize;
use App\Support\FileTypes;

final class Images implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'ResizableImage';

    private array $config = [
        'endpoints' => [
            'byFile' => null,
            'byUrl' => null,
            'delete' => null,
        ],
        'additionalRequestHeaders' => [
            'X-CSRF-TOKEN' => '',
        ],
        'types' => null, // Will be set from FileTypes utility
        'field' => 'image',
        'captionPlaceholder' => 'Enter image caption...',
        'buttonContent' => 'Select an image',
        'maxFileSize' => null, // Will be set from FileSize utility
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
        private string $plan = 'simple',
    ) {
        // Get chunk configuration from ChunkConfigManager for images
        $chunkConfig = ChunkConfigManager::forImages($this->plan);

        // Default endpoints - will be overridden by forDocument() method
        $this->config['endpoints']['byFile'] = null;
        $this->config['endpoints']['byUrl'] = null;
        $this->config['endpoints']['delete'] = null;
        $this->config['types'] = FileTypes::getWebImageFormatsAsValidationString();
        $this->config['maxFileSize'] = $chunkConfig->maxFileSize;

        // Add chunk configuration for frontend (even if not used, provides consistency)
        $this->config['chunkConfig'] = $chunkConfig->toArrayForFrontend();
    }

    public function endpoints(array $endpoints): self
    {
        $this->config['endpoints'] = array_merge($this->config['endpoints'], $endpoints);

        return $this;
    }

    public function uploadEndpoint(string $endpoint): self
    {
        $this->config['endpoints']['byFile'] = $endpoint;
        $this->config['endpoints']['byUrl'] = $endpoint;

        return $this;
    }

    public function deleteEndpoint(string $endpoint): self
    {
        $this->config['endpoints']['delete'] = $endpoint;

        return $this;
    }

    public function types(string $types): self
    {
        $this->config['types'] = $types;

        return $this;
    }

    public function field(string $field): self
    {
        $this->config['field'] = $field;

        return $this;
    }

    public function captionPlaceholder(string $placeholder): self
    {
        $this->config['captionPlaceholder'] = $placeholder;

        return $this;
    }

    public function buttonContent(string $content): self
    {
        $this->config['buttonContent'] = $content;

        return $this;
    }

    public function additionalRequestHeaders(array $headers): self
    {
        $this->config['additionalRequestHeaders'] = array_merge(
            $this->config['additionalRequestHeaders'],
            $headers,
        );

        return $this;
    }

    public function maxFileSize(int $bytes): self
    {
        $this->config['maxFileSize'] = $bytes;

        return $this;
    }

    public function maxFileSizeMB(float $megabytes): self
    {
        $this->config['maxFileSize'] = FileSize::fromMB($megabytes);

        return $this;
    }

    public function inlineToolBar(bool $enabled = true): self
    {
        $this->inlineToolBar = $enabled;

        return $this;
    }

    public function forDocument(string $documentId): self
    {
        $this->config['endpoints']['byFile'] = route('documents.upload-image', [
            'document' => $documentId,
        ]);
        $this->config['endpoints']['byUrl'] = route('documents.upload-image', [
            'document' => $documentId,
        ]);
        $this->config['endpoints']['delete'] = route('documents.delete-image', [
            'document' => $documentId,
        ]);

        return $this;
    }

    public function baseDirectory(string $tenantId, string $documentId): self
    {
        // Use document-specific serving route instead of generic file serving
        $baseUrl = rtrim(url(''), '/');
        $this->config['secureFileEndpoint'] = "{$baseUrl}/documents/{$documentId}/serve";

        return $this;
    }

    public function forPlan(string $plan): self
    {
        $this->plan = $plan;

        // Reconfigure with new plan
        $chunkConfig = ChunkConfigManager::forImages($this->plan);
        $this->config['maxFileSize'] = $chunkConfig->maxFileSize;
        $this->config['chunkConfig'] = $chunkConfig->toArrayForFrontend();

        return $this;
    }

    public function toArray(): array
    {
        return $this->build()->toArray();
    }

    public function toJson($options = 0): string
    {
        return $this->build()->toJson($options);
    }

    public function toPrettyJson(): string
    {
        return $this->build()->toPrettyJson();
    }

    public function generateJavaScript(): string
    {
        $config = json_encode($this->config, JSON_UNESCAPED_SLASHES);

        return <<<JS
/**
 * ResizableImage Plugin for EditorJS
 * Generated by Images.php ConfigBuilder
 */
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
        this.config = Object.assign({$config}, config || {});
        this.data = data || {};

        // Initialize localization
        this.t = this.initializeLocalization();

        // Convert old single image format to new array format
        if (this.data.file && !Array.isArray(this.data.files)) {
            this.data.files = [this.data.file];
            delete this.data.file;
        }
        
        // Convert legacy URL-based data to filename-based data
        if (this.data.files) {
            this.data.files = this.data.files.map(file => {
                if (file.url && !file.filename) {
                    file.filename = file.url.split('/').pop();
                    delete file.url;
                }
                return file;
            });
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

        // Bind methods
        this.onUpload = this.onUpload.bind(this);
    }

    initializeLocalization() {
        const htmlElement = document.documentElement;
        const currentLocale = htmlElement.lang || 'en';
        const locale = currentLocale.split('-')[0];
        
        const translations = {
            'en': {
                captionPlaceholder: 'Enter image caption...',
                buttonContent: 'Select an image',
                galleryCaptionPlaceholder: 'Add a caption for this gallery...',
                addMore: 'Add more',
                uploading: 'Uploading...',
                status: {
                    pending: 'Pending',
                    uploading: 'Uploading...',
                    complete: 'Complete',
                    failed: 'Failed',
                    unknown: 'Unknown'
                },
                fileSize: {
                    bytes: 'Bytes',
                    kb: 'KB',
                    mb: 'MB',
                    gb: 'GB'
                }
            },
            'ar': {
                captionPlaceholder: 'أدخل تسمية توضيحية للصورة...',
                buttonContent: 'اختر صورة',
                galleryCaptionPlaceholder: 'أضف تسمية توضيحية لهذا المعرض...',
                addMore: 'إضافة المزيد',
                uploading: 'جاري الرفع...',
                status: {
                    pending: 'في الانتظار',
                    uploading: 'جاري الرفع...',
                    complete: 'مكتمل',
                    failed: 'فشل',
                    unknown: 'غير معروف'
                },
                fileSize: {
                    bytes: 'بايت',
                    kb: 'كيلوبايت',
                    mb: 'ميغابايت',
                    gb: 'جيجابايت'
                }
            }
        };
        
        return translations[locale] || translations['en'];
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('resizable-image');
        this.wrapper = wrapper;

        this.setupBlockDragDrop(wrapper);

        const hasImages = (this.data.files && this.data.files.length > 0) ||
            (this.data.file && this.data.file.filename && this.data.file.filename.trim() !== '');

        if (hasImages) {
            this.createUploadInterface(wrapper);
            if (this.data.files && this.data.files.length > 0) {
                this.hideUploadContainer();
                this.renderGallery();
            } else {
                this.createImageElement(wrapper);
            }
        } else {
            this.createUploadInterface(wrapper);
        }

        return wrapper;
    }

    setupBlockDragDrop(wrapper) {
        if (this.readOnly) return;

        const handleBlockDragOver = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.add('resizable-image--dragover');
        };

        const handleBlockDragLeave = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!wrapper.contains(e.relatedTarget)) {
                wrapper.classList.remove('resizable-image--dragover');
            }
        };

        const handleBlockDrop = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.classList.remove('resizable-image--dragover');

            if (!this.readOnly && !this.uploading && e.dataTransfer.files.length) {
                const files = Array.from(e.dataTransfer.files).filter(file => {
                    if (!file.type.startsWith('image/')) return false;
                    if (this.config.maxFileSize && file.size > this.config.maxFileSize) {
                        console.warn(`File \${file.name} exceeds max size`);
                        return false;
                    }
                    return true;
                });
                if (files.length > 0) {
                    this.handleUpload(files);
                }
            }
        };

        wrapper.addEventListener('dragover', handleBlockDragOver);
        wrapper.addEventListener('dragleave', handleBlockDragLeave);
        wrapper.addEventListener('drop', handleBlockDrop);

        this.blockDragHandlers = {
            handleBlockDragOver,
            handleBlockDragLeave,
            handleBlockDrop
        };
    }

    createUploadInterface(wrapper) {
        if (this.readOnly) return;

        const uploadContainer = document.createElement('div');
        uploadContainer.classList.add('resizable-image__upload-container');

        const inputId = `resizable-image-input-\${Math.random().toString(36).substr(2, 9)}`;

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = this.config.types;
        fileInput.multiple = true;
        fileInput.classList.add('resizable-image__file-input');
        fileInput.id = inputId;

        const uploadLabel = document.createElement('label');
        uploadLabel.setAttribute('for', inputId);
        uploadLabel.classList.add('resizable-image__upload-button');
        uploadLabel.innerHTML = `
      <div class="resizable-image__upload-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
      </div>
      <div class="resizable-image__upload-text">\${this.t.buttonContent}</div>
    `;

        this.fileInput = fileInput;
        this.uploadContainer = uploadContainer;
        this.uploadLabel = uploadLabel;

        const handleFileChange = (e) => {
            e.preventDefault();
            const files = Array.from(e.target.files).filter(file => {
                if (this.config.maxFileSize && file.size > this.config.maxFileSize) {
                    console.warn(`File \${file.name} exceeds max size`);
                    return false;
                }
                return true;
            });
            
            if (files.length > 0 && !this.uploading) {
                this.handleUpload(files);
            }
            e.target.value = '';
        };

        const handleLabelClick = (e) => {
            e.preventDefault();
            if (!this.readOnly && !this.uploading) {
                fileInput.click();
            }
        };

        fileInput.addEventListener('change', handleFileChange);
        uploadLabel.addEventListener('click', handleLabelClick);

        this.eventHandlers = {
            handleFileChange,
            handleLabelClick
        };

        uploadContainer.appendChild(uploadLabel);
        uploadContainer.appendChild(fileInput);
        wrapper.appendChild(uploadContainer);
    }

    async handleUpload(files) {
        const fileArray = Array.isArray(files) ? files : [files];
        if (this.uploading) return;

        this.uploading = true;
        this.cleanupUploadState();

        this.uploadProgress = fileArray.map((file, index) => ({
            id: `upload-\${Date.now()}-\${Math.random().toString(36).substr(2, 9)}`,
            file,
            name: file.name,
            size: file.size,
            status: 'pending',
            progress: 0,
            error: null,
            retryCount: 0
        }));

        this.showInlineProgress();

        try {
            for (let i = 0; i < this.uploadProgress.length; i++) {
                await this.processFileUpload(i);
            }
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

        uploadItem.status = 'uploading';
        uploadItem.progress = 0;
        this.updateProgressItem(index);

        try {
            const response = await this.performUpload(uploadItem.file);
            const filename = response.file?.filename;
            if (!filename) {
                throw new Error('No filename in response');
            }

            this.addUploadedFile(response);
            uploadItem.status = 'success';
            uploadItem.progress = 100;
            this.updateProgressItem(index);

            const progressThumbnail = this.wrapper.querySelector(`.resizable-image__progress-thumbnail[data-id="\${uploadItem.id}"]`);
            if (progressThumbnail) {
                progressThumbnail.remove();
            }
        } catch (error) {
            uploadItem.status = 'error';
            uploadItem.error = error.message || 'Upload failed';
            this.updateProgressItem(index);
            console.error(`Upload failed for file \${index}:`, error);
        }
    }

    async performUpload(file) {
        if (this.config.uploader && typeof this.config.uploader.uploadByFile === 'function') {
            return await this.config.uploader.uploadByFile(file);
        } else {
            return await this.executeUploadRequest(file);
        }
    }

    async executeUploadRequest(file) {
        const formData = new FormData();
        formData.append(this.config.field, file);

        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            const response = await fetch(this.config.endpoints.byFile, {
                method: 'POST',
                body: formData,
                headers: this.config.additionalRequestHeaders
            });

            let json;
            try {
                json = await response.json();
            } catch (parseError) {
                throw new Error(`Invalid response format: \${response.status}`);
            }

            if (json.success === 0 || json.success === false) {
                throw new Error(json.message || 'Upload failed');
            }

            if (!response.ok) {
                throw new Error(`HTTP \${response.status}: \${response.statusText}`);
            }

            return json;
        } catch (error) {
            throw error;
        }
    }

    addUploadedFile(response) {
        const filename = response.file?.filename;
        if (!filename) {
            throw new Error('No filename in response');
        }
        
        const fileData = {
            filename: filename,
            caption: response.caption || '',
            width: response.width || null,
            height: response.height || null
        };
        this.data.files.push(fileData);
    }

    resolveImageUrl(filename) {
        if (!filename) return '';

        if (this.config.secureFileEndpoint) {
            return `\${this.config.secureFileEndpoint}/images/\${encodeURIComponent(filename)}`;
        }

        console.error('No secure file endpoint configured for ResizableImage plugin');
        return '';
    }

    completeUpload() {
        const hasErrors = this.uploadProgress.some(item => item.status === 'error');
        const successCount = this.uploadProgress.filter(item => item.status === 'success').length;

        if (successCount > 0) {
            this.renderGallery();
        }

        if (!hasErrors && successCount > 0) {
            this.hideUploadContainer();
            this.hideInlineProgress();
            this.uploadProgress = [];
            this.notifyEditorChange();
        } else if (hasErrors) {
            if (!this.data.files || this.data.files.length === 0) {
                this.showUploadContainer();
            } else {
                this.hideUploadContainer();
            }
            this.uploadProgress = this.uploadProgress.filter(item => item.status === 'error');
            if (successCount > 0) {
                this.notifyEditorChange();
            }
            return;
        } else {
            this.showUploadContainer();
            this.hideInlineProgress();
            this.uploadProgress = [];
        }
    }

    notifyEditorChange() {
        console.log('ResizableImage - upload completed, triggering save to persist image data');
        
        requestAnimationFrame(() => {
            if (this.blockAPI && this.blockAPI.dispatchChange) {
                this.blockAPI.dispatchChange();
            }
            
            setTimeout(() => {
                document.dispatchEvent(new CustomEvent('editor:free'));
            }, 0);
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
        const existingGallery = this.wrapper.querySelector('.resizable-image__gallery');
        if (existingGallery) {
            existingGallery.remove();
        }

        if (!this.data.files || this.data.files.length === 0) {
            if (!this.readOnly) {
                this.showUploadContainer();
            }
            return;
        }

        const galleryContainer = document.createElement('div');
        galleryContainer.classList.add('resizable-image__gallery');

        const thumbnailGrid = document.createElement('div');
        thumbnailGrid.classList.add('resizable-image__thumbnail-grid');

        this.data.files.forEach((fileData, index) => {
            const thumbnailItem = this.createThumbnail(fileData, index);
            thumbnailGrid.appendChild(thumbnailItem);
        });

        const addMoreButton = this.createAddMoreButton();
        if (addMoreButton) {
            thumbnailGrid.appendChild(addMoreButton);
        }

        galleryContainer.appendChild(thumbnailGrid);

        const captionElement = this.createCaptionInput();
        if (captionElement) {
            galleryContainer.appendChild(captionElement);
        }

        this.wrapper.appendChild(galleryContainer);
    }

    createThumbnail(fileData, index) {
        const thumbnail = document.createElement('div');
        thumbnail.classList.add('resizable-image__thumbnail');

        const img = document.createElement('img');
        img.src = this.resolveImageUrl(fileData.filename);
        img.alt = fileData.caption || `Image \${index + 1}`;
        img.classList.add('resizable-image__thumbnail-image');
        img.style.margin = '0';

        img.addEventListener('click', () => this.openModal(index));

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
        if (this.readOnly) return null;

        const addButton = document.createElement('div');
        addButton.classList.add('resizable-image__add-more');

        addButton.innerHTML = `
            <div class="resizable-image__add-more-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="resizable-image__add-more-text">\${this.t.addMore}</span>
        `;

        addButton.addEventListener('click', () => this.openFileSelection());
        return addButton;
    }

    createCaptionInput() {
        if (this.readOnly) {
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
        captionInput.placeholder = this.t.galleryCaptionPlaceholder;
        captionInput.classList.add('resizable-image__caption');
        captionInput.value = this.data.caption || '';

        captionInput.addEventListener('input', (e) => {
            this.data.caption = e.target.value;
        });

        return captionInput;
    }

    async handleDelete(index) {
        if (!this.data.files || !this.data.files[index]) return;

        const file = this.data.files[index];
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            if (file.filename) {
                const fullUrl = this.resolveImageUrl(file.filename);
                await this.executeDeleteRequest(fullUrl);
            }

            this.removeFileFromData(index);
            this.updateAfterDelete();
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

    openFileSelection() {
        if (!this.readOnly && !this.uploading && this.fileInput) {
            this.fileInput.click();
        }
    }

    openModal(startIndex = 0) {
        if (!this.data.files || this.data.files.length === 0) return;
        // Modal implementation would go here
        // Simplified for brevity
    }

    async executeDeleteRequest(url) {
        let filePath = url;
        if (filePath.includes('/storage/')) {
            const storageIndex = filePath.indexOf('/storage/');
            filePath = filePath.substring(storageIndex + '/storage/'.length);
        }

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
                throw new Error(`Delete failed: \${response.status}`);
            }

            return response;
        } catch (error) {
            throw error;
        }
    }

    showInlineProgress() {
        if (!this.wrapper || !this.uploadProgress) return;

        if (this.uploadContainer) {
            this.uploadContainer.style.display = 'none';
        }

        const existingProgress = this.wrapper.querySelector('.resizable-image__inline-progress');
        if (existingProgress) {
            existingProgress.remove();
        }

        const progressContainer = document.createElement('div');
        progressContainer.classList.add('resizable-image__inline-progress');

        const thumbnailGrid = document.createElement('div');
        thumbnailGrid.classList.add('resizable-image__progress-grid');

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

    createProgressThumbnail(uploadItem, index) {
        const thumbnail = document.createElement('div');
        thumbnail.classList.add('resizable-image__progress-thumbnail');
        thumbnail.setAttribute('data-id', uploadItem.id);
        thumbnail.setAttribute('data-index', index);

        let previewContent = '';
        if (uploadItem.file && uploadItem.file.type.startsWith('image/')) {
            const previewUrl = URL.createObjectURL(uploadItem.file);
            previewContent = `<img src="\${previewUrl}" alt="\${uploadItem.name}" class="resizable-image__progress-preview" style="margin: 0;">`;
        } else if (uploadItem.url) {
            previewContent = `<img src="\${uploadItem.url}" alt="\${uploadItem.name}" class="resizable-image__progress-preview" style="margin: 0;">`;
        } else {
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
                \${previewContent}
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
                <div class="resizable-image__progress-name">\${uploadItem.name}</div>
                <div class="resizable-image__progress-status-text">\${this.getStatusText(uploadItem.status)}</div>
            </div>
        `;

        return thumbnail;
    }

    updateProgressItem(index) {
        const uploadItem = this.uploadProgress[index];
        if (!uploadItem) return;

        const progressThumbnail = this.wrapper.querySelector(`.resizable-image__progress-thumbnail[data-index="\${index}"]`);
        if (!progressThumbnail) return;

        const progressRing = progressThumbnail.querySelector('.resizable-image__progress-ring-fill');
        const statusText = progressThumbnail.querySelector('.resizable-image__progress-status-text');

        if (progressRing) {
            const circumference = 2 * Math.PI * 16;
            const strokeDashoffset = circumference - (uploadItem.progress / 100) * circumference;
            progressRing.style.strokeDashoffset = strokeDashoffset;
        }

        if (statusText) {
            statusText.textContent = this.getStatusText(uploadItem.status);
        }

        progressThumbnail.classList.remove('status-pending', 'status-uploading', 'status-success', 'status-error');
        progressThumbnail.classList.add(`status-\${uploadItem.status}`);
    }

    getStatusText(status) {
        switch (status) {
            case 'pending': return this.t.status.pending;
            case 'uploading': return this.t.status.uploading;
            case 'success': return this.t.status.complete;
            case 'error': return this.t.status.failed;
            default: return this.t.status.unknown;
        }
    }

    cleanupUploadState() {
        if (this.uploadProgress && this.uploadProgress.length > 0) {
            console.log('Cleaning up existing upload progress state');
            this.uploadProgress = [];
        }
        this.hideInlineProgress();
    }

    cleanupEventListeners() {
        if (this.fileInput && this.eventHandlers) {
            this.fileInput.removeEventListener('change', this.eventHandlers.handleFileChange);
        }

        if (this.uploadLabel && this.eventHandlers) {
            this.uploadLabel.removeEventListener('click', this.eventHandlers.handleLabelClick);
        }

        if (this.wrapper && this.blockDragHandlers) {
            this.wrapper.removeEventListener('dragover', this.blockDragHandlers.handleBlockDragOver);
            this.wrapper.removeEventListener('dragleave', this.blockDragHandlers.handleBlockDragLeave);
            this.wrapper.removeEventListener('drop', this.blockDragHandlers.handleBlockDrop);
        }
    }

    onUpload(e) {
        const file = e.target.files[0];
        if (file) {
            this.handleUpload([file]);
        }
    }

    save() {
        return this.data;
    }

    validate(savedData) {
        if (!savedData) return false;
        if (Object.keys(savedData).length === 0) return true;
        if (savedData.files && Array.isArray(savedData.files)) {
            return true;
        }
        if (savedData.file && (savedData.file.url || savedData.file.filename)) return true;
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

    async removed() {
        if (this.data.files && Array.isArray(this.data.files)) {
            document.dispatchEvent(new CustomEvent('editor:busy'));

            try {
                const deletePromises = this.data.files.map(file => {
                    if (file.filename) {
                        const fullUrl = this.resolveImageUrl(file.filename);
                        return this.executeDeleteRequest(fullUrl);
                    }
                    return Promise.resolve();
                });

                await Promise.all(deletePromises);
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('editor:free'));
                }, 0);
            } catch (error) {
                console.error('Failed to cleanup images on block removal:', error);
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('editor:free'));
                }, 0);
            }
        }
    }

    destroy() {
        this.cleanupEventListeners();
        this.fileInput = null;
        this.uploadLabel = null;
        this.uploadContainer = null;
        this.wrapper = null;
        this.eventHandlers = null;
        this.blockDragHandlers = null;
    }
}

export default ResizableImage;
JS;
    }

    public function build(): BlockConfigContract
    {
        return ImagesConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
