@props(['videoSrc' => '', 'comments' => '[]', 'onComment' => null, 'qualitySources' => null])

<div 
    x-data="videoAnnotation()" 
    x-init="
        // Initialize comments from window global or fallback to empty array
        comments = window.videoComments || [];
        init();
    "
    class="relative w-full bg-black rounded-lg overflow-hidden"
    @destroy.window="destroy()"
>
    <!-- Video Player -->
    <div class="relative">
        <video 
            x-ref="videoPlayer"
            :id="'video-player-' + Math.random().toString(36).substr(2, 9)"
            class="video-js vjs-default-skin w-full"
            controls 
            preload="auto"
            data-setup='{}'
            @if($qualitySources) data-quality-sources='@json($qualitySources)' @endif>
            @if($qualitySources)
                @foreach($qualitySources as $source)
                    <source src="{{ $source['src'] }}" type="{{ $source['type'] ?? 'video/mp4' }}" label="{{ $source['label'] ?? $source['quality'] ?? 'Auto' }}">
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

        <!-- Add Comment Button Overlay -->
        <div 
            x-show="showAddCommentButton"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute top-4 right-4 z-50"
        >
            <button 
                @click="addComment()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 transition-colors duration-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Add Comment</span>
            </button>
        </div>
    </div>

    <!-- Custom Timeline with Comment Markers -->
    <div class="relative mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
        <!-- Timeline Header -->
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Timeline Comments</h3>
            <button 
                @click="showAddCommentButton = !showAddCommentButton"
                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium transition-colors duration-200"
            >
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Comment
            </button>
        </div>

        <!-- Progress Bar with Comment Markers -->
        <div class="relative">
            <!-- Background Progress Bar -->
            <div 
                x-ref="progressBar"
                @click="onProgressBarClick($event)"
                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full cursor-pointer relative overflow-visible"
            >
                <!-- Current Progress -->
                <div 
                    class="h-full bg-blue-600 rounded-full transition-all duration-100"
                    :style="`width: ${duration > 0 ? (currentTime / duration) * 100 : 0}%`"
                ></div>

                <!-- Comment Markers -->
                <template x-for="comment in comments" :key="comment.commentId">
                    <div 
                        class="absolute top-0 transform -translate-x-1/2 cursor-pointer"
                        :style="`left: ${getCommentPosition(comment.timestamp)}px`"
                        @click.stop="seekToComment(comment.timestamp)"
                    >
                        <!-- Comment Avatar Marker -->
                        <div 
                            class="relative group"
                            @click="loadComment(comment.commentId)"
                        >
                            <!-- Avatar -->
                            <div class="w-6 h-6 -mt-2 rounded-full border-2 border-white shadow-lg overflow-hidden hover:scale-110 transition-transform duration-200">
                                <img 
                                    :src="comment.avatar" 
                                    :alt="comment.name"
                                    class="w-full h-full object-cover"
                                >
                            </div>

                            <!-- Hover Tooltip -->
                            <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10">
                                <div class="bg-gray-900 dark:bg-gray-800 text-white text-xs rounded-lg px-3 py-2 shadow-lg whitespace-nowrap max-w-xs">
                                    <div class="font-medium" x-text="'@' + comment.name"></div>
                                    <div class="text-gray-300 dark:text-gray-400 mt-1" x-text="comment.body.length > 50 ? comment.body.substring(0, 50) + '...' : comment.body"></div>
                                    <div class="text-gray-400 dark:text-gray-500 text-xs mt-1" x-text="formatTime(comment.timestamp / 1000)"></div>
                                    <!-- Tooltip Arrow -->
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900 dark:border-t-gray-800"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Time Display -->
        <div class="flex justify-between items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
            <span x-text="formatTime(currentTime)">0:00</span>
            <span x-text="formatTime(duration)">0:00</span>
        </div>

        <!-- Comments List (Optional - shows when comments exist) -->
        <div x-show="comments.length > 0" class="mt-4 space-y-2 max-h-32 overflow-y-auto">
            <template x-for="comment in comments" :key="comment.commentId">
                <div 
                    class="flex items-start space-x-3 p-2 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200"
                    @click="seekToComment(comment.timestamp)"
                >
                    <img 
                        :src="comment.avatar" 
                        :alt="comment.name"
                        class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                    >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="comment.name"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(comment.timestamp / 1000)"></span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1" x-text="comment.body"></p>
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

