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

<body class="bg-zinc-100 p-6 dark:bg-zinc-950">
    <div x-data="designReviewApp()" class="dark">
        <!-- Demo Container -->
        <div class="mx-auto max-w-7xl rounded-2xl bg-white p-8 shadow-xl dark:bg-zinc-900 dark:shadow-zinc-800/25">
            <div class="mb-8">
                <h1 class="mb-3 text-3xl font-bold text-zinc-900 dark:text-zinc-100">Design Review Studio</h1>
                <p class="text-lg text-zinc-600 dark:text-zinc-400">Select any design to start reviewing. Tap or drag to add precise feedback and collaborate with your team.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="group cursor-pointer overflow-hidden rounded-2xl bg-zinc-100 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl dark:bg-zinc-800 dark:hover:shadow-zinc-700/25"
                    @click="openModal('https://picsum.photos/800/600?random=1')">
                    <div class="aspect-video overflow-hidden">
                        <img src="https://picsum.photos/800/600?random=1" alt="Modern Interface Design" 
                             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Modern Interface</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Clean dashboard design</p>
                    </div>
                </div>
                
                <div class="group cursor-pointer overflow-hidden rounded-2xl bg-zinc-100 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl dark:bg-zinc-800 dark:hover:shadow-zinc-700/25"
                    @click="openModal('https://picsum.photos/1200/800?random=2')">
                    <div class="aspect-video overflow-hidden">
                        <img src="https://picsum.photos/1200/800?random=2" alt="Mobile App Design" 
                             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Mobile Experience</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Touch-optimized interface</p>
                    </div>
                </div>
                
                <div class="group cursor-pointer overflow-hidden rounded-2xl bg-zinc-100 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl dark:bg-zinc-800 dark:hover:shadow-zinc-700/25"
                    @click="openModal('https://picsum.photos/600/900?random=3',[],{
    onSaveComment: async (comment, image) => {
        console.log('Starting save...', comment);
        await new Promise(resolve => setTimeout(resolve, 2000)); // 2 second delay
        console.log('Comment saved:', comment);
    },
                    })">
                    <div class="aspect-video overflow-hidden">
                        <img src="https://picsum.photos/600/900?random=3" alt="Creative Layout" 
                             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Creative Layout</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Innovative design approach</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop & Tablet Modal -->
        <div x-show="isOpen && !isMobile" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 p-4 backdrop-blur-sm"
             @click="handleBackdropClick($event)" style="display: none;">
            <div class="relative flex max-h-[95vh] max-w-[95vw] flex-wrap rounded-2xl bg-white shadow-2xl dark:bg-zinc-900 dark:shadow-zinc-950/50" @click.stop>
                <!-- Close Button -->
                <button @click="handleClose()" @touchend.prevent="handleClose()"
                    class="absolute -right-2 -top-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-zinc-900/90 text-white backdrop-blur-sm transition-all duration-200 hover:bg-zinc-800 hover:scale-110 active:scale-95 dark:bg-zinc-100/90 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                
                <!-- Toolbar -->
                <div class="absolute left-4 top-4 z-50 flex space-x-2">
                    <!-- Comment Filter -->
                    <div class="relative" @click.outside="showCommentFilter = false">
                        <button @click="toggleCommentFilter" @touchend.prevent="toggleCommentFilter"
                            :class="showCommentFilter || hasActiveFilter ?
                                'bg-sky-500 text-white hover:bg-sky-400 border-sky-600 shadow-sky-500/25' :
                                'bg-white/90 text-zinc-700 hover:bg-white border-zinc-300 shadow-zinc-500/10 dark:bg-zinc-800/90 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:border-zinc-600'"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border shadow-lg backdrop-blur-sm transition-all duration-200 hover:scale-105 active:scale-95">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                        </button>

                        <!-- Filter Dropdown -->
                        <div x-show="showCommentFilter" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute left-0 top-12 w-64 rounded-xl border border-zinc-200 bg-white/95 p-3 shadow-xl backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-800/95">
                            <div class="mb-2">
                                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Filter Comments</h4>
                            </div>
                            <div class="max-h-48 space-y-2 overflow-y-auto">
                                <template x-for="(comment, index) in comments" :key="'filter-' + comment.id">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700/50">
                                        <input type="checkbox" :value="comment.id" x-model="selectedCommentIds"
                                            class="rounded border-zinc-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-700">
                                        <span class="flex-1 truncate text-zinc-700 dark:text-zinc-300"
                                            x-text="comment.text.slice(0, 35) + (comment.text.length > 35 ? '...' : '')"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visibility Toggle -->
                    <button @click="toggleAllComments" @touchend.prevent="toggleAllComments"
                        :class="allCommentsHidden ?
                            'bg-amber-500 text-white hover:bg-amber-400 border-amber-600 shadow-amber-500/25' :
                            'bg-white/90 text-zinc-700 hover:bg-white border-zinc-300 shadow-zinc-500/10 dark:bg-zinc-800/90 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:border-zinc-600'"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border shadow-lg backdrop-blur-sm transition-all duration-200 hover:scale-105 active:scale-95">
                        <template x-if="allCommentsHidden">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9.27-3.11-10.5-7.5a10.05 10.05 0 013.03-4.57m3.39-2.05A9.953 9.953 0 0112 5c5 0 9.27 3.11 10.5 7.5a9.956 9.956 0 01-4.423 5.568M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
                            </svg>
                        </template>
                        <template x-if="!allCommentsHidden">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"/>
                            </svg>
                        </template>
                    </button>
                </div>

                <!-- Image Container -->
                <div class="relative inline-block cursor-crosshair touch-none select-none rounded-xl overflow-hidden" x-ref="imageContainer"
                    @mousedown.prevent="startSelection($event)" @touchstart.prevent="handleTouchStart($event)"
                    @mousemove="isDragging && updateSelection($event)"
                    @touchmove="isDragging && handleTouchMove($event)" @mouseup="endSelection($event)"
                    @touchend="handleTouchEnd($event)" @mouseleave="isDragging && endSelection($event)"
                    @touchcancel="cancelSelection()">

                    <img :src="currentImage" class="pointer-events-none block h-auto max-h-[85vh] w-auto max-w-full rounded-xl"
                        alt="Design for review" draggable="false">

                    <!-- Existing Comments -->
                    <template x-for="(comment, index) in visibleComments" :key="'visible-' + comment.id">
                        <div class="absolute cursor-pointer border-2 border-sky-500 bg-sky-500/20 rounded-lg transition-all duration-200 hover:z-10 hover:border-sky-600 hover:bg-sky-500/30 hover:scale-105"
                            :class="{ 'bg-sky-500/40 border-sky-700 z-20 scale-105': activeComment === comment.id }"
                            :style="`left: ${comment.x}%; top: ${comment.y}%; width: ${comment.width}%; height: ${comment.height}%; min-width: 24px; min-height: 24px;`"
                            @click.stop="selectComment(comment)">
                            <span
                                class="absolute -left-3 -top-3 flex h-7 w-7 items-center justify-center rounded-full bg-sky-500 text-xs font-bold text-white shadow-lg shadow-sky-500/25 ring-2 ring-white dark:ring-zinc-900"
                                x-text="comments.indexOf(comment) + 1"></span>
                        </div>
                    </template>


                    <!-- Selection Box -->
                    <template x-if="isSelecting">
                        <div class="pointer-events-none absolute border-2 border-dashed border-sky-500 bg-sky-500/10 rounded-lg"
                            :style="`left: ${selectionBox.x}%; top: ${selectionBox.y}%; width: ${selectionBox.width}%; height: ${selectionBox.height}%;`">
                        </div>
                    </template>

                </div>
            </div>
        </div>

        <!-- Mobile Modal -->
        <div x-show="isOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex md:hidden"
             @click="handleBackdropClick($event)" style="display: none;">
            <div class="flex h-full w-full flex-col bg-black">
                <!-- Mobile Header -->
                <div class="flex items-center justify-between bg-zinc-900/95 px-4 py-3 backdrop-blur-sm">
                    <button @click="handleClose()"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-800 text-zinc-300 transition-colors hover:bg-zinc-700 active:bg-zinc-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <h2 class="text-lg font-semibold text-white">Design Review</h2>
                    <div class="flex space-x-2">
                        <button @click="toggleCommentFilter"
                            :class="showCommentFilter || hasActiveFilter ? 'bg-sky-500 text-white' : 'bg-zinc-800 text-zinc-300'"
                            class="flex h-9 w-9 items-center justify-center rounded-full transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                        </button>
                        <button @click="toggleAllComments"
                            :class="allCommentsHidden ? 'bg-amber-500 text-white' : 'bg-zinc-800 text-zinc-300'"
                            class="flex h-9 w-9 items-center justify-center rounded-full transition-colors">
                            <template x-if="allCommentsHidden">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9.27-3.11-10.5-7.5a10.05 10.05 0 013.03-4.57m3.39-2.05A9.953 9.953 0 0112 5c5 0 9.27 3.11 10.5 7.5a9.956 9.956 0 01-4.423 5.568M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
                                </svg>
                            </template>
                            <template x-if="!allCommentsHidden">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Mobile Image Container -->
                <div class="relative flex-1 overflow-hidden">
                    <div class="relative h-full w-full cursor-crosshair touch-none select-none" x-ref="mobileImageContainer"
                        @touchstart.prevent="handleTouchStart($event)"
                        @touchmove="isDragging && handleTouchMove($event)"
                        @touchend="handleTouchEnd($event)"
                        @touchcancel="cancelSelection()">

                        <img :src="currentImage" class="pointer-events-none h-full w-full object-contain"
                            alt="Design for review" draggable="false">

                        <!-- Mobile Comments -->
                        <template x-for="(comment, index) in visibleComments" :key="'mobile-' + comment.id">
                            <div class="absolute cursor-pointer border-2 border-sky-500 bg-sky-500/20 rounded-lg transition-all duration-200 hover:border-sky-600 hover:bg-sky-500/30"
                                :class="{ 'bg-sky-500/40 border-sky-700 z-20': activeComment === comment.id }"
                                :style="`left: ${comment.x}%; top: ${comment.y}%; width: ${comment.width}%; height: ${comment.height}%; min-width: 32px; min-height: 32px;`"
                                @click.stop="selectComment(comment)">
                                <span
                                    class="absolute -left-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-sky-500 text-xs font-bold text-white shadow-lg"
                                    x-text="comments.indexOf(comment) + 1"></span>
                            </div>
                        </template>

                        <!-- Mobile Selection Box -->
                        <template x-if="isSelecting">
                            <div class="pointer-events-none absolute border-2 border-dashed border-sky-500 bg-sky-500/10 rounded-lg"
                                :style="`left: ${selectionBox.x}%; top: ${selectionBox.y}%; width: ${selectionBox.width}%; height: ${selectionBox.height}%;`">
                            </div>
                        </template>

                    </div>
                </div>
            </div>
        </div>


        <!-- Mobile Filter Modal -->
        <div x-show="showCommentFilter && isMobile" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[70] flex items-end bg-black/50 backdrop-blur-sm md:hidden" @click="showCommentFilter = false" style="display: none;">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="transform translate-y-full"
                 x-transition:enter-end="transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="transform translate-y-0"
                 x-transition:leave-end="transform translate-y-full"
                 class="w-full rounded-t-3xl bg-white px-4 pb-8 pt-6 shadow-2xl dark:bg-zinc-900" @click.stop>
                
                <!-- Handle -->
                <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-zinc-300 dark:bg-zinc-600"></div>
                
                <!-- Header -->
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Filter Comments</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Select which comments to show</p>
                </div>
                
                <!-- Content -->
                <div class="max-h-80 space-y-2 overflow-y-auto">
                    <template x-for="(comment, index) in comments" :key="'mobile-filter-' + comment.id">
                        <label class="flex cursor-pointer items-center gap-4 rounded-xl p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <input type="checkbox" :value="comment.id" x-model="selectedCommentIds"
                                class="h-5 w-5 rounded border-zinc-300 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100" 
                                   x-text="comment.text.slice(0, 60) + (comment.text.length > 60 ? '...' : '')"></p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Comment #<span x-text="index + 1"></span></p>
                            </div>
                        </label>
                    </template>
                </div>
            </div>
        </div>

    </div>
</body>

</html>
