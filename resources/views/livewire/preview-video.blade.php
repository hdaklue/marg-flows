<div class="mx-auto max-w-7xl lg:p-6">
    <div class="mb-6">
        <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">Video Preview with Annotations</h1>
        <p class="text-gray-600 dark:text-gray-400">Click on avatar markers to load comments or add new ones at any
            timestamp.</p>
    </div>

    @if (session()->has('message'))
        <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg dark:bg-green-900 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    <div class="" x-data="{
        comments: @js($comments),
        handleComment(action, data) {
            if (action === 'addComment') {
                @this.call('addComment', data.timestamp);
            } else if (action === 'loadComment') {
                @this.call('loadComment', data.commentId);
            }
        }
    }" @comments-updated.window="comments = $event.detail.comments"
        @comment-loaded.window="console.log('Comment loaded:', $event.detail.comment)">


        <!-- Video Annotation Component -->
        <x-video-annotation :video-src="$videoUrl" :quality-sources="$qualitySources" :comments="$comments" :config="$config"
            x-on:add-comment="handleComment('addComment', $event.detail)"
            x-on:load-comment="handleComment('loadComment', $event.detail)" />

        <!-- Comments Summary -->
        <div class="p-6 mt-8 bg-white rounded-lg shadow-lg dark:bg-gray-800">
            <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                Comments Summary (<span x-text="comments.length"></span>)
            </h2>

            <div class="space-y-4">
                <template x-for="comment in comments.sort((a, b) => a.timestamp - b.timestamp)"
                    :key="comment.commentId">
                    <div class="flex items-start p-4 space-x-4 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <img :src="comment.avatar" :alt="comment.name" class="object-cover w-10 h-10 rounded-full">
                        <div class="flex-1">
                            <div class="flex items-center mb-1 space-x-2">
                                <span class="font-medium text-gray-900 dark:text-white" x-text="comment.name"></span>
                                <span class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="formatTime(comment.timestamp)"></span>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300" x-text="comment.body"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Instructions -->
        <div class="p-6 mt-6 rounded-lg bg-blue-50 dark:bg-blue-900/20">
            <h3 class="mb-3 text-lg font-medium text-blue-900 dark:text-blue-100">How to use:</h3>
            <ul class="space-y-2 text-blue-800 dark:text-blue-200">
                <li class="flex items-start space-x-2">
                    <span class="text-blue-600 dark:text-blue-400">•</span>
                    <span>Click "Add Comment" button to add a comment at the current video time</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-blue-600 dark:text-blue-400">•</span>
                    <span>Hover over avatar markers on the timeline to see comment previews</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-blue-600 dark:text-blue-400">•</span>
                    <span>Click on avatar markers to load full comment details and jump to that timestamp</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-blue-600 dark:text-blue-400">•</span>
                    <span>Use the video quality selector in the bottom-right of the video player</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        function formatTime(timestamp) {
            const seconds = Math.floor(timestamp / 1000);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    </script>
</div>
