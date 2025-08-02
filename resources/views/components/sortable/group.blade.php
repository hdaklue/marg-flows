@props([
    'id' => '',
    'container' => '',
    'class' => '',
    'title' => '',
    'count' => null,
    'color' => 'zinc',
    'wireKey' => null,
])

@php
$colorClasses = [
    'zinc' => 'bg-zinc-500',
    'amber' => 'bg-amber-500', 
    'emerald' => 'bg-emerald-500'
];

$countClasses = [
    'zinc' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400',
    'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-400',
    'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400'
];

$baseClasses = 'min-w-[85vw] flex-shrink-0 snap-center rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 md:min-w-0 md:snap-none';
@endphp

<div class="{{ $baseClasses }}" role="region" aria-labelledby="{{ $id }}-column-heading">
    @if($title)
    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 md:p-4">
        <h2 id="{{ $id }}-column-heading" 
            class="flex items-center text-lg font-bold text-zinc-900 dark:text-zinc-100 md:text-base md:font-semibold">
            <div class="w-4 h-4 mr-3 rounded-full {{ $colorClasses[$color] ?? $colorClasses['zinc'] }} md:mr-2 md:h-3 md:w-3" aria-hidden="true"></div>
            {{ $title }}
            @if($count !== null)
            <span class="ml-3 rounded-full px-3 py-1.5 text-sm font-medium md:ml-2 md:px-2 md:py-1 md:text-xs {{ $countClasses[$color] ?? $countClasses['zinc'] }}"
                aria-label="{{ $count }} tasks">
                {{ $count }}
            </span>
            @endif
        </h2>
    </div>
    @endif
    
    <div 
        x-sortable-group 
        id="{{ $id }}" 
        data-container="{{ $container ?: $id }}"
        {{ $attributes->merge(['class' => "list-group min-h-[200px] h-full max-h-[calc(100vh-200px)] overflow-y-auto space-y-2 p-3 md:space-y-3 md:p-4 scroll-smooth snap-y snap-mandatory $class"]) }}
        @if($wireKey) wire:key="{{ $wireKey }}" @endif
    >
        {{ $slot }}
        
        @if($slot->isEmpty())
        <div class="py-6 text-center text-zinc-500 dark:text-zinc-400 md:py-8">
            {{ $emptyState ?? '' }}
        </div>
        @endif
    </div>
</div>