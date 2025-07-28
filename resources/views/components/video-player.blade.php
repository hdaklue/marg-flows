@props([
    'videoUrl' => '',
    'size' => 'md', // sm, md, lg
    'outlined' => true,
    'class' => '',
])

@php
    // Size configurations
    $sizeConfig = [
        'sm' => [
            'container' => 'gap-2 px-2 py-1',
            'video' => 'h-16',
            'border' => 'rounded-md'
        ],
        'md' => [
            'container' => 'gap-3 px-3 py-2',
            'video' => 'h-24',
            'border' => 'rounded-lg'
        ],
        'lg' => [
            'container' => 'gap-4 px-4 py-3',
            'video' => 'h-32',
            'border' => 'rounded-xl'
        ]
    ];

    $config = $sizeConfig[$size] ?? $sizeConfig['md'];
    
    // Style configurations
    $styleClasses = $outlined 
        ? 'bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700'
        : 'bg-transparent border-0';
    
    $baseClasses = "w-full flex items-center {$config['container']} {$styleClasses} {$config['border']}";
    $finalClasses = $class ? $baseClasses . ' ' . $class : $baseClasses;
@endphp

<div class="{{ $finalClasses }}">
    <!-- Video Element -->
    <div class="flex-1 min-w-0">
        <video controls class="w-full {{ $config['video'] }} bg-zinc-900 rounded object-cover">
            <source src="{{ $videoUrl }}" type="video/mp4">
            <source src="{{ $videoUrl }}" type="video/webm">
            Your browser does not support the video tag.
        </video>
    </div>
</div>