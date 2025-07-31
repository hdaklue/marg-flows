@props([
    'audioUrl' => '',
    'useVoiceNoteManager' => false,
    'size' => 'md', // sm, md, lg
    'outlined' => true,
    'class' => '',
    'playerKey' => null,
])

@php
    // Size configurations
    $sizeConfig = [
        'sm' => [
            'container' => 'gap-2 px-2 py-1',
            'button' => 'w-6 h-6',
            'buttonRadius' => 'rounded-md',
            'icon' => 'w-3 h-3',
            'waveform' => 'h-6',
            'text' => 'text-xs',
            'border' => 'rounded-md',
        ],
        'md' => [
            'container' => 'gap-3 px-3 py-2',
            'button' => 'w-8 h-8',
            'buttonRadius' => 'rounded-lg',
            'icon' => 'w-4 h-4',
            'waveform' => 'h-8',
            'text' => 'text-xs',
            'border' => 'rounded-lg',
        ],
        'lg' => [
            'container' => 'gap-4 px-4 py-3',
            'button' => 'w-10 h-10',
            'buttonRadius' => 'rounded-xl',
            'icon' => 'w-5 h-5',
            'waveform' => 'h-10',
            'text' => 'text-sm',
            'border' => 'rounded-xl',
        ],
    ];

    $config = $sizeConfig[$size] ?? $sizeConfig['md'];

    // Style configurations
    $styleClasses = $outlined
        ? 'bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700'
        : 'bg-transparent border-0';

    $baseClasses = "w-full flex items-center {$config['container']} {$styleClasses} {$config['border']}";
    $finalClasses = $class ? $baseClasses . ' ' . $class : $baseClasses;

    // Generate unique container reference
    $containerRef = $playerKey ? "waveformContainer_{$playerKey}" : 'waveformContainer';
@endphp

<div x-data="audioPlayer({
    audioUrl: @js($audioUrl),
    useVoiceNoteManager: true,
    size: @js($size),
    containerRef: @js($containerRef),
    instanceKey: @js($playerKey)
})"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('audioPlayer') }}"
    x-on:destroy="cleanup()"
    @destroy-audio-player.window="if ($event.detail.playerKey === @js($playerKey)) cleanup()"
    class="{{ $finalClasses }}">

    <!-- Play/Pause Button -->
    <button @click="togglePlay()" {{-- :disabled="!isLoaded" --}}
        class="{{ $config['button'] }} {{ $config['buttonRadius'] }} flex shrink-0 items-center justify-center text-white transition-all duration-200"
        :class="{
            'bg-sky-500 hover:bg-sky-600': !(isPlaying ?? false),
            'bg-red-500 hover:bg-red-600': (isPlaying ?? false)
        }">

        <!-- Play Icon -->
        <svg x-show="!(isPlaying ?? false)" class="{{ $config['icon'] }} ml-0.5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z" />
        </svg>

        <!-- Pause Icon -->
        <svg x-show="(isPlaying ?? false)" class="{{ $config['icon'] }}" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
        </svg>
    </button>

    <!-- Waveform Container -->
    <div class="flex-1 min-w-0">
        <div x-ref="{{ $containerRef }}" id="{{ $containerRef }}"
            class="{{ $config['waveform'] }} @if ($outlined) bg-zinc-100 dark:bg-zinc-700 @else bg-zinc-50 dark:bg-zinc-800 @endif w-full rounded">
        </div>
    </div>

    <!-- Time Display -->
    <div
        class="{{ $config['text'] }} min-w-[{{ $size === 'sm' ? '50px' : ($size === 'lg' ? '70px' : '60px') }}] whitespace-nowrap text-right font-mono text-zinc-500 dark:text-zinc-400">
        <span x-text="formatTime ? formatTime(currentTime ?? 0) : '0:00'">0:00</span>
        <span class="text-zinc-400 dark:text-zinc-500">/</span>
        <span x-text="formatTime ? formatTime(duration ?? 0) : '0:00'">0:00</span>
    </div>
</div>
