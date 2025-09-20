<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video Upload Progress Demo</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Video Upload Progress CSS -->
    <link rel="stylesheet" href="{{ asset('css/components/video-upload-progress.css') }}">
    
    <style>
        /* Demo-specific styles */
        .demo-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        
        .dark .demo-section {
            background: rgb(39 39 42);
            color: white;
        }
        
        .upload-drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-drop-zone:hover,
        .upload-drop-zone.drag-over {
            border-color: #0ea5e9;
            background: #f0f9ff;
        }
        
        .dark .upload-drop-zone {
            border-color: #64748b;
            background: rgb(30 30 32);
        }
        
        .dark .upload-drop-zone:hover,
        .dark .upload-drop-zone.drag-over {
            border-color: #0ea5e9;
            background: rgb(12 74 110);
        }
    </style>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100">
    <!-- Header -->
    <header class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        Video Upload Progress Demo
                    </h1>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                        Modern video upload interface with real-time progress tracking
                    </p>
                </div>
                
                <!-- Dark Mode Toggle -->
                <button 
                    @click="document.documentElement.classList.toggle('dark')"
                    class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="Toggle dark mode"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Demo 1: Basic Upload with Progress -->
        <section class="demo-section">
            <h2 class="text-xl font-semibold mb-4">Basic Video Upload</h2>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                Click to select a video file or drag and drop to start the upload process.
            </p>
            
            <!-- Upload Drop Zone -->
            <div 
                class="upload-drop-zone"
                @click="$refs.fileInput1.click()"
                @dragover.prevent="$event.currentTarget.classList.add('drag-over')"
                @dragleave.prevent="$event.currentTarget.classList.remove('drag-over')"
                @drop.prevent="handleDrop($event, 1)"
            >
                <div class="flex flex-col items-center space-y-4">
                    <svg class="w-12 h-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/>
                    </svg>
                    <div>
                        <p class="text-lg font-medium">Drop video files here</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">or click to browse</p>
                    </div>
                    <p class="text-xs text-zinc-400">MP4, WebM, OGV up to 100MB</p>
                </div>
            </div>
            
            <input 
                type="file" 
                x-ref="fileInput1"
                @change="handleFileSelect($event, 1)"
                accept="video/*" 
                class="hidden"
            >
            
            <!-- Progress Container -->
            <div id="progress-container-1" class="mt-6" style="display: none;">
                @include('components.video-upload-progress')
            </div>
        </section>

        <!-- Demo 2: Multiple File Upload -->
        <section class="demo-section">
            <h2 class="text-xl font-semibold mb-4">Multiple Video Upload</h2>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                Upload multiple video files with individual progress tracking.
            </p>
            
            <div 
                class="upload-drop-zone"
                @click="$refs.fileInput2.click()"
                @dragover.prevent="$event.currentTarget.classList.add('drag-over')"
                @dragleave.prevent="$event.currentTarget.classList.remove('drag-over')"
                @drop.prevent="handleDrop($event, 2)"
            >
                <div class="flex flex-col items-center space-y-4">
                    <svg class="w-12 h-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    <div>
                        <p class="text-lg font-medium">Drop multiple video files here</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">or click to browse</p>
                    </div>
                </div>
            </div>
            
            <input 
                type="file" 
                x-ref="fileInput2"
                @change="handleFileSelect($event, 2)"
                accept="video/*" 
                multiple 
                class="hidden"
            >
            
            <!-- Multiple Progress Containers -->
            <div id="multiple-progress-container" class="mt-6 space-y-4"></div>
        </section>

        <!-- Demo 3: Simulated Upload States -->
        <section class="demo-section">
            <h2 class="text-xl font-semibold mb-4">Upload State Simulation</h2>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                Test different upload states and phases without actual file uploads.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <button 
                    @click="simulateUpload('success')"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
                >
                    Simulate Success
                </button>
                <button 
                    @click="simulateUpload('error')"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                >
                    Simulate Error
                </button>
                <button 
                    @click="simulateUpload('chunked')"
                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors"
                >
                    Simulate Chunked Upload
                </button>
            </div>
            
            <!-- Simulation Progress Container -->
            <div id="simulation-progress-container">
                @include('components.video-upload-progress')
            </div>
        </section>

        <!-- Upload Results -->
        <section class="demo-section">
            <h2 class="text-xl font-semibold mb-4">Upload Results</h2>
            <div id="upload-results" class="space-y-4">
                <p class="text-zinc-500 dark:text-zinc-400 text-center py-8">
                    Upload results will appear here
                </p>
            </div>
        </section>
    </main>

    <script>
        // Global Alpine.js data and methods
        document.addEventListener('alpine:init', () => {
            Alpine.data('demoPage', () => ({
                uploads: [],
                
                init() {
                    console.log('Video Upload Progress Demo initialized');
                },

                async handleFileSelect(event, demoNumber) {
                    const files = Array.from(event.target.files);
                    await this.processFiles(files, demoNumber);
                    event.target.value = ''; // Reset input
                },

                async handleDrop(event, demoNumber) {
                    event.currentTarget.classList.remove('drag-over');
                    const files = Array.from(event.dataTransfer.files).filter(file => 
                        file.type.startsWith('video/')
                    );
                    await this.processFiles(files, demoNumber);
                },

                async processFiles(files, demoNumber) {
                    if (files.length === 0) return;

                    if (demoNumber === 1) {
                        // Single file upload
                        await this.uploadSingleFile(files[0]);
                    } else if (demoNumber === 2) {
                        // Multiple file upload
                        await this.uploadMultipleFiles(files);
                    }
                },

                async uploadSingleFile(file) {
                    const container = document.getElementById('progress-container-1');
                    const progressComponent = container.querySelector('[x-data]');
                    
                    if (progressComponent && progressComponent._x_dataStack) {
                        const component = progressComponent._x_dataStack[0];
                        await this.simulateUploadProcess(component, file);
                        container.style.display = 'block';
                    }
                },

                async uploadMultipleFiles(files) {
                    const container = document.getElementById('multiple-progress-container');
                    container.innerHTML = '';

                    for (const file of files) {
                        // Create individual progress component
                        const progressDiv = document.createElement('div');
                        progressDiv.innerHTML = `
                            <div x-data="videoUploadProgress()" class="video-upload-progress">
                                @include('components.video-upload-progress')
                            </div>
                        `;
                        
                        container.appendChild(progressDiv);
                        
                        // Initialize Alpine.js for the new component
                        Alpine.initTree(progressDiv);
                        
                        // Start upload simulation
                        const progressComponent = progressDiv.querySelector('[x-data]');
                        if (progressComponent && progressComponent._x_dataStack) {
                            const component = progressComponent._x_dataStack[0];
                            this.simulateUploadProcess(component, file);
                        }
                    }
                },

                async simulateUpload(type) {
                    const container = document.getElementById('simulation-progress-container');
                    const progressComponent = container.querySelector('[x-data]');
                    
                    if (progressComponent && progressComponent._x_dataStack) {
                        const component = progressComponent._x_dataStack[0];
                        
                        // Create a mock file
                        const mockFile = {
                            name: `demo-video-${type}.mp4`,
                            size: Math.floor(Math.random() * 50) * 1024 * 1024 + 10 * 1024 * 1024 // 10-60MB
                        };
                        
                        await this.simulateUploadProcess(component, mockFile, type);
                    }
                },

                async simulateUploadProcess(component, file, scenario = 'success') {
                    // Reset component
                    component.reset();
                    component.setFileInfo(file.name, file.size);
                    
                    // Determine upload type based on file size
                    const isChunked = file.size > 50 * 1024 * 1024 || scenario === 'chunked';
                    component.setUploadType(isChunked ? 'chunk' : 'single');
                    
                    try {
                        // Phase 1: Upload
                        const uploadPhase = isChunked ? 'chunk_upload' : 'single_upload';
                        const uploadMessage = isChunked ? 'Uploading video in chunks...' : 'Uploading video...';
                        
                        component.updateStatus(uploadMessage, uploadPhase);
                        
                        // Simulate upload progress
                        for (let progress = 0; progress <= 50; progress += 2) {
                            component.updateProgress(progress);
                            await this.sleep(100);
                            
                            // Simulate error during upload
                            if (scenario === 'error' && progress === 20) {
                                throw new Error('Network connection lost');
                            }
                        }
                        
                        // Phase 2: Processing
                        component.updateStatus('Processing video...', 'video_processing');
                        
                        for (let progress = 50; progress <= 100; progress += 3) {
                            component.updateProgress(progress);
                            await this.sleep(150);
                        }
                        
                        // Phase 3: Complete
                        component.updateStatus('Video uploaded successfully!', 'complete');
                        
                        // Add to results
                        this.addUploadResult(file.name, 'success', {
                            size: file.size,
                            uploadType: isChunked ? 'chunked' : 'single'
                        });
                        
                    } catch (error) {
                        component.setError(error.message);
                        this.addUploadResult(file.name, 'error', { error: error.message });
                    }
                },

                addUploadResult(fileName, status, details = {}) {
                    const resultsContainer = document.getElementById('upload-results');
                    const resultItem = document.createElement('div');
                    
                    const statusClass = status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 
                                      'bg-red-50 border-red-200 text-red-800';
                    
                    resultItem.className = `p-4 border rounded-lg ${statusClass}`;
                    resultItem.innerHTML = `
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium">${fileName}</p>
                                <p class="text-sm opacity-75">
                                    Status: ${status.charAt(0).toUpperCase() + status.slice(1)}
                                    ${details.uploadType ? ` • Type: ${details.uploadType}` : ''}
                                    ${details.size ? ` • Size: ${this.formatFileSize(details.size)}` : ''}
                                </p>
                                ${details.error ? `<p class="text-sm mt-1">Error: ${details.error}</p>` : ''}
                            </div>
                            <span class="text-xs opacity-60">${new Date().toLocaleTimeString()}</span>
                        </div>
                    `;
                    
                    if (resultsContainer.children.length === 1 && resultsContainer.children[0].textContent.includes('Upload results will appear here')) {
                        resultsContainer.innerHTML = '';
                    }
                    
                    resultsContainer.insertBefore(resultItem, resultsContainer.firstChild);
                },

                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                },

                sleep(ms) {
                    return new Promise(resolve => setTimeout(resolve, ms));
                }
            }));
        });
    </script>

    <!-- Main App Data -->
    <div x-data="demoPage()" class="hidden"></div>
</body>
</html>