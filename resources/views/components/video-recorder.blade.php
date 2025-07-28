@props([
    'onSubmit' => null,
    'class' => '',
])

@php
    $onSubmitCallback = $onSubmit ?? 'null';
@endphp

<div x-data="{
    errorMessage: '',
    showError: false,
    ...videoRecorder({ onSubmit: {{ $onSubmitCallback }} })
}" x-init="window.addEventListener('video-recorder:error', (e) => {
    errorMessage = e.detail.message;
    showError = true;
    setTimeout(() => showError = false, 5000);
});" x-load
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

    <!-- Error Message -->
    <div x-show="showError" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        class="p-3 mb-3 border border-red-200 rounded-lg bg-red-50 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-start gap-2">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"></path>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">Video Recording</p>
                <p class="text-sm text-red-700 dark:text-red-300" x-text="errorMessage"></p>
            </div>
        </div>
    </div>

    <!-- VideoJS Player -->
    <div class="w-full overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800" @mousedown.stop @mousemove.stop
        @mouseup.stop @touchstart.stop @touchmove.stop @touchend.stop @click.stop>
        <video x-ref="videoRecorder" class="w-full video-js vjs-default-skin" playsinline data-setup='{}'>
        </video>
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
