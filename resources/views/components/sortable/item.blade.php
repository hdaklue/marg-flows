@props([
    'itemId' => '',
    'title' => '',
    'subtitle' => '',
    'color' => 'zinc',
    'currentColumn' => '',
    'availableColumns' => [],
    'class' => '',
    'ariaLabel' => '',
    'sortEnabled' => false,
])

@php
$colorClasses = [
    'zinc' => [
        'border' => 'border-zinc-200 dark:border-zinc-600',
        'bg' => 'bg-zinc-50 dark:bg-zinc-700',
        'hover' => 'md:hover:bg-zinc-100 md:dark:hover:bg-zinc-600',
        'handle' => 'bg-zinc-100 dark:bg-zinc-700',
    ],
    'amber' => [
        'border' => 'border-amber-200 dark:border-amber-800', 
        'bg' => 'bg-amber-50 dark:bg-amber-900/20',
        'hover' => 'md:hover:bg-amber-100 md:dark:hover:bg-amber-800/30',
        'handle' => 'bg-zinc-100 dark:bg-zinc-700',
    ],
    'blue' => [
        'border' => 'border-blue-200 dark:border-blue-800',
        'bg' => 'bg-blue-50 dark:bg-blue-900/20',
        'hover' => 'md:hover:bg-blue-100 md:dark:hover:bg-blue-800/30',
        'handle' => 'bg-zinc-100 dark:bg-zinc-700',
    ],
    'purple' => [
        'border' => 'border-purple-200 dark:border-purple-800',
        'bg' => 'bg-purple-50 dark:bg-purple-900/20',
        'hover' => 'md:hover:bg-purple-100 md:dark:hover:bg-purple-800/30',
        'handle' => 'bg-zinc-100 dark:bg-zinc-700',
    ],
    'emerald' => [
        'border' => 'border-emerald-200 dark:border-emerald-800',
        'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 
        'hover' => 'md:hover:bg-emerald-100 md:dark:hover:bg-emerald-800/30',
        'handle' => 'bg-zinc-100 dark:bg-zinc-700',
    ],
    'red' => [
        'border' => 'border-red-200 dark:border-red-800',
        'bg' => 'bg-red-50 dark:bg-red-900/20',
        'hover' => 'md:hover:bg-red-100 md:dark:hover:bg-red-800/30',
        'handle' => 'bg-zinc-100 dark:bg-zinc-700',
    ]
];

$colors = $colorClasses[$color] ?? $colorClasses['zinc'];
$baseClasses = "list-group-item group relative cursor-move touch-manipulation rounded-lg p-4 outline-none transition-all duration-200 hover:shadow-md focus:border-sky-500 focus:ring-2 focus:ring-sky-500 active:scale-[0.98] md:p-3 md:cursor-grab snap-start overflow-hidden";
$itemClasses = $baseClasses . ' ' . $colors['border'] . ' ' . $colors['bg'] . ' ' . $colors['hover'] . ' ' . $class;
@endphp

<div 
    x-sortable:item="{{ $itemId }}" 
    :id="$id('sortable-item')"
    class="{{ $itemClasses }}"
    tabindex="0" 
    role="button"
    aria-label="{{ $ariaLabel ?: "Task: {$title}. Drag to move between columns." }}"
    aria-describedby="task-{{ $itemId }}-actions"
    x-data="{
        swipeDistance: 0,
        showActions: false
    }"
    @if($availableColumns)
    x-swipe="{
        taskId: '{{ $itemId }}',
        taskTitle: '{{ addslashes($title) }}',
        currentColumn: '{{ $currentColumn }}',
        availableColumns: {{ json_encode($availableColumns) }}
    }"
    @endif
    @swipe:move="swipeDistance = $event.detail.distance; showActions = Math.abs(swipeDistance) > 50"
    @swipe:end="swipeDistance = 0; showActions = false"
>
    <div class="flex items-start justify-between">
        <div class="flex-1 pr-2 min-w-0">
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 md:text-sm break-words">
                {{ $title }}
            </p>
            @if($subtitle)
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 break-words">
                {{ $subtitle }}
            </p>
            @endif
        </div>
        
        <div class="flex items-center space-x-1 md:space-x-2">
            <!-- Mobile-only drag handle (only shown when sorting is enabled) -->
            @if($sortEnabled)
            <div x-sortable:handle
                class="flex items-center justify-center w-12 h-12 transition-all duration-200 rounded-lg opacity-100 sortable-handle cursor-grab touch-manipulation hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-sky-500 active:cursor-grabbing dark:hover:bg-zinc-600 md:hidden {{ $colors['handle'] }}"
                aria-label="Drag handle for {{ $title }}">
                <svg class="w-5 h-5 text-zinc-400 md:h-4 md:w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                </svg>
            </div>
            @endif
        </div>
    </div>

    <!-- Screen reader only action descriptions -->
    <div id="task-{{ $itemId }}-actions" class="sr-only">
        Available actions: @if($sortEnabled)Drag to reorder tasks. @endif@if($availableColumns)On mobile: Swipe to show move options.@endif
    </div>

    @if($availableColumns)
    <!-- Swipe right - Show Move Options -->
    <div class="absolute top-0 left-0 z-10 flex items-center justify-center w-full h-full transition-opacity rounded-lg pointer-events-none bg-gradient-to-r from-sky-100 to-sky-200 dark:from-sky-800 dark:to-sky-700 md:hidden"
        :class="{
            'opacity-80': swipeDistance > 100,
            'opacity-40': swipeDistance > 50 && swipeDistance <= 100
        }"
        x-show="swipeDistance > 50" x-transition.opacity x-cloak>
        <div class="flex items-center text-sky-800 dark:text-sky-200">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            'opacity-40': swipeDistance < -50 && swipeDistance >= -100
        }"
        x-show="swipeDistance < -50" x-transition.opacity x-cloak>
        <div class="flex items-center text-sky-800 dark:text-sky-200">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
            </svg>
            <span class="text-sm font-medium">Move Options</span>
        </div>
    </div>
    @endif

    {{ $slot }}
</div>