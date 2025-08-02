<div class="max-w-6xl p-6 mx-auto">
    <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">
            Alpine Sortable Demo
        </h1>
        <p class="text-zinc-600 dark:text-zinc-400">
            Drag and drop items between columns. Cross-group sorting supported with full accessibility.
        </p>
        <button wire:click="addTodo"
            class="px-4 py-2 mt-4 font-medium text-white transition-colors rounded-lg bg-sky-600 hover:bg-sky-700">
            Add New Task
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3" x-sortable="todo">
        <!-- Todo Column -->
        <div class="bg-white border shadow-sm rounded-xl border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="flex items-center font-semibold text-zinc-900 dark:text-zinc-100">
                    <div class="w-3 h-3 mr-2 rounded-full bg-zinc-500"></div>
                    Todo
                    <span
                        class="px-2 py-1 ml-2 text-xs rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                        {{ count($todos) }}
                    </span>
                </h2>
            </div>
            <div x-sortable-group id="todos" data-container="todos" class="list-group min-h-[200px] space-y-3 p-4"
                wire:key="todos">
                @foreach ($todos as $todo)
                    <div x-sortable:item="{{ $todo['id'] }}" :id="$id('sortable-item')"
                        class="p-3 transition-shadow border rounded-lg cursor-move list-group-item group border-zinc-200 bg-zinc-50 hover:shadow-md dark:border-zinc-600 dark:bg-zinc-700"
                        tabindex="0" role="button" aria-label="Draggable task: {{ $todo['title'] }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $todo['title'] }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    ID: {{ $todo['id'] }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div x-sortable:handle
                                    class="transition-opacity opacity-0 sortable-handle cursor-grab active:cursor-grabbing group-hover:opacity-100"
                                    aria-label="Drag handle">
                                    <svg class="w-4 h-4 text-zinc-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                        </path>
                                    </svg>
                                </div>
                                <button wire:click="removeItem('{{ $todo['id'] }}')"
                                    class="text-red-500 transition-opacity opacity-0 hover:text-red-700 group-hover:opacity-100"
                                    aria-label="Remove task">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if (empty($todos))
                    <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                        <p class="text-sm">No tasks yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- In Progress Column -->
        <div class="bg-white border shadow-sm rounded-xl border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="flex items-center font-semibold text-zinc-900 dark:text-zinc-100">
                    <div class="w-3 h-3 mr-2 rounded-full bg-amber-500"></div>
                    In Progress
                    <span
                        class="px-2 py-1 ml-2 text-xs rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-400">
                        {{ count($inProgress) }}
                    </span>
                </h2>
            </div>
            <div x-sortable-group id="in-progress" data-container="in-progress"
                class="list-group min-h-[200px] space-y-3 p-4" wire:key="in-progress">
                @foreach ($inProgress as $task)
                    <div x-sortable:item="{{ $task['id'] }}" :id="$id('sortable-item')"
                        class="p-3 transition-shadow border rounded-lg cursor-move list-group-item group border-amber-200 bg-amber-50 hover:shadow-md dark:border-amber-800 dark:bg-amber-900/20"
                        tabindex="0" role="button" aria-label="Draggable task: {{ $task['title'] }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $task['title'] }}
                                </p>
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                    ID: {{ $task['id'] }} • In Progress
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div x-sortable:handle
                                    class="transition-opacity opacity-0 sortable-handle cursor-grab active:cursor-grabbing group-hover:opacity-100"
                                    aria-label="Drag handle">
                                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                        </path>
                                    </svg>
                                </div>
                                <button wire:click="removeItem('{{ $task['id'] }}')"
                                    class="text-red-500 transition-opacity opacity-0 hover:text-red-700 group-hover:opacity-100"
                                    aria-label="Remove task">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if (empty($inProgress))
                    <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm">No tasks in progress</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Done Column -->
        <div class="bg-white border shadow-sm rounded-xl border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="flex items-center font-semibold text-zinc-900 dark:text-zinc-100">
                    <div class="w-3 h-3 mr-2 rounded-full bg-emerald-500"></div>
                    Done
                    <span
                        class="px-2 py-1 ml-2 text-xs rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400">
                        {{ count($done) }}
                    </span>
                </h2>
            </div>
            <div x-sortable-group id="done" data-container="done" class="list-group min-h-[200px] space-y-3 p-4"
                wire:key="done">
                @foreach ($done as $task)
                    <div x-sortable:item="{{ $task['id'] }}" :id="$id('sortable-item')"
                        class="p-3 transition-shadow border rounded-lg cursor-move list-group-item group border-emerald-200 bg-emerald-50 hover:shadow-md dark:border-emerald-800 dark:bg-emerald-900/20"
                        tabindex="0" role="button" aria-label="Draggable task: {{ $task['title'] }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $task['title'] }}
                                </p>
                                <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                    ID: {{ $task['id'] }} • Completed
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div x-sortable:handle
                                    class="transition-opacity opacity-0 sortable-handle cursor-grab active:cursor-grabbing group-hover:opacity-100"
                                    aria-label="Drag handle">
                                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                        </path>
                                    </svg>
                                </div>
                                <button wire:click="removeItem('{{ $task['id'] }}')"
                                    class="text-red-500 transition-opacity opacity-0 hover:text-red-700 group-hover:opacity-100"
                                    aria-label="Remove task">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if (empty($done))
                    <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm">No completed tasks</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Debug Info -->
    <div class="p-4 mt-8 text-xs rounded-lg bg-zinc-100 dark:bg-zinc-800">
        <h3 class="mb-2 font-semibold text-zinc-900 dark:text-zinc-100">Debug Info</h3>
        <div class="grid grid-cols-1 gap-4 text-zinc-600 dark:text-zinc-400 md:grid-cols-3">
            <div>
                <strong>Todo ({{ count($todos) }}):</strong>
                @foreach ($todos as $todo)
                    <div>{{ $todo['id'] }}: {{ $todo['title'] }}</div>
                @endforeach
            </div>
            <div>
                <strong>In Progress ({{ count($inProgress) }}):</strong>
                @foreach ($inProgress as $task)
                    <div>{{ $task['id'] }}: {{ $task['title'] }}</div>
                @endforeach
            </div>
            <div>
                <strong>Done ({{ count($done) }}):</strong>
                @foreach ($done as $task)
                    <div>{{ $task['id'] }}: {{ $task['title'] }}</div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .sortable-ghost {
            opacity: 0.5;
            transform: scale(0.95);
        }

        .sortable-chosen {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .sortable-drag {
            transform: rotate(5deg);
            z-index: 9999;
        }

        .sortable-placeholder {
            background: theme('colors.sky.100');
            border: 2px dashed theme('colors.sky.300');
            border-radius: 0.5rem;
            margin: 0.75rem 0;
        }

        .dark .sortable-placeholder {
            background: theme('colors.sky.900/20');
            border-color: theme('colors.sky.700');
        }

        @media (prefers-reduced-motion: reduce) {

            .sortable-ghost,
            .sortable-chosen,
            .sortable-drag {
                transform: none !important;
            }
        }
    </style>
</div>
