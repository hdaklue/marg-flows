<!-- Comment Creation Modal -->
<div x-data="{ isDesktop: window.innerWidth >= 768 }" x-init="$watch('$wire.showCommentModal', value => window.commentModalOpen = value);
window.addEventListener('resize', () => { isDesktop = window.innerWidth >= 768 });" class="fixed inset-0 z-50 bg-zinc-800/90 backdrop-blur-sm"
    x-show="$wire.showCommentModal" x-trap="$wire.showCommentModal" x-cloak
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.showCommentModal && $wire.cancelComment()" role="dialog" aria-modal="true"
    aria-labelledby="comment-title">

    <!-- Single Responsive Modal -->
    <div :class="isDesktop ? 'flex items-center justify-center min-h-full p-4' : 'flex items-end min-h-full'">
        <div x-show="$wire.showCommentModal" @click.outside="$wire.cancelComment()"
            x-transition:enter="transition ease-out duration-200 will-change-transform"
            :x-transition:enter-start="isDesktop ? 'transform scale-95 opacity-0' : 'transform translate-y-full'"
            :x-transition:enter-end="isDesktop ? 'transform scale-100 opacity-100' : 'transform translate-y-0'"
            x-transition:leave="transition ease-in duration-150 will-change-transform"
            :x-transition:leave-start="isDesktop ? 'transform scale-100 opacity-100' : 'transform translate-y-0'"
            :x-transition:leave-end="isDesktop ? 'transform scale-95 opacity-0' : 'transform translate-y-full'"
            :class="isDesktop ? 'w-full max-w-lg p-6 bg-white shadow-2xl rounded-xl dark:bg-zinc-900' :
                'w-full px-4 pt-6 pb-8 bg-white shadow-2xl rounded-t-3xl dark:bg-zinc-900'"
            x-cloak>

            <!-- Mobile Handle (only on mobile) -->
            <div x-show="!isDesktop" class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-zinc-300 dark:bg-zinc-600"
                aria-hidden="true"></div>

            <!-- Header -->
            <div
                :class="isDesktop ? 'flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-700' :
                    'mb-4'">
                <h3 id="comment-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Add Comment
                </h3>
                <p x-show="!isDesktop" class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ !empty($pendingComment) && ($pendingComment['type'] ?? '') === 'area' ? 'Add feedback for the selected area' : 'Add feedback for this point' }}
                </p>
                <button x-show="isDesktop" @click="$wire.cancelComment()"
                    class="p-2 transition-colors rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                    aria-label="Close comment modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div :class="isDesktop ? 'py-4' : 'space-y-4'">
                <div :class="isDesktop ? 'space-y-4' : ''">
                    <div>
                        <label x-show="isDesktop" for="comment-text"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Comment
                        </label>
                        <label x-show="!isDesktop" for="comment-text" class="sr-only">Comment text</label>
                        <div :class="isDesktop ? 'mt-1' : ''">
                            <x-mentionable-text model="commentText" :mentionables="$mentionables ?? []" :hashables="$hashables ?? []"
                                hint="Use @ to mention people and # for hashtags" min-height="30px" id="comment-text"
                                ::class="isDesktop ? '' :
                                    'w-full resize-none rounded-xl border border-zinc-300 bg-white px-4 py-3 text-base transition-colors focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-sky-400'" />

                            <!-- Voice Note Component -->
                            <div class="w-full mt-3">
                                <livewire:reusable.voice-note-component />
                            </div>
                        </div>
                    </div>

                    @if (!empty($pendingComment))
                        <div
                            :class="isDesktop ? 'p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50' :
                                'p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50'">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Position:</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                {{ ucfirst($pendingComment['type'] ?? 'point') }} at
                                {{ number_format($pendingComment['x'] ?? 0, 1) }}%,
                                {{ number_format($pendingComment['y'] ?? 0, 1) }}%
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer -->
            <div
                :class="isDesktop ? 'flex gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700' : 'flex gap-3 pt-2'">
                <button @click="$wire.cancelComment()"
                    :class="isDesktop ?
                        'flex-1 px-4 py-2 font-medium transition-colors rounded-lg bg-zinc-100 text-zinc-700 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' :
                        'flex-1 py-3 font-medium text-center transition-colors rounded-xl bg-zinc-100 text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:bg-zinc-800 dark:text-zinc-300'">
                    Cancel
                </button>
                <button @click="$wire.saveNewComment()" :disabled="(!$wire.commentText || !$wire.commentText.trim())"
                    :class="isDesktop ?
                        'flex-1 px-4 py-2 font-medium text-white transition-colors rounded-lg bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-zinc-300 dark:disabled:bg-zinc-600' :
                        'flex-1 py-3 font-medium text-center text-white transition-colors shadow-lg rounded-xl bg-sky-500 shadow-sky-500/25 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50'">
                    Save Comment
                </button>
            </div>
        </div>
    </div>
</div>
