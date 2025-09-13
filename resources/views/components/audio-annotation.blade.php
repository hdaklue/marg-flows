@props([
    'audioSrc' => '',
    'comments' => [],
    'config' => [],
])


@vite(['resources/css/audio-annotation.css'])



<div 
    x-load 
    x-load-src="/build/resources/js/components/async/audio-annotation.js"
    x-data="audioAnnotation(@js($config), @js($comments))" 
    x-init="init()" 
    @keydown.window="handleKeydown($event)"
    class="audio-annotation-container" 
    data-audio-src="{{ $audioSrc }}">
    <!-- Safari Warning -->
    <div x-show="isSafari" x-cloak class="flex items-center justify-center p-8 min-h-96">
        <div class="max-w-2xl mx-auto space-y-6 text-center">
            <div class="w-16 h-16 mx-auto text-amber-500">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd" />
                </svg>
            </div>

            <div class="space-y-3">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Safari Audio Annotation Not Supported
                </h3>
                <p class="leading-relaxed text-gray-600 dark:text-gray-400">
                    Safari has compatibility issues with our advanced audio annotation features.
                    For the best experience, please use one of these browsers:
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 mt-8 sm:grid-cols-3">
                <!-- Chrome -->
                <a href="https://www.google.com/chrome/" target="_blank"
                    class="flex flex-col items-center p-6 transition-all duration-200 bg-white border border-gray-200 group rounded-xl hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-600">
                    <div class="w-12 h-12 mb-3">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" fill="#4285F4" />
                            <circle cx="12" cy="12" r="6" fill="#34A853" />
                            <circle cx="12" cy="12" r="3" fill="#FBBC05" />
                            <path d="M12 2a10 10 0 0 0-8.66 5L12 12V2z" fill="#EA4335" />
                            <path d="M22 12a10 10 0 0 0-1.34-5L12 12h10z" fill="#4285F4" />
                            <path d="M12 22a10 10 0 0 0 8.66-5L12 12v10z" fill="#34A853" />
                        </svg>
                    </div>
                    <span
                        class="font-medium text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                        Chrome
                    </span>
                </a>

                <!-- Firefox -->
                <a href="https://www.mozilla.org/firefox/" target="_blank"
                    class="flex flex-col items-center p-6 transition-all duration-200 bg-white border border-gray-200 group rounded-xl hover:border-orange-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-orange-600">
                    <div class="w-12 h-12 mb-3">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
                                fill="#FF9500" />
                            <path
                                d="M12 4c-4.41 0-8 3.59-8 8 0 1.41.37 2.73 1.01 3.87C6.15 17.29 8.9 18 12 18c3.1 0 5.85-.71 6.99-2.13C19.63 14.73 20 13.41 20 12c0-4.41-3.59-8-8-8z"
                                fill="#FF5722" />
                        </svg>
                    </div>
                    <span
                        class="font-medium text-gray-900 group-hover:text-orange-600 dark:text-white dark:group-hover:text-orange-400">
                        Firefox
                    </span>
                </a>

                <!-- Arc -->
                <a href="https://arc.net/" target="_blank"
                    class="flex flex-col items-center p-6 transition-all duration-200 bg-white border border-gray-200 group rounded-xl hover:border-purple-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-600">
                    <div class="w-12 h-12 mb-3">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
                                fill="#6366F1" />
                            <path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </div>
                    <span
                        class="font-medium text-gray-900 group-hover:text-purple-600 dark:text-white dark:group-hover:text-purple-400">
                        Arc
                    </span>
                </a>
            </div>
        </div>
    </div>

    <!-- Audio Player (Hidden on Safari) -->
    <div x-show="!isSafari" x-cloak class="overflow-visible audio-player-wrapper">
        <!-- Waveform Container -->
        <div class="overflow-visible waveform-container">
            <div x-ref="waveform" class="waveform"></div>

            <!-- Loading State -->
            <div x-show="!isLoaded" class="waveform-loading">
                <div class="loading-spinner"></div>
                <span>Loading audio...</span>
            </div>

            <!-- Comment Bubbles Overlay -->
            <div x-ref="bubbleOverlay" class="absolute inset-0 pointer-events-none"
                style="top: 100%; height: 30px; z-index: 20;">
                <!-- Bubbles will be positioned here by JavaScript -->
            </div>

        </div>

        <!-- Mobile Frame Navigation (Sliding) -->
        <div x-show="windowWidth < 768" class="fixed inset-x-0 z-50 flex justify-end bottom-4 pe-4">
            <!-- Single Sliding Container -->
            <div class="mobile-controls-container"
                :class="mobileControlsExpanded ? 'mobile-controls-expanded' : 'mobile-controls-collapsed'">

                <!-- Gear Button (Collapsed State) -->
                <button x-show="!mobileControlsExpanded" @click="toggleMobileControls()" class="mobile-gear-button">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>

                <!-- Controls Bar (Expanded State) -->
                <div x-show="mobileControlsExpanded" x-cloak class="flex items-center justify-between w-full">

                    <!-- Frame & Zoom Controls -->
                    <div class="flex items-center justify-between flex-1">
                        <!-- Zoom Out -->
                        <button @click="zoomOut()" :disabled="!isLoaded || currentZoom <= config.zoom.minZoom"
                            class="mobile-control-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                            </svg>
                        </button>

                        <!-- Seek Backward -->
                        <button @click="seekBackward()" :disabled="!isLoaded" class="mobile-control-btn">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Zoom Reset -->
                        <button @click="zoomReset()" :disabled="!isLoaded" class="mobile-control-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>

                        <!-- Seek Forward -->
                        <button @click="seekForward()" :disabled="!isLoaded" class="mobile-control-btn">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Zoom In -->
                        <button @click="zoomIn()" :disabled="!isLoaded || currentZoom >= config.zoom.maxZoom"
                            class="mobile-control-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zm-7-4v8m4-4H7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Close Button -->
                    <button @click="toggleMobileControls()" class="mobile-close-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Audio Controls -->
        <div class="relative p-2 mt-3 overflow-visible rounded-lg bg-zinc-100 dark:bg-zinc-800 sm:p-3"
            style="overflow: visible !important;">
            <div class="grid items-center grid-cols-2 gap-2 overflow-visible sm:gap-4 md:grid-cols-3">
                <!-- START: Play Toggle + Volume -->
                <div class="flex items-center gap-1 sm:gap-3">
                    <!-- Play/Pause Button -->
                    <button @click="togglePlay()" @touchstart="$event.currentTarget.style.transform = 'scale(0.95)'"
                        @touchend="$event.currentTarget.style.transform = 'scale(1)'"
                        @touchcancel="$event.currentTarget.style.transform = 'scale(1)'" :disabled="!isLoaded"
                        class="flex items-center justify-center w-10 h-10 text-white transition-all duration-200 rounded-full shadow-md audio-control-btn bg-sky-600 hover:scale-105 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:bg-zinc-400 dark:focus:ring-offset-zinc-800"
                        title="Play/Pause (Space Bar)">
                        <!-- Play Icon -->
                        <svg x-show="!isPlaying" x-cloak class="ml-0.5 h-5 w-5" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                                clip-rule="evenodd" />
                        </svg>
                        <!-- Pause Icon -->
                        <svg x-show="isPlaying" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M6.75 5.25a.75.75 0 0 1 .75-.75H9a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H7.5a.75.75 0 0 1-.75-.75V5.25Zm7.5 0A.75.75 0 0 1 15 4.5h1.5a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75H15a.75.75 0 0 1-.75-.75V5.25Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Volume Controls -->
                    <div class="flex items-center gap-2" @mouseenter="showVolumeSlider = true"
                        @mouseleave="showVolumeSlider = false">
                        <!-- Volume Button -->
                        <button @click="windowWidth < 640 ? toggleVolumeModal() : toggleMute()"
                            class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg audio-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                            title="Volume Control">
                            <!-- Volume Up Icon -->
                            <svg x-show="!isMuted && volume > 0.5" x-cloak class="w-4 h-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Down Icon -->
                            <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak class="w-4 h-4"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Muted Icon -->
                            <svg x-show="isMuted || volume === 0" x-cloak class="w-4 h-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </button>

                        <!-- Volume Slider (Desktop - appears between icon and percentage) -->
                        <div x-show="showVolumeSlider" x-cloak class="hidden overflow-hidden sm:block"
                            x-transition:enter="transition-all ease-out duration-100"
                            x-transition:enter-start="opacity-0 transform scale-x-0"
                            x-transition:enter-end="opacity-100 transform scale-x-100"
                            x-transition:leave="transition-all ease-in duration-75"
                            x-transition:leave-start="opacity-100 transform scale-x-100"
                            x-transition:leave-end="opacity-0 transform scale-x-0">
                            <input type="range" min="0" max="1" step="0.05"
                                :value="isMuted ? 0 : volume" @input="setVolume($event.target.value)"
                                class="w-20 h-2 transition-colors duration-150 rounded-lg appearance-none cursor-pointer bg-zinc-200 hover:bg-zinc-300 focus:outline-none dark:bg-zinc-600 dark:hover:bg-zinc-500"
                                style="transform-origin: left center;" title="Volume Control">
                        </div>

                        <!-- Volume Percentage (Desktop) -->
                        <span
                            class="hidden w-8 font-mono text-xs text-center transition-colors duration-200 text-zinc-500 dark:text-zinc-400 sm:inline-block"
                            x-text="(isMuted ? '0' : getVolumePercentage()) + '%'">100%</span>

                    </div>
                </div>

                <!-- CENTER: Frame Navigation Controls -->
                <div x-show="windowWidth >= 768" class="flex items-center justify-center gap-1 sm:gap-2">
                    <!-- Backward Seek Button -->
                    <button @click="seekBackward()" @keydown.stop :disabled="!isLoaded"
                        class="flex items-center justify-center w-8 h-8 transition-all duration-200 rounded-lg audio-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:text-zinc-400 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus:ring-zinc-600 dark:focus:ring-offset-zinc-800"
                        title="Step Backward 100ms (← Arrow)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Time Display -->
                    <div class="px-2 py-1 text-xs transition-all duration-200 rounded-md bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400"
                        :title="'Time: ' + formatTime(currentTime)">
                        <span class="inline-block w-16 font-mono text-xs text-center transition-all duration-100"
                            x-text="formatTime(currentTime)">0:00.00</span>
                    </div>

                    <!-- Forward Seek Button -->
                    <button @click="seekForward()" @keydown.stop :disabled="!isLoaded"
                        class="flex items-center justify-center w-8 h-8 transition-all duration-200 rounded-lg audio-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:text-zinc-400 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus:ring-zinc-600 dark:focus:ring-offset-zinc-800"
                        title="Step Forward 100ms (→ Arrow)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- END: Playback Speed + Add Comment + Toggle Regions + Zoom Controls -->
                <div class="flex items-center justify-end gap-1 overflow-visible sm:gap-2">
                    <!-- Playback Speed Control -->
                    <div class="relative">
                        <!-- Speed Button -->
                        <button x-tooltip.raw="Playback speed" x-ref="speedButton"
                            @click="windowWidth < 768 ? toggleSpeedModal() : toggleSpeedMenu()" @keydown.stop
                            class="flex items-center gap-1 px-2 py-1 text-xs font-medium transition-all duration-200 bg-white border rounded-md shadow-sm border-zinc-300 text-zinc-700 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                            :class="{ 'bg-zinc-100 text-zinc-800 dark:bg-zinc-600 dark:text-white': showSpeedMenu }"
                            title="Playback Speed">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="font-mono text-xs" x-text="playbackRate + 'x'">1x</span>
                            <svg class="w-3 h-3 transition-transform duration-200"
                                :class="{ 'rotate-180': showSpeedMenu }" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Speed Dropdown (Desktop Only) -->
                        <div x-show="showSpeedMenu" x-cloak @click.away="showSpeedMenu = false"
                            x-init="$watch('showSpeedMenu', value => {
                                if (value) {
                                    $nextTick(() => {
                                        const button = $refs.speedButton;
                                        const dropdown = $el;
                                        const buttonRect = button.getBoundingClientRect();
                                        const dropdownRect = dropdown.getBoundingClientRect();

                                        // Calculate position below button
                                        let top = buttonRect.bottom + 8;
                                        let left = buttonRect.right - dropdownRect.width;

                                        // Check if dropdown would go below viewport
                                        if (top + dropdownRect.height > window.innerHeight) {
                                            top = buttonRect.top - dropdownRect.height - 8;
                                        }

                                        // Check if dropdown would go outside left edge
                                        if (left < 8) {
                                            left = buttonRect.left;
                                        }

                                        dropdown.style.top = top + 'px';
                                        dropdown.style.left = left + 'px';
                                    });
                                }
                            })"
                            class="fixed z-[9999] hidden min-w-32 rounded-lg bg-white shadow-xl ring-1 ring-black/5 dark:bg-zinc-800 dark:ring-white/10 sm:block"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95">
                            <div class="p-1">
                                <div
                                    class="px-3 py-2 text-xs font-medium border-b border-zinc-100 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                    Speed
                                </div>

                                <template x-for="speed in getSpeedOptions()" :key="speed.value">
                                    <button @click="setPlaybackRate(speed.value); showSpeedMenu = false"
                                        class="flex items-center justify-between w-full px-3 py-2 text-sm transition-colors duration-200 rounded-md"
                                        :class="playbackRate === speed.value ?
                                            'bg-sky-50 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300' :
                                            'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700'">
                                        <span x-text="speed.label"></span>
                                        <!-- Check Icon for Selected -->
                                        <svg x-show="playbackRate === speed.value" x-cloak
                                            class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Add Comment / Region Selection Buttons -->
                    <div x-show="!isSelectingRegion">
                        <button x-tooltip.raw="Add Comment (Shift+C)" @click="addComment()"
                            :disabled="!isLoaded || !config.features?.enableComments"
                            class="flex items-center justify-center px-2 py-1 text-white transition-all duration-200 rounded-md shadow-sm bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:bg-zinc-400 dark:focus:ring-offset-zinc-800"
                            title="Add Comment (Shift+C)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Region Selection Controls -->
                    <div x-show="isSelectingRegion" x-cloak class="flex items-center gap-1">
                        <!-- Finish Selection Button -->
                        <button @click="finishRegionSelection()"
                            class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-white transition-all duration-200 rounded-md shadow-sm bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:scale-95"
                            title="Finish Region Selection">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="hidden sm:inline">Finish</span>
                        </button>

                        <!-- Cancel Selection Button -->
                        <button @click="cancelRegionSelection()"
                            class="flex items-center gap-1 px-2 py-1 text-xs font-medium transition-all duration-200 rounded-md shadow-sm bg-zinc-200 text-zinc-700 hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 active:scale-95 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 dark:focus:ring-offset-zinc-800"
                            title="Cancel Selection">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="hidden sm:inline">Cancel</span>
                        </button>
                    </div>

                    <!-- Toggle Regions Button -->
                    <button x-tooltip.raw="Toggle Comments" @click="toggleRegions()" :disabled="!isLoaded"
                        class="flex items-center justify-center px-2 py-1 transition-all duration-200 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-zinc-800"
                        :class="showRegions ?
                            'bg-purple-600 text-white hover:bg-purple-700' :
                            'bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600'"
                        :title="showRegions ? 'Hide Regions' : 'Show Regions'">
                        <!-- Eye Icon (Show) -->
                        <svg x-show="showRegions" x-cloak class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <!-- Eye Slash Icon (Hide) -->
                        <svg x-show="!showRegions" x-cloak class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>

                    <!-- Zoom Controls (Desktop Only) -->
                    <div x-show="windowWidth >= 768" class="flex items-center gap-1">
                        <!-- Zoom Out Button -->
                        <button x-tooltip.raw="Zoom Out Waveform" @click="zoomOut()"
                            :disabled="!isLoaded || currentZoom <= config.zoom.minZoom"
                            class="flex items-center justify-center px-2 py-1 transition-all duration-200 rounded-md shadow-sm bg-zinc-200 text-zinc-700 hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 dark:focus:ring-offset-zinc-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                            </svg>
                        </button>


                        <!-- Zoom In Button -->
                        <button x-tooltip.raw="Zoom In Waveform" @click="zoomIn()"
                            :disabled="!isLoaded || currentZoom >= config.zoom.maxZoom"
                            class="flex items-center justify-center px-2 py-1 transition-all duration-200 rounded-md shadow-sm bg-zinc-200 text-zinc-700 hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 dark:focus:ring-offset-zinc-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zm-7-4v8m4-4H7" />
                            </svg>
                        </button>

                        <!-- Zoom Reset Button -->
                        <button x-tooltip.raw="Reset Zoom to 1:1" @click="zoomReset()" :disabled="!isLoaded"
                            class="flex items-center justify-center px-2 py-1 transition-all duration-200 rounded-md shadow-sm bg-zinc-200 text-zinc-700 hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 dark:focus:ring-offset-zinc-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Region Loop Toggle Button -->
                    <button x-tooltip.raw="Toggle Region Loop" @click="toggleRegionLoop()" :disabled="!isLoaded"
                        class="flex items-center justify-center px-2 py-1 transition-all duration-200 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-zinc-800"
                        :class="regionLoop ?
                            'bg-blue-600 text-white hover:bg-blue-700' :
                            'bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600'"
                        :title="regionLoop ? 'Disable Loop' : 'Enable Loop'">
                        <!-- Loop Icon -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Keyboard Shortcuts Helper Text (Bottom) -->
            <div
                class="flex flex-wrap items-center justify-center gap-3 pt-2 mt-2 text-xs border-t border-zinc-200 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400 sm:gap-4">
                <span class="flex items-center gap-1">
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">Space</kbd>
                    Play/Pause
                </span>
                <span class="flex items-center gap-1">
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">←</kbd>
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">→</kbd>
                    Seek
                </span>
                <span class="flex items-center gap-1">
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">⇧+C</kbd>
                    Comment
                </span>
                <span class="flex items-center gap-1">
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">Wheel</kbd>
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">Cmd±</kbd>
                    Zoom
                </span>
                <span class="flex items-center gap-1">
                    <kbd
                        class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">⇧+Wheel</kbd>
                    Volume
                </span>
            </div>
        </div>

        <!-- Mobile Volume Modal -->
        <div x-show="showVolumeModal" x-cloak @click.away="showVolumeModal = false"
            class="fixed inset-0 z-50 sm:hidden">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>

            <!-- Modal Content -->
            <div class="fixed bottom-0 left-0 right-0 bg-white shadow-xl rounded-t-xl dark:bg-zinc-900"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="transform translate-y-0"
                x-transition:leave-end="transform translate-y-full">

                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Volume Control</h3>
                    <button @click="showVolumeModal = false"
                        class="p-2 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Volume Content -->
                <div class="p-6 space-y-6">
                    <!-- Volume Level Display -->
                    <div class="text-center">
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white"
                            x-text="(isMuted ? '0' : getVolumePercentage()) + '%'">100%</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Volume Level</div>
                    </div>

                    <!-- Mute Toggle -->
                    <div class="flex items-center justify-center">
                        <button @click="toggleMute()"
                            class="flex items-center justify-center w-16 h-16 transition-colors duration-200 rounded-full bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            <!-- Volume Up Icon -->
                            <svg x-show="!isMuted && volume > 0.5" x-cloak class="w-8 h-8" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Down Icon -->
                            <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak class="w-8 h-8"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Muted Icon -->
                            <svg x-show="isMuted || volume === 0" x-cloak class="w-8 h-8" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </button>
                    </div>

                    <!-- Volume Slider -->
                    <div class="space-y-2">
                        <input type="range" min="0" max="1" step="0.05"
                            :value="isMuted ? 0 : volume" @input="setVolume($event.target.value)"
                            class="w-full h-3 rounded-lg appearance-none cursor-pointer bg-zinc-200 focus:outline-none dark:bg-zinc-700"
                            title="Volume Control">

                        <!-- Volume Scale -->
                        <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>0%</span>
                            <span>25%</span>
                            <span>50%</span>
                            <span>75%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Speed Modal -->
        <div x-show="showSpeedModal" x-cloak @click.away="showSpeedModal = false"
            class="fixed inset-0 z-50 sm:hidden">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>

            <!-- Modal Content -->
            <div class="fixed bottom-0 left-0 right-0 bg-white shadow-xl rounded-t-xl dark:bg-zinc-900"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="transform translate-y-0"
                x-transition:leave-end="transform translate-y-full">

                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Playback Speed</h3>
                    <button @click="showSpeedModal = false"
                        class="p-2 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Speed Content -->
                <div class="p-6 space-y-4">
                    <!-- Current Speed Display -->
                    <div class="text-center">
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white" x-text="playbackRate + 'x'">1x
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Current Speed</div>
                    </div>

                    <!-- Speed Options -->
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="speed in getSpeedOptions()" :key="speed.value">
                            <button @click="setSpeedAndCloseModal(speed.value)"
                                class="flex flex-col items-center justify-center p-4 transition-colors duration-200 border rounded-lg"
                                :class="playbackRate === speed.value ?
                                    'bg-sky-50 text-sky-700 border-sky-300 dark:bg-sky-900/50 dark:text-sky-300 dark:border-sky-600' :
                                    'bg-zinc-50 text-zinc-700 border-zinc-200 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 dark:hover:bg-zinc-700'">

                                <!-- Speed Icon -->
                                <svg class="w-6 h-6 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                        clip-rule="evenodd" />
                                </svg>

                                <!-- Speed Label -->
                                <span class="text-lg font-semibold" x-text="speed.label"></span>

                                <!-- Speed Description -->
                                <span class="text-xs opacity-75">
                                    <span x-show="speed.value < 1">Slower</span>
                                    <span x-show="speed.value === 1">Normal</span>
                                    <span x-show="speed.value > 1">Faster</span>
                                </span>

                                <!-- Selected Indicator -->
                                <svg x-show="playbackRate === speed.value" x-cloak
                                    class="w-5 h-5 mt-1 text-sky-600 dark:text-sky-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </template>
                    </div>

                    <!-- Speed Tips -->
                    <div class="text-center">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Tip: Use slower speeds for detailed analysis or faster speeds to quickly review content
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
