@props(['videoSrc' => '', 'comments' => '[]', 'regions' => '[]', 'qualitySources' => null, 'config' => null])

<!-- Load quality selector CSS and JS -->
{{-- <link href="https://unpkg.com/@silvermine/videojs-quality-selector/dist/css/quality-selector.css" rel="stylesheet">
<script src="https://unpkg.com/@silvermine/videojs-quality-selector/dist/js/silvermine-videojs-quality-selector.min.js"></script> --}}

<div x-data="videoAnnotation(@js($config ?? null), @js($comments ?? []), @js($regions ?? []))" class="relative h-full w-full overflow-hidden bg-zinc-950" tabindex="0"
    @destroy.window="destroy()" @keydown.arrow-left.window.prevent="isCreatingRegion ? shrinkRegionEnd() : stepBackward()"
    @keydown.arrow-right.window.prevent="isCreatingRegion ? expandRegionEnd() : stepForward()"
    @keydown.arrow-up.window.prevent="isCreatingRegion && expandRegionStart()"
    @keydown.arrow-down.window.prevent="isCreatingRegion && shrinkRegionStart()"
    @keydown.space.window.prevent="togglePlay()"
    @keydown.enter.window.prevent="isCreatingRegion && confirmRegionCreation()"
    @keydown.escape.window.prevent="isCreatingRegion && cancelRegionCreation()"
    @keydown.alt.c.window.prevent="!config.mode?.viewOnly && config.annotations?.enableVideoComments && addCommentAtCurrentFrame()"
    @keydown.ctrl.c.window.prevent="!config.mode?.viewOnly && config.annotations?.enableVideoComments && addCommentAtCurrentFrame()"
    tabindex="0">

    <!-- Safari Browser Notice -->
    <x-video-annotaion.components.safari-notice />

    <!-- Video Player (hidden for Safari) - Absolutely positioned video wrapper -->
    <div class="absolute left-0 right-0 top-0" x-ref="videoWrapper" x-show="!isSafari" x-cloak>
        <!-- Video Area - Auto-scales and centers, never exceeds container height -->
        <x-video-annotaion.components.player :qualitySources="$qualitySources" />
    </div>

    <!-- Flying Comments Wrapper -->
    <div class="absolute bottom-48 left-0 right-0 z-40 w-full flex flex-row items-center justify-center gap-2 py-2" 
         x-data="{ comments: [] }"
         @flying-comment-show.window="
             const comment = $event.detail;
             const existingIndex = comments.findIndex(c => c.commentId === comment.commentId);
             if (existingIndex >= 0) {
                 comments[existingIndex] = comment;
             } else {
                 comments.push(comment);
             }
             setTimeout(() => {
                 const index = comments.findIndex(c => c.commentId === comment.commentId);
                 if (index >= 0) comments.splice(index, 1);
             }, 8000);
         "
         @showComment.window="console.log('Show comment clicked:', $event.detail.id)">
        
        <!-- Flying Comments -->
        <template x-for="comment in comments" :key="comment.commentId">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-1"
                 @click="console.log('Flying comment clicked:', comment.commentId); $dispatch('showComment', { id: comment.commentId })"
                 class="flex items-center gap-2 bg-white/90 dark:bg-zinc-800/90 backdrop-blur-sm rounded-full px-3 py-1.5 text-sm shadow-lg border border-white/20 dark:border-zinc-700/50 cursor-pointer hover:bg-white/95 dark:hover:bg-zinc-800/95 transition-colors duration-200">
                
                <!-- Small Avatar -->
                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-sky-400 to-sky-600 flex items-center justify-center text-white text-xs font-bold"
                     x-text="comment.name ? comment.name.charAt(0).toUpperCase() : 'T'">
                </div>
                
                <!-- Comment Text -->
                <span class="text-zinc-700 dark:text-zinc-200 font-medium max-w-xs truncate"
                      x-text="comment.name + ' • ' + formatTime(comment.timestamp) + ' - ' + comment.body">
                </span>
            </div>
        </template>
    </div>

    <!-- Toolbar Area - Fixed position at bottom -->
    <div class="absolute bottom-0 left-0 right-0 z-50">
        <x-video-annotaion.components.tool-bar />
    </div>

    <!-- Unified Region Toolbar -->
    <x-video-annotaion.components.region-helper />
    <!-- Enhanced Mobile Frame Navigation Controls -->
    <div x-show="false" x-cloak
        class="absolute left-1/2 z-40 w-full max-w-lg -translate-x-1/2 rounded-xl bg-black/90 p-3 backdrop-blur-sm sm:hidden"
        :class="showProgressBar ? 'bottom-28' : 'bottom-4'" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-4">

        <!-- Frame Navigation Row -->
        <div class="mb-3 flex items-center justify-center space-x-3">
            <!-- Jump Back 10 Frames -->
            <button @click="jumpFrames(-10)"
                class="flex h-12 w-12 touch-manipulation items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95"
                style="touch-action: manipulation;">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>

            <!-- Step Backward -->
            <button @click="stepBackward()"
                class="flex h-12 w-12 touch-manipulation items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95"
                style="touch-action: manipulation;">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Enhanced Frame Info -->
            <div class="min-w-[120px] rounded-lg border border-white/20 bg-white/10 px-4 py-2.5 text-center text-white">
                <div class="font-mono text-sm font-semibold" x-text="'Frame ' + currentFrameNumber"></div>
                <div class="text-xs text-white/70" x-text="frameRate + ' fps'"></div>
            </div>

            <!-- Step Forward -->
            <button @click="stepForward()"
                class="flex h-12 w-12 touch-manipulation items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95"
                style="touch-action: manipulation;">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Jump Forward 10 Frames -->
            <button @click="jumpFrames(10)"
                class="flex h-12 w-12 touch-manipulation items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95"
                style="touch-action: manipulation;">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Quick Actions Row -->
        <div class="flex space-x-2">
            <!-- Region Creation Button -->
            <button @click="startSimpleRegionCreation()"
                class="flex-1 touch-manipulation rounded-lg bg-blue-600 px-3 py-2.5 text-white transition-all hover:bg-blue-700 active:scale-95"
                style="touch-action: manipulation;">
                <div class="flex items-center justify-center space-x-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-sm font-medium">Create Region</span>
                </div>
            </button>

            <!-- Add Comment Button -->
            <button @click="addCommentAtCurrentFrame()"
                class="flex-1 touch-manipulation rounded-lg bg-green-600 px-3 py-2.5 text-white transition-all hover:bg-green-700 active:scale-95"
                style="touch-action: manipulation;">
                <div class="flex items-center justify-center space-x-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-sm font-medium">Add Comment</span>
                </div>
            </button>

            <!-- Hide Toolbar Button -->
            <button @click="showFrameHelpers = false"
                class="touch-manipulation rounded-lg bg-zinc-600 px-3 py-2.5 text-white transition-all hover:bg-zinc-700 active:scale-95"
                style="touch-action: manipulation;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Region Creation Toolbar -->
    <div x-show="isTouchDevice() && touchInterface.mode === 'REGION_CREATE'" x-cloak
        class="absolute left-1/2 z-40 w-full max-w-sm -translate-x-1/2 rounded-lg bg-black/90 p-4 backdrop-blur-sm sm:hidden"
        :class="showProgressBar ? 'bottom-28' : 'bottom-4'" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-4">

        <!-- Region Creation Header -->
        <div class="mb-4 text-center">
            <div class="text-lg font-semibold text-white">
                📍 Region Creation
            </div>
            <div class="text-sm text-white/70"
                x-text="
                    regionCreationStart && regionCreationEnd ?
                    `Frames ${regionCreationStart.frame} - ${regionCreationEnd.frame}` :
                    regionCreationStart ?
                    `Start: Frame ${regionCreationStart.frame}` : 'Select start frame'
                ">
            </div>
        </div>

        <!-- Frame Navigation Controls -->
        <div class="mb-4 flex items-center justify-center space-x-2">
            <!-- Jump Back 10 -->
            <button @click="jumpFrames(-10)"
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>

            <!-- Step Back -->
            <button @click="stepBackward()"
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Frame Display -->
            <div class="rounded-lg bg-white/10 px-4 py-2 text-white">
                <div class="font-mono text-sm" x-text="'Frame ' + currentFrameNumber"></div>
            </div>

            <!-- Step Forward -->
            <button @click="stepForward()"
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Jump Forward 10 -->
            <button @click="jumpFrames(10)"
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 text-white transition-all hover:bg-white/30 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Region Selection Actions -->
        <div class="mb-4 flex space-x-3">
            <button x-show="!regionCreationStart" @click="setRegionStart()"
                class="flex-1 rounded-lg bg-blue-600 px-4 py-3 text-white transition-all hover:bg-blue-700 active:scale-95">
                🟦 SET START
            </button>

            <button x-show="regionCreationStart && !regionCreationEnd" @click="setRegionEnd()"
                class="flex-1 rounded-lg bg-green-600 px-4 py-3 text-white transition-all hover:bg-green-700 active:scale-95">
                🟩 SET END
            </button>

            <button x-show="regionCreationStart && regionCreationEnd"
                @click="confirmRegionCreation(); exitRegionCreationMode()"
                class="flex-1 rounded-lg bg-green-600 px-4 py-3 text-white transition-all hover:bg-green-700 active:scale-95">
                ✅ CREATE
            </button>
        </div>

        <!-- Cancel Button -->
        <button @click="exitRegionCreationMode()"
            class="w-full rounded-lg bg-red-600 px-4 py-2 text-white transition-all hover:bg-red-700 active:scale-95">
            ❌ CANCEL
        </button>
    </div>

    <!-- Context menu (only if annotations enabled) -->


    <!-- Mobile Modals - Moved to root level to escape container transforms -->

    <!-- Mobile Resolution Modal -->
    <div x-show="showResolutionMenu" x-cloak @click="showResolutionMenu = false; hideCommentTooltip()"
        class="fixed inset-0 z-[9999] flex items-end justify-center bg-black/50 backdrop-blur-sm sm:hidden">
        <div @click.stop class="w-full max-w-md rounded-t-2xl bg-white p-6 dark:bg-zinc-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full">

            <!-- Modal Header -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Select Resolution</h3>
                <button @click="showResolutionMenu = false"
                    class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Resolution Options -->
            <div class="space-y-2">
                <template x-for="(source, index) in qualitySources" :key="`mobile-resolution-${index}-${source.src}`">
                    <button @click="changeResolution(source)"
                        class="flex w-full items-center justify-between rounded-xl p-4 text-left transition-colors duration-200"
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
                            class="h-6 w-6 text-sky-600 dark:text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Mobile Settings Modal -->
    <div x-show="showSettingsMenu" x-cloak @click="showSettingsMenu = false; hideCommentTooltip()"
        class="fixed inset-0 z-[9999] flex items-end justify-center bg-black/50 backdrop-blur-sm sm:hidden">
        <div @click.stop class="w-full max-w-md rounded-t-2xl bg-white p-6 dark:bg-zinc-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full">

            <!-- Modal Header -->
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Video Settings</h3>
                <button @click="showSettingsMenu = false"
                    class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Settings Options -->
            <div class="space-y-4">
                <!-- Show Comments Toggle (only if annotations enabled) -->
                <button x-show="config.features.enableAnnotations" @click="toggleCommentsOnProgressBar()"
                    class="flex w-full items-center justify-between rounded-xl bg-zinc-50 p-4 text-left transition-colors duration-200 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                    <div class="flex items-center gap-4">
                        <div class="rounded-lg bg-sky-100 p-2 dark:bg-sky-900">
                            <svg class="h-5 w-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
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
                        <div class="h-6 w-11 rounded-full bg-zinc-300 transition-colors duration-200 dark:bg-zinc-600"
                            :class="{ 'bg-sky-600 dark:bg-sky-500': showCommentsOnProgressBar }">
                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                                :class="{ 'translate-x-5': showCommentsOnProgressBar }">
                            </div>
                        </div>
                    </div>
                </button>

                <!-- Progress Bar Visibility Toggle -->
                <button @click="toggleProgressBarMode()"
                    class="flex w-full items-center justify-between rounded-xl bg-zinc-50 p-4 text-left transition-colors duration-200 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                    <div class="flex items-center gap-4">
                        <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-medium text-zinc-900 dark:text-white">Progress Bar Visibility
                            </div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                <span x-show="progressBarMode === 'always-visible'">Always visible on video</span>
                                <span x-show="progressBarMode === 'auto-hide'">Auto-hide after 2 seconds</span>
                            </div>
                        </div>
                    </div>
                    <!-- Mode Display -->
                    <div class="rounded-full px-3 py-1 text-xs font-medium"
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
        class="fixed inset-0 z-[9999] flex items-end justify-center bg-black/50 backdrop-blur-sm sm:hidden">
        <div @click.stop class="w-full max-w-md rounded-t-2xl bg-white p-6 dark:bg-zinc-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full">

            <!-- Modal Header -->
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Volume Control</h3>
                <button @click="showVolumeModal = false"
                    class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Volume Controls -->
            <div class="space-y-6">
                <!-- Volume Level Display -->
                <div class="text-center">
                    <div class="text-2xl font-bold" :class="isMuted ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white'">
                        <span x-text="isMuted ? '0%' : Math.round(volume * 100) + '%'"></span>
                    </div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400" x-text="isMuted ? 'Muted' : 'Volume Level'"></div>
                </div>

                <!-- Mute Toggle -->
                <button @click="toggleMute()"
                    class="flex w-full items-center justify-between rounded-xl p-4 text-left transition-colors duration-200"
                    :class="isMuted ? 'bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40' : 'bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-700 dark:hover:bg-zinc-600'">
                    <div class="flex items-center gap-4">
                        <div class="rounded-lg p-3 transition-colors"
                             :class="isMuted ? 'bg-red-100 dark:bg-red-900/40' : 'bg-sky-100 dark:bg-sky-900'">
                            <!-- Volume Up Icon -->
                            <svg x-show="!isMuted && volume > 0.5" x-cloak
                                class="h-6 w-6 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Down Icon -->
                            <svg x-show="!isMuted && volume <= 0.5 && volume > 0" x-cloak
                                class="h-6 w-6 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072M5 15v-2a2 2 0 012-2h1l4-4v12l-4-4H7a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Volume Muted Icon -->
                            <svg x-show="isMuted || volume === 0" x-cloak
                                class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
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
                        <input type="range" min="0" max="1" step="0.01" :value="isMuted ? 0 : volume"
                            @input="setVolume(parseFloat($event.target.value))"
                            class="slider h-3 w-full cursor-pointer appearance-none rounded-lg bg-zinc-300 dark:bg-zinc-600"
                            :class="isMuted ? 'opacity-50' : ''">
                        <div class="mt-2 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Touch-Optimized Styles */
        .touch-manipulation {
            touch-action: manipulation;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* Ensure minimum touch target sizes */
        @media (pointer: coarse) {
            .video-control-btn {
                /* min-width: 44px !important;
                min-height: 44px !important; */
            }

            .progress-bar-container {
                min-height: 44px !important;
                padding: 21px 0 !important;
                margin: -21px 0 !important;
            }
        }

        /* Improved visual feedback for touch */
        .touch-manipulation:active {
            transform: scale(0.98);
            transition: transform 0.1s;
        }

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

        /* Enhanced Progress Bar Styles */
        .progress-fill {
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Touch-friendly button sizing for mobile */
        @media (max-width: 640px) {
            .video-control-btn {
                min-height: 44px;
                min-width: 44px;
            }
        }

        /* Enhanced Mobile touch feedback */
        @media (pointer: coarse) {
            .video-control-btn:active {
                transform: scale(0.95);
            }

            .cursor-pointer {
                -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
            }

            /* Enhanced progress bar touch area for mobile */
            div[x-ref="progressBar"] {
                padding: 8px 0;
                margin: -8px 0;
            }

            /* Enhanced comment bubbles - Modern Design System */
            .comment-bubble-main {
                min-height: 44px;
                min-width: 44px;
                transform-origin: center;
                will-change: transform, box-shadow;
            }
            
            /* Comment bubble interaction states */
            .comment-bubble-container:hover .comment-bubble-main {
                transform: scale(1.08);
                box-shadow: 0 12px 24px -8px rgba(14, 165, 233, 0.3);
            }
            
            .comment-bubble:active .comment-bubble-main {
                transform: scale(0.92);
                transition-duration: 0.1s;
            }
            
            /* Comment connection line enhancement */
            .comment-connection-line {
                will-change: height, background;
            }
            
            /* Comment tooltip improvements */
            .comment-tooltip {
                will-change: transform, opacity;
                filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.15));
            }
            
            .comment-tooltip-content {
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
            }
            
            /* Accessibility improvements */
            .comment-bubble:focus-visible {
                outline: 2px solid #0ea5e9;
                outline-offset: 2px;
            }
            
            /* Comment clustering and layering */
            .comment-bubble {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .comment-bubble:hover {
                z-index: 60 !important;
            }
            
            /* Visual depth for clustered comments */
            .comment-bubble[class*="z-30"] .comment-bubble-main {
                opacity: 0.85;
                transform: scale(0.9);
            }
            
            .comment-bubble[class*="z-35"] .comment-bubble-main {
                opacity: 0.9;
                transform: scale(0.95);
            }
            
            .comment-bubble:hover .comment-bubble-main {
                opacity: 1 !important;
                transform: scale(1.08) !important;
            }
            
            /* Connection line clustering adjustments */
            .comment-bubble[class*="z-30"] .comment-connection-line {
                opacity: 0.6;
                height: 3px;
            }
            
            .comment-bubble[class*="z-35"] .comment-connection-line {
                opacity: 0.75;
                height: 3.5px;
            }

            /* Touch device optimizations */
            @media (pointer: coarse) {
                .comment-bubble-main {
                    min-height: 48px;
                    min-width: 48px;
                }
                
                .comment-tooltip-content {
                    padding: 1rem;
                    font-size: 0.9rem;
                }
                
                /* Reduce clustering on touch devices for better accessibility */
                .comment-bubble[class*="z-30"] .comment-bubble-main,
                .comment-bubble[class*="z-35"] .comment-bubble-main {
                    opacity: 1;
                    transform: scale(1);
                }
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
