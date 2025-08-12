<div x-ref="tool-bar-container"
    class="flex-shrink-0 w-11/12 mx-auto mb-2 border-t rounded-md border-white/10 backdrop-blur-sm dark:bg-zinc-900/90">


    <!-- Progressive Context Display Area (Fixed Above Progress Bar) -->
    <div x-show="contextDisplay.visible" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1" class="px-4 py-2">

        <!-- Desktop Context Information Bar -->
        <div x-show="!isTouchDevice()" class="flex items-center justify-center max-w-md mx-auto">
            <div
                class="px-4 py-2 border shadow-xl rounded-xl border-zinc-700/50 bg-zinc-900/95 backdrop-blur-md dark:border-zinc-600/50 dark:bg-zinc-800/95">
                <div x-data="{ content: getContextDisplayContent() }"
                    x-effect="content = getContextDisplayContent(); if(content.secondary) console.log('Template content:', content);"
                    x-on:video-time-updated.window="content = getContextDisplayContent()"
                    class="flex items-center gap-3">

                    <!-- Primary Time Display -->
                    <div class="font-mono text-lg font-bold tracking-wide text-white" x-text="content.primary">
                    </div>

                    <!-- Secondary Context Information -->
                    <div x-show="content.secondary" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="flex items-center gap-2">

                        <!-- Separator -->
                        <div class="w-1 h-1 rounded-full bg-zinc-400"></div>

                        <!-- Comment Count or Preview -->
                        <template x-if="content.showCommentCount">
                            <div class="flex items-center gap-1 text-sm text-sky-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span x-text="content.secondary"></span>
                            </div>
                        </template>

                        <!-- Comment Preview -->
                        <template x-if="!content.showCommentCount && content.secondary">
                            <div class="max-w-xs text-sm truncate text-zinc-300" x-text="content.secondary"></div>
                        </template>

                        <!-- Author Avatar (if comment available) -->
                        <template x-if="content.comment">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 overflow-hidden rounded-full bg-zinc-700">
                                    <img :src="content.comment.avatar" :alt="content.comment.name"
                                        class="object-cover w-full h-full">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Context Information Bar (Simplified) -->
        <div x-show="isTouchDevice()" class="flex items-center justify-center max-w-sm mx-auto">
            <div
                class="rounded-lg border border-zinc-700/50 bg-zinc-900/95 px-3 py-1.5 shadow-lg backdrop-blur-md dark:border-zinc-600/50 dark:bg-zinc-800/95">
                <div x-data="{ content: getContextDisplayContent() }" x-effect="content = getContextDisplayContent()"
                    x-on:video-time-updated.window="content = getContextDisplayContent()"
                    class="flex items-center justify-center gap-2">

                    <!-- Time + Comment Count Combined -->
                    <div class="font-mono text-base font-bold tracking-wide text-white" x-text="content.primary">
                    </div>

                    <template x-if="content.showCommentCount">
                        <div class="flex items-center gap-1 text-xs text-sky-300">
                            <div class="w-1 h-1 rounded-full bg-zinc-400"></div>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span x-text="contextDisplay.nearbyComments.length"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Layer (Separate from Progress Bar for independent event handling) -->
    <div x-show="showProgressBar && showCommentsOnProgressBar && config.annotations?.showCommentsOnProgressBar && videoLoaded && duration > 0" x-cloak
        class="absolute inset-x-0 z-20 px-4 pointer-events-none bottom-24">
        <!-- Comment Timeline Display Container -->
        <div class="relative w-full h-16 pointer-events-none">
            <template x-for="(comment, index) in comments" :key="comment.commentId">
                <div x-show="config.annotations?.enableProgressBarComments && comment.timestamp"
                    class="absolute z-10 transform -translate-x-1/2"
                    :style="`left: ${(comment.timestamp / duration) * 100}%`">

                    <!-- Secondary Context Display (Author • Time - Comment text format) -->
                    <div x-show="shouldShowTimelineDisplay(comment)"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        class="absolute z-30 transform -translate-x-1/2 cursor-pointer pointer-events-auto bottom-6 left-1/2 min-w-48 max-w-64"
                        @click="handleTimelineCommentClick(comment)" <!-- Enhanced Comment Bubble with new format -->
                        <div
                            class="p-3 bg-white border rounded-lg shadow-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800">
                            <!-- Author • Time - Comment text format -->
                            <div class="flex items-start gap-2">
                                <!-- Avatar -->
                                <div
                                    class="flex-shrink-0 w-6 h-6 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <img x-show="comment.avatar" :src="comment.avatar" :alt="comment.name"
                                        class="object-cover w-full h-full">
                                    <div x-show="!comment.avatar"
                                        class="flex items-center justify-center w-full h-full text-xs font-bold text-white bg-gradient-to-br from-sky-400 to-sky-600"
                                        x-text="comment.name ? comment.name.charAt(0).toUpperCase() : '?'">
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <!-- Header: Author • Time -->
                                    <div class="flex items-center gap-1 mb-1 text-xs text-zinc-600 dark:text-zinc-400">
                                        <span class="font-semibold truncate" x-text="comment.name"></span>
                                        <span class="text-zinc-400 dark:text-zinc-500">•</span>
                                        <span class="font-mono" x-text="formatTime(comment.timestamp)"></span>
                                    </div>

                                    <!-- Comment Body -->
                                    <div class="text-sm line-clamp-2 text-zinc-800 dark:text-zinc-200"
                                        x-text="comment.body">
                                    </div>
                                </div>
                            </div>

                            <!-- Click hint -->
                            <div class="mt-2 text-xs italic text-center text-zinc-400 dark:text-zinc-500">
                                Click to seek and view full comment
                            </div>
                        </div>

                        <!-- Arrow pointing to dot -->
                        <div class="absolute transform -translate-x-1/2 left-1/2 top-full">
                            <div
                                class="w-0 h-0 border-t-4 border-l-4 border-r-4 border-l-transparent border-r-transparent border-t-white dark:border-t-zinc-800">
                            </div>
                        </div>
                    </div>

                    <!-- Comment Dot (Clean, no tooltip) -->
                    <div class="relative cursor-pointer pointer-events-auto"
                        @click="handleCommentClick($event, comment)"
                        @mouseenter="showCommentContext(comment.commentId)"
                        @mouseleave="hideCommentContext(comment.commentId)" role="button"
                        :aria-label="`Comment by ${comment.name} at ${formatTime(comment.timestamp)}`" tabindex="0"
                        @keydown.enter="handleCommentClick($event, comment)"
                        @keydown.space.prevent="handleCommentClick($event, comment)">

                        <!-- Touch Target for Mobile -->
                        <div class="absolute inset-0 -m-3 rounded-full"
                            :class="isTouchDevice() ? 'pointer-events-auto' : 'pointer-events-none'">
                        </div>

                        <!-- Clean Marker Circle -->
                        <div
                            class="w-4 h-4 transition-all duration-200 ease-out rounded-full shadow-sm bg-sky-400 hover:h-5 hover:w-5 hover:bg-sky-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-sky-400/50 focus:ring-offset-1 dark:bg-sky-300 dark:hover:bg-sky-400">
                        </div>

                        <!-- Proximity pulse (shows when mouse is near or timeline hits) -->
                        <div class="absolute inset-0 rounded-full animate-pulse bg-sky-400/40 dark:bg-sky-300/40"
                            x-show="contextDisplay.nearbyComments.some(nearby => nearby.comment.commentId === comment.commentId) ||
                                     Math.abs(comment.timestamp - currentTime) <= 2"
                            x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-150"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Progress Bar Layer -->
    <div x-show="showProgressBar" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2" class="relative p-4" @click.away="showHoverAdd = false">


        <!-- Enhanced Progress Bar with Progressive Context System -->
        <div x-ref="progressBar" @pointerdown.stop="handleProgressBarPointer($event, 'click')" @click.stop
            @dblclick.stop="handleProgressBarPointer($event, 'doubleclick')"
            @mouseenter="onProgressBarMouseEnterWithContext($event)"
            @mouseleave="onProgressBarMouseLeaveWithContext()" @mousemove="onProgressBarMouseMoveWithContext($event)"
            class="relative w-full cursor-pointer group" role="progressbar"
            :aria-valuenow="Math.round(frameAlignedProgressPercentage)" aria-valuemin="0" aria-valuemax="100"
            :aria-label="`Video progress: ${formatTime(currentTime)} of ${formatTime(duration)}`">

            <!-- Progress Track Background -->
            <div class="relative overflow-hidden transition-all duration-200 rounded-full bg-zinc-300/60 dark:bg-zinc-600/60"
                :class="isTouchDevice() ? 'h-3' : 'h-2 group-hover:h-3'">

                <!-- Progress Fill -->
                <div class="h-full transition-all duration-100 ease-out progress-fill bg-sky-500 dark:bg-sky-400"
                    :style="`width: ${frameAlignedProgressPercentage}%`"
                    :class="frameAlignedProgressPercentage >= 100 ? 'rounded-full' : 'rounded-l-full'">

                    <!-- Progress Fill Shine Effect -->
                    <div
                        class="absolute inset-0 rounded-inherit bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-60">
                    </div>
                </div>

                <!-- Buffered Progress (if available) -->
                <div x-show="bufferedPercentage > frameAlignedProgressPercentage"
                    class="absolute top-0 left-0 h-full transition-all duration-200 bg-zinc-400/40 dark:bg-zinc-500/40"
                    :style="`width: ${bufferedPercentage || 0}%`"
                    :class="bufferedPercentage >= 100 ? 'rounded-full' : 'rounded-l-full'">
                </div>

                <!-- Interactive Touch Target (invisible but larger for mobile) -->
                <div class="absolute inset-0 -m-4 sm:-m-2" style="touch-action: manipulation;">
                </div>
            </div>

            <!-- Current Time Indicator (Scrubber) -->
            <div class="absolute transition-opacity duration-200 -translate-x-1/2 -translate-y-1/2 opacity-0 top-1/2 group-hover:opacity-100"
                :class="isTouchDevice() ? 'opacity-100' : ''" :style="`left: ${frameAlignedProgressPercentage}%`">
                <div class="w-4 h-4 transition-transform duration-200 transform bg-white border-2 rounded-full shadow-lg border-sky-500 hover:scale-110 dark:border-sky-400 dark:bg-zinc-100"
                    :class="isDragging ? 'scale-125' : ''">
                </div>
            </div>

            <!-- Old hover tooltip removed - handled by Progressive Context System above -->
        </div>

        <!-- Region Bar -->
        <div x-show="showRegionBar && config.features.enableAnnotations && videoLoaded && duration > 0" x-cloak class="mt-2">
            <!-- Region Creation Area -->
            <div x-ref="regionBar"
                class="relative w-full h-8 overflow-visible transition-colors border rounded-md cursor-default"
                :class="isCreatingRegion || regions.length > 0 ?
                    'bg-zinc-200 border-zinc-400 dark:bg-zinc-800 dark:border-zinc-600' :
                    'bg-zinc-100 border-zinc-300 dark:bg-zinc-700 dark:border-zinc-500'"
                :style="{ touchAction: 'none' }">

                <!-- Region Creation Feedback -->
                <div x-show="isCreatingRegion" x-cloak
                    class="absolute inset-0 z-30 rounded-md group bg-emerald-500/40"
                    :style="regionCreationStart && regionCreationEnd ?
                        `left: ${Math.min(regionCreationStart.x, regionCreationEnd.x)}px;
                                                                                                         width: ${Math.abs(regionCreationEnd.x - regionCreationStart.x)}px` :
                        ''">

                    <!-- Drag Handle for Expanding Region -->
                    <div class="absolute top-0 right-0 flex items-center justify-center w-3 h-full transition-opacity cursor-e-resize bg-emerald-600 opacity-70 hover:opacity-100"
                        @mousedown.prevent.stop="startRegionDrag($event)"
                        @touchstart.prevent.stop="startRegionDrag($event)" title="Drag to expand region">
                        <!-- Drag Icon -->
                        <div class="flex flex-col gap-0.5">
                            <div class="h-1 w-0.5 bg-white"></div>
                            <div class="h-1 w-0.5 bg-white"></div>
                            <div class="h-1 w-0.5 bg-white"></div>
                        </div>
                    </div>
                </div>

                <!-- Existing Regions with Draggable Edges (hidden during region creation) -->
                <template x-for="region in getVisibleRegions()" :key="region.id">
                    <div x-show="!isCreatingRegion && videoLoaded && duration > 0" class="absolute h-full transition-colors border rounded-sm group"
                        :class="region.temporary ? 'bg-emerald-500/40 border-emerald-500/50 hover:bg-emerald-500/50' :
                            'bg-indigo-500 border-indigo-600 hover:bg-indigo-400'"
                        :style="`left: ${region.position.left}%; width: ${region.position.width}%; opacity: ${region.opacity || 0.6}`">

                        <!-- Region Content - Hidden for cleaner appearance -->
                        <!-- <div class="absolute inset-0 flex items-center justify-center text-xs font-medium pointer-events-none"
                             :class="region.temporary ? 'text-emerald-800 dark:text-emerald-200' :
                                 'text-indigo-100 dark:text-indigo-100'">
                            <span x-text="region.title || 'Region'"></span>
                        </div> -->

                        <!-- View Icon beneath region at left edge -->
                        <div class="absolute left-0 z-40 -bottom-6">
                            <button @click="showComment(region.id)"
                                class="flex items-center justify-center w-5 h-5 text-white transition-all bg-indigo-600 rounded-full shadow-lg opacity-80 hover:bg-indigo-700 hover:opacity-100"
                                title="View region details">
                                <!-- Eye/View Icon -->
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Custom Video Controls -->
    <div class="relative p-3 border-t border-white/10">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <!-- Left Controls Group -->
            <div class="flex items-center gap-1 sm:gap-3">
                <!-- Enhanced Play/Pause Button -->
                <button @pointerdown="handlePointerStart($event, 'button')"
                    @pointerup="handlePointerEnd($event, 'button'); togglePlay()"
                    class="flex items-center justify-center text-white transition-all duration-200 shadow-lg video-control-btn group touch-manipulation rounded-xl bg-sky-500 hover:bg-sky-600 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 active:scale-95 dark:focus:ring-offset-zinc-900"
                    :class="isTouchDevice() ? 'w-12 h-12' : 'w-10 h-10'" style="touch-action: manipulation;"
                    :title="isPlaying ? 'Pause (Space Bar)' : 'Play (Space Bar)'"
                    :aria-label="isPlaying ? 'Pause video' : 'Play video'">
                    <!-- Enhanced Play Icon -->
                    <svg x-show="!isPlaying" x-cloak
                        class="ml-0.5 h-5 w-5 transition-transform duration-200 group-hover:scale-110"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <!-- Enhanced Pause Icon -->
                    <svg x-show="isPlaying" x-cloak
                        class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                </button>

                <!-- Frame Navigation Helper Arrows (only on desktop) -->
                <div x-show="showFrameHelpers" x-cloak class="items-center hidden gap-1 opacity-25 sm:flex">
                    <!-- Enhanced Backward Frame Button -->
                    <button @click="eventHandler?.stepBackward()" @keydown.stop
                        class="flex items-center justify-center w-8 h-8 transition-all duration-200 video-control-btn rounded-xl text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-200 dark:focus:ring-zinc-600"
                        title="Previous Frame (← Arrow Key)" aria-label="Previous frame">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Enhanced Forward Frame Button -->
                    <button @click="eventHandler?.stepForward()" @keydown.stop
                        class="flex items-center justify-center w-8 h-8 transition-all duration-200 video-control-btn rounded-xl text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-200 dark:focus:ring-zinc-600"
                        title="Next Frame (→ Arrow Key)" aria-label="Next frame">
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
                    <!-- Enhanced Mobile Volume Button -->
                    <button @click="showVolumeModal = !showVolumeModal"
                        class="flex items-center justify-center transition-all duration-200 video-control-btn h-9 w-9 rounded-xl hover:shadow-md sm:hidden"
                        :class="isMuted ?
                            'text-red-500 bg-red-50 hover:bg-red-100 hover:text-red-600 dark:text-red-400 dark:bg-red-900/20 dark:hover:bg-red-900/40 dark:hover:text-red-300' :
                            'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white'"
                        :aria-label="isMuted ? 'Unmute video' : 'Adjust volume'"
                        :title="isMuted ? 'Muted' : Math.round(volume * 100) + '% volume'">
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
                        <!-- Enhanced Mute/Unmute Button -->
                        <button @click="toggleMute()"
                            class="flex items-center justify-center transition-all duration-200 video-control-btn h-9 w-9 rounded-xl hover:shadow-md"
                            :class="isMuted ?
                                'text-red-500 bg-red-50 hover:bg-red-100 hover:text-red-600 dark:text-red-400 dark:bg-red-900/20 dark:hover:bg-red-900/40 dark:hover:text-red-300' :
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white'"
                            :aria-label="isMuted ? 'Unmute video' : 'Mute video'"
                            :title="isMuted ? 'Unmute' : 'Mute'">
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

                        <!-- Enhanced Volume Percentage Display -->
                        <div class="px-2 py-1 text-xs font-medium text-center rounded-lg min-w-12 bg-zinc-100/50 text-zinc-700 backdrop-blur-sm dark:bg-zinc-700/50 dark:text-zinc-300"
                            :class="isMuted ? 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20' : ''">
                            <span x-text="isMuted ? '0%' : Math.round(volume * 100) + '%'"></span>
                        </div>

                        <!-- Volume Slider (appears on hover) -->
                        <div x-show="showVolumeSlider" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95">
                            <input type="range" min="0" max="1" step="0.1"
                                :value="isMuted ? 0 : volume" @input="setVolume(parseFloat($event.target.value))"
                                class="w-20 h-2 rounded-lg appearance-none cursor-pointer slider bg-zinc-300 dark:bg-zinc-600"
                                :class="isMuted ? 'opacity-50' : ''">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Controls Group -->
            <div class="flex items-center gap-1 sm:gap-2">

                <!-- Resolution Selector -->
                <div class="relative" x-show="qualitySources.length > 0 && config.features.enableResolutionSelector"
                    x-cloak>
                    <button @click="showResolutionMenu = !showResolutionMenu"
                        class="flex items-center justify-center gap-1 transition-all duration-200 video-control-btn h-9 rounded-xl text-zinc-600 hover:bg-zinc-100 hover:text-zinc-800 hover:shadow-md dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white sm:px-3"
                        :class="{ 'bg-zinc-100 text-zinc-800 shadow-md dark:bg-zinc-700 dark:text-white': showResolutionMenu }"
                        :aria-label="'Video quality: ' + (qualitySources.find(s => s.src === currentResolutionSrc)?.label || qualitySources.find(s => s.src === currentResolutionSrc)?.quality || currentResolution?.label || currentResolution?.quality || '1080p')"
                        title="Change video quality">
                        <!-- HD/Quality Icon -->
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="2" y="6" width="20" height="12" rx="2" fill="none"
                                stroke="currentColor" stroke-width="2" />
                            <text x="12" y="14" text-anchor="middle"
                                class="fill-current text-[8px] font-bold">HD</text>
                        </svg>
                        <!-- Text label - hidden on mobile -->
                        <span class="hidden text-xs font-medium sm:block"
                            x-text="qualitySources.find(s => s.src === currentResolutionSrc)?.label || qualitySources.find(s => s.src === currentResolutionSrc)?.quality || currentResolution?.label || currentResolution?.quality || '1080p'"
                            @resolution-changed.window="$nextTick(() => $el.textContent = qualitySources.find(s => s.src === currentResolutionSrc)?.label || qualitySources.find(s => s.src === currentResolutionSrc)?.quality || currentResolution?.label || currentResolution?.quality || '1080p')"></span>
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
                                <button @click="changeResolution(source); showResolutionMenu = false"
                                    class="flex items-center justify-between w-full px-3 py-2 text-sm transition-colors duration-200 rounded-md"
                                    :class="currentResolutionSrc === source.src ?
                                        'bg-sky-50 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300' :
                                        'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700'">
                                    <span x-text="source.label || source.quality"></span>
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
                        class="flex items-center justify-center transition-all duration-200 video-control-btn h-9 w-9 rounded-xl text-zinc-600 hover:bg-zinc-100 hover:text-zinc-800 hover:shadow-md dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                        :class="{ 'bg-zinc-100 text-zinc-800 shadow-md dark:bg-zinc-700 dark:text-white': showSettingsMenu }"
                        aria-label="Video settings" title="Video settings">
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

                <!-- Enhanced Region Creation Toggle -->
                <button @click="!config.mode?.viewOnly && startRegionCreationAtCurrentFrame()"
                    x-show="!config.mode?.viewOnly"
                    class="flex items-center justify-center transition-all duration-200 video-control-btn h-9 w-9 rounded-xl text-zinc-600 hover:bg-zinc-100 hover:text-zinc-800 hover:shadow-md dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                    :class="{ 'bg-sky-100 text-sky-600 shadow-md dark:bg-sky-900/50 dark:text-sky-400': showRegionToolbar }"
                    aria-label="Create region at current frame" title="Create Region at Current Frame">
                    <!-- Bracket Symbol [] -->
                    <span class="font-mono text-sm font-bold">[ ]</span>
                </button>


                <!-- Enhanced Fullscreen Button -->
                <button x-show="config.features.enableFullscreenButton" @click="toggleFullscreen()"
                    class="flex items-center justify-center transition-all duration-200 video-control-btn h-9 w-9 rounded-xl text-zinc-600 hover:bg-zinc-100 hover:text-zinc-800 hover:shadow-md dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                    :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
                    :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'">
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
                    x-show="config.annotations?.enableVideoComments && !config.mode?.viewOnly"
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
                    x-show="config.features?.enableAnnotations && !config.mode?.viewOnly"
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
                            <div class="absolute inset-0 rounded-full bg-sky-500"
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
