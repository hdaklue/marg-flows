/**
 * Chunked File Upload Alpine.js Component
 * Handles large file uploads with chunking support
 */
export default function chunkedFileUploadComponent(config) {
    return {
        // State
        uploading: false,
        uploadingFiles: [],
        uploadedFiles: [],
        error: null,
        state: config.state,
        abortControllers: new Map(),
        updatingState: false,
        
        // Configuration
        config: {
            acceptedFileTypes: config.acceptedFileTypes || [],
            chunkSize: config.chunkSize || 5 * 1024 * 1024, // 5MB
            chunkUploadUrl: config.chunkUploadUrl,
            chunkDeleteUrl: config.chunkDeleteUrl,
            chunkCancelUrl: config.chunkCancelUrl,
            isChunked: config.isChunked || false,
            isDisabled: config.isDisabled || false,
            isMultiple: config.isMultiple || false,
            maxFiles: config.maxFiles || 1,
            maxParallelUploads: config.maxParallelUploads || 3,
            maxSize: config.maxSize,
            minSize: config.minSize,
            placeholder: config.placeholder,
            statePath: config.statePath,
            uploadingMessage: config.uploadingMessage || 'Uploading...',
            storage: config.storage || { disk: 'public', finalDir: 'uploads' },
        },

        // Initialization
        init() {
            this.loadExistingFiles();
            this.$watch('state', () => this.loadExistingFiles());
            
            // Setup drag and drop
            this.setupDragAndDrop();
            
            // Setup cleanup on page unload
            window.addEventListener('beforeunload', () => this.cleanup());
        },

        // Load existing files from Livewire state
        loadExistingFiles() {
            if (!this.state || this.updatingState) return;
            
            // State should always be an array of filenames for chunked uploads
            const fileNames = Array.isArray(this.state) ? this.state : [this.state];
            
            // Only reload if we don't have files or if the state count doesn't match
            // This prevents overwriting during active uploads
            if (this.uploadedFiles.length === 0 || fileNames.length !== this.uploadedFiles.length) {
                this.uploadedFiles = fileNames.map((fileName, index) => ({
                    key: this.generateFileKey(),
                    name: fileName,
                    size: 0, // Size not available from filename
                    url: this.buildFileUrl(fileName),
                    type: null
                }));
            }
        },

        // Setup drag and drop functionality
        setupDragAndDrop() {
            // Use the entire component container as drop zone
            const dropZone = this.$el;
            const visualDropZone = this.$el.querySelector('.fi-fo-file-upload-dropzone');
            
            if (!dropZone || !visualDropZone) return;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, this.preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => this.highlight(visualDropZone), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => this.unhighlight(visualDropZone), false);
            });

            dropZone.addEventListener('drop', this.handleDrop.bind(this), false);
        },

        // Drag and drop helpers
        preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        },

        highlight(element) {
            element.classList.add('drag-over');
        },

        unhighlight(element) {
            element.classList.remove('drag-over');
        },

        handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            this.handleFiles(Array.from(files));
        },

        // File selection handler
        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.handleFiles(files);
            // Clear the input so same file can be selected again
            event.target.value = '';
        },

        // Handle selected files
        handleFiles(files) {
            if (files.length === 0) return;
            
            this.error = null;
            
            // Validate files
            const validationResult = this.validateFiles(files);
            if (!validationResult.valid) {
                this.error = validationResult.error;
                return;
            }

            this.startUploads(files);
        },

        // Validate selected files
        validateFiles(files) {
            // Check file count
            const totalFiles = this.uploadedFiles.length + files.length;
            if (this.config.maxFiles && totalFiles > this.config.maxFiles) {
                return {
                    valid: false,
                    error: `Maximum ${this.config.maxFiles} files allowed. You have ${this.uploadedFiles.length} files and are trying to add ${files.length} more.`
                };
            }

            // Check individual files
            for (const file of files) {
                // Check file size
                if (this.config.maxSize && file.size > this.parseSize(this.config.maxSize)) {
                    return {
                        valid: false,
                        error: `File "${file.name}" (${this.formatFileSize(file.size)}) exceeds maximum size of ${this.config.maxSize}`
                    };
                }

                if (this.config.minSize && file.size < this.parseSize(this.config.minSize)) {
                    return {
                        valid: false,
                        error: `File "${file.name}" (${this.formatFileSize(file.size)}) is smaller than minimum size of ${this.config.minSize}`
                    };
                }

                // Check file type
                if (this.config.acceptedFileTypes.length > 0) {
                    const isValidType = this.config.acceptedFileTypes.some(type => {
                        if (type.includes('/')) {
                            // MIME type check
                            if (type.endsWith('/*')) {
                                return file.type.startsWith(type.slice(0, -1));
                            }
                            return file.type === type;
                        } else {
                            // Extension check
                            return file.name.toLowerCase().endsWith(type.toLowerCase());
                        }
                    });

                    if (!isValidType) {
                        return {
                            valid: false,
                            error: `File "${file.name}" is not an accepted file type. Accepted types: ${this.config.acceptedFileTypes.join(', ')}`
                        };
                    }
                }
            }

            return { valid: true };
        },

        // Start uploading files
        async startUploads(files) {
            this.uploading = true;
            
            // Create upload entries
            const uploadEntries = files.map(file => ({
                id: this.generateFileKey(),
                file,
                name: file.name,
                size: file.size,
                type: file.type,
                progress: 0,
                status: 'pending',
                speed: null,
                uploadedBytes: 0,
                chunks: [],
                uploadedChunks: 0,
                totalChunks: this.config.isChunked ? Math.ceil(file.size / this.config.chunkSize) : 1,
                startTime: null,
                lastProgressTime: null
            }));

            this.uploadingFiles = [...this.uploadingFiles, ...uploadEntries];
            
            // Create a semaphore for parallel uploads
            const semaphore = new Array(this.config.maxParallelUploads).fill(null);
            
            // Process uploads with concurrency control
            const uploadPromises = uploadEntries.map(async (entry) => {
                // Wait for available slot
                await this.waitForSlot(semaphore, entry.id);
                
                try {
                    await this.uploadFile(entry);
                    
                    const entryIndex = this.uploadingFiles.findIndex(f => f.id === entry.id);
                    if (entryIndex !== -1) {
                        this.uploadingFiles[entryIndex].status = 'completed';
                        this.uploadingFiles[entryIndex].progress = 100;
                        
                        // Add to uploaded files
                        this.uploadedFiles.push({
                            key: entry.id,
                            name: entry.name,
                            size: entry.size,
                            type: entry.type,
                            url: this.uploadingFiles[entryIndex].url
                        });
                    }
                    
                } catch (error) {
                    // Don't treat AbortError as a real error - it's expected when cancelling
                    if (error.name !== 'AbortError') {
                        const entryIndex = this.uploadingFiles.findIndex(f => f.id === entry.id);
                        if (entryIndex !== -1) {
                            this.uploadingFiles[entryIndex].status = 'error';
                            this.uploadingFiles[entryIndex].error = error.message;
                        }
                        console.error('Upload error:', error);
                    }
                } finally {
                    // Free the slot
                    const slotIndex = semaphore.findIndex(slot => slot === entry.id);
                    if (slotIndex !== -1) {
                        semaphore[slotIndex] = null;
                    }
                }
            });

            // Wait for all uploads to complete
            await Promise.allSettled(uploadPromises);
            
            // Clean up completed uploads from the uploading list
            this.uploadingFiles = this.uploadingFiles.filter(file => 
                file.status !== 'completed'
            );
            
            this.uploading = this.uploadingFiles.some(file => 
                file.status === 'uploading' || file.status === 'pending'
            );
            
            // Update Livewire state
            this.updateLivewireState();
        },

        // Wait for available upload slot
        async waitForSlot(semaphore, entryId) {
            return new Promise(resolve => {
                const checkSlot = () => {
                    const slotIndex = semaphore.findIndex(slot => slot === null);
                    if (slotIndex !== -1) {
                        semaphore[slotIndex] = entryId;
                        resolve();
                    } else {
                        setTimeout(checkSlot, 100);
                    }
                };
                checkSlot();
            });
        },

        // Upload individual file
        async uploadFile(entry) {
            const entryIndex = this.uploadingFiles.findIndex(f => f.id === entry.id);
            if (entryIndex !== -1) {
                this.uploadingFiles[entryIndex].status = 'uploading';
                this.uploadingFiles[entryIndex].startTime = Date.now();
                this.uploadingFiles[entryIndex].lastProgressTime = this.uploadingFiles[entryIndex].startTime;
            }

            if (this.config.isChunked && entry.file.size > this.config.chunkSize) {
                await this.uploadFileInChunks(entry);
            } else {
                await this.uploadFileDirectly(entry);
            }
        },

        // Upload file in chunks
        async uploadFileInChunks(entry) {
            const file = entry.file;
            const totalChunks = Math.ceil(file.size / this.config.chunkSize);
            
            entry.totalChunks = totalChunks;
            entry.chunks = new Array(totalChunks).fill(false);

            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                // Check if upload was cancelled
                if (this.abortControllers.has(entry.id)) {
                    throw new Error('Upload cancelled');
                }

                const start = chunkIndex * this.config.chunkSize;
                const end = Math.min(start + this.config.chunkSize, file.size);
                const chunk = file.slice(start, end);

                await this.uploadChunk(entry, chunk, chunkIndex, totalChunks);
                
                // Update progress
                const entryIndex = this.uploadingFiles.findIndex(f => f.id === entry.id);
                if (entryIndex !== -1) {
                    this.uploadingFiles[entryIndex].chunks[chunkIndex] = true;
                    this.uploadingFiles[entryIndex].uploadedChunks = this.uploadingFiles[entryIndex].chunks.filter(Boolean).length;
                    this.uploadingFiles[entryIndex].uploadedBytes = this.uploadingFiles[entryIndex].uploadedChunks * this.config.chunkSize;
                    this.uploadingFiles[entryIndex].progress = Math.round((this.uploadingFiles[entryIndex].uploadedChunks / totalChunks) * 100);
                    
                    // Update speed calculation
                    this.updateSpeed(this.uploadingFiles[entryIndex]);
                }
            }
        },

        // Upload file directly (for small files)
        async uploadFileDirectly(entry) {
            const formData = new FormData();
            formData.append('file', entry.file);
            formData.append('fileKey', entry.id);
            formData.append('name', entry.file.name);

            const controller = new AbortController();
            this.abortControllers.set(entry.id, controller);

            try {
                const response = await fetch(this.config.chunkUploadUrl, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal,
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Upload failed');
                }

                const entryIndex = this.uploadingFiles.findIndex(f => f.id === entry.id);
                if (entryIndex !== -1) {
                    this.uploadingFiles[entryIndex].url = result.url;
                    this.uploadingFiles[entryIndex].progress = 100;
                    this.uploadingFiles[entryIndex].uploadedBytes = entry.file.size;
                    this.updateSpeed(this.uploadingFiles[entryIndex]);
                }

            } finally {
                this.abortControllers.delete(entry.id);
            }
        },

        // Upload individual chunk
        async uploadChunk(entry, chunk, chunkIndex, totalChunks) {
            const formData = new FormData();
            formData.append('file', chunk);
            formData.append('fileKey', entry.id);
            formData.append('chunk', chunkIndex);
            formData.append('chunks', totalChunks);
            formData.append('name', entry.file.name);

            const controller = new AbortController();
            this.abortControllers.set(`${entry.id}_${chunkIndex}`, controller);

            try {
                const response = await fetch(this.config.chunkUploadUrl, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal,
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Upload failed');
                }

                if (result.completed) {
                    entry.url = result.url;
                }

            } finally {
                this.abortControllers.delete(`${entry.id}_${chunkIndex}`);
            }
        },

        // Update upload speed calculation
        updateSpeed(entry) {
            const now = Date.now();
            const timeDiff = now - entry.lastProgressTime;
            const totalElapsed = (now - entry.startTime) / 1000; // seconds
            
            // Show speed after 500ms and update every 500ms for more responsive feedback
            if (timeDiff >= 500 && totalElapsed > 0) {
                const speed = entry.uploadedBytes / totalElapsed;
                entry.speed = this.formatFileSize(speed);
                entry.lastProgressTime = now;
            } else if (totalElapsed > 0 && !entry.speed) {
                // Show initial "calculating..." for the first 500ms
                entry.speed = 'calculating...';
            }
        },

        // Cancel individual upload
        async cancelUpload(fileId) {
            // Cancel related requests
            const controllersToCancel = Array.from(this.abortControllers.entries())
                .filter(([key]) => key.startsWith(fileId))
                .map(([, controller]) => controller);

            controllersToCancel.forEach(controller => {
                try {
                    controller.abort();
                } catch (error) {
                    // AbortController.abort() can throw, but that's expected
                    console.debug('Controller abort error (expected):', error);
                }
            });

            // Call server to clean up chunks
            try {
                await fetch(this.config.chunkCancelUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({
                        fileKey: fileId
                    })
                });
            } catch (error) {
                console.error('Error cancelling upload:', error);
            }

            // Remove the file from uploadingFiles array
            this.uploadingFiles = this.uploadingFiles.filter(f => f.id !== fileId);
            
            // Update uploading state
            this.uploading = this.uploadingFiles.some(file => 
                file.status === 'uploading' || file.status === 'pending'
            );
        },

        // Cancel all uploads
        async cancelAllUploads() {
            // Show confirmation dialog
            const uploadingCount = this.uploadingFiles.filter(file => 
                file.status === 'uploading' || file.status === 'pending'
            ).length;
            
            if (uploadingCount === 0) return;
            
            const confirmed = confirm(`Are you sure you want to cancel all ${uploadingCount} upload${uploadingCount > 1 ? 's' : ''}?`);
            if (!confirmed) return;

            this.abortControllers.forEach(controller => {
                try {
                    controller.abort();
                } catch (error) {
                    // AbortController.abort() can throw, but that's expected
                    console.debug('Controller abort error (expected):', error);
                }
            });
            this.abortControllers.clear();

            // Get all files that need to be cancelled
            const filesToCancel = this.uploadingFiles.filter(file => 
                file.status === 'uploading' || file.status === 'pending'
            );

            // Call server to clean up chunks for each file
            const cancelPromises = filesToCancel.map(async (file) => {
                try {
                    await fetch(this.config.chunkCancelUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken()
                        },
                        body: JSON.stringify({
                            fileKey: file.id
                        })
                    });
                } catch (error) {
                    console.error('Error cancelling upload:', error);
                }
            });

            // Wait for all cancellations to complete
            await Promise.allSettled(cancelPromises);

            // Remove all pending and uploading files
            this.uploadingFiles = this.uploadingFiles.filter(file => 
                file.status !== 'uploading' && file.status !== 'pending'
            );

            this.uploading = false;
        },

        // Retry failed upload
        async retryUpload(fileId) {
            const fileIndex = this.uploadingFiles.findIndex(f => f.id === fileId);
            if (fileIndex === -1) return;

            const file = this.uploadingFiles[fileIndex];
            if (file.status !== 'error') return;

            // Reset file state
            this.uploadingFiles[fileIndex].status = 'pending';
            this.uploadingFiles[fileIndex].progress = 0;
            this.uploadingFiles[fileIndex].error = null;
            this.uploadingFiles[fileIndex].uploadedBytes = 0;
            this.uploadingFiles[fileIndex].uploadedChunks = 0;
            this.uploadingFiles[fileIndex].chunks = [];
            this.uploadingFiles[fileIndex].speed = null;

            this.uploading = true;

            try {
                await this.uploadFile(file);
                
                const entryIndex = this.uploadingFiles.findIndex(f => f.id === fileId);
                if (entryIndex !== -1) {
                    this.uploadingFiles[entryIndex].status = 'completed';
                    this.uploadingFiles[entryIndex].progress = 100;
                    
                    // Add to uploaded files
                    this.uploadedFiles.push({
                        key: file.id,
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        url: this.uploadingFiles[entryIndex].url
                    });
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    const entryIndex = this.uploadingFiles.findIndex(f => f.id === fileId);
                    if (entryIndex !== -1) {
                        this.uploadingFiles[entryIndex].status = 'error';
                        this.uploadingFiles[entryIndex].error = error.message;
                    }
                }
            }

            // Update uploading state
            this.uploading = this.uploadingFiles.some(file => 
                file.status === 'uploading' || file.status === 'pending'
            );
        },

        // Remove uploaded file
        async removeFile(fileKey) {
            const file = this.uploadedFiles.find(f => f.key === fileKey);
            if (!file) return;

            // Show confirmation dialog
            const confirmed = confirm(`Are you sure you want to delete "${file.name}"?`);
            if (!confirmed) return;

            try {
                // Construct the file path - extract relative path from URL
                let filePath = file.url || '';
                
                // Handle full URLs (e.g., https://klueportal.test/storage/uploads/file.mp4)
                if (filePath.includes('/storage/')) {
                    const storageIndex = filePath.indexOf('/storage/');
                    filePath = filePath.substring(storageIndex + '/storage/'.length);
                }
                

                // Delete from server
                const response = await fetch(this.config.chunkDeleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({
                        fileKey: fileKey,
                        path: filePath
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Failed to delete file');
                }

                // Remove from UI
                this.uploadedFiles = this.uploadedFiles.filter(f => f.key !== fileKey);
                this.updateLivewireState();

            } catch (error) {
                console.error('Error deleting file:', error);
                this.error = `Failed to delete file: ${error.message}`;
                
                // Clear error after 5 seconds
                setTimeout(() => {
                    this.error = null;
                }, 5000);
            }
        },

        // Update Livewire state
        updateLivewireState() {
            // Prevent recursive updates
            this.updatingState = true;
            
            // Convert to array of filenames only (not full paths)
            const fileNames = this.uploadedFiles.map(file => {
                if (file.url && file.url.includes('/storage/')) {
                    // Extract just the filename from the URL
                    const path = file.url.replace('/storage/', '');
                    return path.split('/').pop(); // Get just the filename with extension
                }
                return file.name; // fallback to original name
            });
            
            // ALWAYS return array for chunked uploads - never a single string
            this.state = fileNames;
            
            // Reset flag after a short delay to allow state propagation
            setTimeout(() => {
                this.updatingState = false;
            }, 100);
        },

        // Cleanup method
        cleanup() {
            try {
                this.cancelAllUploads();
            } catch (error) {
                // Ignore cleanup errors during page unload
                console.debug('Cleanup error (expected during page unload):', error);
            }
        },

        // Utility methods
        generateFileKey() {
            return Date.now().toString(36) + Math.random().toString(36).substring(2);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        parseSize(sizeString) {
            const units = { 
                'B': 1, 'BYTES': 1,
                'KB': 1024, 
                'MB': 1024 * 1024, 
                'GB': 1024 * 1024 * 1024,
                'TB': 1024 * 1024 * 1024 * 1024
            };
            
            const match = sizeString.match(/^(\d+(?:\.\d+)?)\s*(\w+)$/i);
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

        // Build file URL based on storage configuration
        buildFileUrl(fileName) {
            const storage = this.config.storage;
            
            // For public disk, use standard Laravel storage URL structure
            if (storage.disk === 'public') {
                return `/storage/${storage.finalDir}/${fileName}`;
            }
            
            // For cloud storage (S3, DO Spaces, etc.), assume full URLs are provided by server
            // This would typically be handled server-side and passed via the component
            return `/storage/${storage.finalDir}/${fileName}`; // Fallback to standard structure
        }
    };
}