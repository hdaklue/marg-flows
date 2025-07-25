<div x-data="designReviewApp()" x-init="init({
    onSaveComment: async (comment, designId) => {
        await $wire.saveComment(comment, designId);
    }
})" @keydown.escape.window="handleEscape()">
    <div class="fixed z-50 min-h-screen w-full items-center justify-center bg-gray-800/90" x-show="showingComment"
        x-cloak>

        <!-- Comment container -->
        <div x-show="showingComment" x-transition:enter="transition ease-out duration-300" @click.outside="closeComment()"
            x-transition:enter-start="transform translate-y-full opacity-0"
            x-transition:enter-end="transform translate-y-0 opacity-100" x-transition:leave="transition ease-in"
            x-transition:leave-start="transform translate-y-0 opacity-100"
            x-transition:leave-end="transform translate-y-full opacity-0"
            class="md:w-2xl fixed bottom-0 left-1/2 z-50 h-4/5 w-full -translate-x-1/2 transform rounded-lg bg-gray-50 p-4 shadow-2xl dark:bg-zinc-900 md:h-1/2"
            x-cloak>
            <div class="mb-4 flex h-full flex-col text-gray-600 dark:text-gray-400">
                <!-- Fixed top section -->
                <div class="h-12 flex-shrink-0 justify-start self-start text-left">
                    <h3 class="text-xl font-semibold">This button could be larger for better accessibility.</h3>
                    {{-- <h3>Comment ID: <span x-text="activeComment?.id"></span></h3>
                    <p x-text="activeComment?.text"></p> --}}
                </div>

                <!-- Flexible center section that takes remaining space -->
                <div class="dakr:text-gray-50 flex-1 overflow-auto bg-gray-50 p-4 dark:bg-zinc-900/90">
                    @if ($activeCommentId)
                        {{ $activeCommentId }}
                    @endif
                </div>

                <!-- Fixed bottom section -->
                <div class="flex-shrink-0 self-baseline">
                    <button @click="showingComment = false; setTimeout(() => {activeComment = null;}, 100)"
                        class="mt-2 w-full rounded bg-red-200 px-3 py-1 text-red-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Demo Container -->
    <div class="mx-auto max-w-4xl rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800">

        <h1 class="mb-2 text-2xl font-bold text-gray-800 dark:text-white">Design Review Component Demo</h1>
        <p class="mb-4 text-gray-600 dark:text-gray-400">Click on any image to open the review modal. Click or drag on
            the image to add
            comments.</p>


        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($images as $image)
                <div class="flex cursor-pointer space-x-3 overflow-hidden rounded bg-gray-100 p-2 transition-all hover:scale-105 dark:bg-zinc-800 dark:hover:bg-zinc-700"
                    @click="openModal('{{ asset($image['url']) }}', @js($image['comments'] ?? []), '{{ $image['id'] }}')">
                    <div class="h-20 w-20 overflow-hidden">
                        <img src="{{ asset($image['url']) }}" alt="Design" class="h-auto w-full object-cover"
                            loading="lazy">
                    </div>
                    <div class="flex flex-col items-start justify-start space-y-1">
                        <h2 class="pb-1.5 text-xs font-semibold leading-5 text-gray-900 dark:text-gray-50">Snapchat
                            option 3 story size</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><span
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400">Size:</span> 100 MB
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><span
                                class="text-xs font-semibold text-gray-500 dark:text-gray-400">Dimension:</span>
                            1200 x
                            1080</p>
                    </div>
                </div>
            @endforeach
            {{-- <div class="overflow-hidden transition-transform rounded cursor-pointer hover:scale-105"
                @click="openModal($wire.image, $wire.comments)">
                <img :src="$wire.image" alt="Design 3" class="object-cover w-full h-40">
            </div>
            <div class="overflow-hidden transition-transform rounded cursor-pointer hover:scale-105"
                @click="openModal($wire.image, $wire.comments)">
                <img :src="$wire.image" alt="Design 3" class="object-cover w-full h-40">
            </div> --}}
        </div>
    </div>

    <!-- Modal -->
    <div x-show="isOpen" x-transition class="fixed inset-0 z-40 flex items-center justify-center bg-black/90 p-4"
        @click="handleBackdropClick($event)" style="display: none;">
        <div class="relative flex max-h-[95vh] max-w-[95vw] flex-wrap rounded-lg bg-white shadow-2xl transition-all duration-200"
            @click.stop :class="showingComment ? 'scale-95' : ''">
            <button @click="handleClose()" @touchend.prevent="handleClose()"
                class="dakr:text-gray-50 absolute -top-1 right-0 z-10 flex h-8 w-8 -translate-y-full items-center justify-center rounded-full bg-black/50 text-white transition-colors hover:bg-black/70 active:bg-black/90 dark:bg-zinc-700/90 dark:hover:bg-zinc-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
            <div class="absolute -top-1 z-40 flex -translate-y-full space-x-1 overflow-visible"
                x-show="!showCommentPopup" x-transition>
                <div class="relative z-[60]" @click.outside="showCommentFilter = false">
                    <button @click="toggleCommentFilter" @touchend.prevent="toggleCommentFilter"
                        :class="showCommentFilter || hasActiveFilter ?
                            'bg-blue-500  hover:bg-blue-400 border-blue-800 text-gray-300' :
                            'bg-white/70 text-gray-700 hover:bg-white border-gray-900'"
                        class="hover:text-gray-800' flex h-8 w-8 items-center justify-center rounded-full border shadow">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div x-show="showCommentFilter" x-transition x-trap="showCommentFilter"
                        class="absolute mt-2 w-48 space-y-1 rounded-md border border-gray-300 bg-white/70 p-2 shadow-lg hover:bg-white dark:border-zinc-900 dark:bg-zinc-800 dark:hover:bg-zinc-900">
                        <template x-for="(comment, index) in comments">
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input type="checkbox" :value="comment.id" x-model="selectedCommentIds"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span x-text="comment.text.slice(0, 20) + (comment.text.length > 20 ? '...' : '')"
                                    class="dark:text-gray-50"></span>
                            </label>
                        </template>
                        {{-- <div class="pt-2 text-right border-t">
                                <button @click="selectedCommentIds = []" @touchend.prevent="selectedCommentIds = []"
                                    class="text-xs text-blue-600 hover:underline active:text-blue-800">Clear
                                    Filter</button>
                            </div> --}}
                    </div>
                </div>
                <div>
                    <button @click.prevent="toggleAllComments" @touchend.prevent="toggleAllComments"
                        :class="allCommentsHidden ?
                            'bg-blue-500 text-white hover:bg-blue-400 hover:text-white border-blue-800' :
                            'bg-white/70 text-gray-700 hover:bg-white border-black'"
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow active:bg-gray-200">
                        <template x-if="allCommentsHidden">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9.27-3.11-10.5-7.5a10.05 10.05 0 013.03-4.57m3.39-2.05A9.953 9.953 0 0112 5c5 0 9.27 3.11 10.5 7.5a9.956 9.956 0 01-4.423 5.568M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </template>
                        <template x-if="!allCommentsHidden">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                            </svg>
                        </template>

                    </button>
                </div>
                {{-- <div>
                        <button @click="showAllComments" @touchend.prevent="showAllComments()"
                            class="flex items-center justify-center w-8 h-8 text-gray-700 border border-gray-300 rounded-full shadow bg-white/70 hover:bg-white active:bg-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                            </svg>


                        </button>
                    </div> --}}
            </div>

            <div class="relative inline-block cursor-crosshair touch-none select-none" x-ref="imageContainer"
                @mousedown.prevent="startSelection($event)" @touchstart.prevent="handleTouchStart($event)"
                @mousemove="isDragging && updateSelection($event)" @touchmove="isDragging && handleTouchMove($event)"
                @mouseup="endSelection($event)" @touchend="handleTouchEnd($event)"
                @mouseleave="isDragging && endSelection($event)" @touchcancel="cancelSelection()">

                <img :src="currentImage" class="pointer-events-none block h-auto max-h-[85vh] w-auto max-w-full"
                    alt="Design for review" draggable="false">

                <!-- Existing Comments -->
                <template x-for="(comment, index) in visibleComments" :key="comment.id">
                    <div class="hover:border-{$color}-600 absolute cursor-pointer border-2 border-blue-500 bg-blue-500/20 transition-all hover:z-10 hover:bg-blue-500/30"
                        :class="{ 'bg-{$color}-500/40 border-{$color}-700 z-20': activeCommentId === comment.id }"
                        :style="`left: ${comment.x}%; top: ${comment.y}%; width: ${comment.width}%; height: ${comment.height}%; min-width: 20px; min-height: 20px;`">
                        <span
                            class="absolute -left-3 -top-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white shadow"
                            x-text="comments.indexOf(comment) + 1"></span>
                    </div>
                </template>

                <!-- New Comment Marker -->
                <template x-if="newComment">
                    <div class="pointer-events-none absolute z-30 border-2 border-blue-400 bg-blue-400/10"
                        :style="`left: ${newComment.x}%; top: ${newComment.y}%; width: ${newComment.width}%; height: ${newComment.height}%;`">
                    </div>
                </template>

                <!-- Selection Box -->
                <template x-if="isSelecting">
                    <div class="pointer-events-none absolute border-2 border-dashed border-blue-500 bg-blue-500/10"
                        :style="`left: ${selectionBox.x}%; top: ${selectionBox.y}%; width: ${selectionBox.width}%; height: ${selectionBox.height}%;`">
                    </div>
                </template>

                <!-- Comment Popup -->
                <template x-if="newComment">
                    <div x-show="showCommentPopup" x-transition x-trap="showCommentPopup"
                        class="absolute z-50 min-w-[300px] rounded-lg bg-white p-2 shadow-lg transition-all duration-500 dark:bg-zinc-900"
                        :style="commentPopupStyle" @click.stop>

                        <textarea x-autosize x-ref="commentTextarea" x-model="newComment.text" :disabled="isSaving"
                            class="w-full rounded border border-gray-300 p-1.5 text-sm font-semibold focus:border-blue-500 focus:outline-none dark:bg-gray-800 dark:text-gray-50 lg:font-normal"
                            placeholder="Add your comment..." @keydown.ctrl.enter="saveComment()" @keydown.meta.enter="saveComment()"></textarea>
                        <div class="mt-2 flex justify-end gap-2">
                            <button @click.stop="cancelComment()" @touchend.prevent="cancelComment()"
                                class="rounded bg-gray-200 px-2 py-1 text-sm text-gray-700 transition-colors hover:bg-gray-300 active:bg-gray-400">Cancel</button>
                            <button @click="canSave && saveComment()" @touchend.prevent="canSave && saveComment()"
                                :disabled="!canSave || isSaving"
                                :class="!canSave || isSaving ?
                                    'opacity-50 pointer-events-none' :
                                    'hover:bg-blue-600 active:bg-blue-700'"
                                class="rounded bg-blue-500 px-2 py-1 text-sm text-white transition-colors"
                                x-text="isSaving ? 'Saving...' : 'Save'">
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <div x-show="showConfirmDialog" x-transition x-trap="showConfirmDialog"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80" style="display: none;">
        <div class="max-w-md rounded-lg bg-white p-6 dark:bg-zinc-900" @click.stop>
            <h3 class="mb-2 text-lg font-semibold text-red-500">Unsaved Changes</h3>
            <p class="mb-5 text-gray-600 dark:text-gray-50">You have unsaved comments. Are you sure you want to close?
            </p>
            <div class="flex justify-end gap-2">
                <button @click="handleCancelConfirmationDialog" @touchend.prevent="handleCancelConfirmationDialog"
                    class="rounded bg-gray-200 px-2 py-1 text-sm text-gray-700 outline-offset-1 outline-gray-300 transition-colors hover:bg-gray-300 active:bg-gray-400">Cancel</button>
                <button @click="handleConfirmCloseConfirmationDialog"
                    @touchend.prevent="handleConfirmCloseConfirmationDialog"
                    class="rounded bg-red-500 px-2 py-1 text-sm text-white outline-offset-1 outline-red-800 ring-1 ring-red-500 transition-colors hover:bg-red-600 active:bg-red-700 dark:text-gray-50">Close
                    Anyway</button>
            </div>
        </div>
    </div>
</div>
