<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Review Component</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script> --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 p-6">
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
                    @click="openModal('https://picsum.photos/600/900?random=3',[],{
    onSaveComment: async (comment, image) => {
        console.log('Starting save...', comment);
        await new Promise(resolve => setTimeout(resolve, 2000)); // 2 second delay
        console.log('Comment saved:', comment);
    },
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
                            <template x-for="(comment, index) in comments" :key="comment.id">
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
    <script>
        // function designReviewApp() {
        //     function uuidv4() {
        //         return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        //             const r = Math.random() * 16 | 0,
        //                 v = c === 'x' ? r : (r & 0x3 | 0x8);
        //             return v.toString(16);
        //         });
        //     }

        //     return {
        //         isOpen: false,
        //         currentImage: '',
        //         comments: [],
        //         activeComment: null,
        //         showCommentPopup: false,
        //         showConfirmDialog: false,
        //         hasUnsavedChanges: false,
        //         isSelecting: false,
        //         isDragging: false,
        //         allCommentsHidden: false,
        //         _openedViaEvent: false,
        //         selectionStart: {
        //             x: 0,
        //             y: 0,
        //             xPx: null,
        //             yPx: null
        //         },
        //         selectionBox: {
        //             x: 0,
        //             y: 0,
        //             width: 0,
        //             height: 0
        //         },
        //         newComment: null,
        //         popupAnchor: {
        //             x: 0,
        //             y: 0
        //         },
        //         selectedCommentIds: [],
        //         showCommentFilter: false,
        //         visibleComments: [],
        //         filterMode: false,
        //         touchStartTime: 0,
        //         longPressTimer: null,
        //         touchMoved: false,
        //         callbacks: {
        //             onSaveComment: null,
        //             onDeleteComment: null,
        //             onEditComment: null,
        //             onCommentClick: null,
        //             onModalOpen: null,
        //             onModalClose: null
        //         },

        //         get commentPopupStyle() {
        //             if (!this.popupAnchor || !this.$refs.imageContainer) return '';
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const popupWidth = 300,
        //                 popupHeight = 180;
        //             let left = this.popupAnchor.x - rect.left - popupWidth / 2;
        //             let top = (this.popupAnchor.y + 10) - rect.top;
        //             left = Math.max(10, Math.min(left, rect.width - popupWidth - 10));
        //             top = Math.max(10, Math.min(top, rect.height - popupHeight - 10));
        //             return `top: ${top}px; left: ${left}px;`;
        //         },

        //         get hasActiveFilter() {
        //             if (this.allCommentsHidden) return false;
        //             return this.selectedCommentIds.length < this.comments.length;
        //         },

        //         init() {
        //             this.$watch('selectedCommentIds', () => {
        //                 if (this.filterMode) {
        //                     this.updateVisibleComments();
        //                 }
        //             });

        //             // Register event listener for opening modal
        //             window.addEventListener('open-design-review', (event) => {
        //                 this._openedViaEvent = true;
        //                 const {
        //                     imageUrl,
        //                     comments,
        //                     designId
        //                 } = event.detail;

        //                 this.openModal(imageUrl, comments || [], {
        //                     onSaveComment: (comment, image) => {
        //                         if (window.Livewire) {
        //                             Livewire.emit('saveComment', {
        //                                 designId: designId,
        //                                 comment: comment
        //                             });
        //                         }
        //                     },
        //                     onDeleteComment: (commentId) => {
        //                         if (window.Livewire) {
        //                             Livewire.emit('deleteComment', {
        //                                 designId: designId,
        //                                 commentId: commentId
        //                             });
        //                         }
        //                     },
        //                     onCommentClick: (comment) => {
        //                         console.log('Comment clicked:', comment);
        //                     }
        //                 });
        //             });

        //             window.addEventListener('keydown', (event) => {

        //                 if (event.key === 'Escape') {
        //                     event.preventDefault();

        //                     this.handleEscape();
        //                 }
        //             });

        //         },

        //         handleEscape() {

        //             this.handleClose();

        //         },

        //         updateVisibleComments() {
        //             this.visibleComments = this.filterMode ?
        //                 this.comments.filter(c => this.selectedCommentIds.includes(c.id)) :
        //                 this.comments.filter(c => c.text?.trim());
        //         },

        //         toggleCommentFilter() {
        //             this.showCommentFilter = !this.showCommentFilter;
        //             this.filterMode = !this.filterMode;
        //         },

        //         toggleAllComments() {
        //             this.allCommentsHidden ? this.showAllComments() : this.hideAllComments();
        //         },

        //         hideAllComments() {
        //             this.selectedCommentIds = [];
        //             this.visibleComments = [];
        //             this.allCommentsHidden = true;
        //         },

        //         showAllComments() {
        //             this.visibleComments = this.comments;
        //             this.selectedCommentIds = this.comments.map(c => c.id);
        //             this.allCommentsHidden = false;
        //         },

        //         openModal(imageUrl, existingComments = [], callbacks = {}) {
        //             // Check if called via event listener
        //             if (this._openedViaEvent) {
        //                 console.warn(
        //                     'Design Review: Modal opened via both direct method and event. Using direct method parameters.'
        //                 );
        //                 this._openedViaEvent = false;
        //             }

        //             this.currentImage = imageUrl;
        //             this.comments = existingComments.length > 0 ? existingComments : [{
        //                     "id": "c91c1dbe-3ef1-4208-a8e9-9d3f010f0c21",
        //                     "text": "Adjust the spacing here.",
        //                     "x": 12,
        //                     "y": 15,
        //                     "width": 2,
        //                     "height": 2,
        //                     "type": "point",
        //                     "author": "Alice",
        //                     "timestamp": "2025-06-01T10:00:00Z",
        //                     "resolved": false,
        //                 },
        //                 {
        //                     "id": "d7517139-3f2f-453e-9436-8cb31f2fc177",
        //                     "text": "Consider realigning this section.",
        //                     "x": 35,
        //                     "y": 25,
        //                     "width": 15,
        //                     "height": 10,
        //                     "type": "area",
        //                     "author": "Bob",
        //                     "timestamp": "2025-06-01T10:05:00Z",
        //                     "resolved": false
        //                 }
        //             ];
        //             this.selectedCommentIds = this.comments.map(c => c.id);

        //             // Only update callbacks that are provided
        //             if (callbacks.onSaveComment !== undefined) {
        //                 this.callbacks.onSaveComment = callbacks.onSaveComment;
        //             }
        //             if (callbacks.onDeleteComment !== undefined) {
        //                 this.callbacks.onDeleteComment = callbacks.onDeleteComment;
        //             }
        //             if (callbacks.onEditComment !== undefined) {
        //                 this.callbacks.onEditComment = callbacks.onEditComment;
        //             }
        //             if (callbacks.onCommentClick !== undefined) {
        //                 this.callbacks.onCommentClick = callbacks.onCommentClick;
        //             }
        //             if (callbacks.onModalOpen !== undefined) {
        //                 this.callbacks.onModalOpen = callbacks.onModalOpen;
        //             }
        //             if (callbacks.onModalClose !== undefined) {
        //                 this.callbacks.onModalClose = callbacks.onModalClose;
        //             }

        //             this.isOpen = true;
        //             this.hasUnsavedChanges = false;
        //             this.updateVisibleComments();

        //             if (this.callbacks.onModalOpen) {
        //                 this.callbacks.onModalOpen(this.currentImage);
        //             }
        //         },

        //         closeModal() {
        //             this.isOpen = false;
        //             this.reset();
        //             if (this.callbacks.onModalClose) {
        //                 this.callbacks.onModalClose();
        //             }
        //         },

        //         handleCancelConfirmationDialog() {
        //             this.showConfirmDialog = false;
        //             this.$nextTick(() => this.$refs.commentTextarea?.focus());
        //         },


        //         reset() {
        //             this.comments = [];
        //             this.selectedCommentIds = [];
        //             this.activeComment = null;
        //             this.showCommentPopup = false;
        //             this.hasUnsavedChanges = false;
        //             this.isSelecting = false;
        //             this.isDragging = false;
        //             this.allCommentsHidden = false;
        //             this.newComment = null;
        //             this.visibleComments = [];
        //             this.filterMode = false;
        //             this.showCommentFilter = false;
        //             if (this.longPressTimer) {
        //                 clearTimeout(this.longPressTimer);
        //                 this.longPressTimer = null;
        //             }
        //         },

        //         handleClose() {
        //             if (this.hasUnsavedChanges && this.newComment?.text !== '') {
        //                 this.showConfirmDialog = true;
        //                 return;
        //             }
        //             if (this.showCommentPopup && this.newComment?.text === '') {
        //                 this.showCommentPopup = false;
        //                 this.newComment = null;
        //                 return;
        //             }
        //             if (this.showCommentFilter) {
        //                 this.showCommentFilter = false;
        //                 this.filterMode = false;
        //                 return;
        //             }
        //             this.closeModal();
        //         },

        //         handleConfirmCloseConfirmationDialog() {
        //             this.showConfirmDialog = false;
        //             if (this.showCommentPopup) {
        //                 this.showCommentPopup = false;
        //                 this.newComment = null;
        //                 return;
        //             }
        //             this.closeModal();
        //         },

        //         handleBackdropClick(event) {
        //             if (event.target === event.currentTarget) this.handleClose();
        //         },

        //         // Mouse Events
        //         startSelection(event) {
        //             if (event.button !== 0 || this.showCommentPopup) return;
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const x = event.clientX - rect.left;
        //             const y = event.clientY - rect.top;
        //             this.selectionStart = {
        //                 x: (x / rect.width) * 100,
        //                 y: (y / rect.height) * 100,
        //                 xPx: x,
        //                 yPx: y
        //             };
        //             this.isDragging = true;
        //             this.isSelecting = false;
        //         },

        //         updateSelection(event) {
        //             if (!this.isDragging || !this.selectionStart.xPx) return;
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const x = Math.max(0, Math.min(event.clientX - rect.left, rect.width));
        //             const y = Math.max(0, Math.min(event.clientY - rect.top, rect.height));
        //             const distance = Math.sqrt(Math.pow(x - this.selectionStart.xPx, 2) + Math.pow(y - this.selectionStart
        //                 .yPx, 2));
        //             if (distance > 5) this.isSelecting = true;
        //             if (!this.isSelecting) return;

        //             const xPercent = (x / rect.width) * 100;
        //             const yPercent = (y / rect.height) * 100;
        //             this.selectionBox = {
        //                 x: Math.min(this.selectionStart.x, xPercent),
        //                 y: Math.min(this.selectionStart.y, yPercent),
        //                 width: Math.abs(xPercent - this.selectionStart.x),
        //                 height: Math.abs(yPercent - this.selectionStart.y)
        //             };
        //         },

        //         endSelection(event) {
        //             if (!this.isDragging) return;
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const x = event.clientX - rect.left;
        //             const y = event.clientY - rect.top;
        //             this.isDragging = false;

        //             if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
        //                 this.createAreaComment();
        //             } else if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
        //                 this.handleClick(event.clientX, event.clientY, rect);
        //             }

        //             this.resetSelectionState();
        //         },

        //         // Touch Events
        //         handleTouchStart(event) {
        //             if (this.showCommentPopup) return;
        //             const touch = event.touches[0];
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const x = touch.clientX - rect.left;
        //             const y = touch.clientY - rect.top;

        //             this.touchStartTime = Date.now();
        //             this.touchMoved = false;
        //             this.selectionStart = {
        //                 x: (x / rect.width) * 100,
        //                 y: (y / rect.height) * 100,
        //                 xPx: x,
        //                 yPx: y
        //             };
        //             this.isDragging = true;
        //             this.isSelecting = false;

        //             // Long press for area selection on mobile
        //             this.longPressTimer = setTimeout(() => {
        //                 if (!this.touchMoved && this.isDragging) {
        //                     navigator.vibrate && navigator.vibrate(50);
        //                     this.isSelecting = true;
        //                 }
        //             }, 500);
        //         },

        //         handleTouchMove(event) {
        //             if (!this.isDragging) return;
        //             const touch = event.touches[0];
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const x = Math.max(0, Math.min(touch.clientX - rect.left, rect.width));
        //             const y = Math.max(0, Math.min(touch.clientY - rect.top, rect.height));

        //             const distance = Math.sqrt(
        //                 Math.pow(x - this.selectionStart.xPx, 2) +
        //                 Math.pow(y - this.selectionStart.yPx, 2)
        //             );

        //             if (distance > 10) {
        //                 this.touchMoved = true;
        //                 if (this.longPressTimer) {
        //                     clearTimeout(this.longPressTimer);
        //                     this.longPressTimer = null;
        //                 }
        //                 this.isSelecting = true;
        //             }

        //             if (!this.isSelecting) return;

        //             const xPercent = (x / rect.width) * 100;
        //             const yPercent = (y / rect.height) * 100;
        //             this.selectionBox = {
        //                 x: Math.min(this.selectionStart.x, xPercent),
        //                 y: Math.min(this.selectionStart.y, yPercent),
        //                 width: Math.abs(xPercent - this.selectionStart.x),
        //                 height: Math.abs(yPercent - this.selectionStart.y)
        //             };
        //         },

        //         handleTouchEnd(event) {
        //             if (!this.isDragging) return;

        //             if (this.longPressTimer) {
        //                 clearTimeout(this.longPressTimer);
        //                 this.longPressTimer = null;
        //             }

        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const touchDuration = Date.now() - this.touchStartTime;

        //             if (this.isSelecting && this.selectionBox.width > 1 && this.selectionBox.height > 1) {
        //                 this.createAreaComment();
        //             } else if (!this.touchMoved && touchDuration < 500) {
        //                 // It's a tap
        //                 const touch = event.changedTouches[0];
        //                 this.handleClick(touch.clientX, touch.clientY, rect);
        //             }

        //             this.isDragging = false;
        //             this.resetSelectionState();
        //         },

        //         // Common handlers
        //         handleClick(clientX, clientY, rect) {
        //             const x = clientX - rect.left;
        //             const y = clientY - rect.top;
        //             const xPercent = (x / rect.width) * 100;
        //             const yPercent = (y / rect.height) * 100;

        //             if (this.isClickInsideNewComment(xPercent, yPercent)) return;

        //             const clickedComment = this.findCommentAtPoint(xPercent, yPercent);
        //             if (clickedComment) {
        //                 return this.selectComment(clickedComment);
        //             }

        //             this.newComment = {
        //                 text: '',
        //                 x: xPercent - 1,
        //                 y: yPercent - 1,
        //                 width: 2,
        //                 height: 2,
        //                 type: 'point'
        //             };
        //             this.popupAnchor = {
        //                 x: clientX,
        //                 y: clientY
        //             };
        //             this.showCommentPopup = true;
        //             this.hasUnsavedChanges = true;
        //             this.$nextTick(() => this.$refs.commentTextarea?.focus());
        //         },

        //         createAreaComment() {
        //             this.newComment = {
        //                 text: '',
        //                 x: this.selectionBox.x,
        //                 y: this.selectionBox.y,
        //                 width: this.selectionBox.width,
        //                 height: this.selectionBox.height,
        //                 type: 'area'
        //             };
        //             const rect = this.$refs.imageContainer.getBoundingClientRect();
        //             const anchorX = this.selectionBox.x + this.selectionBox.width / 2;
        //             const anchorY = this.selectionBox.y + this.selectionBox.height;
        //             this.popupAnchor = {
        //                 x: rect.left + (anchorX * rect.width / 100),
        //                 y: rect.top + (anchorY * rect.height / 100)
        //             };
        //             this.showCommentPopup = true;
        //             this.hasUnsavedChanges = true;
        //             this.$nextTick(() => this.$refs.commentTextarea?.focus());
        //         },

        //         cancelSelection() {
        //             this.isDragging = false;
        //             this.isSelecting = false;
        //             if (this.longPressTimer) {
        //                 clearTimeout(this.longPressTimer);
        //                 this.longPressTimer = null;
        //             }
        //             this.resetSelectionState();
        //         },

        //         resetSelectionState() {
        //             this.selectionStart = {
        //                 x: 0,
        //                 y: 0,
        //                 xPx: null,
        //                 yPx: null
        //             };
        //             this.selectionBox = {
        //                 x: 0,
        //                 y: 0,
        //                 width: 0,
        //                 height: 0
        //             };
        //             this.isSelecting = false;
        //         },

        //         isClickInsideNewComment(xPercent, yPercent) {
        //             if (!this.newComment) return false;
        //             const c = this.newComment;
        //             return xPercent >= c.x && xPercent <= c.x + c.width && yPercent >= c.y && yPercent <= c.y + c.height;
        //         },

        //         findCommentAtPoint(x, y) {
        //             return this.visibleComments.find(c => x >= c.x && x <= c.x + c.width && y >= c.y && y <= c.y + c
        //                 .height);
        //         },

        //         selectComment(comment) {
        //             this.activeComment = comment.id;
        //             if (this.callbacks.onCommentClick) {
        //                 this.callbacks.onCommentClick(comment);
        //             }
        //         },

        //         saveComment() {
        //             if (!this.newComment?.text?.trim()) return;
        //             const comment = {
        //                 id: uuidv4(),
        //                 ...this.newComment,
        //                 author: 'Current User',
        //                 timestamp: new Date().toISOString(),
        //                 resolved: false
        //             };

        //             this.comments.push(comment);
        //             this.selectedCommentIds.push(comment.id);
        //             this.showCommentPopup = false;
        //             this.hasUnsavedChanges = false;
        //             this.newComment = null;
        //             this.updateVisibleComments();

        //             if (this.callbacks.onSaveComment) {
        //                 this.callbacks.onSaveComment(comment, this.currentImage);
        //             }
        //         },

        //         cancelComment() {
        //             this.showCommentPopup = false;
        //             this.hasUnsavedChanges = false;
        //             this.newComment = null;
        //         },

        //         // Public API methods
        //         setCallbacks(callbacks = {}) {
        //             // Only update callbacks that are provided
        //             if (callbacks.onSaveComment !== undefined) {
        //                 this.callbacks.onSaveComment = callbacks.onSaveComment;
        //             }
        //             if (callbacks.onDeleteComment !== undefined) {
        //                 this.callbacks.onDeleteComment = callbacks.onDeleteComment;
        //             }
        //             if (callbacks.onEditComment !== undefined) {
        //                 this.callbacks.onEditComment = callbacks.onEditComment;
        //             }
        //             if (callbacks.onCommentClick !== undefined) {
        //                 this.callbacks.onCommentClick = callbacks.onCommentClick;
        //             }
        //             if (callbacks.onModalOpen !== undefined) {
        //                 this.callbacks.onModalOpen = callbacks.onModalOpen;
        //             }
        //             if (callbacks.onModalClose !== undefined) {
        //                 this.callbacks.onModalClose = callbacks.onModalClose;
        //             }
        //         },

        //         getComments() {
        //             return this.comments;
        //         },

        //         addComment(comment) {
        //             this.comments.push({
        //                 id: comment.id || uuidv4(),
        //                 ...comment
        //             });
        //             this.selectedCommentIds.push(comment.id);
        //             this.updateVisibleComments();
        //         },

        //         removeComment(commentId) {
        //             this.comments = this.comments.filter(c => c.id !== commentId);
        //             this.selectedCommentIds = this.selectedCommentIds.filter(id => id !== commentId);
        //             this.updateVisibleComments();

        //             if (this.callbacks.onDeleteComment) {
        //                 this.callbacks.onDeleteComment(commentId);
        //             }
        //         }
        //     };
        // }
    </script>
</body>

</html>
