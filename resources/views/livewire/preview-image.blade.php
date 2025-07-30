<div x-data="designAnnotationApp('{{ $image }}', @js($comments), @js($this->getImageWidth()), @js($this->getImageHeight()), @js($this->getImageMetadataForJs()))" @keydown.arrow-up.window.prevent="handleArrowKeys($event)"
    @keydown.arrow-down.window.prevent="handleArrowKeys($event)"
    @keydown.arrow-left.window.prevent="handleArrowKeys($event)"
    @keydown.arrow-right.window.prevent="handleArrowKeys($event)" role="application" aria-label="Image Review Interface"
    class="min-w-screen flex min-h-screen">

    @livewire('feedback.create-feedback-modal')

    <div
        class="mx-auto flex min-h-screen min-w-full flex-col items-center justify-between overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
        <!-- Loading State -->
        <div x-show="!imageReady" x-cloak class="flex h-64 items-center justify-center">
            <div class="flex items-center gap-3 text-zinc-500 dark:text-zinc-400">
                <span class="text-sm">Getting things ready...</span>
            </div>
        </div>

        <!-- Image Viewport -->
        <div class="grow-1 flex h-full w-full flex-col items-center justify-center">
            <div x-show="imageReady" x-cloak>
                <div x-ref="scrollContainer" @wheel.prevent="handleWheel($event)" role="img"
                    :aria-label="`Design image for review. Zoom level: ${Math.round(zoomLevel * 100)}%. ${zoomLevel > 1 ? 'Use arrow keys to navigate.' : 'Click or drag to add comments.'}`"
                    :style="{
                        position: 'relative',
                        display: 'flex',
                        overflow: 'hidden',
                        background: 'rgba(0, 0, 0, 0.9)',
                        width: mainContainerWidth > 0 ? mainContainerWidth + 'px' : '95vw',
                        height: mainContainerHeight > 0 ? mainContainerHeight + 'px' : '90vh'
                    }">

                    <!-- Main Container - Fills viewport exactly -->
                    <div x-ref="mainContainer"
                        style="position: relative; overflow: hidden; background: transparent; width: 100%; height: 100%;">

                        <!-- Inner Wrapper - Absolute positioned, centered, scales with zoom -->
                        <div x-ref="innerWrapper"
                            style="position: absolute; left: 50%; top: 50%; background: transparent; transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), width 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), height 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);"
                            :style="{
                                width: innerWrapperWidth > 0 ? innerWrapperWidth + 'px' : '400px',
                                height: innerWrapperHeight > 0 ? innerWrapperHeight + 'px' : '300px',
                                transform: `translate(calc(-50% + ${panX}px), calc(-50% + ${panY}px))`
                            }"
                            @mousedown.prevent="startSelection($event)"
                            @touchstart.prevent="!isMobile && handleTouchStart($event)"
                            @mousemove.stop="isDragging && throttledUpdateSelection($event)"
                            @touchmove.stop="!isMobile && isDragging && handleTouchMove($event)"
                            @mouseup="endSelection($event)" @touchend.stop="!isMobile && handleTouchEnd($event)"
                            @mouseleave.stop="isDragging && endSelection($event)" .stop
                            @touchcancel,stop="!isMobile && cancelSelection()" @contextmenu.prevent>

                            <!-- Image fills the inner wrapper completely -->
                            <img :src="imageUrl" x-ref="image" @load="onImageLoad()"
                                style="
                                pointer-events: none;
                                display: block;
                                width: 100%;
                                height: 100%;
                                object-fit: fill;
                                transition: opacity 0.2s ease;
                                opacity: 1;
                                user-drag: none;
                                -webkit-user-drag: none;
                                -khtml-user-drag: none;
                                -moz-user-drag: none;
                                -o-user-drag: none;
                             "
                                alt="Design for review" draggable="false">

                            <!-- Comments Overlay (hidden when zoomed) -->
                            <template x-for="(comment, index) in visibleComments" :key="comment.id">
                                <div x-show="!isZoomed"
                                    class="absolute cursor-pointer border-2 border-sky-500 bg-sky-500/20 transition-all duration-200 hover:z-10 hover:border-sky-600 hover:bg-sky-500/30 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                                    :style="`left: ${comment.x}%; top: ${comment.y}%; width: ${comment.width}%; height: ${comment.height}%; min-width: 20px; min-height: 20px;`"
                                    @click="selectComment(comment)" @keydown.enter="selectComment(comment)"
                                    @keydown.space.prevent="selectComment(comment)" tabindex="0" role="button"
                                    :aria-label="`Comment ${index + 1}: ${comment.text?.slice(0, 50) || 'No text'}${comment.text?.length > 50 ? '...' : ''}`">
                                    <span
                                        class="absolute -left-3 -top-3 flex h-6 w-6 items-center justify-center rounded-full bg-sky-500 text-xs font-bold text-white shadow-md"
                                        x-text="index + 1" aria-hidden="true"></span>
                                </div>
                            </template>

                            <!-- Enhanced Selection Box with Animation -->
                            <template x-if="isSelecting">
                                <div class="pointer-events-none absolute animate-pulse rounded border-2 border-dashed border-blue-400 bg-blue-400/20 transition-all duration-150"
                                    :style="`left: ${selectionBox.x}%; top: ${selectionBox.y}%; width: ${selectionBox.width}%; height: ${selectionBox.height}%;`"
                                    role="presentation" aria-label="Selection area for comment">
                                    <!-- Selection corners for visual feedback -->
                                    <div class="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-blue-500"></div>
                                    <div class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-blue-500"></div>
                                    <div class="absolute -bottom-1 -left-1 h-2 w-2 rounded-full bg-blue-500"></div>
                                    <div class="absolute -bottom-1 -right-1 h-2 w-2 rounded-full bg-blue-500"></div>
                                </div>
                            </template>
                        </div>
                        <!-- End Inner Wrapper -->


                        <!-- Selection Mode Indicator -->
                        <div x-show="showSelectionMode" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute left-1/2 top-4 z-30 -translate-x-1/2 transform rounded-full bg-blue-500 px-4 py-2 text-sm text-white shadow-lg">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 animate-pulse rounded-full bg-white"></div>
                                <span>Drag to select area</span>
                            </div>
                        </div>

                        <!-- Context Hints for Mobile -->
                        <div x-show="isMobile && isZoomed && currentGesture === null && !showSelectionMode"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-2"
                            class="absolute bottom-20 left-1/2 -translate-x-1/2 transform rounded-lg border border-gray-700/50 bg-gray-900/80 px-3 py-2 text-xs text-white shadow-lg backdrop-blur-sm">
                            <div class="flex items-center gap-2">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 4v16l13-8z" />
                                </svg>
                                <span>Tap empty area → Drag to select</span>
                            </div>
                        </div>

                        <!-- Desktop Mode Indicator -->
                        <div x-show="!isMobile && showModeIndicator"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-4 top-4 z-30 rounded-lg border px-3 py-2 text-sm font-medium shadow-lg backdrop-blur-sm"
                            :class="{
                                'bg-blue-500 text-white border-blue-400': currentMode === 'select',
                                'bg-orange-500 text-white border-orange-400': currentMode === 'pan'
                            }">
                            <div class="flex items-center gap-2">
                                <template x-if="currentMode === 'select'">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                        </svg>
                                        <span>Selection Mode</span>
                                    </div>
                                </template>
                                <template x-if="currentMode === 'pan'">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                        </svg>
                                        <span>Pan Mode</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Desktop Instructions -->
                        <div x-show="!isMobile && isZoomed && !showModeIndicator && !isDragging"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-2"
                            class="absolute bottom-20 left-1/2 -translate-x-1/2 transform rounded-lg border border-gray-700/50 bg-gray-900/80 px-3 py-2 text-xs text-white shadow-lg backdrop-blur-sm">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1">
                                    <kbd
                                        class="rounded border border-gray-600 bg-gray-700 px-1.5 py-0.5 text-xs">Space</kbd>
                                    <span>+ drag to pan</span>
                                </div>
                                <div class="text-gray-400">•</div>
                                <div class="flex items-center gap-1">
                                    <span>Drag to select</span>
                                </div>
                            </div>
                        </div>

                        <!-- Gesture State Indicator (Debug/Development) -->
                        <div x-show="currentGesture && window.location.hostname === 'localhost'"
                            class="absolute right-4 top-4 rounded border border-gray-600 bg-black/70 px-2 py-1 font-mono text-xs text-white">
                            <div class="flex flex-col gap-1">
                                <div>State: <span class="text-blue-300" x-text="currentGesture || 'idle'"></span>
                                </div>
                                <div>Zoom: <span class="text-green-300"
                                        x-text="Math.round(zoomLevel * 100) + '%'"></span>
                                </div>
                                <div>Sequence: <span class="text-yellow-300"
                                        x-text="gestureSequence.slice(-3).join(' → ')"></span></div>
                            </div>
                        </div>

                    </div>
                    <!-- End Main Container -->
                </div>
                <!-- End Viewport -->
            </div>
        </div>

        <!-- Toolbar -->

        <div
            class="flex h-12 w-full grow-0 items-center justify-center gap-3 place-self-end rounded-b-lg border-t border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
            <!-- Comment Filter -->
            <div class="relative" @click.outside="showCommentFilter = false">
                <button @click="toggleCommentFilter" @touchstart.passive @touchend.passive="toggleCommentFilter"
                    :class="showCommentFilter || hasActiveFilter ?
                        'bg-sky-500 hover:bg-sky-400 text-white' :
                        'bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300'"
                    class="flex h-10 w-10 items-center justify-center rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-sky-500 sm:h-8 sm:w-8"
                    x-tooltip="'Filter comments'" aria-label="Toggle comment filter">
                    <svg class="h-5 w-5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Dropdown positioned above footer -->
                <div x-show="showCommentFilter" x-cloak x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    x-trap="showCommentFilter"
                    class="absolute bottom-full left-1/2 z-50 mb-2 hidden w-56 -translate-x-1/2 space-y-1 rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-800 sm:block"
                    role="menu" aria-label="Comment filter options">
                    <div class="mb-2">
                        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Filter Comments</h4>
                    </div>
                    <div class="max-h-48 space-y-2 overflow-y-auto">
                        <template x-for="(comment, index) in comments" :key="comment.id">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded p-2 text-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                role="menuitemcheckbox" :aria-checked="selectedCommentIds.includes(comment.id)">
                                <input type="checkbox" :value="comment.id" x-model="selectedCommentIds"
                                    class="mt-0.5 rounded border-zinc-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-zinc-600">
                                <span x-text="comment.text?.slice(0, 35) + (comment.text?.length > 35 ? '...' : '')"
                                    class="flex-1 text-zinc-700 dark:text-zinc-300"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Show/Hide Comments -->
            <button @click="toggleAllComments" @touchstart.passive @touchend.passive="toggleAllComments"
                :class="allCommentsHidden ?
                    'bg-sky-500 text-white hover:bg-sky-400' :
                    'bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300'"
                class="flex h-10 w-10 items-center justify-center rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-sky-500 sm:h-8 sm:w-8"
                x-tooltip="allCommentsHidden ? 'Show all comments' : 'Hide all comments'"
                :aria-label="allCommentsHidden ? 'Show all comments' : 'Hide all comments'">
                <template x-if="allCommentsHidden">
                    <svg class="h-5 w-5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9.27-3.11-10.5-7.5a10.05 10.05 0 013.03-4.57m3.39-2.05A9.953 9.953 0 0112 5c5 0 9.27 3.11 10.5 7.5a9.956 9.956 0 01-4.423 5.568M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" />
                    </svg>
                </template>
                <template x-if="!allCommentsHidden">
                    <svg class="h-5 w-5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                    </svg>
                </template>
            </button>

            <!-- Enhanced Zoom Controls with Gesture Status -->
            <div class="flex items-center gap-1">
                <button @click="zoomOut()" @touchstart.passive @touchend.passive="zoomOut()" :disabled="!canZoomOut"
                    :class="!canZoomOut ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:bg-zinc-800 dark:text-zinc-300 sm:h-8 sm:w-8"
                    x-tooltip="'Zoom out'" aria-label="Zoom out">
                    <svg class="h-5 w-5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M8 11h6" />
                    </svg>
                </button>

                <button @click="resetZoom()" @touchstart.passive @touchend.passive="resetZoom()"
                    :class="[
                        zoomLevel === 1 ? 'opacity-50' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700',
                        currentGesture === 'pinching' ? 'ring-2 ring-blue-400 bg-blue-50 dark:bg-blue-900/20' : ''
                    ]"
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:bg-zinc-800 dark:text-zinc-300 sm:h-8 sm:w-8"
                    x-tooltip="zoomLevel > 1 ? 'Reset zoom (100%). Pinch to zoom on mobile.' : 'Reset zoom to 100%'"
                    aria-label="Reset zoom">
                    <span class="text-xs font-bold" x-text="`${Math.round(zoomLevel * 100)}%`"></span>
                </button>

                <button @click="zoomIn()" @touchstart.passive @touchend.passive="zoomIn()" :disabled="!canZoomIn"
                    :class="!canZoomIn ? 'opacity-50 cursor-not-allowed' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:bg-zinc-800 dark:text-zinc-300 sm:h-8 sm:w-8"
                    x-tooltip="'Zoom in'" aria-label="Zoom in">
                    <svg class="h-5 w-5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M8 11h6" />
                        <path d="M11 8v6" />
                    </svg>
                </button>

                <!-- Mobile Gesture Hint -->
                <template x-if="isMobile && gestureSequence.length === 0">
                    <div class="ml-2 hidden text-xs text-zinc-500 dark:text-zinc-400 sm:block">
                        Pinch to zoom
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Mobile Comment Filter Modal -->
    <div x-show="showCommentFilter && isMobile" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-end bg-black/50 backdrop-blur-sm"
        @click="showCommentFilter = false" role="dialog" aria-modal="true" aria-labelledby="filter-modal-title">

        <div class="w-full rounded-t-3xl bg-white px-4 pb-8 pt-6 shadow-2xl dark:bg-zinc-900" @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-y-0"
            x-transition:leave-end="transform translate-y-full" role="document">

            <!-- Handle -->
            <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-zinc-300 dark:bg-zinc-600" aria-hidden="true">
            </div>

            <!-- Header -->
            <div class="mb-4">
                <h3 id="filter-modal-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Filter
                    Comments</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Select which comments to show</p>
            </div>

            <!-- Content -->
            <div class="max-h-[60vh] space-y-3 overflow-y-auto">
                <template x-for="(comment, index) in comments" :key="comment.id">
                    <label
                        class="flex cursor-pointer items-start gap-4 rounded-xl p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                        role="menuitemcheckbox" :aria-checked="selectedCommentIds.includes(comment.id)">
                        <input type="checkbox" :value="comment.id" x-model="selectedCommentIds"
                            class="mt-1 h-5 w-5 rounded border-zinc-300 text-sky-600 shadow-sm focus:ring-sky-500 dark:border-zinc-600">
                        <div class="flex-1">
                            <span x-text="comment.text?.slice(0, 50) + (comment.text?.length > 50 ? '...' : '')"
                                class="block text-zinc-900 dark:text-zinc-100"></span>
                            <span x-text="`Comment ${index + 1}`"
                                class="text-xs text-zinc-500 dark:text-zinc-400"></span>
                        </div>
                    </label>
                </template>

                <template x-if="comments.length === 0">
                    <div class="py-8 text-center">
                        <p class="text-zinc-500 dark:text-zinc-400">No comments available</p>
                    </div>
                </template>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4">
                <button @click="showCommentFilter = false"
                    class="flex-1 rounded-xl bg-zinc-100 py-3 text-center font-medium text-zinc-700 transition-colors hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    type="button">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>
