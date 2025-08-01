<div x-ref="tool-bar-container"
    class="flex-shrink-0 w-11/12 mx-auto mb-2 border-t rounded-md border-white/10 backdrop-blur-sm dark:bg-zinc-900/90">
    <!-- Progress Bar
    Layer -->
    <div x-show="showProgressBar" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2" class="relative p-4" @click.away="showHoverAdd = false">

        <!-- Comment Bubbles Above Progress Bar (only if annotations enabled) -->
        <div x-show="showCommentsOnProgressBar && config.annotations?.showCommentsOnProgressBar" x-cloak
            class="relative mb-3">
            <div class="relative">
                <template x-for="comment in comments" :key="comment.commentId">
                    <div x-show="config.annotations?.enableProgressBarComments && comment.timestamp"
                        class="absolute z-40 transform -translate-x-1/2"
                        :style="`left: ${(comment.timestamp / duration) * 100}%`"
                        @mouseenter="!isTouchDevice() && showCommentTooltip(comment.commentId)"
                        @mouseleave="!isTouchDevice() && hideCommentTooltip()"
                        @click="isTouchDevice() && toggleCommentTooltip(comment.commentId); $dispatch('video-annotation:seek-comment', { commentId: comment.commentId, timestamp: comment.timestamp })">

                        <!-- Avatar Bubble -->
                        <div
                            class="w-6 h-6 overflow-hidden transition-transform duration-200 bg-white border-2 border-white rounded-full shadow-lg hover:scale-110 dark:border-zinc-800 dark:bg-zinc-800">
                            <img :src="comment.avatar" :alt="comment.name" class="object-cover w-full h-full">
                        </div>

                        <!-- Connecting Line -->
                        <div
                            class="absolute left-1/2 top-full h-2 w-0.5 -translate-x-1/2 transform bg-white/80 dark:bg-zinc-200/80">
                        </div>

                        <!-- Tooltip (Desktop hover + Mobile click) -->
                        <div class="absolute z-50 transition-opacity duration-200 pointer-events-none bottom-8"
                            :class="[
                                getTooltipPosition(comment.timestamp),
                                isCommentTooltipVisible(comment) ? 'opacity-100' : 'opacity-0'
                            ]">
                            <div
                                class="max-w-xs px-3 py-2 text-xs text-white border rounded-lg shadow-xl whitespace-nowrap border-zinc-700 bg-zinc-900 dark:bg-zinc-800">
                                <div class="font-medium" x-text="'@' + comment.name"></div>
                                <div class="mt-1 text-zinc-300 dark:text-zinc-400"
                                    x-text="comment.body.length > 50 ? comment.body.substring(0, 50) + '...' : comment.body">
                                </div>
                                <div class="mt-1 text-xs text-zinc-400 dark:text-zinc-500"
                                    x-text="formatTime(comment.timestamp)"></div>
                                <!-- Tooltip Arrow -->
                                <div class="absolute w-0 h-0 transform border-t-4 border-l-4 border-r-4 top-full border-l-transparent border-r-transparent border-t-zinc-900 dark:border-t-zinc-800"
                                    :class="getArrowPosition(comment.timestamp)">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Progress Bar -->
        <div x-ref="progressBar" @pointerdown.stop="handleProgressBarPointer($event, 'click')" @click.stop
            @dblclick.stop="handleProgressBarPointer($event, 'doubleclick')"
            class="relative w-full h-2 overflow-visible transition-transform duration-150 border rounded-full cursor-pointer border-sky-400/30 bg-zinc-500/50 backdrop-blur-sm hover:scale-y-150 sm:h-3 sm:hover:scale-y-125">
            <!-- Current Progress -->
            <div class="h-full transition-all duration-100 rounded-l-full progress-fill bg-gradient-to-r from-sky-300 to-sky-600"
                :style="`width: ${frameAlignedProgressPercentage}%`"
                :class="{ 'rounded-r-full': frameAlignedProgressPercentage >= 100 }"></div>

            <!-- Hover Tooltip -->
            <div x-show="showTooltip"
                class="pointer-events-none absolute bottom-6 z-[9999] hidden -translate-x-1/2 transform sm:block"
                :style="`left: ${hoverX}px;`" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90">
                <div class="px-2 py-1 font-mono text-xs text-white rounded shadow-lg bg-zinc-900 dark:bg-zinc-800">
                    <span x-text="hoverTime"></span>
                </div>
                <!-- Tooltip Arrow pointing down to progress bar -->
                <div
                    class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-zinc-900 dark:border-t-zinc-800">
                </div>
            </div>
        </div>

        <!-- Region Bar -->
        <div x-show="showRegionBar && config.features.enableAnnotations && !isTouchDevice()" x-cloak class="mt-2">
            <!-- Region Creation Area (Hidden on Mobile) -->
            <div x-ref="regionBar" @mousedown.prevent.stop="startRegionCreation($event)"
                @mousemove.stop="isCreatingRegion && updateRegionCreation($event)"
                @mouseup.stop="isCreatingRegion && finishRegionCreation($event)"
                @touchstart.prevent.stop="startRegionCreation($event)"
                @touchmove.prevent.stop="isCreatingRegion && updateRegionCreation($event)"
                @touchend.prevent.stop="isCreatingRegion && finishRegionCreation($event)"
                class="relative w-full h-8 overflow-hidden transition-colors border rounded-md cursor-crosshair"
                :class="isCreatingRegion || regions.length > 0 ?
                    'bg-zinc-200 border-zinc-400 dark:bg-zinc-800 dark:border-zinc-600' :
                    'bg-zinc-100 border-zinc-300 dark:bg-zinc-700 dark:border-zinc-500'"
                :style="{ touchAction: 'none' }">

                <!-- Region Creation Feedback -->
                <div x-show="isCreatingRegion" x-cloak
                    class="absolute inset-0 z-30 rounded-md bg-gradient-to-r from-emerald-400/50 via-emerald-500/30 to-emerald-600/50"
                    :style="regionCreationStart && regionCreationEnd ?
                        `left: ${Math.min(regionCreationStart.x, regionCreationEnd.x)}px;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                width: ${Math.abs(regionCreationEnd.x - regionCreationStart.x)}px` :
                        ''">

                    <div
                        class="absolute inset-0 flex items-center justify-center text-xs font-bold pointer-events-none text-emerald-900 drop-shadow-sm dark:text-emerald-100">
                        Creating Region
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Video Controls -->
    <div class="relative p-3 border-t border-white/10">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <!-- Left Controls Group -->
            <div class="flex items-center gap-1 sm:gap-3">
                <!-- Play/Pause Button -->
                <button @pointerdown="handlePointerStart($event, 'button')"
                    @pointerup="handlePointerEnd($event, 'button'); togglePlay()"
                    class="flex items-center justify-center text-white transition-all duration-200 rounded-md shadow-md video-control-btn touch-manipulation bg-sky-600 hover:scale-105 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 active:scale-95 dark:focus:ring-offset-zinc-800"
                    :class="isTouchDevice() ? 'w-12 h-12' : 'w-8 h-8'" style="touch-action: manipulation;"
                    title="Play/Pause (Space Bar)">
                    <!-- Play Icon -->
                    <svg x-show="!isPlaying" x-cloak class="ml-0.5 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <!-- Pause Icon -->
                    <svg x-show="isPlaying" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                </button>

                <!-- Frame Navigation Helper Arrows (only on desktop) -->
                <div x-show="showFrameHelpers" x-cloak class="items-center hidden gap-1 opacity-25 sm:flex">
                    <!-- Backward Frame Button -->
                    <button @click="stepBackward()" @keydown.stop
                        class="flex items-center justify-center w-8 h-8 transition-all duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus:ring-zinc-600"
                        title="Previous Frame (← Arrow Key)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Forward Frame Button -->
                    <button @click="stepForward()" @keydown.stop
                        class="flex items-center justify-center w-8 h-8 transition-all duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus:ring-zinc-600"
                        title="Next Frame (→ Arrow Key)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Frame Info Display -->
                    <div class="hidden px-2 py-1 text-xs rounded-md bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 lg:block"
                        :title="'Frame ' + currentFrameNumber + ' at ' + frameRate + 'fps'">
                        F<span x-text="currentFrameNumber"></span>
                    </div>
                </div>

                <!-- Volume Controls -->
                <div class="flex items-center gap-1 sm:gap-2">
                    <!-- Mobile Volume Button -->
                    <button @click="showVolumeModal = !showVolumeModal"
                        class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white sm:hidden">
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
                            class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white">
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

                        <!-- Volume Percentage Display (always visible) -->
                        <div class="text-xs text-center min-w-8 text-zinc-600 dark:text-zinc-300">
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
                                class="w-20 h-2 rounded-lg appearance-none cursor-pointer slider bg-zinc-300 dark:bg-zinc-600">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Controls Group -->
            <div class="flex items-center gap-1 sm:gap-2">

                <!-- Resolution Selector -->
                <div class="relative" x-show="qualitySources.length > 1 && config.features.enableResolutionSelector"
                    x-cloak>
                    <button @click="showResolutionMenu = !showResolutionMenu"
                        class="flex items-center justify-center h-8 gap-1 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white sm:px-2"
                        :class="{ 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-white': showResolutionMenu }">
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
                        class="absolute right-0 z-50 hidden mb-2 bg-white rounded-lg shadow-lg bottom-full min-w-32 ring-1 ring-black/5 dark:bg-zinc-800 dark:ring-white/10 sm:block"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95">
                        <div class="p-1">
                            <div
                                class="px-3 py-2 text-xs font-medium border-b border-zinc-100 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Resolution
                            </div>

                            <template x-for="(source, index) in qualitySources"
                                :key="`resolution-${index}-${source.src}`">
                                <button @click="changeResolution(source)"
                                    class="flex items-center justify-between w-full px-3 py-2 text-sm transition-colors duration-200 rounded-md"
                                    :class="currentResolutionSrc === source.src ?
                                        'bg-sky-50 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300' :
                                        'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700'">
                                    <span x-text="source.label || source.quality || 'Auto'"></span>
                                    <!-- Check Icon for Selected -->
                                    <svg x-show="currentResolutionSrc === source.src" x-cloak
                                        class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="currentColor"
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
                        class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                        :class="{ 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-white': showSettingsMenu }">
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
                        class="absolute right-0 z-50 hidden w-56 mb-2 bg-white rounded-lg shadow-lg bottom-full ring-1 ring-black/5 dark:bg-zinc-800 dark:ring-white/10 sm:block"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95">
                        <div class="p-2">
                            <div
                                class="px-3 py-2 text-xs font-medium border-b border-zinc-100 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Video Settings
                            </div>

                            <!-- Show Comments Toggle (only if annotations enabled) -->
                            <button x-show="config.features.enableAnnotations" @click="toggleCommentsOnProgressBar()"
                                class="flex items-center justify-between w-full px-3 py-2 text-sm rounded-md text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
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
                                        :class="showCommentsOnProgressBar ? 'bg-sky-600 dark:bg-sky-500' :
                                            'bg-zinc-300 dark:bg-zinc-600'">
                                        <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                                            :class="showCommentsOnProgressBar ? 'translate-x-5' : ''">
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <!-- Progress Bar Visibility Toggle -->
                            <button @click="toggleProgressBarMode()"
                                class="flex items-center justify-between w-full px-3 py-2 text-sm rounded-md text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Progress Bar</span>
                                </div>
                                <!-- Mode Display -->
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    <span x-show="progressBarMode === 'always-visible'">Always</span>
                                    <span x-show="progressBarMode === 'auto-hide'">Auto-hide</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Frame Helper Toggle -->
                <button @click="startRegionCreationAtCurrentFrame()"
                    class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                    :class="{ 'bg-sky-100 text-sky-600 dark:bg-sky-900 dark:text-sky-400': showRegionToolbar }"
                    title="Create Region at Current Frame">
                    <!-- Frame Icon -->
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 4v16l4-4h6a2 2 0 002-2V6a2 2 0 00-2-2H7z" />
                    </svg>
                </button>


                <!-- Fullscreen Button -->
                <button x-show="config.features.enableFullscreenButton" @click="toggleFullscreen()"
                    class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white">
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




    <!-- Touch Context Menu (Hammer.js powered) -->
    <div x-show="touchInterface.enabled && touchInterface.contextMenuVisible" x-cloak @click="hideTouchContextMenu()"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.stop class="w-full max-w-sm p-6 mx-4 bg-white rounded-2xl dark:bg-zinc-800"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform scale-75 opacity-0"
            x-transition:enter-end="transform scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform scale-100 opacity-100"
            x-transition:leave-end="transform scale-75 opacity-0">

            <!-- Header -->
            <div class="mb-6 text-center">
                <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Quick Actions</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    At <span x-text="formatTime(currentTime)" class="font-mono"></span>
                </p>
            </div>

            <!-- Action Grid -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <!-- Add Comment -->
                <button @click="addCommentAtCurrentFrame(); hideTouchContextMenu()"
                    x-show="config.annotations?.enableVideoComments"
                    class="flex flex-col items-center p-4 transition-colors rounded-xl bg-sky-50 hover:bg-sky-100 dark:bg-sky-900/20 dark:hover:bg-sky-900/40">
                    <div class="flex items-center justify-center w-12 h-12 mb-2 rounded-full bg-sky-500">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Add Comment</span>
                </button>

                <!-- Create Region -->
                <button @click="startSimpleRegionCreation(); hideTouchContextMenu()"
                    x-show="config.features?.enableAnnotations"
                    class="flex flex-col items-center p-4 transition-colors rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40">
                    <div class="flex items-center justify-center w-12 h-12 mb-2 rounded-full bg-emerald-500">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Create
                        Region</span>
                </button>
            </div>

            <!-- Cancel Button -->
            <button @click="hideTouchContextMenu()"
                class="w-full py-3 font-medium transition-colors rounded-xl bg-zinc-100 text-zinc-900 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600">
                Cancel
            </button>
        </div>
    </div>

    <!-- Touch Region Creation Modal -->
    <div x-show="touchInterface.enabled && touchInterface.actionModalVisible && touchInterface.mode === 'REGION_CREATE'"
        x-cloak class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 bg-black/50">
                <h2 class="text-xl font-bold text-white">Create Region</h2>
                <button @click="exitRegionCreationMode()"
                    class="p-2 text-white rounded-full bg-white/20 hover:bg-white/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Video Preview (Small) -->
            <div class="flex items-center justify-center flex-1 p-4">
                <div class="w-full max-w-md">
                    <!-- Region Info Display -->
                    <div class="p-6 mb-6 text-center rounded-2xl bg-white/10 backdrop-blur-sm">
                        <div class="mb-4 text-white">
                            <div class="mb-1 text-sm opacity-80">Region Duration</div>
                            <div class="font-mono text-2xl font-bold"
                                x-text="regionCreationStart && regionCreationEnd ? formatTime(Math.abs(regionCreationEnd.time - regionCreationStart.time)) : '0:00'">
                                0:00
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm text-white">
                            <div>
                                <div class="opacity-80">Start</div>
                                <div class="font-mono font-medium"
                                    x-text="regionCreationStart ? formatTime(regionCreationStart.time) : '0:00'">
                                    0:00</div>
                            </div>
                            <div>
                                <div class="opacity-80">End</div>
                                <div class="font-mono font-medium"
                                    x-text="regionCreationEnd ? formatTime(regionCreationEnd.time) : '0:00'">
                                    0:00
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Large Timeline Scrubber -->
                    <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-sm">
                        <div class="mb-4 text-center text-white">
                            <div class="text-sm opacity-80">Adjust Region</div>
                        </div>

                        <!-- Large Touch-Friendly Timeline -->
                        <div class="relative h-16 mb-6 overflow-hidden rounded-full bg-black/30">
                            <!-- Progress Fill -->
                            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-sky-400 to-sky-600"
                                :style="`width: ${frameAlignedProgressPercentage}%`"></div>

                            <!-- Region Highlight -->
                            <div x-show="regionCreationStart && regionCreationEnd"
                                class="absolute top-0 h-full border-l-2 border-r-2 border-emerald-300 bg-emerald-400/80"
                                :style="regionCreationStart && regionCreationEnd ?
                                    `left: ${(Math.min(regionCreationStart.time, regionCreationEnd.time) / duration) * 100}%;width: ${Math.abs(regionCreationEnd.time - regionCreationStart.time) / duration * 100}%` :
                                    ''">
                            </div>
                        </div>

                        <!-- Adjustment Controls -->
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                @click="if(regionCreationStart) { regionCreationStart.time = Math.max(0, regionCreationStart.time - 0.5); regionCreationStart.frame = getFrameNumber(regionCreationStart.time); player && player.currentTime(regionCreationStart.time); }"
                                class="py-3 font-medium text-orange-200 rounded-xl bg-orange-500/20 hover:bg-orange-500/30">
                                ← Start -0.5s
                            </button>
                            <button
                                @click="if(regionCreationStart) { regionCreationStart.time = Math.min(duration-1, regionCreationStart.time + 0.5); regionCreationStart.frame = getFrameNumber(regionCreationStart.time); player && player.currentTime(regionCreationStart.time); }"
                                class="py-3 font-medium text-orange-200 rounded-xl bg-orange-500/20 hover:bg-orange-500/30">
                                Start +0.5s →
                            </button>
                            <button
                                @click="if(regionCreationEnd) { regionCreationEnd.time = Math.max(regionCreationStart?.time + 0.5 || 0.5, regionCreationEnd.time - 0.5); regionCreationEnd.frame = getFrameNumber(regionCreationEnd.time); player && player.currentTime(regionCreationEnd.time); }"
                                class="py-3 font-medium rounded-xl bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/30">
                                ← End -0.5s
                            </button>
                            <button
                                @click="if(regionCreationEnd) { regionCreationEnd.time = Math.min(duration, regionCreationEnd.time + 0.5); regionCreationEnd.frame = getFrameNumber(regionCreationEnd.time); player && player.currentTime(regionCreationEnd.time); }"
                                class="py-3 font-medium rounded-xl bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/30">
                                End +0.5s →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="p-4 bg-black/50">
                <div class="flex gap-3">
                    <button @click="exitRegionCreationMode()"
                        class="flex-1 py-4 font-medium text-red-200 rounded-xl bg-red-500/20 hover:bg-red-500/30">
                        Cancel
                    </button>
                    <button @click="confirmRegionCreation(); exitRegionCreationMode()"
                        class="flex-1 py-4 font-medium text-white rounded-xl bg-emerald-500 hover:bg-emerald-600"
                        :disabled="!regionCreationStart || !regionCreationEnd || Math.abs(regionCreationEnd.time -
                            regionCreationStart.time) < 0.5"
                        :class="{
                            'opacity-50 cursor-not-allowed': !regionCreationStart || !
                                regionCreationEnd ||
                                Math.abs(
                                    regionCreationEnd.time - regionCreationStart.time) < 0.5
                        }">
                        ✓ Create Region
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
