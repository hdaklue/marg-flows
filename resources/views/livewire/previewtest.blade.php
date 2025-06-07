<div x-init="">
    <div x-data="designReviewApp()">
        <!-- Demo Container -->
        <div class="mx-auto max-w-4xl rounded-lg bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-bold">Design Review Component Demo</h1>
            <p class="mb-4 text-gray-600">Click on any image to open the review modal. Click or drag on the image to add
                comments.</p>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="cursor-pointer overflow-hidden rounded transition-transform hover:scale-105"
                    @click="openModal('https://picsum.photos/800/600?random=1')">
                    <img src="https://picsum.photos/800/600?random=1" alt="Design 1" class="h-40 w-full object-cover">
                </div>
                <div class="cursor-pointer overflow-hidden rounded transition-transform hover:scale-105"
                    @click="openModal('https://picsum.photos/1200/800?random=2')">
                    <img src="https://picsum.photos/1200/800?random=2" alt="Design 2" class="h-40 w-full object-cover">
                </div>
                <div class="cursor-pointer overflow-hidden rounded transition-transform hover:scale-105"
                    @click="openModal('https://picsum.photos/600/900?random=3', $wire.comments,{
    onSaveComment: async (comment) => {
        const result = await $wire.call('saveComment', comment );
        return result.original;
    }
})">
                    <img src="https://picsum.photos/600/900?random=3" alt="Design 3" class="h-40 w-full object-cover">
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div x-show="isOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
            @click="handleBackdropClick($event)" style="display: none;">
            <div class="relative flex max-h-[95vh] max-w-[95vw] flex-wrap rounded-lg bg-white shadow-2xl" @click.stop>
                <button @click="handleClose()" @touchend.prevent="handleClose()"
                    class="absolute right-0 top-5 z-10 flex h-8 w-8 -translate-y-full items-center justify-center rounded-full bg-black/50 text-white transition-colors hover:bg-black/70 active:bg-black/90">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
                <div class="absolute top-5 z-50 flex -translate-y-full space-x-1 overflow-visible">
                    <div class="relative z-[60]" @click.outside="showCommentFilter = false">
                        <button @click="toggleCommentFilter" @touchend.prevent="toggleCommentFilter"
                            :class="showCommentFilter || hasActiveFilter ?
                                'bg-blue-500 text-white hover:bg-blue-400 border-blue-800' :
                                'bg-white/70 text-gray-700 hover:bg-white border-gray-900'"
                            class="flex h-8 w-8 items-center justify-center rounded-full border shadow hover:bg-white active:bg-gray-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div x-show="showCommentFilter" x-transition
                            class="absolute mt-2 w-48 space-y-1 rounded-md border border-gray-300 bg-white/70 p-2 shadow-lg hover:bg-white">
                            <template x-for="(comment, index) in comments">
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="checkbox" :value="comment.id" x-model="selectedCommentIds"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span
                                        x-text="comment.text.slice(0, 20) + (comment.text.length > 20 ? '...' : '')"></span>
                                </label>
                            </template>
                            {{-- <div class="border-t pt-2 text-right">
                                <button @click="selectedCommentIds = []" @touchend.prevent="selectedCommentIds = []"
                                    class="text-xs text-blue-600 hover:underline active:text-blue-800">Clear
                                    Filter</button>
                            </div> --}}
                        </div>
                    </div>
                    <div>
                        <button @click="toggleAllComments" @touchend.prevent="toggleAllComments"
                            :class="allCommentsHidden ?
                                'bg-blue-500 text-white hover:bg-blue-400 hover:text-white border-blue-800' :
                                'bg-white/70 text-gray-700 hover:bg-white border-black'"
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow active:bg-gray-200">
                            <template x-if="allCommentsHidden">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9.27-3.11-10.5-7.5a10.05 10.05 0 013.03-4.57m3.39-2.05A9.953 9.953 0 0112 5c5 0 9.27 3.11 10.5 7.5a9.956 9.956 0 01-4.423 5.568M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <line x1="3" y1="3" x2="21" y2="21"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
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
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white/70 text-gray-700 shadow hover:bg-white active:bg-gray-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
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
                    @mousemove="isDragging && updateSelection($event)"
                    @touchmove="isDragging && handleTouchMove($event)" @mouseup="endSelection($event)"
                    @touchend="handleTouchEnd($event)" @mouseleave="isDragging && endSelection($event)"
                    @touchcancel="cancelSelection()">

                    <img :src="currentImage" class="pointer-events-none block h-auto max-h-[85vh] w-auto max-w-full"
                        alt="Design for review" draggable="false">

                    <!-- Existing Comments -->
                    <template x-for="(comment, index) in visibleComments" :key="comment.id">
                        <div class="absolute cursor-pointer border-2 border-blue-500 bg-blue-500/20 transition-all hover:z-10 hover:border-blue-600 hover:bg-blue-500/30"
                            :class="{ 'bg-blue-500/40 border-blue-700 z-20': activeComment === comment.id }"
                            :style="`left: ${comment.x}%; top: ${comment.y}%; width: ${comment.width}%; height: ${comment.height}%; min-width: 20px; min-height: 20px;`"
                            @click.stop="selectComment(comment)">
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
                        <div x-show="showCommentPopup" x-transition
                            class="absolute z-50 min-w-[300px] rounded-lg bg-white p-2 shadow-lg transition-all duration-500"
                            :style="commentPopupStyle" @click.stop>

                            <textarea x-ref="commentTextarea" x-model="newComment.text" :disabled="isSaving"
                                class="min-h-[80px] w-full resize-y rounded border border-gray-300 p-2 text-sm focus:border-blue-500 focus:outline-none"
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
        <div x-show="showConfirmDialog" x-transition
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80" style="display: none;">
            <div class="max-w-md rounded-lg bg-white p-6" @click.stop>
                <h3 class="mb-2 text-lg font-semibold text-red-500">Unsaved Changes</h3>
                <p class="mb-5 text-gray-600">You have unsaved comments. Are you sure you want to close?</p>
                <div class="flex justify-end gap-2">
                    <button @click="handleCancelConfirmationDialog" @touchend.prevent="handleCancelConfirmationDialog"
                        class="rounded bg-gray-200 px-2 py-1 text-gray-700 transition-colors hover:bg-gray-300 active:bg-gray-400">Cancel</button>
                    <button @click="handleConfirmCloseConfirmationDialog"
                        @touchend.prevent="handleConfirmCloseConfirmationDialog"
                        class="rounded bg-red-500 px-2 py-1 text-sm text-white transition-colors hover:bg-red-600 active:bg-red-700">Close
                        Anyway</button>
                </div>
            </div>
        </div>
    </div>

</div>
