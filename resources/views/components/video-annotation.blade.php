@props(['videoSrc' => '', 'comments' => '[]', 'qualitySources' => null, 'config' => null])

<!-- Load quality selector CSS and JS -->
{{-- <link href="https://unpkg.com/@silvermine/videojs-quality-selector/dist/css/quality-selector.css" rel="stylesheet">
<script src="https://unpkg.com/@silvermine/videojs-quality-selector/dist/js/silvermine-videojs-quality-selector.min.js"></script> --}}

<div x-data="videoAnnotation(@js($config ?? null), @js($comments ?? []))" class="relative w-full overflow-visible bg-black rounded-lg focus:outline-none" tabindex="0"
    @destroy.window="destroy()" @contextmenu.prevent="handleVideoRightClick($event)"
    @keydown.arrow-left.window.prevent="stepBackward()" @keydown.arrow-right.window.prevent="stepForward()"
    @keydown.space.window.prevent="togglePlay()"
    @keydown.enter.window.prevent="isCreatingRegion && confirmRegionCreation()"
    @keydown.escape.window.prevent="isCreatingRegion && cancelRegionCreation()"
    @keydown.alt.c.window.prevent="config.annotations?.enableVideoComments && addCommentAtCurrentFrame()"
    @keydown.ctrl.c.window.prevent="config.annotations?.enableVideoComments && addCommentAtCurrentFrame()"
    tabindex="0">

    <!-- Safari Browser Notice -->
    <div x-show="isSafari" x-cloak
        class="flex items-center justify-center w-full p-6 border shadow-sm min-h-96 rounded-xl border-slate-200 bg-gradient-to-br from-slate-50 to-zinc-100 dark:border-zinc-700 dark:from-zinc-800 dark:to-zinc-900">
        <div class="max-w-md text-center">
            <div class="flex justify-center mb-6">
                <div class="p-3 rounded-full bg-sky-100 dark:bg-sky-900/30">
                    <svg class="w-8 h-8 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <h3 class="mb-3 text-2xl font-semibold text-zinc-900 dark:text-white">Better Experience Available</h3>
            <p class="mb-8 leading-relaxed text-zinc-600 dark:text-zinc-300">
                This video annotation feature is optimized for modern browsers. Switch to one of these for the best
                experience:
            </p>
            <div class="space-y-3">
                <a href="https://www.google.com/chrome/" target="_blank"
                    class="flex items-center gap-4 p-4 transition-all duration-200 bg-white border rounded-lg group border-zinc-200 hover:border-sky-300 hover:shadow-md dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-sky-500">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10" fill="#4285F4" />
                            <circle cx="12" cy="12" r="4" fill="white" />
                            <path d="M12 7a5 5 0 0 0-4.33 2.5l2.17 3.75A2 2 0 0 1 12 11h5a5 5 0 0 0-5-4z"
                                fill="#34A853" />
                            <path d="M12 17a5 5 0 0 0 4.33-2.5l-2.17-3.75A2 2 0 0 1 12 13H7a5 5 0 0 0 5 4z"
                                fill="#FBBC05" />
                            <path d="M7 12a5 5 0 0 0 2.5 4.33l3.75-2.17A2 2 0 0 1 11 12V7a5 5 0 0 0-4 5z"
                                fill="#EA4335" />
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <div
                            class="font-medium text-zinc-900 group-hover:text-sky-600 dark:text-white dark:group-hover:text-sky-400">
                            Chrome</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Fast & reliable</div>
                    </div>
                    <svg class="w-4 h-4 text-zinc-400 group-hover:text-sky-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
                <a href="https://www.mozilla.org/firefox/" target="_blank"
                    class="flex items-center gap-4 p-4 transition-all duration-200 bg-white border rounded-lg group border-zinc-200 hover:border-orange-300 hover:shadow-md dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-orange-500">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0z"
                                fill="#FF7139" />
                            <path
                                d="M8.9 6.6c.8-1.4 2.4-2.4 4.2-2.4 1.8 0 3.4 1 4.2 2.4.8 1.4.8 3.2 0 4.6-.8 1.4-2.4 2.4-4.2 2.4-1.8 0-3.4-1-4.2-2.4-.8-1.4-.8-3.2 0-4.6z"
                                fill="#FF4500" />
                            <path d="M12 7c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5z" fill="#FFB366" />
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <div
                            class="font-medium text-zinc-900 group-hover:text-orange-600 dark:text-white dark:group-hover:text-orange-400">
                            Firefox</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Privacy focused</div>
                    </div>
                    <svg class="w-4 h-4 text-zinc-400 group-hover:text-orange-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
                <a href="https://arc.net/" target="_blank"
                    class="flex items-center gap-4 p-4 transition-all duration-200 bg-white border rounded-lg group border-zinc-200 hover:border-purple-300 hover:shadow-md dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-purple-500">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                            <defs>
                                <linearGradient id="arcGradient" x1="0%" y1="0%" x2="100%"
                                    y2="100%">
                                    <stop offset="0%" style="stop-color:#6366F1" />
                                    <stop offset="50%" style="stop-color:#8B5CF6" />
                                    <stop offset="100%" style="stop-color:#EC4899" />
                                </linearGradient>
                            </defs>
                            <circle cx="12" cy="12" r="10" fill="url(#arcGradient)" />
                            <path d="M8 12c0-2.2 1.8-4 4-4s4 1.8 4 4-1.8 4-4 4-4-1.8-4-4z" fill="white"
                                opacity="0.9" />
                            <circle cx="12" cy="12" r="2" fill="url(#arcGradient)" />
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <div
                            class="font-medium text-zinc-900 group-hover:text-purple-600 dark:text-white dark:group-hover:text-purple-400">
                            Arc ✨</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Beautiful & modern</div>
                    </div>
                    <svg class="w-4 h-4 text-zinc-400 group-hover:text-purple-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Video Player (hidden for Safari) -->
    <div x-show="!isSafari" x-cloak>

        <!-- Mobile Frame Navigation Controls -->
        <div x-show="showFrameHelpers" x-cloak
            class="absolute z-50 flex items-center gap-2 p-2 -translate-x-1/2 rounded-lg left-1/2 top-4 bg-black/80 backdrop-blur-sm sm:hidden">
            <!-- Backward Frame Button -->
            <button @click="stepBackward()" @touchstart="$event.currentTarget.style.transform = 'scale(0.95)'"
                @touchend="$event.currentTarget.style.transform = 'scale(1)'"
                @touchcancel="$event.currentTarget.style.transform = 'scale(1)'"
                class="flex items-center justify-center w-10 h-10 text-white transition-all duration-200 rounded-full bg-white/20 hover:bg-white/30 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Frame Info -->
            <div class="px-3 py-1 text-xs text-white rounded-md bg-white/20 backdrop-blur-sm"
                :title="'Frame ' + currentFrameNumber + ' at ' + frameRate + 'fps'">
                Frame <span x-text="currentFrameNumber"></span>
            </div>

            <!-- Forward Frame Button -->
            <button @click="stepForward()" @touchstart="$event.currentTarget.style.transform = 'scale(0.95)'"
                @touchend="$event.currentTarget.style.transform = 'scale(1)'"
                @touchcancel="$event.currentTarget.style.transform = 'scale(1)'"
                class="flex items-center justify-center w-10 h-10 text-white transition-all duration-200 rounded-full bg-white/20 hover:bg-white/30 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Context menu (only if annotations enabled) -->
        <div x-cloak x-show="showContextMenu && config.annotations.enableContextMenu"
            @click.away.window="showContextMenu = false"
            class="fixed z-50 flex flex-col rounded-lg w-36 bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-200"
            :style="`left: ${contextMenuX}px; top: ${contextMenuY}px`">
            <div @click="addCommentFromContextMenu()"
                class="p-2 text-xs rounded-lg cursor-pointer dark:hover:bg-zinc-700">
                Add
                comment</div>
        </div>

        <!-- Video Player -->
        <div class="relative flex justify-center cursor-pointer"
            @click.prevent="handleVideoClick(); hideCommentTooltip(); showHoverAdd = false"
            @touchstart="handleTouchStart($event)"
            @touchend="handleTouchEnd($event); hideCommentTooltip(); showHoverAdd = false"
            @mouseenter="handleVideoHover()" @mouseleave="handleVideoLeave()">
            <video crossorigin="anonymous" x-ref="videoPlayer"
                :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
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
                    <svg x-show="!isPlaying" x-cloak class="w-12 h-12 text-white" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <!-- Pause Icon Overlay -->
                    <svg x-show="isPlaying" x-cloak class="w-12 h-12 text-white" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                </div>
            </div>

            <!-- Frame Navigation Feedback -->
            <div x-show="frameNavigationDirection" x-cloak x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-75"
                class="absolute inset-0 flex items-center pointer-events-none"
                :class="frameNavigationDirection === 'forward' ? 'justify-end pr-8' : 'justify-start pl-8'">
                <div class="p-3 rounded-full bg-black/70 backdrop-blur-sm">
                    <!-- Forward Arrow -->
                    <svg x-show="frameNavigationDirection === 'forward'" x-cloak class="w-8 h-8 text-white"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <!-- Backward Arrow -->
                    <svg x-show="frameNavigationDirection === 'backward'" x-cloak class="w-8 h-8 text-white"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </div>
            </div>

            <!-- Progress Bar Overlay with Comment Markers -->
            <div x-show="showProgressBar" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                class="absolute bottom-0 left-0 right-0 z-10 p-4" @click.away="showHoverAdd = false">
                <!-- Comment Bubbles Above Progress Bar (only if annotations enabled) -->
                <div class="relative mb-2"
                    :class="showCommentsOnProgressBar && config.features.enableAnnotations ? 'h-16' : 'h-0'">
                    <div x-show="showCommentsOnProgressBar && config.features.enableAnnotations" x-cloak>
                        <template x-for="(comment, index) in (comments || [])"
                            :key="`comment-${comment.commentId || index}`">
                            <div class="absolute bottom-0 transform -translate-x-1/2 cursor-pointer comment-bubble"
                                :style="`left: ${getCommentPosition(comment.timestamp)}px`"
                                @click.stop="$dispatch('video-annotation:seek-comment', { commentId: comment.commentId, timestamp: comment.timestamp })"
                                @touchstart.stop="handleCommentTouchStart($event, comment)"
                                @touchend.stop="handleCommentTouchEnd($event, comment)">
                                <!-- Comment Bubble -->
                                <div class="relative group" @click="handleCommentClick($event, comment)">
                                    <!-- Avatar Bubble -->
                                    <div
                                        class="w-6 h-6 overflow-hidden transition-transform duration-200 bg-white border-2 border-white rounded-full shadow-lg hover:scale-110 dark:border-zinc-800 dark:bg-zinc-800">
                                        <img :src="comment.avatar" :alt="comment.name"
                                            class="object-cover w-full h-full">
                                    </div>

                                    <!-- Connecting Line -->
                                    <div
                                        class="absolute left-1/2 top-full h-2 w-0.5 -translate-x-1/2 transform bg-white/80 dark:bg-zinc-200/80">
                                    </div>

                                    <!-- Tooltip (Desktop hover + Mobile click) -->
                                    <div class="absolute z-50 transition-opacity duration-200 pointer-events-none bottom-8"
                                        :class="[
                                            getTooltipPosition(comment.timestamp),
                                            isCommentTooltipVisible(comment) ? 'opacity-100' :
                                            'opacity-0 group-hover:opacity-100'
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
                            </div>
                        </template>
                    </div>
                </div>


                <!-- Progress Bar with Click/Double-Click and Draggable Seek Circle -->
                <!-- Progress Bar Container with Larger Hover Area -->
                <div @mouseenter="onProgressBarMouseEnter($event)" @mouseleave="onProgressBarMouseLeave()"
                    @mousemove="updateHoverPosition($event)" class="relative w-full py-3 -my-3">
                    <!-- Actual Progress Bar -->
                    <div x-ref="progressBar" @click.stop="handleProgressBarClick($event)"
                        @dblclick.stop="handleProgressBarDoubleClick($event)"
                        @touchstart.prevent="onProgressBarTouchStart($event)" {{-- @touchmove="onProgressBarTouchMove($event)"  --}}
                        @touchend.prevent="onProgressBarTouchEnd($event)"
                        class="relative w-full h-2 overflow-visible border rounded-full cursor-pointer border-sky-400/30 bg-zinc-500/50 backdrop-blur-sm sm:h-3">
                        <!-- Current Progress -->
                        <div class="h-full transition-all duration-100 rounded-l-full progress-fill bg-gradient-to-r from-sky-300 to-sky-600"
                            :style="`width: ${duration > 0 ? (currentTime / duration) * 100 : 0}%`"
                            :class="{ 'rounded-r-full': duration > 0 && (currentTime / duration) * 100 >= 100 }"></div>

                        <!-- Draggable Seek Circle -->
                        <div x-show="showSeekCircle" x-cloak
                            class="absolute transition-all duration-200 transform -translate-x-1/2 -translate-y-1/2 top-1/2"
                            :style="`left: ${seekCircleX}px`" :class="{ 'scale-125': isDragging }"
                            @mousedown.stop="startDrag($event)" @touchstart.stop="startCircleDrag($event)"
                            @touchmove.stop="handleTouchDragMove($event)" @touchend.stop="endTouchDrag($event)"
                            @click.stop @dblclick.stop>
                            <!-- Outer circle with glow -->
                            <div class="w-4 h-4 bg-white rounded-full shadow-lg ring-2 ring-sky-500/50 sm:h-5 sm:w-5"
                                :class="{ 'ring-4 ring-sky-500/70 shadow-sky-500/30': isDragging }">
                                <!-- Inner circle -->
                                <div class="w-2 h-2 translate-x-1 translate-y-1 rounded-full bg-sky-500 sm:h-3 sm:w-3 sm:translate-x-1 sm:translate-y-1"
                                    :class="{ 'bg-sky-600': isDragging }"></div>
                            </div>
                        </div>

                        <!-- Progress Bar Time Preview (follows pointer) - Above progress bar -->
                        <div x-show="showTooltip"
                            class="pointer-events-none absolute bottom-6 z-[9999] hidden -translate-x-1/2 transform sm:block"
                            :style="`left: ${hoverX}px;`" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90">
                            <div
                                class="px-2 py-1 font-mono text-xs text-white rounded shadow-lg bg-zinc-900 dark:bg-zinc-800">
                                <span x-text="formatTime(dragCurrentTime)">0:00</span>
                                <!-- Tooltip Arrow pointing down to progress bar -->
                                <div
                                    class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-zinc-900 dark:border-t-zinc-800">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Region Bar -->
                <div x-show="showRegionBar && config.features.enableAnnotations" x-cloak class="mt-2">
                    <!-- Region Creation Area -->
                    <div x-ref="regionBar" @mousedown.prevent="startRegionCreation($event)"
                        @mousemove="isCreatingRegion && updateRegionCreation($event)"
                        @mouseup="isCreatingRegion && finishRegionCreation($event)"
                        @mouseleave="isCreatingRegion && cancelRegionCreation()"
                        @touchstart.prevent="startRegionCreation($event)"
                        @touchmove.prevent="isCreatingRegion && updateRegionCreation($event)"
                        @touchend.prevent="isCreatingRegion && finishRegionCreation($event)"
                        class="relative w-full h-8 overflow-hidden transition-colors border rounded-md cursor-crosshair"
                        :class="isCreatingRegion || regions.length > 0 ?
                            'bg-zinc-200 border-zinc-400 dark:bg-zinc-800 dark:border-zinc-600' :
                            'bg-zinc-300 border-zinc-500 dark:bg-zinc-700 dark:border-zinc-500'">

                        <!-- Region Creation Preview -->
                        <div x-show="isCreatingRegion && regionCreationStart && regionCreationEnd" x-cloak
                            class="absolute top-0 h-full border-l-4 border-r-4 shadow-lg border-emerald-700 bg-emerald-400 dark:border-emerald-800 dark:bg-emerald-500"
                            :style="regionCreationStart && regionCreationEnd ?
                                `left: ${Math.min(regionCreationStart.x, regionCreationEnd.x)}px; width: ${Math.abs(regionCreationEnd.x - regionCreationStart.x)}px` :
                                ''">

                            <!-- Simple Creation Label -->
                            <div x-show="isCreatingRegion" x-cloak
                                class="absolute inset-0 flex items-center justify-center text-xs font-bold pointer-events-none text-emerald-900 drop-shadow-sm dark:text-emerald-100">
                                Creating Region
                            </div>

                            <!-- Real-time Tooltip Following End Edge -->
                            <div x-show="isCreatingRegion && regionCreationEnd" x-cloak
                                class="pointer-events-none absolute bottom-8 z-[9999] -translate-x-1/2 transform"
                                :style="`left: ${regionCreationEnd ? regionCreationEnd.x : 0}px;`"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-90"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-90">
                                <div
                                    class="px-3 py-2 font-mono text-xs text-white rounded-lg shadow-xl bg-emerald-800 dark:bg-emerald-900">
                                    <div class="font-bold text-emerald-100">End Time</div>
                                    <div class="text-emerald-200"
                                        x-text="regionCreationEnd ? formatTime(regionCreationEnd.time) : '0:00'">0:00
                                    </div>
                                    <div class="mt-1 text-[10px] text-emerald-300"
                                        x-text="regionCreationEnd ? `Frame ${regionCreationEnd.frame}` : 'Frame 0'">
                                        Frame 0</div>
                                    <!-- Tooltip Arrow pointing down -->
                                    <div
                                        class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-emerald-800 dark:border-t-emerald-900">
                                    </div>
                                </div>
                            </div>

                            <!-- Start Time Tooltip (Static at start edge) -->
                            <div x-show="isCreatingRegion && regionCreationStart" x-cloak
                                class="pointer-events-none absolute bottom-8 z-[9998] -translate-x-1/2 transform"
                                :style="`left: ${regionCreationStart ? regionCreationStart.x : 0}px;`">
                                <div
                                    class="px-2 py-1 font-mono text-xs text-white rounded shadow-lg bg-emerald-600 dark:bg-emerald-700">
                                    <div class="text-emerald-100"
                                        x-text="regionCreationStart ? formatTime(regionCreationStart.time) : '0:00'">
                                        0:00</div>
                                    <!-- Tooltip Arrow pointing down -->
                                    <div
                                        class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-emerald-600 dark:border-t-emerald-700">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Region Creation Controls -->
                        <div x-show="isCreatingRegion && !isMobile" x-cloak
                            class="absolute inset-x-0 flex items-center justify-center gap-3 p-3 border rounded-lg shadow-xl -bottom-16 border-zinc-600 bg-zinc-800 dark:bg-zinc-900">
                            <!-- Duration Display -->
                            <div class="font-mono text-xs text-zinc-300">
                                Duration: <span
                                    x-text="regionCreationStart && regionCreationEnd ? formatTime(Math.abs(regionCreationEnd.time - regionCreationStart.time)) : '0:00'"
                                    class="font-bold text-emerald-300">0:00</span>
                            </div>

                            <!-- Arrow Controls -->
                            <div class="flex items-center gap-1">
                                <button @click="reduceRegionEnd()"
                                    class="flex items-center justify-center w-8 h-8 text-orange-300 transition-colors rounded bg-orange-900/50 hover:bg-orange-800/70"
                                    title="Reduce End (Shift + ← Arrow)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <div class="px-2 text-xs text-zinc-400">End Edge</div>

                                <button @click="expandRegionEnd()"
                                    class="flex items-center justify-center w-8 h-8 transition-colors rounded bg-emerald-900/50 text-emerald-300 hover:bg-emerald-800/70"
                                    title="Expand End (Shift + → Arrow)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <button @click="cancelRegionCreation()"
                                    class="rounded bg-red-900/50 px-3 py-1.5 text-xs font-medium text-red-200 transition-colors hover:bg-red-800/70"
                                    title="Cancel (Esc)">
                                    Cancel
                                </button>

                                <button @click="confirmRegionCreation()"
                                    class="rounded bg-emerald-900/50 px-3 py-1.5 text-xs font-medium text-emerald-200 transition-colors hover:bg-emerald-800/70"
                                    title="Create Region (Enter)"
                                    :disabled="!regionCreationStart || !regionCreationEnd || Math.abs(regionCreationEnd.time -
                                        regionCreationStart.time) < frameDuration * 2"
                                    :class="{ 'opacity-50 cursor-not-allowed': !regionCreationStart || !regionCreationEnd ||
                                            Math.abs(regionCreationEnd.time - regionCreationStart.time) <
                                            frameDuration * 2 }">
                                    ✓ Create Region
                                </button>
                            </div>
                        </div>

                        <!-- Existing Regions -->
                        <template x-for="region in regions" :key="region.id">
                            <div x-show="isRegionVisible(region)" x-cloak
                                class="absolute top-0 h-full transition-colors border-l-4 border-r-4 shadow-lg cursor-pointer group border-sky-700 bg-sky-400 hover:bg-sky-500 hover:shadow-xl dark:border-sky-800 dark:bg-sky-500 dark:hover:bg-sky-400"
                                :style="`left: ${getRegionPosition(region).left}px; width: ${getRegionPosition(region).width}px`"
                                @click="jumpToRegionStart(region)" @mouseenter="showRegionTooltipFor(region.id)"
                                @mouseleave="hideRegionTooltips()">

                                <!-- Region Tooltip -->
                                <div x-show="showRegionTooltip === region.id" x-cloak
                                    class="absolute z-50 p-2 mb-2 text-xs text-white rounded-lg shadow-xl pointer-events-none bottom-full whitespace-nowrap bg-zinc-900 dark:bg-zinc-800"
                                    :style="`left: 50%; transform: translateX(-50%)`">
                                    <div class="font-medium" x-text="region.title"></div>
                                    <div class="text-zinc-300 dark:text-zinc-400">
                                        <span x-text="formatTime(region.startTime)"></span>
                                        -
                                        <span x-text="formatTime(region.endTime)"></span>
                                    </div>
                                    <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                        Frames <span x-text="region.startFrame"></span>-<span
                                            x-text="region.endFrame"></span>
                                    </div>
                                    <!-- Tooltip Arrow -->
                                    <div
                                        class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-zinc-900 dark:border-t-zinc-800">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Region Bar Instructions -->
                        <div x-show="regions.length === 0 && !isCreatingRegion" x-cloak
                            class="absolute inset-0 flex items-center justify-center text-xs pointer-events-none text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Drag to create region
                            </div>
                        </div>
                    </div>

                    <!-- Minimal Timeline Below Region Bar -->
                    <div x-show="duration > 0" x-cloak class="relative w-full h-4 mt-1">
                        <!-- Timeline Markers -->
                        <template
                            x-for="tick in Array.from({length: Math.floor(duration / (duration > 300 ? 30 : 10)) + 1}, (_, i) => i * (duration > 300 ? 30 : 10))"
                            :key="`timeline-${tick}`">
                            <div class="absolute top-0 pointer-events-none"
                                :style="`left: ${(tick / duration) * 100}%`" x-show="tick <= duration && tick > 0">
                                <!-- Solid tick mark -->
                                <div class="w-px h-3 bg-zinc-600 dark:bg-zinc-400"></div>
                                <!-- Clear time label -->
                                <div class="absolute top-3 -translate-x-1/2 transform whitespace-nowrap rounded bg-white px-1 font-mono text-[8px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                    x-text="formatTime(tick)">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>


        </div>

        <!-- Custom Video Controls -->
        <div class="relative p-2 mt-3 rounded-lg bg-zinc-100 dark:bg-zinc-800 sm:p-3">
            <div class="flex items-center justify-between gap-2 sm:gap-4">
                <!-- Left Controls Group -->
                <div class="flex items-center gap-1 sm:gap-3">
                    <!-- Play/Pause Button -->
                    <button @click="togglePlay()" @touchstart="$event.currentTarget.style.transform = 'scale(0.95)'"
                        @touchend="$event.currentTarget.style.transform = 'scale(1)'"
                        @touchcancel="$event.currentTarget.style.transform = 'scale(1)'"
                        class="flex items-center justify-center w-10 h-10 text-white transition-all duration-200 rounded-full shadow-md video-control-btn bg-sky-600 hover:scale-105 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 active:scale-95 dark:focus:ring-offset-zinc-800"
                        title="Play/Pause (Space Bar)">
                        <!-- Play Icon -->
                        <svg x-show="!isPlaying" x-cloak class="ml-0.5 h-5 w-5" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <!-- Pause Icon -->
                        <svg x-show="isPlaying" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                        </svg>
                    </button>

                    <!-- Frame Navigation Helper Arrows (only on desktop) -->
                    <div x-show="showFrameHelpers" x-cloak class="items-center hidden gap-1 sm:flex">
                        <!-- Backward Frame Button -->
                        <button @click="stepBackward()" @keydown.stop
                            class="flex items-center justify-center w-8 h-8 transition-all duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus:ring-zinc-600"
                            title="Previous Frame (← Arrow Key)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <!-- Forward Frame Button -->
                        <button @click="stepForward()" @keydown.stop
                            class="flex items-center justify-center w-8 h-8 transition-all duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus:ring-zinc-600"
                            title="Next Frame (→ Arrow Key)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
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
                    <div class="relative"
                        x-show="qualitySources.length > 1 && config.features.enableResolutionSelector" x-cloak>
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
                                <button x-show="config.features.enableAnnotations"
                                    @click="toggleCommentsOnProgressBar()"
                                    class="flex items-center justify-between w-full px-3 py-2 text-sm rounded-md text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
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
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
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
                    <button @click="toggleFrameHelpers()"
                        class="flex items-center justify-center w-8 h-8 transition-colors duration-200 rounded-lg video-control-btn text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                        :class="{ 'bg-sky-100 text-sky-600 dark:bg-sky-900 dark:text-sky-400': showFrameHelpers }"
                        title="Frame Navigation (← → Keys, Alt+C/Ctrl+C Add Comment)">
                        <!-- Frame Icon -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 4v16l4-4h6a2 2 0 002-2V6a2 2 0 00-2-2H7z" />
                            <circle cx="11" cy="11" r="1" fill="currentColor" />
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

        <!-- Mobile Resolution Modal -->
        <div x-show="showResolutionMenu" x-cloak @click="showResolutionMenu = false; hideCommentTooltip()"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:hidden"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.stop class="w-full max-w-md p-6 bg-white rounded-t-2xl dark:bg-zinc-800"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="transform translate-y-0"
                x-transition:leave-end="transform translate-y-full">

                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Select Resolution</h3>
                    <button @click="showResolutionMenu = false"
                        class="p-1 rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Resolution Options -->
                <div class="space-y-2">
                    <template x-for="(source, index) in qualitySources"
                        :key="`mobile-resolution-${index}-${source.src}`">
                        <button @click="changeResolution(source)"
                            class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl"
                            :class="currentResolutionSrc === source.src ?
                                'bg-sky-50 ring-2 ring-sky-500 dark:bg-sky-900/30 dark:ring-sky-400' :
                                'bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600'">
                            <div>
                                <div class="text-base font-medium text-zinc-900 dark:text-white"
                                    x-text="source.label || source.quality || 'Auto'"></div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Video Quality</div>
                            </div>
                            <!-- Check Icon for Selected -->
                            <svg x-show="currentResolutionSrc === source.src" x-cloak
                                class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="currentColor"
                                viewBox="0 0 24 24">
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
            <div @click.stop class="w-full max-w-md p-6 bg-white rounded-t-2xl dark:bg-zinc-800"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="transform translate-y-0"
                x-transition:leave-end="transform translate-y-full">

                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Video Settings</h3>
                    <button @click="showSettingsMenu = false"
                        class="p-1 rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
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
                        class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                        <div class="flex items-center gap-4">
                            <div class="p-2 rounded-lg bg-sky-100 dark:bg-sky-900">
                                <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-base font-medium text-zinc-900 dark:text-white">Progress Bar Comments
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Show comment markers on timeline
                                </div>
                            </div>
                        </div>
                        <!-- Toggle Switch -->
                        <div class="relative">
                            <div class="h-6 transition-colors duration-200 rounded-full w-11 bg-zinc-300 dark:bg-zinc-600"
                                :class="{ 'bg-sky-600 dark:bg-sky-500': showCommentsOnProgressBar }">
                                <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                                    :class="{ 'translate-x-5': showCommentsOnProgressBar }">
                                </div>
                            </div>
                        </div>
                    </button>

                    <!-- Progress Bar Visibility Toggle -->
                    <button @click="toggleProgressBarMode()"
                        class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-green-100 rounded-lg dark:bg-green-900">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-base font-medium text-zinc-900 dark:text-white">Progress Bar
                                    Visibility
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
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
            <div @click.stop class="w-full max-w-md p-6 bg-white rounded-t-2xl dark:bg-zinc-800"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="transform translate-y-0"
                x-transition:leave-end="transform translate-y-full">

                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Volume Control</h3>
                    <button @click="showVolumeModal = false"
                        class="p-1 rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
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
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                            <span x-text="Math.round(volume * 100) + '%'"></span>
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Volume Level</div>
                    </div>

                    <!-- Mute Toggle -->
                    <button @click="toggleMute()"
                        class="flex items-center justify-between w-full p-4 text-left transition-colors duration-200 rounded-xl bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-lg bg-sky-100 dark:bg-sky-900">
                                <!-- Volume Up Icon -->
                                <svg x-show="!isMuted && volume > 0.5" x-cloak
                                    class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                                </svg>
                                <!-- Volume Down Icon -->
                                <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak
                                    class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.536 8.464a5 5 0 010 7.072M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                                </svg>
                                <!-- Volume Muted Icon -->
                                <svg x-show="isMuted || volume === 0" x-cloak
                                    class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-base font-medium text-zinc-900 dark:text-white">
                                    <span x-show="!isMuted">Mute Audio</span>
                                    <span x-show="isMuted">Unmute Audio</span>
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Toggle sound on/off</div>
                            </div>
                        </div>
                    </button>

                    <!-- Volume Slider -->
                    <div class="space-y-3">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Adjust Volume</label>
                        <div class="relative">
                            <input type="range" min="0" max="1" step="0.01"
                                :value="volume" @input="setVolume($event.target.value)"
                                class="w-full h-3 rounded-lg appearance-none cursor-pointer slider bg-zinc-300 dark:bg-zinc-600">
                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>0%</span>
                                <span>50%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End Safari wrapper -->

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

    /* Modern browser optimizations */

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
    .fixed.inset-0 {
        height: 100vh;
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
