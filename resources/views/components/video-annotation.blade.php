@props(['videoSrc' => '', 'comments' => '[]', 'onComment' => null, 'qualitySources' => null])

<!-- Load quality selector CSS and JS -->
{{-- <link href="https://unpkg.com/@silvermine/videojs-quality-selector/dist/css/quality-selector.css" rel="stylesheet">
<script src="https://unpkg.com/@silvermine/videojs-quality-selector/dist/js/silvermine-videojs-quality-selector.min.js"></script> --}}

<div x-data="videoAnnotation()" class="relative w-full overflow-visible bg-black rounded-lg" @destroy.window="destroy()">
    <!-- Video Player -->
    <div class="relative flex justify-center cursor-pointer" @click="handleVideoClick(); hideCommentTooltip(); showHoverAdd = false"
        @touchstart="handleTouchStart($event)" @touchend="handleTouchEnd($event); hideCommentTooltip(); showHoverAdd = false">
        <video x-ref="videoPlayer" :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
            class="w-full h-auto video-js vjs-fluid vjs-default-skin" preload="auto" data-setup='{}'
            @if ($qualitySources) data-quality-sources='@json($qualitySources)' @endif>
            @if ($qualitySources)
                @foreach ($qualitySources as $index => $source)
                    <source src="{{ $source['src'] }}" type="{{ $source['type'] ?? 'video/mp4' }}"
                        label="{{ $source['label'] ?? ($source['quality'] ?? 'Auto') }}"
                        @if ($source['selected'] ?? $index === 0) selected="true" @endif>
                @endforeach
            @elseif($videoSrc)
                <source src="{{ $videoSrc }}" type="video/mp4">
            @endif
            <p class="vjs-no-js">
                To view this video please enable JavaScript, and consider upgrading to a web browser that
                <a href="https://videojs.com/html5-video-support/" target="_blank">
                    supports HTML5 video
                </a>.
            </p>
        </video>

        <!-- Play/Pause Overlay Icon -->
        <div x-show="showPlayPauseOverlay" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-75"
            class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="p-4 rounded-full bg-black/60 backdrop-blur-sm">
                <!-- Play Icon Overlay -->
                <svg x-show="!isPlaying" x-cloak class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
                <!-- Pause Icon Overlay -->
                <svg x-show="isPlaying" x-cloak class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                </svg>
            </div>
        </div>

        <!-- Progress Bar Overlay with Comment Markers -->
        <div class="absolute bottom-0 left-0 right-0 p-4 z-10" @click.away="showHoverAdd = false">
            <!-- Comment Bubbles Above Progress Bar -->
            <div class="relative mb-2" :class="showCommentsOnProgressBar ? 'h-16' : 'h-0'">
                <div x-show="showCommentsOnProgressBar" x-cloak>
                    <template x-for="(comment, index) in comments" :key="`comment-${index}-${comment.commentId}`">
                        <div class="absolute bottom-0 transform -translate-x-1/2 cursor-pointer"
                            :style="`left: ${getCommentPosition(comment.timestamp)}px`"
                            @click.stop="seekToComment(comment.timestamp)"
                            @touchstart.stop="handleCommentTouchStart($event, comment)"
                            @touchend.stop="handleCommentTouchEnd($event, comment)">
                            <!-- Comment Bubble -->
                            <div class="relative group" @click="handleCommentClick($event, comment)">
                                <!-- Avatar Bubble -->
                                <div
                                    class="w-6 h-6 overflow-hidden transition-transform duration-200 bg-white border-2 border-white rounded-full shadow-lg hover:scale-110 dark:border-gray-800 dark:bg-gray-800">
                                    <img :src="comment.avatar" :alt="comment.name"
                                        class="object-cover w-full h-full">
                                </div>

                                <!-- Connecting Line -->
                                <div
                                    class="absolute left-1/2 top-full h-2 w-0.5 -translate-x-1/2 transform bg-white/80 dark:bg-gray-200/80">
                                </div>

                                <!-- Tooltip (Desktop hover + Mobile click) -->
                                <div class="absolute z-50 transition-opacity duration-200 pointer-events-none bottom-8"
                                    :class="[
                                        getTooltipPosition(comment.timestamp),
                                        isCommentTooltipVisible(comment) ? 'opacity-100' :
                                        'opacity-0 group-hover:opacity-100'
                                    ]">
                                    <div
                                        class="max-w-xs px-3 py-2 text-xs text-white bg-gray-900 border border-gray-700 rounded-lg shadow-xl whitespace-nowrap dark:bg-gray-800">
                                        <div class="font-medium" x-text="'@' + comment.name"></div>
                                        <div class="mt-1 text-gray-300 dark:text-gray-400"
                                            x-text="comment.body.length > 50 ? comment.body.substring(0, 50) + '...' : comment.body">
                                        </div>
                                        <div class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                                            x-text="formatTime(comment.timestamp / 1000)"></div>
                                        <!-- Tooltip Arrow -->
                                        <div class="absolute w-0 h-0 transform border-t-4 border-l-4 border-r-4 top-full border-l-transparent border-r-transparent border-t-gray-900 dark:border-t-gray-800"
                                            :class="getArrowPosition(comment.timestamp)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Progress Bar Hover Tooltip -->
            <div x-show="showHoverAdd" class="absolute z-[9999] transform -translate-x-1/2 pointer-events-none"
                :style="`left: calc(1rem + ${hoverX}px); bottom: 32px;`" 
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
                <div class="px-3 py-2 text-xs text-white bg-gray-900 rounded-lg shadow-lg whitespace-nowrap dark:bg-gray-800">
                    <span class="hidden sm:inline">Click to seek • Double-click to add comment</span>
                    <span class="sm:hidden">Tap to seek • Hold to add comment</span>
                    <!-- Tooltip Arrow -->
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900 dark:border-t-gray-800"></div>
                </div>
            </div>

            <!-- Progress Bar with Click/Double-Click -->
            <div x-ref="progressBar" 
                @click="handleProgressBarClick($event)" 
                @dblclick="handleProgressBarDoubleClick($event)"
                @touchstart="onProgressBarTouchStart($event)"
                @touchmove="onProgressBarTouchMove($event)" 
                @touchend="onProgressBarTouchEnd($event)"
                @mousemove="updateHoverPosition($event)"
                @mouseenter="showHoverAdd = true"
                @mouseleave="showHoverAdd = false"
                class="relative w-full h-2 overflow-hidden rounded-full cursor-pointer bg-white/30 backdrop-blur-sm">
                <!-- Current Progress -->
                <div class="h-full transition-all duration-100 bg-white rounded-l-full"
                    :style="`width: ${duration > 0 ? (currentTime / duration) * 100 : 0}%`"
                    :class="{ 'rounded-r-full': duration > 0 && (currentTime / duration) * 100 >= 100 }"></div>
            </div>
        </div>

    </div>

    <!-- Custom Video Controls -->
    <div class="relative p-2 mt-3 bg-gray-100 rounded-lg sm:p-3 dark:bg-gray-800">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <!-- Left Controls Group -->
            <div class="flex items-center gap-1 sm:gap-3">
                <!-- Play/Pause Button -->
                <button @click="togglePlay()" @touchstart="$event.currentTarget.style.transform = 'scale(0.95)'"
                    @touchend="$event.currentTarget.style.transform = 'scale(1)'"
                    @touchcancel="$event.currentTarget.style.transform = 'scale(1)'"
                    class="flex items-center justify-center w-10 h-10 text-white transition-all duration-200 bg-blue-600 rounded-full shadow-md video-control-btn hover:scale-105 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:scale-95 dark:focus:ring-offset-gray-800">
                    <!-- Play Icon -->
                    <svg x-show="!isPlaying" x-cloak class="ml-0.5 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <!-- Pause Icon -->
                    <svg x-show="isPlaying" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                </button>

                <!-- Volume Controls -->
                <div class="flex items-center gap-1 sm:gap-2">
                    <!-- Mute/Unmute Button -->
                    <button @click="toggleMute()"
                        class="flex items-center justify-center w-8 h-8 text-gray-600 transition-colors duration-200 rounded-lg video-control-btn hover:bg-gray-200 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                        <!-- Volume Up Icon -->
                        <svg x-show="!isMuted && volume > 0.5" x-cloak class="w-5 h-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                        </svg>
                        <!-- Volume Down Icon -->
                        <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak class="w-5 h-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.536 8.464a5 5 0 010 7.072M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                        </svg>
                        <!-- Volume Muted Icon -->
                        <svg x-show="isMuted || volume === 0" x-cloak class="w-5 h-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                        </svg>
                    </button>

                    <!-- Volume Slider (hidden on mobile) -->
                    <div class="hidden sm:block">
                        <input type="range" min="0" max="1" step="0.1" :value="volume"
                            @input="setVolume($event.target.value)"
                            class="w-20 h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer slider dark:bg-gray-600">
                    </div>
                </div>

                <!-- Time Display -->
                <div class="items-center hidden gap-1 font-mono text-sm text-gray-600 sm:flex dark:text-gray-300">
                    <span x-text="formatTime(currentTime)">0:00</span>
                    <span class="text-gray-400">/</span>
                    <span x-text="formatTime(duration)">0:00</span>
                </div>
            </div>

            <!-- Right Controls Group -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Mobile Time Display -->
                <div class="flex items-center gap-1 font-mono text-xs text-gray-600 sm:hidden dark:text-gray-300">
                    <span x-text="formatTime(currentTime)">0:00</span>
                    <span class="text-gray-400">/</span>
                    <span x-text="formatTime(duration)">0:00</span>
                </div>

                <!-- Resolution Selector -->
                <div class="relative" x-show="qualitySources.length > 1" x-cloak>
                    <button @click="showResolutionMenu = !showResolutionMenu"
                        class="flex items-center justify-center h-8 gap-1 text-gray-600 transition-colors duration-200 rounded-lg video-control-btn hover:bg-gray-200 hover:text-gray-800 sm:px-2 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                        :class="{ 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-white': showResolutionMenu }">
                        <!-- HD Icon -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1h3zM9 12l2 2 4-4" />
                        </svg>
                        <!-- Text label - hidden on mobile -->
                        <span class="hidden text-xs font-medium sm:block"
                            x-text="currentResolution ? (currentResolution.label || currentResolution.quality || 'Auto') : 'Auto'"></span>
                        <!-- Chevron - hidden on mobile -->
                        <svg class="hidden w-3 h-3 transition-transform duration-200 sm:block"
                            :class="{ 'rotate-180': showResolutionMenu }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Resolution Dropdown (Desktop) -->
                    <div x-show="showResolutionMenu" x-cloak @click.away="showResolutionMenu = false"
                        class="absolute right-0 hidden mb-2 bg-white rounded-lg shadow-lg bottom-full min-w-32 ring-1 ring-black/5 sm:block dark:bg-gray-800 dark:ring-white/10 z-50"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95">
                        <div class="p-1">
                            <div
                                class="px-3 py-2 text-xs font-medium text-gray-500 border-b border-gray-100 dark:border-gray-700 dark:text-gray-400">
                                Resolution
                            </div>

                            <template x-for="(source, index) in qualitySources"
                                :key="`resolution-${index}-${source.src}`">
                                <button @click="changeResolution(source)"
                                    class="flex items-center justify-between w-full px-3 py-2 text-sm transition-colors duration-200 rounded-md"
                                    :class="currentResolutionSrc === source.src ?
                                        'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                                    <span x-text="source.label || source.quality || 'Auto'"></span>
                                    <!-- Check Icon for Selected -->
                                    <svg x-show="currentResolutionSrc === source.src" x-cloak
                                        class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Settings Menu -->
                <div class="relative">
                    <button @click="showSettingsMenu = !showSettingsMenu"
                        class="flex items-center justify-center w-8 h-8 text-gray-600 transition-colors duration-200 rounded-lg video-control-btn hover:bg-gray-200 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                        :class="{ 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-white': showSettingsMenu }">
                        <!-- Settings Icon -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    <!-- Settings Dropdown (Desktop) -->
                    <div x-show="showSettingsMenu" x-cloak @click.away="showSettingsMenu = false"
                        class="absolute right-0 hidden w-56 mb-2 bg-white rounded-lg shadow-lg bottom-full ring-1 ring-black/5 sm:block dark:bg-gray-800 dark:ring-white/10 z-50"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95">
                        <div class="p-2">
                            <div
                                class="px-3 py-2 text-xs font-medium text-gray-500 border-b border-gray-100 dark:border-gray-700 dark:text-gray-400">
                                Video Settings
                            </div>

                            <!-- Show Comments Toggle -->
                            <button @click="toggleCommentsOnProgressBar()"
                                class="flex items-center justify-between w-full px-3 py-2 text-sm text-gray-700 rounded-md hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span>Show Comments on Progress Bar</span>
                                </div>
                                <!-- Toggle Switch -->
                                <div class="relative">
                                    <div class="w-10 h-5 transition-colors duration-200 bg-gray-300 rounded-full dark:bg-gray-600"
                                        :class="{ 'bg-blue-600 dark:bg-blue-500': showCommentsOnProgressBar }">
                                        <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                                            :class="{ 'translate-x-5': showCommentsOnProgressBar }">
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Fullscreen Button -->
                <button @click="toggleFullscreen()"
                    class="flex items-center justify-center w-8 h-8 text-gray-600 transition-colors duration-200 rounded-lg video-control-btn hover:bg-gray-200 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                    <!-- Enter Fullscreen Icon -->
                    <svg x-show="!isFullscreen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <!-- Exit Fullscreen Icon -->
                    <svg x-show="isFullscreen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9h4.5M15 9V4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15h4.5M15 15v4.5m0-4.5l5.5 5.5" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Resolution Modal -->
    <div x-show="showResolutionMenu" x-cloak @click="showResolutionMenu = false; hideCommentTooltip()"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:hidden"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.stop class="w-full max-w-md p-6 bg-white rounded-t-2xl dark:bg-gray-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full">

            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Resolution</h3>
                <button @click="showResolutionMenu = false"
                    class="p-1 text-gray-400 rounded-lg hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Resolution Options -->
            <div class="space-y-2">
                <template x-for="(source, index) in qualitySources" :key="`mobile-resolution-${index}-${source.src}`">
                    <button @click="changeResolution(source)"
                        class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl"
                        :class="currentResolutionSrc === source.src ?
                            'bg-blue-50 ring-2 ring-blue-500 dark:bg-blue-900/30 dark:ring-blue-400' :
                            'bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600'">
                        <div>
                            <div class="text-base font-medium text-gray-900 dark:text-white"
                                x-text="source.label || source.quality || 'Auto'"></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Video Quality</div>
                        </div>
                        <!-- Check Icon for Selected -->
                        <svg x-show="currentResolutionSrc === source.src" x-cloak
                            class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Mobile Settings Modal -->
    <div x-show="showSettingsMenu" x-cloak @click="showSettingsMenu = false; hideCommentTooltip()"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:hidden"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.stop class="w-full max-w-md p-6 bg-white rounded-t-2xl dark:bg-gray-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full">

            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Video Settings</h3>
                <button @click="showSettingsMenu = false"
                    class="p-1 text-gray-400 rounded-lg hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Settings Options -->
            <div class="space-y-4">
                <!-- Show Comments Toggle -->
                <button @click="toggleCommentsOnProgressBar()"
                    class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-medium text-gray-900 dark:text-white">Progress Bar Comments
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Show comment markers on timeline
                            </div>
                        </div>
                    </div>
                    <!-- Toggle Switch -->
                    <div class="relative">
                        <div class="h-6 transition-colors duration-200 bg-gray-300 rounded-full w-11 dark:bg-gray-600"
                            :class="{ 'bg-blue-600 dark:bg-blue-500': showCommentsOnProgressBar }">
                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                                :class="{ 'translate-x-5': showCommentsOnProgressBar }">
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Comments List (Optional - shows when comments exist) -->
    <div x-show="comments.length > 0" x-cloak class="p-4 mt-4 rounded-lg bg-gray-50 dark:bg-gray-900">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Comments</h3>
        </div>
        <div class="space-y-2 overflow-y-auto max-h-32">
            <template x-for="(comment, index) in comments" :key="`list-comment-${index}-${comment.commentId}`">
                <div class="flex items-start p-2 space-x-3 transition-colors duration-200 bg-white rounded-lg cursor-pointer hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                    @click="seekToComment(comment.timestamp); loadComment(comment.commentId)">
                    <img :src="comment.avatar" :alt="comment.name"
                        class="flex-shrink-0 object-cover w-8 h-8 rounded-full">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white"
                                x-text="comment.name"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                x-text="formatTime(comment.timestamp / 1000)"></span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300" x-text="comment.body"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
    /* Custom Volume Slider Styles */
    .slider::-webkit-slider-thumb {
        appearance: none;
        height: 16px;
        width: 16px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .slider::-webkit-slider-thumb:hover {
        background: #2563eb;
        transform: scale(1.1);
    }

    .slider::-moz-range-thumb {
        height: 16px;
        width: 16px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .slider::-moz-range-thumb:hover {
        background: #2563eb;
        transform: scale(1.1);
    }

    .dark .slider::-webkit-slider-thumb {
        border-color: #374151;
    }

    .dark .slider::-moz-range-thumb {
        border-color: #374151;
    }

    /* Touch-friendly button sizing for mobile */
    @media (max-width: 640px) {
        .video-control-btn {
            min-height: 44px;
            min-width: 44px;
        }
    }

    /* Mobile touch feedback */
    @media (pointer: coarse) {
        .video-control-btn:active {
            transform: scale(0.95);
        }

        .cursor-pointer {
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
        }

        /* Make progress bar touch-friendly on mobile */
        div[x-ref="progressBar"] {
            height: 20px;
        }

        /* Make comment bubbles more touch-friendly */
        .group .h-6.w-6 {
            min-height: 32px;
            min-width: 32px;
        }
    }

    /* Prevent text selection on touch */
    .video-control-btn,
    div[x-ref="progressBar"],
    .cursor-pointer {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-touch-callout: none;
    }
</style>

<script>
    // Set comments data globally to avoid JSON parsing issues
    window.videoComments = @json($comments);
</script>
