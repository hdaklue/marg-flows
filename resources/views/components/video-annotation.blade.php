@props(['videoSrc' => '', 'comments' => '[]', 'onComment' => null, 'qualitySources' => null])

<div x-data="videoAnnotation()" x-init="// Initialize comments from window global or fallback to empty array
comments = window.videoComments || [];
init();

// Watch for changes that affect comment positioning
$watch('duration', () => updateProgressBarWidth());
$watch('progressBarWidth', () => $nextTick(() => {}));" class="relative w-full overflow-hidden rounded-lg bg-black"
    @destroy.window="destroy()">
    <!-- Video Player -->
    <div class="relative flex justify-center">
        <video x-ref="videoPlayer" :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
            class="video-js vjs-fluid vjs-default-skin h-auto w-full" controls preload="auto" data-setup='{}'
            @if ($qualitySources) data-quality-sources='@json($qualitySources)' @endif>
            @if ($qualitySources)
                @foreach ($qualitySources as $source)
                    <source src="{{ $source['src'] }}" type="{{ $source['type'] ?? 'video/mp4' }}"
                        label="{{ $source['label'] ?? ($source['quality'] ?? 'Auto') }}">
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

    </div>

    <!-- Custom Timeline with Comment Markers -->
    <div class="relative mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
        <!-- Timeline Header -->
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Timeline Comments</h3>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Hover over timeline to add comments
            </div>
        </div>

        <!-- Progress Bar with Comment Markers -->
        <div class="relative">
            <!-- Comment Bubbles Above Progress Bar -->
            <div class="relative mb-2 h-12">
                <template x-for="comment in comments" :key="comment.commentId">
                    <div class="absolute bottom-0 -translate-x-1/2 transform cursor-pointer"
                        :style="`left: ${getCommentPosition(comment.timestamp)}px`"
                        @click.stop="seekToComment(comment.timestamp)">
                        <!-- Comment Bubble -->
                        <div class="group relative" @click="loadComment(comment.commentId)">
                            <!-- Avatar Bubble -->
                            <div
                                class="h-8 w-8 overflow-hidden rounded-full border-2 border-white bg-white shadow-lg transition-transform duration-200 hover:scale-110 dark:border-gray-800 dark:bg-gray-800">
                                <img :src="comment.avatar" :alt="comment.name" class="h-full w-full object-cover">
                            </div>

                            <!-- Connecting Line -->
                            <div
                                class="absolute left-1/2 top-full h-2 w-0.5 -translate-x-1/2 transform bg-gray-400 dark:bg-gray-500">
                            </div>

                            <!-- Hover Tooltip -->
                            <div
                                class="pointer-events-none absolute bottom-10 left-1/2 z-20 -translate-x-1/2 transform opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <div
                                    class="max-w-xs whitespace-nowrap rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-xs text-white shadow-xl dark:bg-gray-800">
                                    <div class="font-medium" x-text="'@' + comment.name"></div>
                                    <div class="mt-1 text-gray-300 dark:text-gray-400"
                                        x-text="comment.body.length > 50 ? comment.body.substring(0, 50) + '...' : comment.body">
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                                        x-text="formatTime(comment.timestamp / 1000)"></div>
                                    <!-- Tooltip Arrow -->
                                    <div
                                        class="absolute left-1/2 top-full h-0 w-0 -translate-x-1/2 transform border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900 dark:border-t-gray-800">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Progress Bar with Hover Add Button -->
            <div x-ref="progressBar" @click="onProgressBarClick($event)" @mousemove="updateHoverPosition($event)"
                @mouseenter="showHoverAdd = true" @mouseleave="showHoverAdd = false"
                class="relative h-3 w-full cursor-pointer overflow-visible rounded-full bg-gray-200 dark:bg-gray-700">
                <!-- Current Progress -->
                <div class="h-full rounded-full bg-blue-600 transition-all duration-100"
                    :style="`width: ${duration > 0 ? (currentTime / duration) * 100 : 0}%`"></div>

                <!-- Hover Add Comment Button -->
                <div x-show="showHoverAdd" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                    class="pointer-events-none absolute -top-6 -translate-x-1/2 transform" :style="`left: ${hoverX}px`">
                    <button @click.stop="addCommentAtPosition($event)"
                        class="pointer-events-auto flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-colors duration-200 hover:bg-blue-700">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Time Display -->
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span x-text="formatTime(currentTime)">0:00</span>
            <span x-text="formatTime(duration)">0:00</span>
        </div>

        <!-- Comments List (Optional - shows when comments exist) -->
        <div x-show="comments.length > 0" class="mt-4 max-h-32 space-y-2 overflow-y-auto">
            <template x-for="comment in comments" :key="comment.commentId">
                <div class="flex cursor-pointer items-start space-x-3 rounded-lg bg-white p-2 transition-colors duration-200 hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                    @click="seekToComment(comment.timestamp)">
                    <img :src="comment.avatar" :alt="comment.name"
                        class="h-8 w-8 flex-shrink-0 rounded-full object-cover">
                    <div class="min-w-0 flex-1">
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

<script>
    // Set comments data globally to avoid JSON parsing issues
    window.videoComments = @json($comments);
</script>
