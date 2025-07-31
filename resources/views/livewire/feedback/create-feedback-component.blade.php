<!-- Feedback Component -->
@php
    $urgencyArray = App\Enums\Feedback\FeedbackUrgency::colorfulArray($urgency);
@endphp
<div x-data="{
    isDesktop: window.innerWidth >= 768,
    showCancelConfirm: false,
    isProcessingCancel: false,
    isDirty() {
        return $wire.isDirty();
    },
    cancelComment() {
        // Check if there are actually unsaved changes
        const hasText = $wire.commentText && $wire.commentText.trim().length > 0;
        const hasVoiceNotes = $wire.hasVoiceNotes;
        const hasUnuploadedVoiceNotes = $wire.hasUnuploadedVoiceNotes;

        if (hasText || hasVoiceNotes || hasUnuploadedVoiceNotes) {
            this.showCancelConfirm = true;
        } else {
            $wire.clear();
            this.$dispatch('voice-note:canceled');
        }
    },
    confirmCancelling() {
        $wire.handleConfirmCancel();
        this.showCancelConfirm = false;
    }
}" 
    x-init="
        // Add beforeunload listener
        window.addEventListener('beforeunload', (event) => {
            if (isDirty()) {
                event.preventDefault();
                event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Add Livewire navigate listener
        document.addEventListener('livewire:navigate', (event) => {
            if (isDirty()) {
                event.preventDefault();
                showCancelConfirm = true;
                return false;
            }
        });

        // Resize handler
        window.addEventListener('resize', () => {
            isDesktop = window.innerWidth >= 768;
        });
    "
    class="w-full max-w-2xl mx-auto" 
    @mentionable:text="this.commentText = $event.detail.state;"
    @feedback:comment-created="$dispatch('wv-manager:player:destroy'); $dispatch('wv-manager:recorder:destroy')"
    @feedback:show-cancel-confirmation.stop="showCancelConfirm = true">

    <!-- Compact Feedback Input Area -->
    <div class="p-4 border border-zinc-200 rounded-lg bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/30">
        <div class="space-y-3">
            <!-- Input Area -->
            <div>
                <x-mentionable-text 
                    live="true" 
                    statePath="commentText" 
                    :mentionables="$mentionables ?? []"
                    :hashables="$hashables ?? []" 
                    hint="Add your feedback... Use @ to mention people and # to embed documents"
                    min-height="24px" 
                    id="comment-text" />
            </div>

            <!-- Voice Recorder - Full Width -->
            <div class="w-full">
                <livewire:reusable.voice-note-component outlined />
            </div>

            <!-- Controls Row -->
            <div class="flex items-center justify-between gap-3">
                <!-- Left side: Urgency -->
                <div class="flex items-center gap-3">
                    <x-reusable.forms.select 
                        statePath="urgency" 
                        allowColors 
                        size="sm"
                        :options="$urgencyArray" 
                        defaultValue="2" />
                </div>

                <!-- Right side: Actions -->
                <div class="flex items-center gap-2">
                    <button @click.prevent="cancelComment()" 
                        class="px-3 py-1.5 text-sm font-medium transition-colors rounded-md text-zinc-600 hover:text-zinc-800 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-700">
                        Clear
                    </button>
                    <button @click="$wire.saveNewComment()" :disabled="!$wire.canSave"
                        class="px-4 py-1.5 text-sm font-medium text-white transition-colors bg-sky-600 rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        Post
                    </button>
                </div>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="p-2 border border-red-200 rounded-md bg-red-50 dark:border-red-800 dark:bg-red-900/20">
                    @foreach ($errors->all() as $error)
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
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
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/30">
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