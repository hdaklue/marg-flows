{{-- Configuration Script --}}
<script>
    window.chunkedFileUploadConfig_{{ uniqid() }} = {
        modalMode: {{ isset($modalMode) ? ($modalMode ? 'true' : 'false') : 'false' }},
        allowUrlImport: {{ isset($allowUrlImport) ? ($allowUrlImport ? 'true' : 'false') : 'false' }},
        acceptedFileTypes: {!! json_encode($acceptedFileTypes ?? ['image/jpeg', 'image/png', 'application/pdf', 'video/mp4']) !!},
        chunkSize: {{ $chunkSize ?? (5 * 1024 * 1024) }},
        multiple: {{ isset($multiple) ? ($multiple ? 'true' : 'false') : 'true' }},
        maxFiles: {{ $maxFiles ?? 10 }},
        statePath: '{{ $statePath ?? 'files' }}',
        routes: {
            store: '{{ route('chunked-upload.store') }}',
            delete: '{{ route('chunked-upload.delete') }}',
            cancel: '{{ route('chunked-upload.cancel') }}'
        }
    };
</script>

{{-- Standalone Modern File Upload Component --}}
<div 
    x-data="chunkedFileUploadWrapper"
    class="chunked-file-upload-wrapper"
>
    {{-- Upload trigger button (when in modal mode) --}}
    <template x-if="modalMode">
        <button 
            type="button"
            @click="openModal()"
            class="flex items-center justify-center px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Upload Files
        </button>
    </template>

    {{-- Modal backdrop and dialog --}}
    <div 
        x-show="showModal" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-zinc-800/90 backdrop-blur-sm"
        @click="closeModal()"
    >
        <div class="flex items-center justify-center min-h-full p-4">
            <div 
                @click.stop
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl dark:bg-zinc-900"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Upload files</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Select and upload the files of your choice</p>
                    </div>
                    <button 
                        @click="closeModal()" 
                        class="p-2 text-zinc-400 rounded-lg hover:bg-zinc-100 hover:text-zinc-600 transition-colors dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Modal Content --}}
                <div class="p-6">
                    {{-- Upload Area --}}
                    <div class="mb-6">
                        <label class="block w-full cursor-pointer group">
                            <div 
                                :class="isDragOver ? 'border-sky-500 bg-sky-50 dark:bg-sky-900/20' : 'border-zinc-300 dark:border-zinc-600'"
                                class="flex flex-col items-center justify-center w-full h-64 border-2 border-dashed rounded-xl transition-all duration-200 hover:border-sky-400 hover:bg-sky-50/50 dark:hover:bg-sky-900/10 group-hover:border-sky-400"
                                @drop.prevent="handleDrop($event)"
                                @dragover.prevent="handleDragOver($event)"
                                @dragenter.prevent="handleDragOver($event)"
                                @dragleave.prevent="handleDragLeave($event)"
                            >
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="p-4 rounded-full bg-zinc-100 dark:bg-zinc-800 group-hover:bg-sky-100 dark:group-hover:bg-sky-900/30 transition-colors">
                                        <svg class="w-12 h-12 text-zinc-400 group-hover:text-sky-500 transition-colors dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    </div>
                                    
                                    <div class="text-center space-y-2">
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            Choose a file or drag & drop it here.
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">JPEG, PNG, PDF, and MP4 formats, up to 50 MB</p>
                                    </div>
                                    
                                    <span class="px-6 py-3 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20 transition-all dark:bg-sky-700 dark:hover:bg-sky-600 cursor-pointer inline-block">
                                        Browse File
                                    </span>
                                </div>
                            </div>
                            
                            <input 
                                type="file" 
                                class="sr-only" 
                                x-ref="fileInput"
                                multiple
                                accept="{{ implode(',', $acceptedFileTypes ?? ['image/jpeg', 'image/png', 'application/pdf', 'video/mp4']) }}"
                                @change="handleFileSelect($event)" 
                            />
                        </label>
                    </div>

                    {{-- File List --}}
                    <div x-show="uploadingFiles.length > 0" class="space-y-3">
                        <template x-for="file in uploadingFiles" :key="file.id">
                            <div class="relative overflow-hidden bg-white rounded-lg border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700">
                                <div class="absolute inset-0 bg-gradient-to-r from-sky-500/10 to-transparent transition-all duration-300"
                                     :style="`width: ${file.progress}%`"></div>
                                
                                <div class="relative p-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="relative flex-shrink-0">
                                            <div class="w-10 h-12 bg-red-100 rounded flex items-center justify-center" x-show="file.type?.includes('pdf')">
                                                <span class="text-xs font-bold text-red-600">PDF</span>
                                            </div>
                                            <div class="w-10 h-12 bg-blue-100 rounded flex items-center justify-center" x-show="file.type?.startsWith('image/')">
                                                <span class="text-xs font-bold text-blue-600">IMG</span>
                                            </div>
                                            <div class="w-10 h-12 bg-purple-100 rounded flex items-center justify-center" x-show="file.type?.startsWith('video/')">
                                                <span class="text-xs font-bold text-purple-600">VID</span>
                                            </div>
                                            <div class="w-10 h-12 bg-zinc-100 rounded flex items-center justify-center" x-show="!file.type?.includes('pdf') && !file.type?.startsWith('image/') && !file.type?.startsWith('video/')">
                                                <span class="text-xs font-bold text-zinc-600">DOC</span>
                                            </div>
                                            
                                            {{-- Status indicator --}}
                                            <div class="absolute -bottom-1 -right-1">
                                                <template x-if="file.status === 'uploading'">
                                                    <div class="w-6 h-6 bg-sky-500 rounded-full flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l3-3m0 0l3 3m-3-3v9"></path>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="file.status === 'completed'">
                                                    <div class="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="file.status === 'error'">
                                                    <div class="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center">
                                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate" x-text="file.name"></p>
                                            <div class="flex items-center space-x-3 mt-1">
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="formatFileSize(file.uploadedBytes) + ' of ' + formatFileSize(file.size)"></span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400" x-show="file.status === 'uploading' && file.speed">
                                                    <span x-text="file.speed"></span>/s
                                                </span>
                                                <span class="text-xs text-emerald-600 dark:text-emerald-400" x-show="file.status === 'completed'">
                                                    Completed
                                                </span>
                                                <span class="text-xs text-red-600 dark:text-red-400" x-show="file.status === 'error'">
                                                    <span x-text="file.error"></span>
                                                </span>
                                            </div>
                                            
                                            {{-- Progress bar --}}
                                            <div x-show="file.status === 'uploading'" class="w-full bg-zinc-200 rounded-full h-1.5 mt-2 dark:bg-zinc-700">
                                                <div class="bg-sky-600 h-1.5 rounded-full transition-all duration-300 dark:bg-sky-500" 
                                                     :style="`width: ${file.progress}%`"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-shrink-0">
                                            <button x-show="file.status === 'uploading'" 
                                                    @click="cancelUpload(file.id)"
                                                    class="p-1.5 text-zinc-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors dark:hover:bg-red-900/20"
                                                    title="Cancel upload">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <button x-show="file.status === 'completed'" 
                                                    @click="removeFile(file.key)"
                                                    class="p-1.5 text-zinc-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors dark:hover:bg-red-900/20"
                                                    title="Remove file">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                            <button x-show="file.status === 'error'" 
                                                    @click="retryUpload(file.id)"
                                                    class="p-1.5 text-zinc-400 hover:text-sky-600 rounded-lg hover:bg-sky-50 transition-colors dark:hover:bg-sky-900/20"
                                                    title="Retry upload">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Uploaded Files List --}}
                    <div x-show="uploadedFiles.length > 0" class="mt-6">
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Uploaded Files</h4>
                        <div class="space-y-2">
                            <template x-for="file in uploadedFiles" :key="file.key">
                                <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg dark:bg-emerald-900/20">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100" x-text="file.name"></p>
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400" x-text="formatFileSize(file.size)"></p>
                                        </div>
                                    </div>
                                    <button @click="removeFile(file.key)"
                                            class="p-1 text-emerald-400 hover:text-red-600 rounded transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- URL Import Section --}}
                    <template x-if="allowUrlImport">
                        <div>
                            <div class="flex items-center my-8">
                                <div class="flex-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                                <span class="px-4 text-sm text-zinc-500 bg-white dark:bg-zinc-900 dark:text-zinc-400">OR</span>
                                <div class="flex-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                            </div>

                            <div class="space-y-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Import from URL Link</span>
                                    <div class="flex mt-2 space-x-3">
                                        <input 
                                            type="url" 
                                            x-model="importUrl"
                                            placeholder="Paste file URL"
                                            class="flex-1 px-4 py-2.5 text-sm border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" 
                                        />
                                        <button 
                                            @click="importFromUrl()" 
                                            :disabled="!importUrl.trim()"
                                            class="px-6 py-2.5 text-sm font-medium text-sky-600 border border-sky-600 rounded-lg hover:bg-sky-50 disabled:opacity-50 disabled:cursor-not-allowed dark:text-sky-400 dark:border-sky-400 dark:hover:bg-sky-900/20">
                                            Import
                                        </button>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Inline Upload Area (when not in modal mode) --}}
    <template x-if="!modalMode">
        <div class="space-y-4">
            <label class="block w-full cursor-pointer group">
                <div 
                    :class="isDragOver ? 'border-sky-500 bg-sky-50 dark:bg-sky-900/20' : 'border-zinc-300 dark:border-zinc-600'"
                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg transition-all duration-200 hover:border-sky-400 hover:bg-sky-50/50 dark:hover:bg-sky-900/10"
                    @drop.prevent="handleDrop($event)"
                    @dragover.prevent="handleDragOver($event)"
                    @dragenter.prevent="handleDragOver($event)"
                    @dragleave.prevent="handleDragLeave($event)"
                >
                    <div class="text-center space-y-2">
                        <svg class="w-8 h-8 mx-auto text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Drop files here or click to browse</p>
                    </div>
                </div>
                
                <input 
                    type="file" 
                    class="sr-only" 
                    multiple
                    accept="image/jpeg,image/png,application/pdf,video/mp4"
                    @change="handleFileSelect($event)" 
                />
            </label>
        </div>
    </template>
</div>


<style>
[x-cloak] {
    display: none !important;
}

.drag-over {
    @apply border-sky-500 bg-sky-50 scale-105 dark:bg-sky-900/20;
}
</style>