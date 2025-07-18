@props(['videoSrc' => '', 'comments' => '[]', 'onComment' => null, 'qualitySources' => null])

<div x-data="videoAnnotation()" x-init="// Initialize comments from window global or fallback to empty array
comments = window.videoComments || [];
init();

// Watch for changes that affect comment positioning
$watch('duration', () => updateProgressBarWidth());
$watch('progressBarWidth', () => $nextTick(() => {}));" class="relative w-full overflow-hidden bg-black rounded-lg"
    @destroy.window="destroy()">
    <!-- Video Player -->
    <div class="relative">
        <video x-ref="videoPlayer" :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
            class="w-full video-js vjs-default-skin" controls preload="auto" data-setup='{}'
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
    <div class="relative p-4 mt-4 rounded-lg bg-gray-50 dark:bg-gray-900">
        <!-- Timeline Header -->
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Timeline Comments</h3>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Hover over timeline to add comments
            </div>
        </div>

        <!-- Progress Bar with Comment Markers -->
        <div class="relative">
            <!-- Comment Bubbles Above Progress Bar -->
            <div class="relative h-12 mb-2">
                <template x-for="comment in comments" :key="comment.commentId">
                    <div class="absolute bottom-0 transform -translate-x-1/2 cursor-pointer"
                        :style="`left: ${getCommentPosition(comment.timestamp)}px`"
                        @click.stop="seekToComment(comment.timestamp)">
                        <!-- Comment Bubble -->
                        <div class="relative group" @click="loadComment(comment.commentId)">
                            <!-- Avatar Bubble -->
                            <div
                                class="w-8 h-8 overflow-hidden transition-transform duration-200 bg-white border-2 border-white rounded-full shadow-lg hover:scale-110 dark:border-gray-800 dark:bg-gray-800">
                                <img :src="comment.avatar" :alt="comment.name" class="object-cover w-full h-full">
                            </div>

                            <!-- Connecting Line -->
                            <div
                                class="absolute left-1/2 top-full h-2 w-0.5 -translate-x-1/2 transform bg-gray-400 dark:bg-gray-500">
                            </div>

                            <!-- Hover Tooltip -->
                            <div
                                class="absolute z-20 transition-opacity duration-200 transform -translate-x-1/2 opacity-0 pointer-events-none bottom-10 left-1/2 group-hover:opacity-100">
                                <div
                                    class="max-w-xs px-3 py-2 text-xs text-white bg-gray-900 border border-gray-700 rounded-lg shadow-xl whitespace-nowrap dark:bg-gray-800">
                                    <div class="font-medium" x-text="'@' + comment.name"></div>
                                    <div class="mt-1 text-gray-300 dark:text-gray-400"
                                        x-text="comment.body.length > 50 ? comment.body.substring(0, 50) + '...' : comment.body">
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                                        x-text="formatTime(comment.timestamp / 1000)"></div>
                                    <!-- Tooltip Arrow -->
                                    <div
                                        class="absolute w-0 h-0 transform -translate-x-1/2 border-t-4 border-l-4 border-r-4 left-1/2 top-full border-l-transparent border-r-transparent border-t-gray-900 dark:border-t-gray-800">
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
                class="relative w-full h-3 overflow-visible bg-gray-200 rounded-full cursor-pointer dark:bg-gray-700">
                <!-- Current Progress -->
                <div class="h-full transition-all duration-100 bg-blue-600 rounded-full"
                    :style="`width: ${duration > 0 ? (currentTime / duration) * 100 : 0}%`"></div>

                <!-- Hover Add Comment Button -->
                <div x-show="showHoverAdd" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                    class="absolute transform -translate-x-1/2 pointer-events-none -top-6" :style="`left: ${hoverX}px`">
                    <button @click.stop="addCommentAtPosition($event)"
                        class="flex items-center justify-center w-6 h-6 text-white transition-colors duration-200 bg-blue-600 rounded-full shadow-lg pointer-events-auto hover:bg-blue-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Time Display -->
        <div class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
            <span x-text="formatTime(currentTime)">0:00</span>
            <span x-text="formatTime(duration)">0:00</span>
        </div>

        <!-- Comments List (Optional - shows when comments exist) -->
        <div x-show="comments.length > 0" class="mt-4 space-y-2 overflow-y-auto max-h-32">
            <template x-for="comment in comments" :key="comment.commentId">
                <div class="flex items-start p-2 space-x-3 transition-colors duration-200 bg-white rounded-lg cursor-pointer hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                    @click="seekToComment(comment.timestamp)">
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

<script>
    // Set comments data globally to avoid JSON parsing issues
    window.videoComments = @json($comments);
</script>
