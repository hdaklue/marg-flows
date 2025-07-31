<div x-data="recorder({
    onSubmit: (blob) => $wire.call('uploadRecording', blob),
    instanceKey: @js($instanceKey),
    maxDuration: @js($maxDurationInSeconds)
})"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('voiceRecorder') }}"
    class="relative flex items-center w-full p-1 transition-all duration-300 border rounded-lg border-zinc-300 dark:border-zinc-600"
    :class="{
        'border-sky-400 bg-sky-50 dark:border-sky-500 dark:bg-sky-950/30 animate-pulse': isRecording,
        'bg-white dark:bg-zinc-900': !isRecording
    }"
    x-init="init()">

    <!-- Upload Progress Bar -->
    <div x-show="isUploading" x-cloak
        class="absolute top-0 left-0 right-0 h-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
        x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">
        <div class="h-full transition-all duration-300 ease-out rounded-full bg-amber-500"
            :style="`width: ${uploadProgress}%`"></div>
    </div>

    <!-- Record Button -->
    <div class="grow-0">
        <button @click="isRecording ? stopRecording() : startRecording()" :disabled="!isSupported || isUploading"
            class="flex items-center justify-center p-2 text-sm font-semibold transition-all duration-300 rounded-lg shrink-0"
            :class="{
                // Solid styles (default)
                @if (!$outlined) 'text-white bg-emerald-500 hover:bg-emerald-600': !isRecording && isSupported && !isUploading,
                'text-white bg-red-500 hover:bg-red-600 animate-pulse': isRecording,
                'text-white bg-amber-500': isUploading,
                'text-white bg-zinc-400 cursor-not-allowed': !isSupported || isUploading
                @else
                // Outlined styles
                'text-emerald-600 border border-emerald-500 hover:bg-emerald-50 dark:text-emerald-400 dark:border-emerald-400 dark:hover:bg-emerald-950/20': !isRecording && isSupported && !isUploading,
                'text-red-600 border border-red-500 hover:bg-red-50 dark:text-red-400 dark:border-red-400 dark:hover:bg-red-950/20 animate-pulse': isRecording,
                'text-amber-600 border border-amber-500 bg-amber-50 dark:text-amber-400 dark:border-amber-400 dark:bg-amber-950/20': isUploading,
                'text-zinc-400 border border-zinc-300 cursor-not-allowed dark:border-zinc-600': !isSupported || isUploading @endif
            }">

            <!-- Start Recording Icon -->
            <svg x-show="!isRecording && !isUploading" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8.25 4.5a3.75 3.75 0 1 1 7.5 0v4.5a3.75 3.75 0 1 1-7.5 0V4.5Z" />
                <path
                    d="M6 10.5a.75.75 0 0 1 .75.75 5.25 5.25 0 0 0 10.5 0 .75.75 0 0 1 1.5 0 6.751 6.751 0 0 1-6 6.709v2.291h3a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1 0-1.5h3v-2.291A6.751 6.751 0 0 1 5.25 11.25.75.75 0 0 1 6 10.5Z" />
            </svg>

            <!-- Stop Recording Icon -->
            <svg x-show="isRecording && !isUploading" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd"
                    d="M4.5 7.5a3 3 0 0 1 3-3h9a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-9a3 3 0 0 1-3-3v-9Z"
                    clip-rule="evenodd" />
            </svg>

            <!-- Upload Spinner -->
            <svg x-show="isUploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </button>
    </div>

    <!-- Waveform Container -->
    <div x-show="isRecording || hasRecording" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="flex-1 mx-3" x-cloak>

        <!-- Recording Waveform -->
        <div x-show="isRecording" class="w-full">
            <div x-ref="recordingWaveform" class="w-full h-8 rounded bg-zinc-50 dark:bg-zinc-800"></div>
        </div>

        <!-- Playback Interface -->
        <div x-show="hasRecording && !isRecording" class="w-full">
            <div class="flex items-center gap-2">
                <!-- Play/Pause Button -->
                <button @click="togglePlayback()"
                    class="flex items-center justify-center w-6 h-6 transition-colors duration-200 rounded shrink-0"
                    :class="{
                        @if (!$outlined) 'bg-sky-500 text-white hover:bg-sky-600': !isPlaying,
                        'bg-red-500 text-white hover:bg-red-600': isPlaying
                        @else
                        'border border-sky-500 text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-950': !isPlaying,
                        'border border-red-500 text-red-500 hover:bg-red-50 dark:hover:bg-red-950': isPlaying @endif
                    }">

                    <!-- Play Icon -->
                    <svg x-show="!isPlaying" class="ml-0.5 h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                            clip-rule="evenodd" />
                    </svg>

                    <!-- Pause Icon -->
                    <svg x-show="isPlaying" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M6.75 5.25a.75.75 0 0 1 .75-.75H9a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V5.25Zm7.5 0A.75.75 0 0 1 15 4.5h1.5a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H15a.75.75 0 0 1-.75-.75V5.25Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Playback Waveform Container -->
                <div class="flex-1 min-w-0">
                    <div x-ref="playbackWaveform" class="w-full h-8 rounded bg-zinc-50 dark:bg-zinc-800"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons (when has recording) -->
    <div x-show="hasRecording && !isRecording" class="flex items-center gap-1">
        <!-- Submit Button -->
        <button @click="submitRecording()" :disabled="isUploading"
            class="flex items-center justify-center p-1 transition-colors duration-200 rounded"
            :class="{
                @if (!$outlined) 'bg-emerald-500 text-white hover:bg-emerald-600': !isUploading,
                'bg-zinc-400 text-white cursor-not-allowed': isUploading
                @else
                'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20': !isUploading,
                'text-zinc-400 cursor-not-allowed': isUploading @endif
            }">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
        </button>

        <!-- Delete Button -->
        <button @click="deleteRecording()"
            class="flex items-center justify-center p-1 transition-colors duration-200 rounded"
            :class="{
                @if (!$outlined) 'bg-red-500 text-white hover:bg-red-600': true
                @else
                'text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20': true @endif
            }">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
        </button>
    </div>

    <!-- Timer / Max Duration (always at the end) -->
    <div class="ml-2 text-xs whitespace-nowrap">
        <!-- Timer (when recording) -->
        <div x-show="isRecording"
            :class="{
                'text-red-500 dark:text-red-400 font-medium': currentDuration >= maxDuration * 0.9,
                'text-amber-500 dark:text-amber-400 font-medium': currentDuration >= maxDuration * 0.75 &&
                    currentDuration < maxDuration * 0.9,
                'text-zinc-500 dark:text-zinc-400': currentDuration < maxDuration * 0.75
            }">
            <span x-text="formatTime(currentDuration)"></span>
            <span class="text-zinc-400 dark:text-zinc-500">/</span>
            <span x-text="formatTime(maxDuration)"></span>
        </div>

        <!-- Max Duration Hint (when idle) -->
        <div x-show="!isRecording && !hasRecording && isSupported" class="text-zinc-500 dark:text-zinc-400" x-cloak>
            Max: <span x-text="formatTime(maxDuration)"></span>
        </div>
    </div>

    <!-- Not Supported Message -->
    <p x-show="!isSupported" class="text-xs text-red-500 md:text-sm" x-cloak>
        Voice recording is not supported in your browser
    </p>
</div>
