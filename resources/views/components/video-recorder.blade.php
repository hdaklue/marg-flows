@props([
    'onSubmit' => null,
    'class' => '',
])

@php
    $onSubmitCallback = $onSubmit ?? 'null';
@endphp

<div x-data="videoRecorder({ onSubmit: {{ $onSubmitCallback }} })" x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('videoRecorder') }}"
    x-on:destroy="destroy()" x-on:comment-created.window="resetRecording()"
    class="{{ $class }} video-recorder relative w-full transition-all duration-300">
    <!-- Upload Progress Bar -->
    <div x-show="isUploading" x-cloak class="w-full h-1 mb-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
        x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">
        <div class="h-full transition-all duration-300 ease-out bg-green-500 rounded-full"
            :style="`width: ${uploadProgress}%`"></div>
    </div>


    <!-- Video Player Container -->
    <div class="relative w-full overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
        <!-- VideoJS Player -->
        <div class="w-full" @mousedown.stop @mousemove.stop @mouseup.stop @touchstart.stop @touchmove.stop
            @touchend.stop @click.stop>
            <video x-ref="videoRecorder" id="video-recorder" class="w-full video-js vjs-default-skin" playsinline
                data-setup='{}'>
            </video>
        </div>

        <!-- Custom Controls Overlay -->
        <div class="absolute inset-0 z-50 pointer-events-none">
            <!-- Recording Timer (when recording) -->
            <div x-show="isRecording" x-cloak
                class="absolute px-3 py-1 font-mono text-sm text-white bg-red-500 rounded-full shadow-lg pointer-events-none right-4 top-4">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                    <span x-text="formatTime(currentTime)"></span>
                </div>
            </div>

            <!-- Record Button (positioned at bottom center) -->
            <div x-show="deviceReady && !isRecording && !hasRecording" x-cloak
                class="absolute transform -translate-x-1/2 pointer-events-auto bottom-8 left-1/2">
                <button @click="startRecording()"
                    class="flex items-center justify-center w-20 h-20 text-white transition-all duration-200 bg-red-500 rounded-full shadow-lg hover:scale-105 hover:bg-red-600 focus:outline-none focus:ring-4 focus:ring-red-500/30">
                    <!-- Record Icon (Hero Icon) -->
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="6" />
                    </svg>
                </button>
            </div>

            <!-- Stop Recording Button (positioned at bottom center) -->
            <div x-show="isRecording" x-cloak
                class="absolute transform -translate-x-1/2 pointer-events-auto bottom-8 left-1/2">
                <button @click="stopRecording()"
                    class="flex items-center justify-center w-20 h-20 text-white transition-all duration-200 rounded-full shadow-lg bg-zinc-800/80 backdrop-blur-sm hover:scale-105 hover:bg-zinc-700/80 focus:outline-none focus:ring-4 focus:ring-zinc-500/30">
                    <!-- Stop Icon (Hero Icon) -->
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="6" y="6" width="12" height="12" rx="2" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div x-show="hasRecording && !isRecording" x-cloak class="flex justify-end mt-3">
        <button @click="submitRecording()" :disabled="isUploading"
            class="flex items-center gap-2 px-4 py-2 text-white transition-colors duration-200 bg-green-500 rounded-lg hover:bg-green-600 disabled:bg-green-300"
            :class="{ 'cursor-not-allowed': isUploading }">
            <!-- Upload Icon -->
            <svg x-show="!isUploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <!-- Spinner -->
            <svg x-show="isUploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span x-text="isUploading ? 'Uploading...' : 'Submit Video'"></span>
        </button>
    </div>
</div>

@vite(['resources/css/components/video-recorder.css'])
