@props(['videoSrc' => '', 'comments' => '[]', 'onComment' => null, 'qualitySources' => null, 'config' => null])

<!-- Load quality selector CSS and JS -->
{{-- <link href="https://unpkg.com/@silvermine/videojs-quality-selector/dist/css/quality-selector.css" rel="stylesheet">
<script src="https://unpkg.com/@silvermine/videojs-quality-selector/dist/js/silvermine-videojs-quality-selector.min.js"></script> --}}

<div x-data="videoAnnotation(@if($config) @js($config) @else null @endif)" class="relative w-full overflow-visible bg-black rounded-lg" @destroy.window="destroy()"
    @contextmenu="handleVideoRightClick($event)">
    
    <!-- Context menu (only if annotations enabled) -->
    <div x-cloak x-show="showContextMenu && config.annotations.enableContextMenu" @click.away.window="showContextMenu = false"
        class="fixed z-50 flex flex-col bg-gray-100 rounded-lg w-36 dark:bg-gray-800 dark:text-gray-200"
        :style="`left: ${contextMenuX}px; top: ${contextMenuY}px`">
        <div @click="addCommentFromContextMenu()" class="p-2 text-xs rounded-lg cursor-pointer dark:hover:bg-gray-700">Add
            comment</div>
    </div>

    <!-- Video Player -->
    <div class="relative flex justify-center cursor-pointer"
        @click.prevent="handleVideoClick(); hideCommentTooltip(); showHoverAdd = false"
        @touchstart="handleTouchStart($event)"
        @touchend="handleTouchEnd($event); hideCommentTooltip(); showHoverAdd = false" @mouseenter="handleVideoHover()"
        @mouseleave="handleVideoLeave()">
        <video x-ref="videoPlayer" :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
            class="w-full h-auto video-js vjs-fluid vjs-default-skin" preload="auto" data-setup='{}' playsinline
            webkit-playsinline
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
        <div x-show="showProgressBar" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2" class="absolute bottom-0 left-0 right-0 z-10 p-4"
            @click.away="showHoverAdd = false">
            <!-- Comment Bubbles Above Progress Bar (only if annotations enabled) -->
            <div class="relative mb-2" :class="showCommentsOnProgressBar && config.features.enableAnnotations ? 'h-16' : 'h-0'">
                <div x-show="showCommentsOnProgressBar && config.features.enableAnnotations" x-cloak>
                    <template x-for="(comment, index) in comments" :key="`comment-${index}-${comment.commentId}`">
                        <div class="absolute bottom-0 transform -translate-x-1/2 cursor-pointer comment-bubble"
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

            <!-- Progress Bar Time Preview (Desktop hover) -->
            <div x-show="showHoverAdd && !isTouchDevice()"
                class="pointer-events-none absolute bottom-12 z-[9999] hidden -translate-x-1/2 transform sm:block"
                :style="`left: calc(1rem + ${hoverX}px);`" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90">
                <div
                    class="px-2 py-1 text-xs font-mono text-white bg-gray-900 rounded shadow-lg dark:bg-gray-800">
                    <span x-text="formatTime((hoverX / progressBarWidth) * duration)">0:00</span>
                    <!-- Tooltip Arrow -->
                    <div
                        class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-gray-900 dark:border-t-gray-800">
                    </div>
                </div>
            </div>

            <!-- Progress Bar with Click/Double-Click -->
            <div x-ref="progressBar" @click="handleProgressBarClick($event)"
                @dblclick="handleProgressBarDoubleClick($event)" @touchstart="onProgressBarTouchStart($event)"
                @touchmove="onProgressBarTouchMove($event)" @touchend="onProgressBarTouchEnd($event)"
                @mousemove="updateHoverPosition($event)" 
                @mouseenter="showHoverAdd = true"
                @mouseleave="showHoverAdd = false"
                class="relative w-full h-2 overflow-hidden rounded-full cursor-pointer bg-gray-500/50 backdrop-blur-sm border border-blue-400/30">
                <!-- Current Progress -->
                <div class="h-full transition-all duration-100 bg-gradient-to-r from-blue-300 to-blue-600 rounded-l-full progress-fill"
                    :style="`width: ${duration > 0 ? (currentTime / duration) * 100 : 0}%`"
                    :class="{ 'rounded-r-full': duration > 0 && (currentTime / duration) * 100 >= 100 }"></div>
            </div>

            <!-- Time Display Under Progress Bar -->
            <div class="flex items-center justify-between mt-1 text-xs font-mono text-white drop-shadow-lg">
                <span x-text="formatTime(currentTime)" class="px-1 bg-black/50 rounded backdrop-blur-sm">0:00</span>
                <span x-text="formatTime(duration)" class="px-1 bg-black/50 rounded backdrop-blur-sm">0:00</span>
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
                    <!-- Mobile Volume Button -->
                    <button @click="showVolumeModal = !showVolumeModal"
                        class="flex items-center justify-center w-8 h-8 text-gray-600 transition-colors duration-200 rounded-lg video-control-btn hover:bg-gray-200 hover:text-gray-800 sm:hidden dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
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

                    <!-- Desktop Volume Controls -->
                    <div class="items-center hidden gap-2 sm:flex" @mouseenter="showVolumeSlider = true"
                        @mouseleave="showVolumeSlider = false">
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
                            <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak class="w-5 h-5"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                        <!-- Volume Percentage Display -->
                        <div x-show="!showVolumeSlider"
                            class="text-xs text-center text-gray-600 min-w-8 dark:text-gray-300">
                            <span x-text="Math.round(volume * 100) + '%'"></span>
                        </div>

                        <!-- Volume Slider (appears on hover) -->
                        <div x-show="showVolumeSlider" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95">
                            <input type="range" min="0" max="1" step="0.1"
                                :value="volume" @input="setVolume($event.target.value)"
                                class="w-20 h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer slider dark:bg-gray-600">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Controls Group -->
            <div class="flex items-center gap-1 sm:gap-2">

                <!-- Resolution Selector -->
                <div class="relative" x-show="qualitySources.length > 1 && config.features.enableResolutionSelector" x-cloak>
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
                        class="absolute right-0 z-50 hidden mb-2 bg-white rounded-lg shadow-lg bottom-full min-w-32 ring-1 ring-black/5 sm:block dark:bg-gray-800 dark:ring-white/10"
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
                <div class="relative" x-show="config.features.enableSettingsMenu">
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
                        class="absolute right-0 z-50 hidden w-56 mb-2 bg-white rounded-lg shadow-lg bottom-full ring-1 ring-black/5 sm:block dark:bg-gray-800 dark:ring-white/10"
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

                            <!-- Show Comments Toggle (only if annotations enabled) -->
                            <button x-show="config.features.enableAnnotations" @click="toggleCommentsOnProgressBar()"
                                class="flex items-center justify-between w-full px-3 py-2 text-sm text-gray-700 rounded-md hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span>Show Comments</span>
                                </div>
                                <!-- Toggle Switch -->
                                <div class="relative">
                                    <div class="w-10 h-5 transition-colors duration-200 rounded-full"
                                        :class="showCommentsOnProgressBar ? 'bg-blue-600 dark:bg-blue-500' :
                                            'bg-gray-300 dark:bg-gray-600'">
                                        <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                                            :class="showCommentsOnProgressBar ? 'translate-x-5' : ''">
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <!-- Progress Bar Visibility Toggle -->
                            <button @click="toggleProgressBarMode()"
                                class="flex items-center justify-between w-full px-3 py-2 text-sm text-gray-700 rounded-md hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Progress Bar</span>
                                </div>
                                <!-- Mode Display -->
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span x-show="progressBarMode === 'always-visible'">Always</span>
                                    <span x-show="progressBarMode === 'auto-hide'">Auto-hide</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Fullscreen Button -->
                <button x-show="config.features.enableFullscreenButton" @click="toggleFullscreen()"
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
                <!-- Show Comments Toggle (only if annotations enabled) -->
                <button x-show="config.features.enableAnnotations" @click="toggleCommentsOnProgressBar()"
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

                <!-- Progress Bar Visibility Toggle -->
                <button @click="toggleProgressBarMode()"
                    class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-medium text-gray-900 dark:text-white">Progress Bar Visibility
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <span x-show="progressBarMode === 'always-visible'">Always visible on video</span>
                                <span x-show="progressBarMode === 'auto-hide'">Auto-hide after 2 seconds</span>
                            </div>
                        </div>
                    </div>
                    <!-- Mode Display -->
                    <div class="px-3 py-1 text-xs font-medium rounded-full"
                        :class="progressBarMode === 'always-visible' ?
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'">
                        <span x-show="progressBarMode === 'always-visible'">Always</span>
                        <span x-show="progressBarMode === 'auto-hide'">Auto-hide</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Volume Modal -->
    <div x-show="showVolumeModal" x-cloak @click="showVolumeModal = false"
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Volume Control</h3>
                <button @click="showVolumeModal = false"
                    class="p-1 text-gray-400 rounded-lg hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Volume Controls -->
            <div class="space-y-6">
                <!-- Volume Level Display -->
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span x-text="Math.round(volume * 100) + '%'"></span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Volume Level</div>
                </div>

                <!-- Mute Toggle -->
                <button @click="toggleMute()"
                    class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-lg dark:bg-blue-900">
                            <!-- Volume Up Icon -->
                            <svg x-show="!isMuted && volume > 0.5" x-cloak
                                class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Down Icon -->
                            <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak
                                class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Muted Icon -->
                            <svg x-show="isMuted || volume === 0" x-cloak
                                class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-medium text-gray-900 dark:text-white">
                                <span x-show="!isMuted">Mute Audio</span>
                                <span x-show="isMuted">Unmute Audio</span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Toggle sound on/off</div>
                        </div>
                    </div>
                </button>

                <!-- Volume Slider -->
                <div class="space-y-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Adjust Volume</label>
                    <div class="relative">
                        <input type="range" min="0" max="1" step="0.01" :value="volume"
                            @input="setVolume($event.target.value)"
                            class="w-full h-3 bg-gray-300 rounded-lg appearance-none cursor-pointer slider dark:bg-gray-600">
                        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
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

    /* Safari and WebKit specific fixes */
    @supports (-webkit-touch-callout: none) {

        /* iOS Safari specific styles */
        video {
            -webkit-playsinline: true;
        }

        /* Fix for iOS Safari video player quirks */
        video {
            -webkit-playsinline: true;
            playsinline: true;
        }

        /* Safari slider improvements */
        .slider::-webkit-slider-track {
            -webkit-appearance: none;
            background: transparent;
        }

        /* Better touch targets for Safari */
        .video-control-btn {
            min-height: 48px;
            min-width: 48px;
        }
    }

    /* Prevent text selection and improve touch behavior */
    .video-control-btn,
    div[x-ref="progressBar"],
    .cursor-pointer {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-touch-callout: none;
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }

    /* Cross-browser button active states */
    .video-control-btn:active,
    button:active {
        transition: transform 0.1s ease;
    }

    /* Performance optimizations */
    .video-control-btn,
    div[x-ref="progressBar"],
    .cursor-pointer {
        will-change: transform;
    }

    /* Smooth progress bar updates */
    .progress-fill {
        will-change: width;
    }

    /* Optimize comment bubble positioning */
    .comment-bubble {
        will-change: transform, opacity;
    }

    /* Fix for Chrome Android address bar height changes */
    @supports (height: 100dvh) {
        .fixed.inset-0 {
            height: 100dvh;
        }
    }

    /* Firefox specific fixes */
    @-moz-document url-prefix() {
        .slider {
            background: transparent;
        }

        .slider::-moz-range-track {
            background: #d1d5db;
            border: none;
            height: 8px;
            border-radius: 4px;
        }
    }
</style>

<script>
    // Set comments data globally to avoid JSON parsing issues
    window.videoComments = @json($comments);
</script>
