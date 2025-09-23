{{-- Video Upload Progress Modal Component --}}
<div x-data="videoUploadProgress()" 
     x-show="uploadState.isActive" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
     style="display: none;"
     id="video-upload-progress-modal">
    
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-700 w-full max-w-md overflow-hidden">
        
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Video Upload
                </h3>
                <button @click="cancelUpload()" 
                        x-show="!uploadState.isComplete && !uploadState.hasError"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Phase Indicators --}}
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-6">
                <template x-for="(phase, index) in phases" :key="phase.key">
                    <div class="flex items-center" :class="{ 'flex-1': index < phases.length - 1 }">
                        <div class="flex flex-col items-center">
                            {{-- Phase Icon --}}
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300"
                                 :class="getPhaseIconClasses(phase.key)">
                                <template x-if="phase.key === 'upload'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </template>
                                <template x-if="phase.key === 'processing'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </template>
                                <template x-if="phase.key === 'complete'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </template>
                            </div>
                            {{-- Phase Label --}}
                            <span class="text-xs font-medium mt-2 transition-colors duration-300"
                                  :class="getPhaseTextClasses(phase.key)"
                                  x-text="phase.label"></span>
                        </div>
                        {{-- Connector Line --}}
                        <div x-show="index < phases.length - 1" 
                             class="flex-1 h-0.5 mx-3 transition-colors duration-300"
                             :class="getConnectorClasses(phase.key)"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Progress Section --}}
        <div class="px-6 pb-4">
            {{-- Progress Bar --}}
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                          x-text="getStatusMessage()"></span>
                    <span class="text-sm font-medium text-sky-600 dark:text-sky-400"
                          x-text="uploadState.progress + '%'"></span>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300 ease-out"
                         :style="`width: ${uploadState.progress}%`"
                         :class="{ 
                             'bg-gradient-to-r from-sky-500 to-sky-600': !isProcessingPhase(uploadState.phase),
                             'bg-gradient-to-r from-sky-400 via-sky-500 to-sky-600 animate-pulse': isProcessingPhase(uploadState.phase)
                         }"></div>
                </div>
            </div>

            {{-- Upload Metrics --}}
            <div x-show="!uploadState.hasError && !uploadState.isComplete && uploadState.progress < 90" class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Upload Speed</div>
                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                         x-text="formatSpeed(getCurrentSpeed())"></div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Time Remaining</div>
                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                         x-text="formatETA(getCurrentETA())"></div>
                </div>
            </div>

            {{-- File Info --}}
            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">File Size:</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100"
                          x-text="formatFileSize(getTotalBytes())"></span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1" x-show="uploadState.progress < 90">
                    <span class="text-zinc-600 dark:text-zinc-400">Uploaded:</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100"
                          x-text="formatFileSize(getCurrentBytesUploaded())"></span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1" x-show="fileInfo.name">
                    <span class="text-zinc-600 dark:text-zinc-400">Filename:</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100 truncate ml-2"
                          x-text="fileInfo.name"></span>
                </div>
            </div>

            {{-- Error State --}}
            <div x-show="uploadState.hasError" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-1">Upload Failed</h4>
                        <p class="text-sm text-red-700 dark:text-red-300" x-text="uploadState.errorMessage"></p>
                    </div>
                </div>
            </div>

            {{-- Success State --}}
            <div x-show="uploadState.isComplete && !uploadState.hasError"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-emerald-800 dark:text-emerald-200">Upload Complete</h4>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">Your video has been successfully uploaded and processed.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex justify-end space-x-3">
                <template x-if="uploadState.hasError">
                    <button @click="retryUpload()"
                            class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Retry Upload
                    </button>
                </template>
                <template x-if="uploadState.isComplete">
                    <button @click="closeModal()"
                            class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Done
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>