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
            <div class="text-center space-y-4">
                <svg class="w-12 h-12 mx-auto text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                
                <div class="text-center space-y-2">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Choose a file or drag & drop it here.
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->acceptedTypesDisplay }}</p>
                </div>
                
                <span class="px-6 py-3 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-500/20 transition-all dark:bg-sky-700 dark:hover:bg-sky-600 cursor-pointer inline-block">
                    Browse Files
                </span>
            </div>
        </div>
        
        <input 
            type="file" 
            class="sr-only" 
            x-ref="fileInput"
            @if($multiple) multiple @endif
            accept="{{ $this->acceptedTypes }}"
            @change="handleFileSelect($event)" 
        />
    </label>
</div>

{{-- URL Import (if enabled) --}}
@if($allowUrlImport)
    <div class="mb-6">
        <button 
            @click="toggleUrlImport()"
            class="flex items-center text-sm text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            Or import from URL
        </button>
        
        <div x-show="showUrlImport" x-collapse class="mt-3">
            <div class="flex gap-2">
                <input 
                    type="url" 
                    x-model="urlToImport"
                    x-ref="urlInput"
                    placeholder="Enter file URL..."
                    class="flex-1 px-3 py-2 text-sm border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                />
                <button 
                    @click="importFromUrl()"
                    :disabled="!urlToImport.trim()"
                    class="px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-4 focus:ring-sky-500/20"
                >
                    Import
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Uploading Files List --}}
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
                            <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="formatFileSize(file.uploadedBytes || 0) + ' of ' + formatFileSize(file.size)"></span>
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