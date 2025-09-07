{{-- Test view for ChunkedFileUpload component --}}
<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChunkedFileUpload Component Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                ChunkedFileUpload Component Test
            </h1>
            <button 
                @click="darkMode = !darkMode"
                class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-lg">
                <span x-show="!darkMode">🌙 Dark</span>
                <span x-show="darkMode" x-cloak>☀️ Light</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Inline Mode Test --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-lg">
                <h2 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-white">
                    Inline Mode (Images)
                </h2>
                
                <div x-data="chunkedFileUploadComponent({
                    acceptedFileTypes: ['image/jpeg', 'image/png', 'image/gif'],
                    chunkSize: 2097152,
                    chunkUploadUrl: '/test-upload',
                    chunkDeleteUrl: '/test-delete',
                    chunkCancelUrl: '/test-cancel',
                    isChunked: true,
                    isDisabled: false,
                    isMultiple: true,
                    isPreviewable: true,
                    isImageUpload: true,
                    isVideoUpload: false,
                    maxFiles: 5,
                    maxParallelUploads: 3,
                    maxSize: '50MB',
                    minSize: null,
                    placeholder: 'Select images to upload',
                    statePath: 'test_files',
                    uploadingMessage: 'Uploading your images...',
                    storage: { disk: 'public', finalDir: 'uploads', tempDir: 'uploads/temp' },
                    modalMode: false,
                    showUrlImport: false,
                    autoFocus: true
                })" class="space-y-4">
                    
                    <x-chunked-file-upload.upload-area 
                        :isDisabled="false" 
                        clickToUploadText="Click to upload images or drag and drop" />
                    
                    <x-chunked-file-upload.progress-section />
                    
                    <x-chunked-file-upload.file-list />
                    
                    <x-chunked-file-upload.messages />
                </div>
            </div>

            {{-- Modal Mode Test --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-lg">
                <h2 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-white">
                    Modal Mode (With URL Import)
                </h2>
                
                <div x-data="chunkedFileUploadComponent({
                    acceptedFileTypes: ['application/pdf', 'image/*', 'video/*'],
                    chunkSize: 5242880,
                    chunkUploadUrl: '/test-upload',
                    chunkDeleteUrl: '/test-delete',
                    chunkCancelUrl: '/test-cancel',
                    isChunked: true,
                    isDisabled: false,
                    isMultiple: true,
                    isPreviewable: true,
                    isImageUpload: false,
                    isVideoUpload: false,
                    maxFiles: 10,
                    maxParallelUploads: 3,
                    maxSize: '100MB',
                    minSize: null,
                    placeholder: 'Select files to upload',
                    statePath: 'test_documents',
                    uploadingMessage: 'Uploading your files...',
                    storage: { disk: 'public', finalDir: 'uploads', tempDir: 'uploads/temp' },
                    modalMode: true,
                    showUrlImport: true,
                    autoFocus: true
                })" class="space-y-4">
                    
                    {{-- Modal Trigger --}}
                    <div class="flex items-center justify-center">
                        <button
                            @click="openModal()"
                            class="flex items-center space-x-2 px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span>Upload Files</span>
                            <span x-show="completedFiles.length > 0" x-cloak
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-sky-100 text-xs font-medium text-sky-800"
                                x-text="completedFiles.length">
                            </span>
                        </button>
                    </div>

                    {{-- Modal --}}
                    <div x-show="showModal && config.modalMode" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0">
                        
                        <div class="fixed inset-0 bg-zinc-950/75 backdrop-blur-sm" @click="closeModal()"></div>

                        <div x-ref="modal" tabindex="-1"
                            class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-zinc-800 rounded-xl shadow-2xl border border-zinc-200 dark:border-zinc-700 flex flex-col"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            @click.stop>

                            {{-- Modal Header --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">Upload Files</h2>
                                <button @click="closeModal()" 
                                    class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Modal Body --}}
                            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                <x-chunked-file-upload.upload-area 
                                    :modalMode="true"
                                    :isDisabled="false"
                                    dragDropText="Drop files here to upload"
                                    clickToUploadText="Click to upload or drag and drop" />
                                
                                <x-chunked-file-upload.url-import 
                                    placeholder="Enter file URL to import..." />
                                
                                <x-chunked-file-upload.progress-section />
                                
                                <x-chunked-file-upload.file-list :modal="true" />
                                
                                <x-chunked-file-upload.messages />
                            </div>

                            {{-- Modal Footer --}}
                            <div class="flex justify-end px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                                <button @click="closeModal()" 
                                    class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 rounded-lg dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Completed Files (outside modal for testing) --}}
                    <div x-show="completedFiles.length > 0" x-cloak>
                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white mb-2">Completed Files:</h3>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400">
                            <template x-for="file in completedFiles" :key="file.key">
                                <div class="flex items-center justify-between py-1">
                                    <span x-text="file.name"></span>
                                    <button @click="removeFile(file.key)" 
                                        class="text-red-500 hover:text-red-700">Remove</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Event Log --}}
        <div class="mt-8 bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-lg">
            <h2 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-white">
                Event Log
            </h2>
            <div x-data="{ events: [] }" 
                @modal-opened.window="events.unshift(`[${new Date().toLocaleTimeString()}] Modal opened`)"
                @modal-closed.window="events.unshift(`[${new Date().toLocaleTimeString()}] Modal closed`)"
                @file-uploaded.window="events.unshift(`[${new Date().toLocaleTimeString()}] File uploaded: ${$event.detail.fileName}`)"
                @file-error.window="events.unshift(`[${new Date().toLocaleTimeString()}] File error: ${$event.detail.fileName} - ${$event.detail.error}`)"
                @file-cancelled.window="events.unshift(`[${new Date().toLocaleTimeString()}] File cancelled: ${$event.detail.fileName}`)">
                
                <div class="bg-zinc-900 dark:bg-zinc-950 text-zinc-100 p-4 rounded-lg font-mono text-sm max-h-48 overflow-y-auto">
                    <template x-for="event in events.slice(0, 20)" :key="event">
                        <div x-text="event"></div>
                    </template>
                    <div x-show="events.length === 0" class="text-zinc-500">No events logged yet...</div>
                </div>
                
                <button @click="events = []" 
                    class="mt-2 px-3 py-1 text-xs bg-zinc-200 hover:bg-zinc-300 text-zinc-800 rounded dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-zinc-200">
                    Clear Log
                </button>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="mt-8 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-sky-900 dark:text-sky-100 mb-2">Testing Instructions</h3>
            <ul class="space-y-2 text-sm text-sky-800 dark:text-sky-200">
                <li>• Try dragging files to both upload areas</li>
                <li>• Test the modal functionality with the "Upload Files" button</li>
                <li>• Test URL import in the modal (try: https://picsum.photos/800/600)</li>
                <li>• Check event logging in the bottom panel</li>
                <li>• Test dark mode toggle</li>
                <li>• Test mobile responsiveness by resizing window</li>
                <li>• Test keyboard navigation (Tab, Enter, Escape)</li>
            </ul>
        </div>
    </div>

    {{-- Initialize Alpine --}}
    <script>
        // Mock data for testing
        window.chunkedFileUploadComponent = function(config) {
            return {
                // Core state
                showModal: false,
                dragActive: false,
                uploadingFiles: [],
                completedFiles: [],
                error: null,
                success: null,
                warning: null,
                info: null,
                importUrl: '',
                importingFromUrl: false,
                uploading: false,
                
                config: config,
                
                init() {
                    console.log('ChunkedFileUpload component initialized', config);
                },
                
                // Mock methods for testing UI
                openModal() { 
                    this.showModal = true; 
                    this.$dispatch('modal-opened');
                },
                closeModal() { 
                    this.showModal = false; 
                    this.$dispatch('modal-closed');
                },
                
                handleFileSelect(event) {
                    const files = Array.from(event.target.files);
                    console.log('Files selected:', files);
                    this.simulateUpload(files);
                },
                
                simulateUpload(files) {
                    files.forEach(file => {
                        const mockFile = {
                            id: Date.now() + Math.random(),
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            status: 'uploading',
                            progress: 0
                        };
                        
                        this.uploadingFiles.push(mockFile);
                        this.simulateProgress(mockFile);
                    });
                },
                
                simulateProgress(file) {
                    const interval = setInterval(() => {
                        file.progress += Math.random() * 20;
                        if (file.progress >= 100) {
                            file.progress = 100;
                            file.status = 'completed';
                            
                            // Move to completed
                            this.completedFiles.push({
                                key: file.id,
                                name: file.name,
                                size: file.size,
                                type: file.type,
                                url: '#'
                            });
                            
                            this.uploadingFiles = this.uploadingFiles.filter(f => f.id !== file.id);
                            this.$dispatch('file-uploaded', { fileId: file.id, fileName: file.name });
                            
                            clearInterval(interval);
                        }
                    }, 500);
                },
                
                removeFile(key) {
                    this.completedFiles = this.completedFiles.filter(f => f.key !== key);
                },
                
                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }
            };
        };
    </script>
</body>
</html>