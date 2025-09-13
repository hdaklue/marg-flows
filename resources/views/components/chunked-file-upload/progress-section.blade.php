{{-- Upload Progress Section --}}
<div x-show="uploadingFiles.length > 0" x-cloak class="space-y-4">
    {{-- Progress Header --}}
    <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-zinc-950 dark:text-white flex items-center">
            <div class="mr-2 flex h-4 w-4 items-center justify-center">
                <x-filament::loading-indicator x-show="uploading" class="h-4 w-4 text-sky-500" />
                <x-filament::icon x-show="!uploading" icon="heroicon-o-clock" class="h-4 w-4 text-amber-500" />
            </div>
            <span x-text="uploading ? 'Uploading Files...' : 'Upload Queue'"></span>
            <span class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-sky-100 text-xs font-medium text-sky-800 dark:bg-sky-900/50 dark:text-sky-200"
                x-text="uploadingFiles.length"></span>
        </h4>
        
        {{-- Cancel All Button --}}
        <x-filament::button 
            x-show="uploading"
            @click="cancelAllUploads()"
            size="xs" 
            color="danger"
            icon="heroicon-o-x-mark">
            Cancel All
        </x-filament::button>
    </div>

    {{-- Overall Progress Bar --}}
    <div x-show="uploading" x-cloak class="space-y-2">
        <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
            <span>Overall Progress</span>
            <span x-text="Math.round(uploadingFiles.reduce((acc, file) => acc + (file.progress || 0), 0) / uploadingFiles.length) + '%'"></span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div class="h-full rounded-full bg-sky-500 transition-all duration-500 dark:bg-sky-400"
                x-bind:style="`width: ${Math.round(uploadingFiles.reduce((acc, file) => acc + (file.progress || 0), 0) / uploadingFiles.length)}%`">
            </div>
        </div>
    </div>

    {{-- Individual File Progress --}}
    <div class="space-y-3">
        <template x-for="file in uploadingFiles" :key="file.id">
            <div class="relative overflow-hidden rounded-lg border transition-all duration-200"
                x-bind:class="{
                    'border-red-200 bg-red-50 dark:border-red-700 dark:bg-red-900/10': file.status === 'error',
                    'border-sky-200 bg-sky-50 dark:border-sky-700 dark:bg-sky-900/10': file.status === 'uploading',
                    'border-amber-200 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/10': file.status === 'pending',
                    'border-zinc-200 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800': file.status === 'cancelled',
                    'border-emerald-200 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900/10': file.status === 'completed'
                }">
                
                {{-- Progress Background --}}
                <div class="absolute inset-0 transition-all duration-300"
                    x-show="file.status === 'uploading'"
                    x-bind:style="`width: ${file.progress || 0}%`"
                    class="bg-sky-100/50 dark:bg-sky-800/20">
                </div>

                <div class="relative p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex min-w-0 flex-1 items-center space-x-3">
                            {{-- Status Icon --}}
                            <div class="flex-shrink-0">
                                <div class="relative flex h-8 w-8 items-center justify-center">
                                    {{-- Pending --}}
                                    <x-filament::icon 
                                        x-show="file.status === 'pending'" 
                                        icon="heroicon-o-clock"
                                        class="h-5 w-5 text-amber-500" />

                                    {{-- Uploading --}}
                                    <x-filament::loading-indicator
                                        x-show="file.status === 'uploading'"
                                        class="h-5 w-5 text-sky-500" />

                                    {{-- Completed --}}
                                    <x-filament::icon 
                                        x-show="file.status === 'completed'"
                                        icon="heroicon-o-check-circle"
                                        class="h-5 w-5 text-emerald-500" />

                                    {{-- Error --}}
                                    <x-filament::icon 
                                        x-show="file.status === 'error'"
                                        icon="heroicon-o-x-circle"
                                        class="h-5 w-5 text-red-500" />

                                    {{-- Cancelled --}}
                                    <x-filament::icon 
                                        x-show="file.status === 'cancelled'"
                                        icon="heroicon-o-minus-circle"
                                        class="h-5 w-5 text-zinc-400" />
                                </div>
                            </div>

                            {{-- File Details --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium transition-colors duration-200"
                                    x-bind:class="{
                                        'text-red-800 dark:text-red-200': file.status === 'error',
                                        'text-sky-800 dark:text-sky-200': file.status === 'uploading',
                                        'text-amber-800 dark:text-amber-200': file.status === 'pending',
                                        'text-zinc-600 dark:text-zinc-400': file.status === 'cancelled',
                                        'text-emerald-800 dark:text-emerald-200': file.status === 'completed'
                                    }"
                                    x-text="file.name">
                                </p>
                                
                                <div class="mt-1 flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs"
                                            x-bind:class="{
                                                'text-red-600 dark:text-red-400': file.status === 'error',
                                                'text-sky-600 dark:text-sky-400': file.status === 'uploading',
                                                'text-amber-600 dark:text-amber-400': file.status === 'pending',
                                                'text-zinc-500 dark:text-zinc-500': file.status === 'cancelled',
                                                'text-emerald-600 dark:text-emerald-400': file.status === 'completed'
                                            }"
                                            x-text="formatFileSize(file.size)">
                                        </span>

                                        <span class="text-xs font-medium capitalize"
                                            x-bind:class="{
                                                'text-red-600 dark:text-red-400': file.status === 'error',
                                                'text-sky-600 dark:text-sky-400': file.status === 'uploading',
                                                'text-amber-600 dark:text-amber-400': file.status === 'pending',
                                                'text-zinc-500 dark:text-zinc-500': file.status === 'cancelled',
                                                'text-emerald-600 dark:text-emerald-400': file.status === 'completed'
                                            }"
                                            x-text="file.status === 'uploading' ? `${file.progress || 0}%` : file.status">
                                        </span>
                                    </div>

                                    {{-- Upload Speed & ETA --}}
                                    <div x-show="file.status === 'uploading' && (file.speed || file.eta)" x-cloak
                                        class="flex items-center space-x-2 text-xs text-sky-600 dark:text-sky-400">
                                        <span x-show="file.speed" x-text="`${file.speed}/s`"></span>
                                        <span x-show="file.eta && file.eta > 0" x-text="`ETA: ${formatETA(file.eta)}`"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center space-x-2">
                            {{-- Cancel Upload --}}
                            <x-filament::icon-button
                                x-show="file.status === 'uploading' || file.status === 'pending'"
                                icon="heroicon-m-x-mark"
                                color="danger"
                                size="sm"
                                @click="cancelUpload(file.id)"
                                :tooltip="'Cancel upload'" />

                            {{-- Retry Upload --}}
                            <x-filament::icon-button
                                x-show="file.status === 'error' && file.retryCount < file.maxRetries"
                                icon="heroicon-m-arrow-path"
                                color="amber"
                                size="sm"
                                @click="retryUpload(file.id)"
                                :tooltip="'Retry upload'" />

                            {{-- Remove from Queue --}}
                            <x-filament::icon-button
                                x-show="file.status === 'error' || file.status === 'cancelled'"
                                icon="heroicon-m-trash"
                                color="zinc"
                                size="sm"
                                @click="uploadingFiles = uploadingFiles.filter(f => f.id !== file.id)"
                                :tooltip="'Remove from queue'" />
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div x-show="file.status === 'uploading'" x-cloak class="mt-3">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-600">
                            <div class="h-full rounded-full bg-sky-500 transition-all duration-300 dark:bg-sky-400"
                                x-bind:style="`width: ${file.progress || 0}%`">
                            </div>
                        </div>
                    </div>

                    {{-- Chunk Progress --}}
                    <div x-show="file.totalChunks > 1 && file.status === 'uploading'" x-cloak class="mt-2">
                        <div class="flex items-center space-x-1 text-xs text-sky-600 dark:text-sky-400">
                            <x-filament::icon icon="heroicon-o-squares-2x2" class="h-3 w-3" />
                            <span>Chunk <span x-text="file.uploadedChunks || 0"></span> of <span x-text="file.totalChunks"></span></span>
                        </div>
                    </div>

                    {{-- Error Message --}}
                    <div x-show="file.status === 'error' && file.error" x-cloak class="mt-2">
                        <div class="flex items-start space-x-1 rounded-md bg-red-100 p-2 dark:bg-red-900/20">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 mt-0.5 text-red-500 flex-shrink-0" />
                            <p class="text-xs text-red-700 dark:text-red-300" x-text="file.error"></p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>