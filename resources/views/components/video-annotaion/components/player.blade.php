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
            {{-- <div x-show="frameNavigationDirection" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-75" class="absolute inset-0 flex items-center pointer-events-none"
            :class="frameNavigationDirection === 'forward' ? 'justify-end pr-8' : 'justify-start pl-8'">
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
            </div>
        </div> --}}

            <!-- Region Creation Mode Overlay -->
            <div x-show="touchInterface.enabled && touchInterface.mode === 'REGION_CREATE'" x-cloak
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center bg-black/20 backdrop-blur-[1px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                <!-- Region Info Card -->
                <div class="p-4 mb-4 text-center rounded-2xl bg-emerald-500/90 backdrop-blur-sm">
                    <div class="mb-2 text-white">
                        <div class="text-lg font-bold">Creating Region</div>
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
    <div x-cloak x-show="showContextMenu && config.annotations.enableContextMenu"
        @click.away.window="showContextMenu = false"
        class="fixed z-50 flex flex-col rounded-lg w-36 bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-200"
        :style="`left: ${contextMenuX}px; top: ${contextMenuY}px`">
        <div @click="addCommentFromContextMenu()" class="p-2 text-xs rounded-lg cursor-pointer dark:hover:bg-zinc-700">
            Add
            comment</div>
    </div>
</div>
