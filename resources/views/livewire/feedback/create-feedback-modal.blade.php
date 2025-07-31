<!-- Comment Creation Modal -->
@php
    $urgencyArray = App\Enums\Feedback\FeedbackUrgency::simpleArray($urgency);
@endphp
<div x-data="{
    isDesktop: window.innerWidth >= 768,
    showCancelConfirm: false,
    isProcessingCancel: false,
    cancelComment() {
        // Check if there are actually unsaved changes
        const hasText = $wire.commentText && $wire.commentText.trim().length > 0;
        const hasVoiceNotes = $wire.hasVoiceNotes;
        const hasUnuploadedVoiceNotes = $wire.hasUnuploadedVoiceNotes;

        if (hasText || hasVoiceNotes || hasUnuploadedVoiceNotes) {
            this.showCancelConfirm = true;
        } else {
            $wire.set('showCommentModal', false);
            this.$dispatch('voice-note:canceled');
        }
    },
    confirmCancelling() {
        $wire.handleConfirmCancel();
        this.showCancelConfirm = false;

    },
    destroy() {
        // Detach the handler, avoiding memory and side-effect leakage
        console.log('sss')
    },
}" x-init="window.addEventListener('resize', () => { isDesktop = window.innerWidth >= 768 });" class="fixed inset-0 z-50 bg-zinc-800/90 backdrop-blur-sm"
    @mentionable:text="this.commentText = $event.detail.state;"
    @feedback-modal:comment-created="$dispatch('wv-manager:player:destroy'); $dispatch('wv-manager:recorder:destroy')"
    @feedback-modal:show-cancel-confirmation.stop="showCancelConfirm = true" x-show="$wire.showCommentModal"
    x-trap.noscroll.inert="$wire.showCommentModal" x-cloak x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" role="dialog" aria-modal="true" aria-labelledby="comment-title">

    <!-- Single Responsive Modal -->
    <div :class="isDesktop ? 'flex items-center justify-center min-h-full p-4' : 'flex items-end min-h-full'">
        <div x-show="$wire.showCommentModal" @click.outside="cancelComment()"
            x-transition:enter="transition ease-out duration-200 will-change-transform"
            :x-transition:enter-start="isDesktop ? 'transform scale-95 opacity-0' : 'transform translate-y-full'"
            :x-transition:enter-end="isDesktop ? 'transform scale-100 opacity-100' : 'transform translate-y-0'"
            x-transition:leave="transition ease-in duration-150 will-change-transform"
            :x-transition:leave-start="isDesktop ? 'transform scale-100 opacity-100' : 'transform translate-y-0'"
            :x-transition:leave-end="isDesktop ? 'transform scale-95 opacity-0' : 'transform translate-y-full'"
            :class="isDesktop ? 'w-full max-w-lg p-6 bg-white shadow-2xl rounded-xl dark:bg-zinc-900' :
                'w-full max-h-screen overflow-y-auto px-4 pt-6 pb-8 bg-white shadow-2xl rounded-t-3xl dark:bg-zinc-900'"
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
                <button tabindex="-1" x-show="isDesktop" @click="cancelComment()"
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
                            <x-mentionable-text live="true" statePath="commentText" :mentionables="$mentionables ?? []"
                                :hashables="$hashables ?? []" hint="Use @ to mention people and # to embed documents"
                                min-height="30px" id="comment-text" ::class="isDesktop ? '' :
                                    'w-full resize-none rounded-xl border border-zinc-300 bg-white px-4 py-3 text-base transition-colors focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-sky-400'" />
                            <div class="grid grid-cols-3">

                                <x-reusable.forms.select statePath="urgency" allowColors size="sm"
                                    :options="$urgencyArray" defaultValue="2" />
                            </div>

                            <!-- Media Components -->
                            <div class="w-full mt-3 space-y-3">
                                <!-- Voice Notes -->
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

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="p-3 mb-4 border border-red-200 rounded-lg bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Footer -->
            <div
                :class="isDesktop ? 'flex gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700' : 'flex gap-2 pt-6'">
                <button @click.prevent="cancelComment()"
                    :class="isDesktop ?
                        'flex-1 px-4 py-2 font-medium transition-colors rounded-lg bg-zinc-100 text-zinc-700 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' :
                        'px-4 py-2 font-medium text-center transition-colors rounded-xl bg-zinc-100 text-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:bg-zinc-800 dark:text-zinc-300'">
                    Cancel
                </button>
                <button @click="$wire.saveNewComment()" :disabled="!$wire.canSave"
                    :class="isDesktop ?
                        'flex-1 px-4 py-2 font-medium text-center transition-colors rounded-lg border border-sky-500 text-sky-500 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 dark:hover:bg-sky-950/20 dark:text-sky-400 dark:border-sky-400' :
                        'flex-1 py-2 font-medium text-center transition-colors rounded-xl border border-sky-500 text-sky-500 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 dark:hover:bg-sky-950/20 dark:text-sky-400 dark:border-sky-400'">
                    Save Comment
                </button>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div x-show="showCancelConfirm" x-cloak class="fixed inset-0 z-[60] bg-zinc-900/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @keydown.escape.stop="showCancelConfirm = false">

        <div class="flex items-center justify-center min-h-full p-4">
            <div x-show="showCancelConfirm" @click.outside="showCancelConfirm = false" @click.stop
                class="w-full max-w-sm p-6 bg-white shadow-2xl rounded-xl dark:bg-zinc-900"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="transform scale-95 opacity-0"
                x-transition:enter-end="transform scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="transform scale-100 opacity-100"
                x-transition:leave-end="transform scale-95 opacity-0">

                <div class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/30">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>

                    <h3 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        Discard changes?
                    </h3>

                    <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                        You have unsaved changes. Are you sure you want to discard them?
                    </p>

                    <div class="flex gap-3">
                        <button
                            @click.stop="isProcessingCancel = true; showCancelConfirm = false; setTimeout(() => isProcessingCancel = false, 100)"
                            class="flex-1 px-4 py-2 font-medium transition-colors rounded-lg bg-zinc-100 text-zinc-700 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Keep editing
                        </button>
                        <button @click.stop="confirmCancelling()"
                            class="flex-1 px-4 py-2 font-medium text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Discard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
