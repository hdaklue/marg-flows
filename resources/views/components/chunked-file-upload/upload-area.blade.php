@props([
    'modalMode' => false,
    'isDisabled' => false,
    'dragDropText' => 'Drop files here to upload',
    'clickToUploadText' => 'Click to upload or drag and drop',
])

<div class="w-full">
    <label
        x-ref="dropZone"
        class="dropzone group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed transition-all duration-200"
        :class="{
            'border-zinc-300 bg-zinc-50 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:hover:bg-zinc-700': !dragActive && !config.isDisabled,
            'border-sky-400 bg-sky-50 ring-2 ring-sky-200 dark:border-sky-500 dark:bg-sky-900/20 dark:ring-sky-800': dragActive && !config.isDisabled,
            'border-zinc-200 bg-zinc-100 cursor-not-allowed dark:border-zinc-700 dark:bg-zinc-900': config.isDisabled,
            'h-32': !modalMode,
            'h-40 sm:h-48': modalMode
        }"
        tabindex="0"
        role="button"
        :aria-disabled="config.isDisabled"
        aria-describedby="upload-instructions"
        @click="config.isDisabled || $refs.fileInput.click()"
        @keydown="handleKeyboardNavigation($event)"
        :disabled="config.isDisabled">
        
        {{-- Upload Content --}}
        <div class="flex flex-col items-center justify-center p-6 text-center">
            {{-- Icon --}}
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg transition-colors"
                :class="{
                    'bg-sky-100 dark:bg-sky-900/50': dragActive && !config.isDisabled,
                    'bg-zinc-100 dark:bg-zinc-700': !dragActive && !config.isDisabled,
                    'bg-zinc-200 dark:bg-zinc-800': config.isDisabled
                }">
                <x-filament::icon 
                    icon="heroicon-o-cloud-arrow-up"
                    class="h-6 w-6 transition-colors"
                    :class="{
                        'text-sky-600 dark:text-sky-400': dragActive && !config.isDisabled,
                        'text-zinc-500 dark:text-zinc-400': !dragActive && !config.isDisabled,
                        'text-zinc-400 dark:text-zinc-500': config.isDisabled
                    }" />
            </div>

            {{-- Main Text --}}
            <div class="space-y-2">
                <p class="text-sm font-semibold transition-colors"
                    :class="{
                        'text-sky-700 dark:text-sky-300': dragActive && !config.isDisabled,
                        'text-zinc-700 dark:text-zinc-300': !dragActive && !config.isDisabled,
                        'text-zinc-500 dark:text-zinc-500': config.isDisabled
                    }"
                    x-text="dragActive ? '{{ $dragDropText }}' : '{{ $clickToUploadText }}'">
                </p>

                {{-- File Info --}}
                <div id="upload-instructions" class="space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                    <p x-show="config.acceptedFileTypes.length > 0">
                        Supported: <span x-text="config.acceptedFileTypes.slice(0, 3).join(', ')"></span>
                        <span x-show="config.acceptedFileTypes.length > 3" 
                            x-text="' and ' + (config.acceptedFileTypes.length - 3) + ' more'"></span>
                    </p>
                    <p x-show="config.maxSize">
                        Max size: <span x-text="config.maxSize"></span>
                    </p>
                    <p x-show="config.maxFiles">
                        Max files: <span x-text="config.maxFiles"></span>
                    </p>
                    <div x-show="config.isChunked" class="flex items-center justify-center space-x-1 text-sky-600 dark:text-sky-400">
                        <x-filament::icon icon="heroicon-o-squares-2x2" class="h-3 w-3" />
                        <span>Chunked upload enabled</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Overlay for Drag State --}}
        <div 
            x-show="dragActive" 
            x-cloak
            class="absolute inset-0 rounded-xl bg-sky-500/10 dark:bg-sky-400/10 backdrop-blur-sm border-2 border-sky-400 dark:border-sky-500">
            <div class="flex h-full items-center justify-center">
                <div class="flex items-center space-x-2 rounded-lg bg-sky-100/90 px-4 py-2 dark:bg-sky-900/90">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                    <span class="text-sm font-medium text-sky-700 dark:text-sky-300">{{ $dragDropText }}</span>
                </div>
            </div>
        </div>

        {{-- Hidden File Input --}}
        <input 
            x-ref="fileInput" 
            type="file" 
            class="hidden sr-only" 
            :multiple="config.isMultiple"
            :accept="config.acceptedFileTypes.join(',')" 
            :disabled="config.isDisabled"
            @change="handleFileSelect($event)"
            aria-describedby="upload-instructions" />
    </label>
</div>