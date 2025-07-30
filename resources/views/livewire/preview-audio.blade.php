<div class="mx-auto max-w-7xl lg:p-6">
    <div class="mb-6">
        <h1 class="mb-2 text-3xl font-bold text-zinc-900 dark:text-white">Audio Preview with Annotations</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Click on waveform regions to view comments or add new ones at any
            timestamp.</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-700 dark:bg-green-900 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 rounded-lg bg-red-100 p-4 text-red-700 dark:bg-red-900 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

    <div class="" x-data="{ comments: @js($comments) }"
        @audio-annotation:add-comment.window="console.log('Add audio comment:', $event.detail); @this.call('addComment', $event.detail.timestamp || $event.detail.start, $event.detail.precision, $event.detail.start, $event.detail.end)"
        @audio-annotation:view-comment="console.log('View audio comment:', $event.detail); @this.call('viewComment', $event.detail.commentId)"
        @audio-annotation:seek-comment="console.log('Seek audio comment:', $event.detail)"
        @audio-annotation:comment-added.window="console.log('Audio comment added:', $event.detail)"
        @audio-annotation:comment-clicked.window="console.log('Audio comment clicked:', $event.detail)"
        @comments-updated.window="comments = $event.detail.comments"
        @comment-loaded.window="console.log('Audio comment loaded:', $event.detail.comment)">

        <!-- Audio Annotation Component -->
        <div wire:ignore>
            <x-audio-annotation :audio-src="$audioUrl" :comments="$comments" :config="$config" />
        </div>

        <!-- Audio Controls Panel -->
        <div class="mt-8 rounded-lg bg-white p-6 shadow-lg dark:bg-zinc-800">
            <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">
                Audio Controls
            </h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Annotation Settings -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Annotation Settings</h3>

                    <div class="flex items-center">
                        <input type="checkbox" id="enable-annotations"
                            wire:model.live="config.features.enableAnnotations" wire:change="toggleAnnotations"
                            class="h-4 w-4 rounded border-zinc-300 bg-zinc-100 text-sky-600 focus:ring-2 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-700 dark:ring-offset-zinc-800 dark:focus:ring-sky-600">
                        <label for="enable-annotations"
                            class="ml-2 text-sm font-medium text-zinc-900 dark:text-zinc-300">
                            Enable Audio Annotations
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="enable-comments" wire:model.live="config.features.enableComments"
                            class="h-4 w-4 rounded border-zinc-300 bg-zinc-100 text-sky-600 focus:ring-2 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-700 dark:ring-offset-zinc-800 dark:focus:ring-sky-600">
                        <label for="enable-comments" class="ml-2 text-sm font-medium text-zinc-900 dark:text-zinc-300">
                            Enable Comments
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="enable-keyboard-shortcuts"
                            wire:model.live="config.features.enableKeyboardShortcuts"
                            class="h-4 w-4 rounded border-zinc-300 bg-zinc-100 text-sky-600 focus:ring-2 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-700 dark:ring-offset-zinc-800 dark:focus:ring-sky-600">
                        <label for="enable-keyboard-shortcuts"
                            class="ml-2 text-sm font-medium text-zinc-900 dark:text-zinc-300">
                            Enable Keyboard Shortcuts
                        </label>
                    </div>
                </div>

                <!-- Audio Source -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Audio Source</h3>

                    <div class="space-y-2">
                        <label for="audio-url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Audio URL
                        </label>
                        <div class="flex space-x-2">
                            <input type="url" id="audio-url" wire:model="audioUrl"
                                placeholder="https://example.com/audio.mp3"
                                class="flex-1 rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                            <button wire:click="updateAudioSource(audioUrl)"
                                class="rounded-md bg-sky-600 px-4 py-2 text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:opacity-50">
                                Update
                            </button>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Note: Audio file must be publicly accessible and CORS-enabled
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Summary -->
        <div class="mt-8 rounded-lg bg-white p-6 shadow-lg dark:bg-zinc-800">
            <h2 class="mb-4 text-xl font-semibold text-zinc-900 dark:text-white">
                Audio Comments (<span x-text="comments.length"></span>)
            </h2>

            <div class="space-y-4" x-show="comments.length > 0">
                <template x-for="comment in comments.sort((a, b) => a.timestamp - b.timestamp)"
                    :key="comment.commentId">
                    <div class="flex items-start space-x-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-700">
                        <img :src="comment.avatar || 'https://ui-avatars.com/api/?name=' + (comment.name || 'User ') + ' &
                            background = a855f7 & color = fff '"
                            :alt="comment.name || 'User'" class="h-10 w-10 rounded-full object-cover">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center space-x-2">
                                <span class="font-medium text-zinc-900 dark:text-white"
                                    x-text="comment.name || 'User'"></span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400"
                                    x-text="formatAudioTime(comment.timestamp) + ' - ' + formatAudioTime(comment.timestamp + (comment.duration || 2.0))"></span>
                            </div>
                            <p class="text-zinc-700 dark:text-zinc-300" x-text="comment.body || 'No comment text'"></p>
                        </div>
                        <button @click="$dispatch('audio-annotation:seek-comment', { commentId: comment.commentId })"
                            class="rounded bg-sky-600 px-3 py-1 text-xs text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            Seek
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="comments.length === 0" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                No audio comments yet. Add your first comment using the audio player controls.
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-6 rounded-lg bg-sky-50 p-6 dark:bg-sky-900/20">
            <h3 class="mb-3 text-lg font-medium text-sky-900 dark:text-sky-100">How to use Audio Annotations:</h3>
            <ul class="space-y-2 text-sky-800 dark:text-sky-200">
                <li class="flex items-start space-x-2">
                    <span class="text-sky-600 dark:text-sky-400">•</span>
                    <span>Use <kbd class="rounded bg-sky-200 px-2 py-1 dark:bg-sky-800">Space</kbd> to play/pause
                        audio</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-sky-600 dark:text-sky-400">•</span>
                    <span>Use <kbd class="rounded bg-sky-200 px-2 py-1 dark:bg-sky-800">←</kbd> and <kbd
                            class="rounded bg-sky-200 px-2 py-1 dark:bg-sky-800">→</kbd> to seek backward/forward</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-sky-600 dark:text-sky-400">•</span>
                    <span>Use <kbd class="rounded bg-sky-200 px-2 py-1 dark:bg-sky-800">Alt+C</kbd> (Windows) or <kbd
                            class="rounded bg-sky-200 px-2 py-1 dark:bg-sky-800">⌃+C</kbd> (Mac) to add comments</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-sky-600 dark:text-sky-400">•</span>
                    <span>Click on waveform regions to view and interact with comments</span>
                </li>
                <li class="flex items-start space-x-2">
                    <span class="text-sky-600 dark:text-sky-400">•</span>
                    <span>Adjust volume using the volume slider or mute button</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        function formatAudioTime(timestamp) {
            const minutes = Math.floor(timestamp / 60);
            const seconds = Math.floor(timestamp % 60);
            const milliseconds = Math.floor((timestamp % 1) * 100);
            return `${minutes}:${seconds.toString().padStart(2, '0')}.${milliseconds.toString().padStart(2, '0')}`;
        }

        // Make function globally available
        window.formatAudioTime = formatAudioTime;
    </script>
</div>
