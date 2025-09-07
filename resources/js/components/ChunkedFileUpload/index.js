/**
 * Enhanced Chunked File Upload Alpine.js Component
 * Modern file upload interface with modal support, URL import, and enhanced UX
 * Follows Laravel/Filament v4 and Alpine.js v3 conventions
 * 
 * Features:
 * - Modal and inline modes with enhanced UI
 * - Enhanced drag & drop with visual feedback
 * - Real-time progress tracking with ETA
 * - URL import functionality
 * - File management (add, remove, cancel, retry)
 * - Enhanced error handling and validation
 * - Responsive design with mobile support
 * - Dark mode support (zinc color scheme)
 * - Accessibility features (ARIA, keyboard navigation)
 * - Event-driven architecture with proper Alpine.js patterns
 * 
 * @param {Object} config - Component configuration from PHP
 * @returns {Object} Alpine component object
 */
export default function chunkedFileUploadComponent(config) {
    return {
        // === Core State Management ===
        showModal: false,
        isDragOver: false, // For template compatibility
        dragActive: false,
        dragCounter: 0, // Track drag events for proper highlighting
        uploading: false,
        uploadingFiles: [],
        completedFiles: [],
        uploadedFiles: [], // Alias for completedFiles for compatibility
        error: null,
        success: null,
        importUrl: '',
        urlToImport: '', // Alias for template compatibility
        showUrlImport: false, // URL import toggle state
        importingFromUrl: false,
        
        // Livewire integration - only serializable data
        state: config.state || [],
        abortControllers: new Map(),
        updatingState: false,
        
        // Non-serializable data (kept separate from Livewire)
        
        // === Configuration ===
        config: {
            // File handling
            acceptedFileTypes: config.acceptedFileTypes || [],
            chunkSize: config.chunkSize || 5242880, // 5MB default
            maxParallelUploads: config.maxParallelUploads || 3,
            maxFileSize: config.maxFileSize || 52428800, // 50MB default
            minFileSize: config.minFileSize || 0,
            maxFiles: config.maxFiles || null,
            
            // UI configuration
            isMultiple: config.multiple !== false, // Default to true for chunked uploads
            isDisabled: config.isDisabled || false,
            isPreviewable: config.isPreviewable || false,
            isImageUpload: config.isImageUpload || false,
            isVideoUpload: config.isVideoUpload || false,
            modalMode: config.modalMode || false,
            allowUrlImport: config.allowUrlImport || false,
            isChunked: true, // Always true for this component
            
            // Server endpoints (use route object structure)
            urls: {
                upload: config.routes?.store,
                delete: config.routes?.delete,
                cancel: config.routes?.cancel,
                importUrl: config.importUrlEndpoint || null
            },
            
            // Storage configuration
            storage: {
                disk: config.storage?.disk || 'public',
                finalDir: config.storage?.finalDir || 'uploads',
                tempDir: config.storage?.tempDir || 'uploads/temp'
            },
            
            // Messages
            messages: {
                uploading: config.uploadingMessage || 'Uploading files...',
                dragOver: 'Drop files here to upload',
                clickToUpload: 'Click to upload or drag and drop',
                maxFilesReached: 'Maximum number of files reached',
                invalidFileType: 'Invalid file type',
                fileTooLarge: 'File too large',
                fileTooSmall: 'File too small',
                uploadFailed: 'Upload failed',
                uploadCancelled: 'Upload cancelled',
                urlImportFailed: 'Failed to import from URL'
            }
        },

        // === Component Initialization ===
        init() {
            this.loadExistingFiles();
            this.$watch('state', () => this.loadExistingFiles());
            this.setupEventListeners();
            this.setupDragAndDrop();
            this.autoFocusModal();
        },

        // === Enhanced Modal Management ===
        openModal() {
            if (!this.config.modalMode) return;
            
            this.showModal = true;
            this.error = null;
            this.success = null;
            
            // Prevent body scroll
            document.body.classList.add('overflow-hidden');
            
            // Focus trap and accessibility
            this.$nextTick(() => {
                const modal = this.$refs.modal;
                const firstInput = this.$refs.fileInput || this.$refs.urlInput;
                
                if (modal) {
                    modal.focus();
                    this.trapFocus(modal);
                }
                
                if (firstInput && !this.config.autoFocus === false) {
                    firstInput.focus();
                }
            });
            
            // Emit modal opened event
            this.$dispatch('modal-opened', { component: 'chunked-file-upload' });
        },

        closeModal(force = false) {
            if (!this.config.modalMode) return;
            
            // Prevent closing if uploads are in progress (unless forced)
            if (this.uploading && !force) {
                if (!confirm('Files are still uploading. Are you sure you want to close?')) {
                    return;
                }
                this.cancelAllUploads();
            }
            
            this.showModal = false;
            this.dragActive = false;
            this.dragCounter = 0;
            this.importUrl = '';
            this.importingFromUrl = false;
            
            // Restore body scroll
            document.body.classList.remove('overflow-hidden');
            
            // Clear messages if no uploads in progress
            if (!this.uploading || force) {
                this.clearMessages();
            }
            
            // Emit modal closed event
            this.$dispatch('modal-closed', { component: 'chunked-file-upload' });
        },

        // === Drag & Drop Enhancement ===
        setupDragAndDrop() {
            const dropZone = this.config.modalMode ? 
                this.$refs.modalDropZone || this.$el : 
                this.$el.querySelector('.fi-fo-file-upload-dropzone') || this.$el;
            
            if (!dropZone) return;

            // Prevent default drag behaviors on document
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                document.addEventListener(eventName, this.preventDefaults, false);
                dropZone.addEventListener(eventName, this.preventDefaults, false);
            });

            // Handle drag enter/over for visual feedback
            dropZone.addEventListener('dragenter', this.handleDragEnter.bind(this), false);
            dropZone.addEventListener('dragover', this.handleDragEnter.bind(this), false);
            dropZone.addEventListener('dragleave', this.handleDragLeave.bind(this), false);
            dropZone.addEventListener('drop', this.handleDrop.bind(this), false);
        },

        handleDragEnter(event) {
            if (this.config.isDisabled || !this.hasValidFiles(event.dataTransfer)) return;
            
            event.preventDefault();
            this.dragCounter++;
            this.dragActive = true;
            this.isDragOver = true;
        },

        handleDragOver(event) {
            if (this.config.isDisabled) return;
            event.preventDefault();
            this.isDragOver = true;
        },

        handleDragLeave(event) {
            if (this.config.isDisabled) return;
            
            event.preventDefault();
            this.dragCounter--;
            
            // Only deactivate when leaving the actual drop zone
            if (this.dragCounter <= 0) {
                this.dragActive = false;
                this.isDragOver = false;
                this.dragCounter = 0;
            }
        },

        handleDrop(event) {
            if (this.config.isDisabled) return;
            
            event.preventDefault();
            this.dragActive = false;
            this.isDragOver = false;
            this.dragCounter = 0;
            
            const files = Array.from(event.dataTransfer.files);
            if (files.length > 0) {
                this.handleFiles(files);
            }
        },

        // === File Selection & Validation ===
        handleFileSelect(event) {
            if (this.config.isDisabled) return;
            
            const files = Array.from(event.target.files);
            this.handleFiles(files);
            
            // Reset input for reselection of same files
            event.target.value = '';
        },

        handleFiles(files) {
            if (!files.length) return;
            
            this.clearMessages();
            
            // Pre-validate files
            const validation = this.validateFiles(files);
            if (!validation.valid) {
                this.showError(validation.error);
                return;
            }
            
            this.startUploads(files);
        },

        validateFiles(files) {
            // Check if disabled
            if (this.config.isDisabled) {
                return { valid: false, error: 'File uploads are disabled' };
            }

            // Check total file limit
            const totalFiles = this.completedFiles.length + this.uploadingFiles.filter(f => f.status !== 'error').length + files.length;
            if (this.config.maxFiles && totalFiles > this.config.maxFiles) {
                return {
                    valid: false,
                    error: `Maximum ${this.config.maxFiles} files allowed. Current: ${this.completedFiles.length}, Adding: ${files.length}`
                };
            }

            // Validate each file
            for (const file of files) {
                // File size validation
                if (this.config.maxFileSize && file.size > this.config.maxFileSize) {
                    return {
                        valid: false,
                        error: `"${file.name}" (${this.formatFileSize(file.size)}) exceeds maximum size of ${this.formatFileSize(this.config.maxFileSize)}`
                    };
                }

                if (this.config.minFileSize && file.size < this.config.minFileSize) {
                    return {
                        valid: false,
                        error: `"${file.name}" (${this.formatFileSize(file.size)}) is below minimum size of ${this.formatFileSize(this.config.minFileSize)}`
                    };
                }

                // File type validation
                if (this.config.acceptedFileTypes.length > 0 && !this.isValidFileType(file)) {
                    return {
                        valid: false,
                        error: `"${file.name}" is not an accepted file type. Accepted: ${this.config.acceptedFileTypes.join(', ')}`
                    };
                }
            }

            return { valid: true };
        },

        // === Upload Management ===
        async startUploads(files) {
            this.uploading = true;
            this.clearMessages();
            
            // Create upload entries
            const uploadEntries = files.map(file => this.createUploadEntry(file));
            this.uploadingFiles = [...this.uploadingFiles, ...uploadEntries];
            
            // Process uploads with concurrency control
            await this.processUploadsWithConcurrency(uploadEntries);
            
            // Update state after all uploads
            this.finishUploads();
        },

        createUploadEntry(file) {
            const entry = {
                id: this.generateFileKey(),
                name: file.name,
                size: file.size,
                type: file.type,
                progress: 0,
                status: 'pending', // pending, uploading, completed, error, cancelled
                speed: null,
                eta: null,
                uploadedBytes: 0,
                chunks: [],
                uploadedChunks: 0,
                totalChunks: this.shouldUseChunking(file) ? Math.ceil(file.size / this.config.chunkSize) : 1,
                startTime: null,
                lastProgressTime: null,
                error: null,
                url: null,
                retryCount: 0,
                maxRetries: 3
            };
            
            // Store File object separately to avoid serialization issues
            // This Map is not reactive and won't be synced with Livewire
            if (!this._fileObjects) {
                this._fileObjects = new Map();
            }
            this._fileObjects.set(entry.id, file);
            
            return entry;
        },

        async processUploadsWithConcurrency(uploadEntries) {
            const semaphore = new Array(this.config.maxParallelUploads).fill(null);
            
            const uploadPromises = uploadEntries.map(async (entry) => {
                await this.waitForSlot(semaphore, entry.id);
                
                try {
                    await this.uploadFile(entry);
                    this.handleUploadSuccess(entry);
                } catch (error) {
                    this.handleUploadError(entry, error);
                } finally {
                    this.releaseSlot(semaphore, entry.id);
                }
            });

            await Promise.allSettled(uploadPromises);
        },

        async uploadFile(entry) {
            const file = this._fileObjects.get(entry.id);
            if (!file) {
                throw new Error('File object not found');
            }
            
            this.updateUploadStatus(entry.id, 'uploading', { startTime: Date.now() });
            
            if (this.shouldUseChunking(file)) {
                await this.uploadFileInChunks(entry);
            } else {
                await this.uploadFileDirectly(entry);
            }
        },

        // === Chunked Upload Implementation ===
        async uploadFileInChunks(entry) {
            const file = this._fileObjects.get(entry.id);
            if (!file) {
                throw new Error('File object not found');
            }
            
            const totalChunks = Math.ceil(file.size / this.config.chunkSize);
            this.updateUploadStatus(entry.id, null, { 
                totalChunks, 
                chunks: new Array(totalChunks).fill(false) 
            });

            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                // Check for cancellation
                if (this.abortControllers.has(entry.id) && this.abortControllers.get(entry.id).signal.aborted) {
                    throw new Error('Upload cancelled');
                }

                const start = chunkIndex * this.config.chunkSize;
                const end = Math.min(start + this.config.chunkSize, file.size);
                const chunk = file.slice(start, end);

                await this.uploadChunk(entry, chunk, chunkIndex, totalChunks, file.name);
                this.updateChunkProgress(entry.id, chunkIndex);
            }
        },

        async uploadFileDirectly(entry) {
            const file = this._fileObjects.get(entry.id);
            if (!file) {
                throw new Error('File object not found');
            }
            
            const formData = this.buildFormData(file, entry.id, { 
                direct: true,
                originalName: file.name 
            });
            const response = await this.makeUploadRequest(formData, entry.id);
            
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Upload failed');
            }
            
            this.updateUploadStatus(entry.id, null, { 
                url: result.url, 
                progress: 100,
                uploadedBytes: file.size 
            });
        },

        async uploadChunk(entry, chunk, chunkIndex, totalChunks, fileName) {
            const formData = this.buildFormData(chunk, entry.id, {
                chunk: chunkIndex,
                chunks: totalChunks,
                originalName: fileName
            });

            const controller = new AbortController();
            const chunkId = `${entry.id}_${chunkIndex}`;
            this.abortControllers.set(chunkId, controller);

            try {
                const response = await this.makeUploadRequest(formData, chunkId, controller.signal);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Chunk upload failed');
                }
                
                if (result.completed) {
                    this.updateUploadStatus(entry.id, null, { url: result.url });
                }
            } finally {
                this.abortControllers.delete(chunkId);
            }
        },

        // === URL Import Functionality ===
        toggleUrlImport() {
            this.showUrlImport = !this.showUrlImport;
            if (this.showUrlImport) {
                this.$nextTick(() => {
                    const input = this.$refs.urlInput;
                    if (input) input.focus();
                });
            }
        },

        async importFromUrl() {
            const url = this.urlToImport || this.importUrl;
            if (!url.trim() || !this.config.urls.importUrl) {
                this.showError('Please enter a valid URL');
                return;
            }

            this.importingFromUrl = true;
            this.clearMessages();

            try {
                const response = await fetch(this.config.urls.importUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({
                        url: url.trim(),
                        statePath: this.config.statePath || ''
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to import from URL');
                }

                // Add imported file to completed files
                this.completedFiles.push({
                    key: this.generateFileKey(),
                    name: result.filename || this.extractFilenameFromUrl(this.importUrl),
                    size: result.fileSize || 0,
                    type: result.mimeType || this.guessTypeFromUrl(this.importUrl),
                    url: result.url,
                    imported: true
                });

                this.importUrl = '';
                this.urlToImport = '';
                this.updateLivewireState();
                this.showSuccess('File imported successfully from URL');

                // Close modal if in modal mode
                if (this.config.modalMode) {
                    setTimeout(() => this.closeModal(), 1500);
                }

            } catch (error) {
                console.error('URL import error:', error);
                this.showError(`Failed to import from URL: ${error.message}`);
            } finally {
                this.importingFromUrl = false;
            }
        },

        // === Enhanced File Management ===
        async cancelUpload(fileId) {
            const file = this.uploadingFiles.find(f => f.id === fileId);
            if (!file) return;

            // Cancel all related requests
            this.cancelUploadRequests(fileId);

            // Update status
            this.updateUploadStatus(fileId, 'cancelled');

            // Clean up on server
            try {
                await this.makeServerCleanupRequest(fileId);
            } catch (error) {
                console.warn('Server cleanup failed:', error);
            }

            // Clean up File object reference
            if (this._fileObjects) {
                this._fileObjects.delete(fileId);
            }
            
            // Remove from uploading files
            this.uploadingFiles = this.uploadingFiles.filter(f => f.id !== fileId);
            this.updateUploadingState();
            
            // Emit cancellation event
            this.$dispatch('file-cancelled', { fileId, fileName: file.name });
        },

        async cancelAllUploads() {
            const uploadingFiles = [...this.uploadingFiles];
            
            for (const file of uploadingFiles) {
                if (file.status === 'uploading' || file.status === 'pending') {
                    await this.cancelUpload(file.id);
                }
            }
            
            this.clearMessages();
            this.showSuccess('All uploads cancelled');
        },

        async removeFile(fileKey) {
            const file = this.completedFiles.find(f => f.key === fileKey);
            if (!file) return;

            // Show confirmation dialog
            if (!confirm(`Are you sure you want to delete "${file.name}"?`)) {
                return;
            }

            try {
                let filePath = this.extractFilePathFromUrl(file.url);
                
                // If we can't extract the path from URL, try to construct it
                if (!filePath && file.name) {
                    filePath = `uploads/${file.name}`;
                }
                
                // If still no path, use the file key as fallback
                if (!filePath) {
                    filePath = `uploads/${fileKey}`;
                }
                
                console.log('Delete request:', { fileKey, filePath, fileUrl: file.url });
                
                const response = await fetch(this.config.urls.delete, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        fileKey,
                        path: filePath
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Failed to delete file');
                }

                // Remove from completed files
                this.completedFiles = this.completedFiles.filter(f => f.key !== fileKey);
                this.updateLivewireState();
                this.showSuccess(`"${file.name}" deleted successfully`);

            } catch (error) {
                console.error('File deletion error:', error);
                this.showError(`Failed to delete "${file.name}": ${error.message}`);
            }
        },

        async retryUpload(fileId) {
            const fileIndex = this.uploadingFiles.findIndex(f => f.id === fileId);
            if (fileIndex === -1) return;

            const entry = this.uploadingFiles[fileIndex];
            if (entry.retryCount >= entry.maxRetries) {
                this.showError(`Maximum retry attempts reached for "${entry.name}"`);
                return;
            }

            // Check if File object still exists
            if (!this._fileObjects || !this._fileObjects.has(fileId)) {
                this.showError(`File data no longer available for "${entry.name}"`);
                return;
            }

            // Reset file state for retry
            this.resetUploadEntry(fileIndex);
            entry.retryCount++;

            try {
                await this.uploadFile(entry);
                this.handleUploadSuccess(entry);
            } catch (error) {
                this.handleUploadError(entry, error);
            }
        },

        // === Helper Methods ===
        shouldUseChunking(file) {
            return this.config.isChunked && file.size > this.config.chunkSize;
        },

        isValidFileType(file) {
            if (this.config.acceptedFileTypes.length === 0) return true;
            
            return this.config.acceptedFileTypes.some(type => {
                if (type.includes('/')) {
                    // MIME type check
                    return type.endsWith('/*') ? 
                        file.type.startsWith(type.slice(0, -1)) : 
                        file.type === type;
                } else {
                    // Extension check
                    return file.name.toLowerCase().endsWith(type.toLowerCase());
                }
            });
        },

        hasValidFiles(dataTransfer) {
            if (!dataTransfer || !dataTransfer.types) return false;
            return dataTransfer.types.includes('Files');
        },

        getFileIcon(file) {
            const type = file.type || '';
            
            if (type.startsWith('image/')) {
                return {
                    bg: 'bg-blue-100',
                    text: 'text-blue-600',
                    label: 'IMG'
                };
            }
            if (type.startsWith('video/')) {
                return {
                    bg: 'bg-purple-100',
                    text: 'text-purple-600',
                    label: 'VID'
                };
            }
            if (type.includes('pdf')) {
                return {
                    bg: 'bg-red-100',
                    text: 'text-red-600',
                    label: 'PDF'
                };
            }
            
            // Default file icon
            return {
                bg: 'bg-zinc-100',
                text: 'text-zinc-600',
                label: 'DOC'
            };
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        calculateETA(entry) {
            if (!entry.startTime || !entry.uploadedBytes || entry.uploadedBytes === 0) return null;
            
            const elapsed = (Date.now() - entry.startTime) / 1000;
            const speed = entry.uploadedBytes / elapsed;
            const remaining = entry.size - entry.uploadedBytes;
            
            return speed > 0 ? Math.ceil(remaining / speed) : null;
        },

        formatETA(seconds) {
            if (!seconds || seconds <= 0) return '';
            
            if (seconds < 60) return `${seconds}s`;
            if (seconds < 3600) return `${Math.ceil(seconds / 60)}m`;
            return `${Math.ceil(seconds / 3600)}h`;
        },

        // === State Management Helpers ===
        updateUploadStatus(fileId, status, updates = {}) {
            const index = this.uploadingFiles.findIndex(f => f.id === fileId);
            if (index === -1) return;
            
            const file = this.uploadingFiles[index];
            
            if (status) file.status = status;
            Object.assign(file, updates);
            
            // Update progress-related calculations
            if (updates.uploadedBytes !== undefined || updates.uploadedChunks !== undefined) {
                this.updateProgressCalculations(file);
            }
        },

        updateProgressCalculations(entry) {
            // Update progress percentage
            if (entry.totalChunks > 1) {
                entry.progress = Math.round((entry.uploadedChunks / entry.totalChunks) * 100);
                entry.uploadedBytes = entry.uploadedChunks * this.config.chunkSize;
            }
            
            // Update speed and ETA
            this.updateSpeedCalculation(entry);
        },

        updateSpeedCalculation(entry) {
            if (!entry.startTime) return;
            
            const now = Date.now();
            const elapsed = (now - entry.startTime) / 1000;
            
            if (elapsed > 0.5) { // Update every 500ms
                const speed = entry.uploadedBytes / elapsed;
                entry.speed = this.formatFileSize(speed);
                entry.eta = this.calculateETA(entry);
                entry.lastProgressTime = now;
            }
        },

        updateChunkProgress(fileId, chunkIndex) {
            const index = this.uploadingFiles.findIndex(f => f.id === fileId);
            if (index === -1) return;
            
            const file = this.uploadingFiles[index];
            file.chunks[chunkIndex] = true;
            file.uploadedChunks = file.chunks.filter(Boolean).length;
            
            this.updateProgressCalculations(file);
        },

        handleUploadSuccess(entry) {
            const index = this.uploadingFiles.findIndex(f => f.id === entry.id);
            if (index === -1) return;
            
            const file = this._fileObjects.get(entry.id);
            const fileSize = file ? file.size : entry.size;
            
            this.updateUploadStatus(entry.id, 'completed', {
                progress: 100,
                uploadedBytes: fileSize
            });
            
            // Move to completed files (only serializable data)
            this.completedFiles.push({
                key: entry.id,
                name: entry.name,
                size: entry.size,
                type: entry.type,
                url: entry.url,
                uploaded_at: new Date().toISOString()
            });
            
            // Clean up File object reference
            if (this._fileObjects) {
                this._fileObjects.delete(entry.id);
            }
            
            // Announce success to screen readers
            this.announceProgress(entry.name, 100, 'completed');
            
            // Emit success event with file details for Livewire integration
            this.$dispatch('file-uploaded', { 
                fileId: entry.id, 
                fileName: entry.name, 
                fileUrl: entry.url,
                fileSize: entry.size,
                fileType: entry.type
            });
        },

        handleUploadError(entry, error) {
            if (error.name === 'AbortError' || error.message.includes('cancelled')) {
                this.updateUploadStatus(entry.id, 'cancelled');
            } else {
                this.updateUploadStatus(entry.id, 'error', {
                    error: error.message || 'Upload failed'
                });
                
                // Announce error to screen readers
                this.announceProgress(entry.name, 0, 'error');
                
                // Emit error event
                this.$dispatch('file-error', { 
                    fileId: entry.id, 
                    fileName: entry.name, 
                    error: error.message 
                });
            }
        },

        finishUploads() {
            // Remove completed uploads from uploading list
            this.uploadingFiles = this.uploadingFiles.filter(f => 
                f.status !== 'completed'
            );
            
            this.updateUploadingState();
            this.updateLivewireState();
            
            // Show success message if all uploads completed
            const hasErrors = this.uploadingFiles.some(f => f.status === 'error');
            if (!hasErrors && this.completedFiles.length > 0) {
                this.showSuccess('All files uploaded successfully');
                
                // Auto-close modal if in modal mode and all uploads are successful
                if (this.config.modalMode && this.showModal) {
                    setTimeout(() => {
                        this.closeModal();
                    }, 2000); // Wait 2 seconds to show success message
                }
            }
        },

        updateUploadingState() {
            this.uploading = this.uploadingFiles.some(f => 
                f.status === 'uploading' || f.status === 'pending'
            );
        },

        // === Livewire Integration ===
        loadExistingFiles() {
            if (!this.state || this.updatingState) return;
            
            const fileNames = Array.isArray(this.state) ? this.state : [this.state];
            
            // Only reload if we don't have files or counts don't match
            if (this.completedFiles.length === 0 || fileNames.length !== this.completedFiles.length) {
                this.completedFiles = fileNames.map(fileName => ({
                    key: this.generateFileKey(),
                    name: fileName,
                    size: 0, // Size not available from filename
                    type: this.guessTypeFromFilename(fileName),
                    url: this.buildFileUrl(fileName)
                }));
                
                // Keep uploadedFiles alias in sync
                this.uploadedFiles = [...this.completedFiles];
            }
        },

        updateLivewireState() {
            if (this.updatingState) return;
            
            this.updatingState = true;
            
            // Convert to serializable file metadata (no File objects)
            const serializableFiles = this.completedFiles.map(file => ({
                key: file.key,
                name: file.name,
                size: file.size || 0,
                type: file.type || 'application/octet-stream',
                url: file.url || '',
                uploaded_at: file.uploaded_at || new Date().toISOString(),
                imported: file.imported || false
            }));
            
            // Update state without File objects
            this.state = serializableFiles;
            
            // Keep uploadedFiles alias in sync
            this.uploadedFiles = [...serializableFiles];
            
            setTimeout(() => {
                this.updatingState = false;
            }, 100);
        },

        // === Utility Methods ===
        buildFormData(fileOrChunk, fileId, options = {}) {
            const formData = new FormData();
            formData.append('file', fileOrChunk);
            formData.append('fileKey', fileId);
            
            // Always add the name parameter (required by backend)
            if (options.originalName) {
                formData.append('name', options.originalName);
            } else {
                // Fallback to fileId if no name provided
                formData.append('name', fileId);
            }
            
            if (options.chunk !== undefined) {
                formData.append('chunk', options.chunk);
                formData.append('chunks', options.chunks);
            }
            
            return formData;
        },

        async makeUploadRequest(formData, requestId, signal = null) {
            const controller = signal ? null : new AbortController();
            if (!signal && controller) {
                this.abortControllers.set(requestId, controller);
                signal = controller.signal;
            }

            try {
                const response = await fetch(this.config.urls.upload, {
                    method: 'POST',
                    body: formData,
                    signal,
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                return response;
            } finally {
                if (controller) {
                    this.abortControllers.delete(requestId);
                }
            }
        },

        async makeServerCleanupRequest(fileId) {
            await fetch(this.config.urls.cancel, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({ fileKey: fileId })
            });
        },

        cancelUploadRequests(fileId) {
            // Cancel all controllers related to this file
            Array.from(this.abortControllers.entries())
                .filter(([key]) => key.startsWith(fileId))
                .forEach(([key, controller]) => {
                    try {
                        controller.abort();
                        this.abortControllers.delete(key);
                    } catch (error) {
                        console.debug('Expected abort error:', error);
                    }
                });
        },

        async waitForSlot(semaphore, entryId) {
            return new Promise(resolve => {
                const checkSlot = () => {
                    const index = semaphore.findIndex(slot => slot === null);
                    if (index !== -1) {
                        semaphore[index] = entryId;
                        resolve();
                    } else {
                        setTimeout(checkSlot, 100);
                    }
                };
                checkSlot();
            });
        },

        releaseSlot(semaphore, entryId) {
            const index = semaphore.findIndex(slot => slot === entryId);
            if (index !== -1) {
                semaphore[index] = null;
            }
        },

        resetUploadEntry(index) {
            const entry = this.uploadingFiles[index];
            entry.status = 'pending';
            entry.progress = 0;
            entry.error = null;
            entry.uploadedBytes = 0;
            entry.uploadedChunks = 0;
            entry.chunks = [];
            entry.speed = null;
            entry.eta = null;
            entry.startTime = null;
            entry.lastProgressTime = null;
        },

        // === Event Handlers ===
        setupEventListeners() {
            // Cleanup on page unload
            window.addEventListener('beforeunload', () => this.cleanup());
            
            // Modal keyboard handling
            if (this.config.modalMode) {
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.showModal) {
                        this.closeModal();
                    }
                });
            }
        },

        autoFocusModal() {
            if (this.config.modalMode && this.config.autoFocus) {
                this.$nextTick(() => {
                    if (this.showModal && this.$refs.modal) {
                        this.$refs.modal.focus();
                    }
                });
            }
        },

        trapFocus(element) {
            const focusableElements = element.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            element.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
        },

        preventDefaults(event) {
            event.preventDefault();
            event.stopPropagation();
        },

        // === Message Handling ===
        showError(message) {
            this.error = message;
            this.success = null;
            
            // Auto-clear after 10 seconds
            setTimeout(() => {
                if (this.error === message) {
                    this.error = null;
                }
            }, 10000);
        },

        showSuccess(message) {
            this.success = message;
            this.error = null;
            
            // Auto-clear after 5 seconds
            setTimeout(() => {
                if (this.success === message) {
                    this.success = null;
                }
            }, 5000);
        },

        clearMessages() {
            this.error = null;
            this.success = null;
        },

        // === File Utility Methods ===
        generateFileKey() {
            return Date.now().toString(36) + Math.random().toString(36).substring(2);
        },

        parseSize(sizeString) {
            if (typeof sizeString === 'number') return sizeString;
            
            const units = { 
                'B': 1, 'BYTES': 1,
                'KB': 1024, 
                'MB': 1024 * 1024, 
                'GB': 1024 * 1024 * 1024,
                'TB': 1024 * 1024 * 1024 * 1024
            };
            
            const match = String(sizeString).match(/^(\d+(?:\.\d+)?)\s*(\w+)$/i);
            if (match) {
                const value = parseFloat(match[1]);
                const unit = match[2].toUpperCase();
                return value * (units[unit] || 1);
            }
            return parseInt(sizeString) || 0;
        },

        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        buildFileUrl(fileName) {
            const { disk, finalDir } = this.config.storage;
            return disk === 'public' ? 
                `/storage/${finalDir}/${fileName}` : 
                `/storage/${finalDir}/${fileName}`;
        },

        extractFilePathFromUrl(url) {
            if (!url) return '';
            
            if (url.includes('/storage/')) {
                const storageIndex = url.indexOf('/storage/');
                return url.substring(storageIndex + '/storage/'.length);
            }
            
            return url;
        },

        extractFilenameFromUrl(url) {
            try {
                const urlObj = new URL(url);
                const path = urlObj.pathname;
                return path.split('/').pop() || 'imported-file';
            } catch {
                return 'imported-file';
            }
        },

        guessTypeFromUrl(url) {
            const ext = url.split('.').pop()?.toLowerCase();
            const mimeMap = {
                'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png', 'gif': 'image/gif',
                'mp4': 'video/mp4', 'webm': 'video/webm', 'avi': 'video/avi',
                'mp3': 'audio/mpeg', 'wav': 'audio/wav', 'ogg': 'audio/ogg',
                'pdf': 'application/pdf', 'doc': 'application/msword',
                'zip': 'application/zip', 'rar': 'application/x-rar'
            };
            return mimeMap[ext] || 'application/octet-stream';
        },

        guessTypeFromFilename(filename) {
            const ext = filename.split('.').pop()?.toLowerCase();
            const mimeMap = {
                'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png', 'gif': 'image/gif',
                'mp4': 'video/mp4', 'webm': 'video/webm', 'avi': 'video/avi',
                'mp3': 'audio/mpeg', 'wav': 'audio/wav', 'ogg': 'audio/ogg',
                'pdf': 'application/pdf', 'doc': 'application/msword'
            };
            return mimeMap[ext] || null;
        },

        // === Enhanced Accessibility & Mobile Support ===
        handleKeyboardNavigation(event) {
            // Handle Enter/Space for triggering file input
            if ((event.key === 'Enter' || event.key === ' ') && event.target.classList.contains('dropzone')) {
                event.preventDefault();
                this.$refs.fileInput?.click();
            }
            
            // Handle Escape for modal
            if (event.key === 'Escape' && this.showModal) {
                event.preventDefault();
                this.closeModal();
            }
        },

        // Touch/mobile support for drag and drop
        handleTouchStart(event) {
            if (this.config.isDisabled) return;
            event.preventDefault();
            this.dragActive = true;
        },

        handleTouchMove(event) {
            if (this.config.isDisabled) return;
            event.preventDefault();
        },

        handleTouchEnd(event) {
            if (this.config.isDisabled) return;
            event.preventDefault();
            this.dragActive = false;
            
            // Handle file drop simulation for mobile
            const touch = event.changedTouches[0];
            const element = document.elementFromPoint(touch.clientX, touch.clientY);
            
            if (element && element.closest('.dropzone')) {
                // Trigger file input on mobile
                this.$refs.fileInput?.click();
            }
        },

        // Enhanced responsive design helpers
        isMobile() {
            return window.innerWidth < 640; // Tailwind 'sm' breakpoint
        },

        isTablet() {
            return window.innerWidth >= 640 && window.innerWidth < 1024; // Between 'sm' and 'lg'
        },

        // ARIA announcements for screen readers
        announceToScreenReader(message, priority = 'polite') {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', priority);
            announcement.setAttribute('aria-atomic', 'true');
            announcement.className = 'sr-only';
            announcement.textContent = message;
            
            document.body.appendChild(announcement);
            
            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 1000);
        },

        // Enhanced progress announcements
        announceProgress(fileName, progress, status) {
            if (status === 'uploading' && progress % 25 === 0) {
                this.announceToScreenReader(`${fileName} is ${progress}% uploaded`);
            } else if (status === 'completed') {
                this.announceToScreenReader(`${fileName} upload completed successfully`);
            } else if (status === 'error') {
                this.announceToScreenReader(`${fileName} upload failed`, 'assertive');
            }
        },

        // === Cleanup ===
        cleanup() {
            try {
                // Cancel all active uploads
                this.abortControllers.forEach(controller => {
                    try {
                        controller.abort();
                    } catch (error) {
                        console.debug('Cleanup abort error (expected):', error);
                    }
                });
                this.abortControllers.clear();
                
                // Clean up File object references
                if (this._fileObjects) {
                    this._fileObjects.clear();
                }
                
                // Restore body scroll if modal was open
                if (this.showModal) {
                    document.body.classList.remove('overflow-hidden');
                }
            } catch (error) {
                console.debug('Cleanup error (expected during unload):', error);
            }
        }
    };
}