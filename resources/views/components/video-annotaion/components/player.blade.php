@props(['qualitySources'])
<div class="relative w-full h-full max-w-full max-h-full overflow-hidden">
    <!-- Video Container - scales to fit available space, maintains aspect ratio -->
    <div class="relative flex items-center justify-center w-full h-full bg-black">
        <div class="relative flex items-center justify-center w-full h-full cursor-pointer" x-ref="videoContainer"
            @click.prevent="!touchInterface.enabled && !isCreatingRegion && !pointerState.ghostClickPrevention && handleVideoClick(); hideCommentTooltip(); showHoverAdd = false"
            @contextmenu.prevent="handleVideoRightClick($event)" @touchstart="handleTouchStart($event)"
            @touchend="handleTouchEnd($event); hideCommentTooltip(); showHoverAdd = false"
            @mouseenter="!isTouchDevice() && handleVideoHover()" @mouseleave="!isTouchDevice() && handleVideoLeave()"
            style="touch-action: manipulation; -webkit-tap-highlight-color: transparent; -webkit-touch-callout: none; -webkit-user-select: none; user-select: none;">
            <video crossorigin="anonymous" x-ref="videoPlayer"
                :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
                class="w-full h-full vjs-fluid video-js vjs-default-skin" preload="auto" playsinline
                webkit-playsinline="true"
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
                    To view this video please enable JavaScript, and consider upgrading to a web browser
                    that
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
                x-transition:leave-end="opacity-0 scale-75" class="absolute inset-0 flex items-center pointer-events-none"
                :class="frameNavigationDirection === 'forward' ? 'justify-end pr-8' : frameNavigationDirection === 'seek' ? 'justify-center' : 'justify-start pl-8'">
                <div class="p-3 rounded-full bg-black/70 backdrop-blur-sm">
                    <!-- Forward Arrow -->
                    <svg x-show="frameNavigationDirection === 'forward'" x-cloak class="w-8 h-8 text-white" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <!-- Backward Arrow -->
                    <svg x-show="frameNavigationDirection === 'backward'" x-cloak class="w-8 h-8 text-white" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <!-- Seek Icon for Comment Navigation -->
                    <svg x-show="frameNavigationDirection === 'seek'" x-cloak class="w-8 h-8 text-white" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
            </div>

            <!-- Region Creation Mode Overlay -->
            <div x-show="touchInterface.enabled && touchInterface.mode === 'REGION_CREATE'" x-cloak
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center bg-black/20 backdrop-blur-[1px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                <!-- Region Info Card -->
                <div class="p-4 mb-4 text-center rounded-2xl bg-emerald-500/90 backdrop-blur-sm">
                    <div class="mb-2 text-white">
                        <div class="text-lg font-bold">Region Duration</div>
                        <div class="font-mono text-emerald-100"
                            x-text="regionCreationStart && regionCreationEnd ? formatTime(Math.abs(regionCreationEnd.time - regionCreationStart.time)) : '0:00'">
                            0:00
                        </div>
                    </div>
                    <div class="text-xs text-emerald-100 opacity-90">
                        <span x-text="regionCreationStart ? formatTime(regionCreationStart.time) : '0:00'">0:00</span>
                        →
                        <span x-text="regionCreationEnd ? formatTime(regionCreationEnd.time) : '0:00'">0:00</span>
                    </div>
                </div>

                <!-- Gesture Hints -->
                <div class="p-3 text-center rounded-xl bg-white/10 backdrop-blur-sm">
                    <div class="space-y-1 text-sm text-white">
                        <div class="flex items-center justify-center gap-2">
                            <span>←</span> <span class="text-xs">Shrink End</span> <span>→</span> <span
                                class="text-xs">Expand End</span>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <span>↑</span> <span class="text-xs">Expand Start</span> <span>↓</span> <span
                                class="text-xs">Shrink Start</span>
                        </div>
                        <div class="mt-2 text-xs opacity-75">
                            Tap to confirm • Long press to cancel
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Enhanced Context Menu -->
    <div x-cloak x-show="showContextMenu"
        @click.away="hideContextMenu()"
        class="fixed z-50 flex flex-col rounded-lg w-44 bg-white/95 dark:bg-zinc-800/95 backdrop-blur-sm border border-zinc-200 dark:border-zinc-700 shadow-lg"
        :style="`left: ${contextMenuX}px; top: ${contextMenuY}px`"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100" 
        x-transition:leave-end="opacity-0 scale-95">
        
        <!-- Context Menu Header -->
        <div class="px-3 py-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
            <span x-text="'At ' + (contextMenuTime ? formatTime(contextMenuTime) : '0:00')"></span>
        </div>
        
        <!-- Add Comment Option -->
        <div @click="handleContextMenuAction('add-comment')" 
             class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Add Comment
        </div>
        
        <!-- Create Region Option -->
        <div @click="handleContextMenuAction('create-region')" 
             class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
            </svg>
            Create Region
        </div>
        
        <!-- Divider -->
        <div class="border-t border-zinc-200 dark:border-zinc-700"></div>
        
        <!-- Seek to Time Option -->
        <div @click="seekTo(contextMenuTime); hideContextMenu()" 
             class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Seek Here
        </div>
    </div>
</div>
