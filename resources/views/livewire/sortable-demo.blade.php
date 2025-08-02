<div class="w-full p-4 mx-auto md:p-6">
    {{-- <div class="mb-6 md:mb-8">
        <h1 class="mb-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100 md:text-3xl">
            Mobile Kanban Board
        </h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 md:text-base">
            Drag and drop items between columns. Optimized for mobile with touch-friendly interactions.
        </p>

        <!-- Keyboard shortcuts help -->
        <div class="mt-2" x-data="{ showKeyboardHelp: false }" wire:ignore>
            <button @click="showKeyboardHelp = !showKeyboardHelp"
                class="text-xs underline rounded text-zinc-500 hover:text-zinc-700 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:text-zinc-400 dark:hover:text-zinc-200"
                aria-expanded="false" :aria-expanded="showKeyboardHelp.toString()">
                <span x-show="!showKeyboardHelp">Show keyboard shortcuts</span>
                <span x-show="showKeyboardHelp" x-cloak>Hide keyboard shortcuts</span>
            </button>

            <div x-show="showKeyboardHelp" x-cloak x-collapse
                class="p-3 mt-2 text-xs border rounded-lg bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    <div>
                        <strong class="text-zinc-900 dark:text-zinc-100">Navigation:</strong>
                        <ul class="mt-1 space-y-1 text-zinc-600 dark:text-zinc-400">
                            <li><kbd class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">Tab</kbd> Navigate
                                between tasks</li>
                            <li><kbd class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">Enter/Space</kbd>
                                Focus drag handle</li>
                            <li><kbd class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">↑/↓</kbd> Move
                                between tasks</li>
                        </ul>
                    </div>
                    <div>
                        <strong class="text-zinc-900 dark:text-zinc-100">Actions:</strong>
                        <ul class="mt-1 space-y-1 text-zinc-600 dark:text-zinc-400">
                            <li><kbd class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">→</kbd> Move task
                                to next column</li>
                            <li><kbd
                                    class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">Delete/Backspace</kbd>
                                Remove task</li>
                            <li><kbd class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">Drag</kbd> Reorder
                                tasks</li>
                        </ul>
                    </div>
                </div>
                <div class="pt-2 mt-2 border-t border-zinc-200 dark:border-zinc-600">
                    <strong class="text-zinc-900 dark:text-zinc-100">Mobile:</strong>
                    <span class="text-zinc-600 dark:text-zinc-400">Swipe right to move forward, swipe left to delete,
                        long press for options</span>
                </div>
            </div>
        </div>
        <!-- Screen reader announcements area -->
        <div aria-live="polite" aria-atomic="true" class="sr-only" id="announcements"></div>

        <!-- Mobile-optimized FAB for adding tasks -->
        <button wire:click="addTodo"
            class="fixed z-50 flex items-center justify-center text-white transition-all duration-200 rounded-full shadow-lg bottom-6 right-6 h-14 w-14 bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-300 active:scale-95 md:relative md:bottom-auto md:right-auto md:z-auto md:mt-4 md:h-auto md:w-auto md:rounded-lg md:px-4 md:py-2 md:shadow-none md:focus:ring-2"
            aria-label="Add New Task"
            @click="
                setTimeout(() => {
                    document.getElementById('announcements').textContent = 'New task added';
                }, 100);
            ">
            <!-- Mobile: Show only plus icon -->
            <svg class="w-6 h-6 md:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <!-- Desktop: Show text -->
            <span class="hidden font-medium md:inline">Add New Task</span>
        </button>
    </div> --}}

    <!-- Horizontal scrolling columns for both mobile and desktop -->
    <x-sortable.container group-name="todo" class="w-full">
        <!-- Column container with scroll snap -->
        <div class="flex gap-6 pb-4 overflow-x-auto snap-x snap-mandatory scroll-smooth scrollbar-hide"
            x-data="{
                currentColumnIndex: 0,
                totalColumns: {{ count($this->columns) }},
                updateColumnIndex() {
                    const container = this.$el;
                    const scrollLeft = container.scrollLeft;
                    // Mobile: 85vw per column, Desktop: Column width based on total columns
                    const isMobile = window.innerWidth < 768;
                    const columnWidth = isMobile ?
                        container.clientWidth * 0.85 :
                        container.clientWidth / Math.min(this.totalColumns, 4); // Max 4 columns visible on desktop
                    const gap = isMobile ? 16 : 24; // 1rem = 16px, 1.5rem = 24px
                    this.currentColumnIndex = Math.round(scrollLeft / (columnWidth + gap));
                },
                scrollToColumn(index) {
                    const container = this.$refs.columnsContainer;
                    const isMobile = window.innerWidth < 768;
                    const columnWidth = isMobile ?
                        container.clientWidth * 0.85 :
                        container.clientWidth / Math.min(this.totalColumns, 4);
                    const gap = isMobile ? 16 : 24;
                    container.scrollTo({
                        left: index * (columnWidth + gap),
                        behavior: 'smooth'
                    });
                    this.currentColumnIndex = index;
                },
                scrollLeft() {
                    if (this.currentColumnIndex > 0) {
                        this.scrollToColumn(this.currentColumnIndex - 1);
                    }
                },
                scrollRight() {
                    if (this.currentColumnIndex < this.totalColumns - 1) {
                        this.scrollToColumn(this.currentColumnIndex + 1);
                    }
                }
            }" x-ref="columnsContainer" @scroll.passive="updateColumnIndex()"
            @keydown.arrow-left.window.prevent="scrollLeft()" @keydown.arrow-right.window.prevent="scrollRight()">

            <!-- Desktop arrow navigation -->
            <div
                class="hidden md:fixed md:bottom-8 md:left-1/2 md:z-40 md:flex md:-translate-x-1/2 md:transform md:gap-3">
                <!-- Left arrow -->
                <button @click="scrollLeft()" :disabled="currentColumnIndex === 0"
                    class="p-3 transition-all duration-200 border rounded-lg shadow-lg border-zinc-200 bg-white/90 backdrop-blur-sm hover:bg-white disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950/90 dark:hover:bg-zinc-900"
                    aria-label="Scroll to previous column">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                </button>

                <!-- Right arrow -->
                <button @click="scrollRight()" :disabled="currentColumnIndex >= totalColumns - 1"
                    class="p-3 transition-all duration-200 border rounded-lg shadow-lg border-zinc-200 bg-white/90 backdrop-blur-sm hover:bg-white disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950/90 dark:hover:bg-zinc-900"
                    aria-label="Scroll to next column">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Column navigation indicators (mobile only) -->
            <div class="fixed z-40 flex p-2 space-x-2 transform -translate-x-1/2 rounded-lg bottom-8 left-1/2 bg-zinc-900/80 backdrop-blur-sm md:hidden"
                x-show="true" x-transition.opacity x-data="{
                    columns: @js($this->columns)
                }">
                <template x-for="(column, index) in columns" :key="index">
                    <button
                        @click="
                        const container = $refs.columnsContainer;
                        const isMobile = window.innerWidth < 768;
                        const columnWidth = isMobile ?
                            container.clientWidth * 0.85 :
                            container.clientWidth / 3;
                        const gap = isMobile ? 16 : 24;
                        container.scrollTo({
                            left: index * (columnWidth + gap),
                            behavior: 'smooth'
                        });
                        currentColumnIndex = index;
                        if (navigator.vibrate) navigator.vibrate(25);
                    "
                        class="relative p-2 transition-all duration-200 touch-manipulation focus:outline-none"
                        :aria-label="`Navigate to ${column.name} column`">
                        <!-- Visual indicator bar -->
                        <div class="transition-all duration-200 rounded-lg"
                            :class="{
                                [`w-6 h-2 bg-${column.color}-500`]: currentColumnIndex === index,
                                    [`w-2 h-2 bg-${column.color}-500 opacity-60`]: currentColumnIndex !== index
                            }">
                        </div>
                        <!-- Column name tooltip -->
                        <div x-show="currentColumnIndex === index"
                            class="absolute px-2 py-1 text-xs font-medium text-white transform -translate-x-1/2 rounded-lg shadow-lg pointer-events-none -top-8 left-1/2 whitespace-nowrap bg-zinc-800/90"
                            x-transition.opacity.duration.200ms x-text="column.name">
                        </div>
                    </button>
                </template>
            </div>

            @foreach ($this->columns as $column)
                <x-sortable.group :id="$column['id']" :container="$column['id']" :title="$column['name']" :count="count($this->{$column['property']})"
                    :color="$column['color']" :wire-key="$column['id']" :sort-enabled="$this->isSortingEnabled()">

                    @foreach ($this->{$column['property']} as $task)
                        <x-sortable.item :item-id="$task['id']" :title="$task['title']" :subtitle="'ID: ' . $task['id'] . ($column['id'] === 'done' ? ' • Completed' : ($column['id'] === 'in-progress' ? ' • In Progress' : ''))" :color="$column['color']"
                            :current-column="$column['id']" :available-columns="$this->getAvailableColumnsFor($column['id'])" :sort-enabled="$this->isSortingEnabled()" />
                    @endforeach

                    @if (empty($this->{$column['property']}))
                        <x-slot name="emptyState">
                            @if ($column['id'] === 'todos')
                                <svg class="w-10 h-10 mx-auto mb-3 opacity-50 md:h-12 md:w-12" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                                <p class="text-xs md:text-sm">No tasks yet</p>
                                <p class="mt-1 text-xs opacity-75 md:hidden">Tap + to add tasks</p>
                            @elseif($column['id'] === 'in-progress')
                                <svg class="w-10 h-10 mx-auto mb-3 opacity-50 md:h-12 md:w-12" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-xs md:text-sm">No tasks in progress</p>
                                <p class="mt-1 text-xs opacity-75 md:hidden">Drag tasks here</p>
                            @else
                                <svg class="w-10 h-10 mx-auto mb-3 opacity-50 md:h-12 md:w-12" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-xs md:text-sm">No completed tasks</p>
                                <p class="mt-1 text-xs opacity-75 md:hidden">Complete tasks appear here</p>
                            @endif
                        </x-slot>
                    @endif
                </x-sortable.group>
            @endforeach
        </div>
    </x-sortable.container>

    <!-- Pure JS modal is injected by alpine-sortable.js -->

    <style>
        /* Enhanced mobile-first drag states */
        .sortable-ghost {
            opacity: 0.3;
            transform: scale(0.95);
            transition: all 0.2s ease;
        }

        .sortable-chosen {
            transform: scale(1.05);
            box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.15), 0 10px 20px -5px rgba(0, 0, 0, 0.1);
            z-index: 999;
            transition: all 0.2s ease;
        }

        .sortable-drag {
            transform: rotate(2deg) scale(1.05);
            z-index: 9999;
            opacity: 0.9;
            transition: all 0.2s ease;
        }

        .sortable-placeholder {
            background: theme('colors.sky.100');
            border: 2px dashed theme('colors.sky.300');
            border-radius: 0.75rem;
            margin: 0.5rem 0;
            min-height: 60px;
            transition: all 0.2s ease;
        }

        .dark .sortable-placeholder {
            background: theme('colors.sky.900/20');
            border-color: theme('colors.sky.700');
        }

        /* Mobile-specific enhancements */
        @media (max-width: 768px) {
            .sortable-chosen {
                transform: scale(1.08);
                box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.2);
            }

            .sortable-drag {
                transform: rotate(1deg) scale(1.08);
            }

            .sortable-placeholder {
                min-height: 70px;
                border-width: 3px;
            }

            /* Mobile column scroll snap */
            .snap-x {
                scroll-snap-type: x mandatory;
            }

            .snap-center {
                scroll-snap-align: center;
            }

            /* Touch-friendly scrollbars */
            .overflow-x-auto::-webkit-scrollbar {
                height: 6px;
            }

            .overflow-x-auto::-webkit-scrollbar-track {
                background: transparent;
            }

            .overflow-x-auto::-webkit-scrollbar-thumb {
                background: theme('colors.zinc.300');
                border-radius: 3px;
            }

            .dark .overflow-x-auto::-webkit-scrollbar-thumb {
                background: theme('colors.zinc.600');
            }

            /* Smooth scrolling for mobile */
            .scroll-smooth {
                scroll-behavior: smooth;
            }

            /* Enhanced mobile touch targets */
            .touch-manipulation {
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
            }

            /* Mobile column width consistency */
            .min-w-\[85vw\] {
                min-width: 85vw;
            }
        }

        /* Performance optimization for 60fps animations */
        .sortable-ghost,
        .sortable-chosen,
        .sortable-drag {
            will-change: transform, opacity;
            backface-visibility: hidden;
        }

        /* Enhanced column scrolling */
        .list-group {
            /* Custom scrollbar for better UX */
            scrollbar-width: thin;
            scrollbar-color: theme('colors.zinc.300') transparent;
        }

        .list-group::-webkit-scrollbar {
            width: 6px;
        }

        .list-group::-webkit-scrollbar-track {
            background: transparent;
        }

        .list-group::-webkit-scrollbar-thumb {
            background: theme('colors.zinc.300');
            border-radius: 3px;
        }

        .list-group::-webkit-scrollbar-thumb:hover {
            background: theme('colors.zinc.400');
        }

        .dark .list-group {
            scrollbar-color: theme('colors.zinc.600') transparent;
        }

        .dark .list-group::-webkit-scrollbar-thumb {
            background: theme('colors.zinc.600');
        }

        .dark .list-group::-webkit-scrollbar-thumb:hover {
            background: theme('colors.zinc.500');
        }

        /* Smooth scroll snap behavior */
        .snap-y {
            scroll-snap-type: y mandatory;
        }

        .snap-start {
            scroll-snap-align: start;
        }

        /* Ensure scroll snap works with spacing */
        .list-group>*+* {
            scroll-snap-stop: always;
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {

            .sortable-ghost,
            .sortable-chosen,
            .sortable-drag,
            .sortable-placeholder {
                transform: none !important;
                transition: none !important;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .sortable-placeholder {
                border-width: 3px;
                border-style: solid;
            }
        }

        /* Mobile handle feedback states */
        .handle-active {
            background-color: theme('colors.zinc.200') !important;
            transform: scale(0.95);
        }

        .dark .handle-active {
            background-color: theme('colors.zinc.600') !important;
        }

        .handle-long-press {
            background-color: theme('colors.sky.200') !important;
            transform: scale(1.1);
            box-shadow: 0 0 0 2px theme('colors.sky.400');
        }

        .dark .handle-long-press {
            background-color: theme('colors.sky.700') !important;
            box-shadow: 0 0 0 2px theme('colors.sky.500');
        }

        /* Dragging state for items */
        .dragging {
            z-index: 1000;
            pointer-events: none;
        }

        /* Loading states for better UX */
        .sortable-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .sortable-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid theme('colors.zinc.200');
            border-top: 2px solid theme('colors.sky.500');
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Screen reader only content */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Enhanced focus styles for accessibility */
        .focus\:ring-sky-500:focus {
            ring-color: theme('colors.sky.500');
        }

        .focus\:ring-red-500:focus {
            ring-color: theme('colors.red.500');
        }

        /* High contrast focus indicators */
        @media (prefers-contrast: high) {

            .focus\:ring-2:focus,
            .focus\:ring-4:focus {
                ring-width: 3px;
                ring-color: #000;
            }

            .dark .focus\:ring-2:focus,
            .dark .focus\:ring-4:focus {
                ring-color: #fff;
            }
        }

        /* Keyboard navigation improvements */
        .list-group-item:focus-within {
            ring-width: 2px;
            ring-color: theme('colors.sky.500');
            ring-offset-width: 2px;
        }
    </style>
