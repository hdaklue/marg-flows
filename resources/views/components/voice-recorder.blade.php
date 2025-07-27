@props([
    'onSubmit' => null,
    'class' => '',
])

@php
    $onSubmitCallback = $onSubmit ?? 'null';
@endphp

<div x-data="recorder({
    onSubmit: {{ $onSubmitCallback }}
})" x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('voiceRecorder') }}"
    x-init="init()" x-on:destroy="resetState()" x-on:comment-created.window="resetState()"
    class="{{ $class }} relative flex w-full items-center rounded-full border border-zinc-300 p-1 transition-all duration-300 dark:border-zinc-600"
    :class="{
        'border-red-500 shadow-lg shadow-red-500/20': isRecording
    }"
    @voice-note:canceled.window="resetState()">
    
    <!-- Upload Progress Bar (absolute positioned at top) -->
    <div x-show="isUploading" x-cloak 
         class="absolute top-0 left-0 right-0 h-1 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100">
        <div class="h-full bg-amber-500 transition-all duration-300 ease-out rounded-full"
             :style="`width: ${uploadProgress}%`"></div>
    </div>

    <!-- Button (always visible, fixed position) -->
    <div class="grow-0">
        <button
            @click="!isRecording && !hasRecording ? handleMainButtonClick() : (isRecording ? handleMainButtonClick() : submitRecording())"
            :disabled="!isSupported || isUploading"
            class="flex items-center justify-center p-2 text-sm font-semibold text-white transition-all duration-300 rounded-full button-morph shrink-0"
            :class="{
                'bg-sky-500 hover:bg-sky-600': !isRecording && !hasRecording && isSupported && !isUploading,
                'bg-red-500 hover:bg-red-600 recording-pulse': isRecording,
                'bg-green-600 hover:bg-green-700': hasRecording && !isRecording && !isUploading,
                'bg-amber-500': isUploading,
                'bg-zinc-400 cursor-not-allowed': !isSupported || isUploading
            }">

            <!-- Start Recording Icon (Microphone) -->
            <svg x-show="!isRecording && !hasRecording && !isUploading" class="w-4 h-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 1a4 4 0 0 1 4 4v6a4 4 0 0 1-8 0V5a4 4 0 0 1 4-4z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 10v1a7 7 0 0 1-14 0v-1" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19v4" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 23h8" />
            </svg>

            <!-- Stop Recording Icon (Stop Square) -->
            <svg x-show="isRecording && !isUploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z" />
            </svg>

            <!-- Upload Icon (Arrow Up) -->
            <svg x-show="hasRecording && !isRecording && !isUploading" class="w-4 h-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
            </svg>

            <!-- Upload Spinner -->
            <svg x-show="isUploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>


    <!-- Content Container (waveform/player) -->
    <div x-show="isRecording || hasRecording" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" class="flex-1 mx-3" x-cloak>

        <!-- Recording Waveform -->
        <div x-show="isRecording" class="w-full">
            <div class="flex h-8 items-center justify-center space-x-0.5 overflow-hidden">
                <template x-for="i in 15" :key="i">
                    <div class="w-0.5 rounded-full bg-gradient-to-t from-red-500 to-red-300 transition-all duration-75 ease-out"
                        :style="`height: ${Math.max(2, 8 + Math.sin((Date.now() + i * 100) / 150) * 4 + (volumeLevel * (20 + Math.sin(i * 0.5) * 15)))}px;
                                                                                                          opacity: ${0.6 + volumeLevel * 0.4 + Math.sin((Date.now() + i * 50) / 300) * 0.2}`">
                    </div>
                </template>
            </div>
        </div>

        <!-- Audio Player Interface -->
        <div x-show="hasRecording && !isRecording" class="w-full"
             x-init="$watch('hasRecording', value => {
                 if (value && audioUrl && !wavesurfer) {
                     $nextTick(() => initWavesurfer());
                 }
             })">
            <div class="flex items-center gap-2">
                <!-- Play/Pause Button -->
                <button @click="togglePlayback()" 
                    :disabled="!playerLoaded"
                    class="flex items-center justify-center w-7 h-7 transition-colors duration-200 rounded-full shrink-0 border"
                    :class="{
                        'border-sky-500 text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-950': !isPlaying && playerLoaded,
                        'border-red-500 text-red-500 hover:bg-red-50 dark:hover:bg-red-950': isPlaying,
                        'border-zinc-300 text-zinc-400 cursor-not-allowed dark:border-zinc-600': !playerLoaded
                    }">
                    
                    <!-- Play Icon -->
                    <svg x-show="!isPlaying" class="w-3 h-3 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    
                    <!-- Pause Icon -->
                    <svg x-show="isPlaying" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                    </svg>
                </button>

                <!-- Waveform Container -->
                <div class="flex-1 min-w-0">
                    <div x-ref="playbackWaveform" class="w-full h-6 rounded">
                        <!-- Loading state -->
                        <div x-show="!playerLoaded" class="flex items-center justify-center h-full">
                            <div class="w-3 h-3 border border-zinc-400 border-t-transparent rounded-full animate-spin"></div>
                        </div>
                    </div>
                </div>

                <!-- Time Display -->
                <div class="text-xs font-mono text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                    <span x-text="formatTime(playbackCurrentTime)">0:00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Timer (when recording) -->
    <div x-show="isRecording" class="text-xs whitespace-nowrap text-zinc-500 dark:text-zinc-400"
        x-text="formatTime(currentDuration)"></div>

    <!-- Delete Button (when has recording) -->
    <div x-show="hasRecording && !isRecording">
        <button @click.prevent="deleteRecording()"
            class="flex items-center justify-center p-1 text-red-500 rounded hover:bg-red-50 dark:hover:bg-red-900/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>

    <!-- Not Supported Message -->
    <p x-show="!isSupported" class="text-xs text-red-500 md:text-sm" x-cloak>
        Voice recording is not supported in your browser
    </p>
</div>
@vite(['resources/css/components/voice-recorder.css'])
