<div class="max-w-6xl p-4 mx-auto md:p-6">
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

    <!-- Mobile: Horizontal scrolling columns, Desktop: Grid layout -->
    <div class="md:grid md:grid-cols-3 md:gap-6" x-sortable="todo"
        @sortable:sort.window="
            console.log('SORTABLE:SORT EVENT:', $event.detail);
            // Handle the sort action based on event detail
            const { action, item, from, to } = $event.detail;
            if (action === 'move') {
                $wire.moveToColumn(item, to);
            } else if (action === 'reorder') {
                // Handle reorder within same column if needed
                console.log('Reorder within column:', from);
            }
        ">
        <!-- Mobile column container with scroll snap -->
        <div class="flex gap-4 pb-4 overflow-x-auto snap-x snap-mandatory scroll-smooth md:contents"
            x-data="{
                swipeStartX: 0,
                swipeStartY: 0,
                pullStartY: 0,
                pullDistance: 0,
                isPulling: false,
                refreshThreshold: 100,
                currentColumnIndex: 0,
                totalColumns: 3,
                updateColumnIndex() {
                    const container = this.$el;
                    const scrollLeft = container.scrollLeft;
                    const columnWidth = container.clientWidth * 0.85; // 85vw per column
                    this.currentColumnIndex = Math.round(scrollLeft / (columnWidth + 16)); // 16px gap
                }
            }" x-ref="columnsContainer" @scroll.passive="updateColumnIndex()"
            @touchstart.passive="
                swipeStartX = $event.touches[0].clientX;
                swipeStartY = $event.touches[0].clientY;
                pullStartY = $event.touches[0].clientY;
                isPulling = false;
             "
            @touchmove.passive="
                if ($event.touches[0] && window.scrollY === 0) {
                    pullDistance = Math.max(0, $event.touches[0].clientY - pullStartY);
                    isPulling = pullDistance > 20;
                }
             "
            @touchend.passive="
                if (isPulling && pullDistance > refreshThreshold) {
                    // Trigger refresh
                    $wire.refreshBoard();
                    if (navigator.vibrate) navigator.vibrate([50, 50, 50]);
                }
                pullDistance = 0;
                isPulling = false;
             ">
            <!-- Pull to refresh indicator -->
            <div class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center transition-all duration-200 bg-white dark:bg-zinc-800 md:hidden"
                :style="{ transform: `translateY(${Math.min(pullDistance * 0.5, 80)}px)`, opacity: isPulling ? 1 : 0 }"
                x-show="isPulling" x-transition.opacity>
                <div class="flex items-center p-4 text-zinc-600 dark:text-zinc-400">
                    <svg class="w-5 h-5 mr-2 animate-spin" :class="{ 'animate-spin': pullDistance > refreshThreshold }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    <span class="text-sm font-medium"
                        x-text="pullDistance > refreshThreshold ? 'Release to refresh' : 'Pull to refresh'"></span>
                </div>
            </div>

            <!-- Column navigation indicators (mobile only) -->
            <div class="fixed z-40 flex p-2 space-x-2 transform -translate-x-1/2 rounded-full bottom-8 left-1/2 bg-zinc-900/80 backdrop-blur-sm md:hidden"
                x-show="true" x-transition.opacity x-data="{
                    columns: [
                        { name: 'Todo', bgClass: 'bg-zinc-500' },
                        { name: 'In Progress', bgClass: 'bg-amber-500' },
                        { name: 'Done', bgClass: 'bg-emerald-500' }
                    ]
                }">
                <template x-for="(column, index) in columns" :key="index">
                    <button
                        @click="
                        const container = $refs.columnsContainer;
                        const columnWidth = container.clientWidth * 0.85 + 16; // 85vw + gap
                        container.scrollTo({
                            left: index * columnWidth,
                            behavior: 'smooth'
                        });
                        currentColumnIndex = index;
                        if (navigator.vibrate) navigator.vibrate(25);
                    "
                        class="relative p-2 transition-all duration-200 touch-manipulation focus:outline-none"
                        :aria-label="`Navigate to ${column.name} column`">
                        <!-- Visual indicator bar -->
                        <div class="transition-all duration-200 rounded-full"
                            :class="{
                                [`w-6 h-2 ${column.bgClass}`]: currentColumnIndex === index,
                                    [`w-2 h-2 ${column.bgClass} opacity-60`]: currentColumnIndex !== index
                            }">
                        </div>
                        <!-- Column name tooltip -->
                        <div x-show="currentColumnIndex === index"
                            class="absolute px-2 py-1 text-xs font-medium text-white transform -translate-x-1/2 rounded-md shadow-lg pointer-events-none -top-8 left-1/2 whitespace-nowrap bg-zinc-800/90"
                            x-transition.opacity.duration.200ms x-text="column.name">
                        </div>
                    </button>
                </template>
            </div>

            <!-- Mobile layout uses horizontal scroll -->
            <!-- Todo Column -->
            <div class="min-w-[85vw] flex-shrink-0 snap-center rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 md:min-w-0 md:snap-none"
                role="region" aria-labelledby="todo-column-heading">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 md:p-4">
                    <h2 id="todo-column-heading"
                        class="flex items-center text-lg font-bold text-zinc-900 dark:text-zinc-100 md:text-base md:font-semibold">
                        <div class="w-4 h-4 mr-3 rounded-full bg-zinc-500 md:mr-2 md:h-3 md:w-3" aria-hidden="true">
                        </div>
                        Todo
                        <span
                            class="ml-3 rounded-full bg-zinc-100 px-3 py-1.5 text-sm font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400 md:ml-2 md:px-2 md:py-1 md:text-xs"
                            aria-label="{{ count($todos) }} tasks">
                            {{ count($todos) }}
                        </span>
                    </h2>
                </div>
                <div x-sortable-group id="todos" data-container="todos"
                    class="list-group min-h-[200px] space-y-2 p-3 md:space-y-3 md:p-4" wire:key="todos">
                    @foreach ($todos as $todo)
                        <div x-sortable:item="{{ $todo['id'] }}" :id="$id('sortable-item')"
                            class="list-group-item group relative cursor-move touch-manipulation rounded-lg border border-zinc-200 bg-zinc-50 p-4 outline-none transition-all duration-200 hover:shadow-md focus:border-sky-500 focus:ring-2 focus:ring-sky-500 active:scale-[0.98] dark:border-zinc-600 dark:bg-zinc-700 md:p-3"
                            tabindex="0" role="button"
                            aria-label="Task: {{ $todo['title'] }}. Drag to move between columns, or use keyboard shortcuts."
                            aria-describedby="task-{{ $todo['id'] }}-actions"
                            @keydown.enter="$refs.dragHandle.focus()" @keydown.space.prevent="$refs.dragHandle.focus()"
                            @keydown.arrow-right.prevent="$wire.moveToColumn('{{ $todo['id'] }}', 'in-progress')" x-data="{
                                swipeDistance: 0,
                                showActions: false
                            }"
                            x-swipe="{
                            taskId: '{{ $todo['id'] }}',
                            taskTitle: '{{ addslashes($todo['title']) }}',
                            currentColumn: 'todos',
                            availableColumns: [
                                { id: 'in-progress', name: 'In Progress', color: 'amber' },
                                { id: 'done', name: 'Done', color: 'emerald' }
                            ]
                        }"
                            @swipe:move="swipeDistance = $event.detail.distance; showActions = Math.abs(swipeDistance) > 50"
                            @swipe:end="swipeDistance = 0; showActions = false">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 pr-2">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 md:text-sm">
                                        {{ $todo['title'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        ID: {{ $todo['id'] }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-1 md:space-x-2">
                                    <!-- Always visible drag handle on mobile for better UX -->
                                    <div x-sortable:handle x-ref="dragHandle"
                                        class="flex items-center justify-center w-12 h-12 transition-all duration-200 rounded-lg opacity-100 sortable-handle cursor-grab touch-manipulation bg-zinc-100 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-sky-500 active:cursor-grabbing dark:bg-zinc-700 dark:hover:bg-zinc-600 md:h-8 md:w-8 md:rounded-none md:bg-transparent md:opacity-0 md:hover:bg-transparent md:group-hover:opacity-100 md:dark:bg-transparent md:dark:hover:bg-transparent"
                                        role="button" tabindex="0"
                                        aria-label="Drag handle for {{ $todo['title'] }}. Use arrow keys to move between columns."
                                        @keydown.arrow-up.prevent="$el.closest('.list-group-item').previousElementSibling?.focus()"
                                        @keydown.arrow-down.prevent="$el.closest('.list-group-item').nextElementSibling?.focus()"
                                        @keydown.arrow-right.prevent="$wire.moveToColumn('{{ $todo['id'] }}', 'in-progress')"
                                        @keydown.arrow-left.prevent="$el.closest('.list-group-item').focus()">
                                        <svg class="w-5 h-5 text-zinc-400 md:h-4 md:w-4" fill="currentColor"
                                            viewBox="0 0 20 20" aria-hidden="true">
                                            <path
                                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                            </path>
                                        </svg>
                                    </div>
                                    <!-- Mobile: Show delete button on swipe or long press -->
                                </div>
                            </div>

                            <!-- Screen reader only action descriptions -->
                            <div id="task-{{ $todo['id'] }}-actions" class="sr-only">
                                Available actions: Right arrow to move to In Progress, Drag handle to reorder tasks. 
                                On mobile: Swipe to show move options.
                            </div>

                            <!-- Swipe action indicators for Todo column -->
                            <!-- Swipe right - Show Move Options -->
                            <div class="absolute top-0 left-0 z-10 flex items-center justify-center w-full h-full transition-opacity rounded-lg pointer-events-none bg-gradient-to-r from-sky-100 to-sky-200 dark:from-sky-800 dark:to-sky-700 md:hidden"
                                :class="{
                                    'opacity-80': swipeDistance > 100,
                                    'opacity-40': swipeDistance > 50 &&
                                        swipeDistance <= 100
                                }"
                                x-show="swipeDistance > 50" x-transition.opacity>
                                <div class="flex items-center text-sky-800 dark:text-sky-200">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <span class="text-sm font-medium">Move Options</span>
                                </div>
                            </div>

                            <!-- Swipe left - Show Move Options (same as right) -->
                            <div class="absolute top-0 left-0 z-10 flex items-center justify-center w-full h-full transition-opacity rounded-lg pointer-events-none bg-gradient-to-l from-sky-100 to-sky-200 dark:from-sky-800 dark:to-sky-700 md:hidden"
                                :class="{
                                    'opacity-80': swipeDistance < -100,
                                    'opacity-40': swipeDistance < -50 &&
                                        swipeDistance >= -100
                                }"
                                x-show="swipeDistance < -50" x-transition.opacity>
                                <div class="flex items-center text-sky-800 dark:text-sky-200">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <span class="text-sm font-medium">Move Options</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if (empty($todos))
                        <div class="py-6 text-center text-zinc-500 dark:text-zinc-400 md:py-8">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-50 md:h-12 md:w-12" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <p class="text-xs md:text-sm">No tasks yet</p>
                            <p class="mt-1 text-xs opacity-75 md:hidden">Tap + to add tasks</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- In Progress Column -->
            <div
                class="min-w-[85vw] flex-shrink-0 snap-center rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 md:min-w-0 md:snap-none">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 md:p-4">
                    <h2
                        class="flex items-center text-lg font-bold text-zinc-900 dark:text-zinc-100 md:text-base md:font-semibold">
                        <div class="w-4 h-4 mr-3 rounded-full bg-amber-500 md:mr-2 md:h-3 md:w-3"></div>
                        In Progress
                        <span
                            class="ml-3 rounded-full bg-amber-100 px-3 py-1.5 text-sm font-medium text-amber-600 dark:bg-amber-900 dark:text-amber-400 md:ml-2 md:px-2 md:py-1 md:text-xs">
                            {{ count($inProgress) }}
                        </span>
                    </h2>
                </div>
                <div x-sortable-group id="in-progress" data-container="in-progress"
                    class="list-group min-h-[200px] space-y-2 p-3 md:space-y-3 md:p-4" wire:key="in-progress">
                    @foreach ($inProgress as $task)
                        <div x-sortable:item="{{ $task['id'] }}" :id="$id('sortable-item')"
                            class="list-group-item group relative cursor-move touch-manipulation rounded-lg border border-amber-200 bg-amber-50 p-4 transition-all duration-200 hover:shadow-md active:scale-[0.98] dark:border-amber-800 dark:bg-amber-900/20 md:p-3"
                            tabindex="0" role="button" aria-label="Draggable task: {{ $task['title'] }}"
                            x-data="{
                                swipeDistance: 0,
                                showActions: false
                            }"
                            x-swipe="{
                            taskId: '{{ $task['id'] }}',
                            taskTitle: '{{ addslashes($task['title']) }}',
                            currentColumn: 'in-progress',
                            availableColumns: [
                                { id: 'todos', name: 'Todo', color: 'zinc' },
                                { id: 'done', name: 'Done', color: 'emerald' }
                            ],
                        }"
                            @swipe:move="swipeDistance = $event.detail.distance; showActions = Math.abs(swipeDistance) > 50"
                            @swipe:end="swipeDistance = 0; showActions = false">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 pr-2">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 md:text-sm">
                                        {{ $task['title'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        ID: {{ $task['id'] }} • In Progress
                                    </p>
                                </div>
                                <div class="flex items-center space-x-1 md:space-x-2">
                                    <div x-sortable:handle
                                        class="flex items-center justify-center w-12 h-12 transition-all duration-200 rounded-lg opacity-100 sortable-handle cursor-grab touch-manipulation bg-zinc-100 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-sky-500 active:cursor-grabbing dark:bg-zinc-700 dark:hover:bg-zinc-600 md:h-8 md:w-8 md:rounded-none md:bg-transparent md:opacity-0 md:hover:bg-transparent md:group-hover:opacity-100 md:dark:bg-transparent md:dark:hover:bg-transparent"
                                        aria-label="Drag handle">
                                        <svg class="w-5 h-5 text-amber-400 md:h-4 md:w-4" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path
                                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Swipe right - Show Move Options -->
                            <div class="absolute top-0 left-0 z-10 flex items-center justify-center w-full h-full transition-opacity rounded-lg pointer-events-none bg-gradient-to-r from-sky-100 to-sky-200 dark:from-sky-800 dark:to-sky-700 md:hidden"
                                :class="{
                                    'opacity-80': swipeDistance > 100,
                                    'opacity-40': swipeDistance > 50 &&
                                        swipeDistance <= 100
                                }"
                                x-show="swipeDistance > 50" x-transition.opacity>
                                <div class="flex items-center text-sky-800 dark:text-sky-200">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <span class="text-sm font-medium">Move Options</span>
                                </div>
                            </div>

                            <!-- Swipe left - Show Move Options (same as right) -->
                            <div class="absolute top-0 left-0 z-10 flex items-center justify-center w-full h-full transition-opacity rounded-lg pointer-events-none bg-gradient-to-l from-sky-100 to-sky-200 dark:from-sky-800 dark:to-sky-700 md:hidden"
                                :class="{
                                    'opacity-80': swipeDistance < -100,
                                    'opacity-40': swipeDistance < -50 &&
                                        swipeDistance >= -100
                                }"
                                x-show="swipeDistance < -50" x-transition.opacity>
                                <div class="flex items-center text-sky-800 dark:text-sky-200">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <span class="text-sm font-medium">Move Options</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if (empty($inProgress))
                        <div class="py-6 text-center text-zinc-500 dark:text-zinc-400 md:py-8">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-50 md:h-12 md:w-12" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-xs md:text-sm">No tasks in progress</p>
                            <p class="mt-1 text-xs opacity-75 md:hidden">Drag tasks here</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Done Column -->
            <div
                class="min-w-[85vw] flex-shrink-0 snap-center rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 md:min-w-0 md:snap-none">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 md:p-4">
                    <h2
                        class="flex items-center text-lg font-bold text-zinc-900 dark:text-zinc-100 md:text-base md:font-semibold">
                        <div class="w-4 h-4 mr-3 rounded-full bg-emerald-500 md:mr-2 md:h-3 md:w-3"></div>
                        Done
                        <span
                            class="ml-3 rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-medium text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400 md:ml-2 md:px-2 md:py-1 md:text-xs">
                            {{ count($done) }}
                        </span>
                    </h2>
                </div>
                <div x-sortable-group id="done" data-container="done"
                    class="list-group min-h-[200px] space-y-2 p-3 md:space-y-3 md:p-4" wire:key="done">
                    @foreach ($done as $task)
                        <div x-sortable:item="{{ $task['id'] }}" :id="$id('sortable-item')"
                            class="list-group-item group relative cursor-move touch-manipulation rounded-lg border border-emerald-200 bg-emerald-50 p-4 transition-all duration-200 hover:shadow-md active:scale-[0.98] dark:border-emerald-800 dark:bg-emerald-900/20 md:p-3"
                            tabindex="0" role="button" aria-label="Draggable task: {{ $task['title'] }}"
                            x-data="{
                                swipeDistance: 0,
                                showActions: false
                            }"
                            x-swipe="{
                            taskId: '{{ $task['id'] }}',
                            taskTitle: '{{ addslashes($task['title']) }}',
                            currentColumn: 'done',
                            availableColumns: [
                                { id: 'todos', name: 'Todo', color: 'zinc' },
                                { id: 'in-progress', name: 'In Progress', color: 'amber' }
                            ],
                        }"
                            @swipe:move="swipeDistance = $event.detail.distance; showActions = Math.abs(swipeDistance) > 50"
                            @swipe:end="swipeDistance = 0; showActions = false">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 pr-2">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 md:text-sm">
                                        {{ $task['title'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                        ID: {{ $task['id'] }} • Completed
                                    </p>
                                </div>
                                <div class="flex items-center space-x-1 md:space-x-2">
                                    <div x-sortable:handle
                                        class="flex items-center justify-center w-12 h-12 transition-all duration-200 rounded-lg opacity-100 sortable-handle cursor-grab touch-manipulation bg-zinc-100 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-sky-500 active:cursor-grabbing dark:bg-zinc-700 dark:hover:bg-zinc-600 md:h-8 md:w-8 md:rounded-none md:bg-transparent md:opacity-0 md:hover:bg-transparent md:group-hover:opacity-100 md:dark:bg-transparent md:dark:hover:bg-transparent"
                                        aria-label="Drag handle">
                                        <svg class="w-5 h-5 text-emerald-400 md:h-4 md:w-4" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path
                                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile swipe indicator -->
                            <div class="absolute inset-0 pointer-events-none md:hidden"
                                :style="{ transform: `translateX(${Math.max(-100, Math.min(100, swipeDistance * 0.3))}px)` }"
                                x-show="Math.abs(swipeDistance) > 10" x-transition.opacity>
                            </div>
                        </div>
                    @endforeach
                    @if (empty($done))
                        <div class="py-6 text-center text-zinc-500 dark:text-zinc-400 md:py-8">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-50 md:h-12 md:w-12" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-xs md:text-sm">No completed tasks</p>
                            <p class="mt-1 text-xs opacity-75 md:hidden">Complete tasks appear here</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

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
