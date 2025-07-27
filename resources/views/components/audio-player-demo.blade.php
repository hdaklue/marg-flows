{{-- Demo component to showcase audio player sizes and styles --}}
<div class="space-y-6 p-6">
    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Audio Player Variations</h2>
    
    {{-- Small size with border --}}
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Small (sm) - Outlined</h3>
        <x-audio-player 
            audio-url="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" 
            size="sm" 
            :outlined="true"
        />
    </div>

    {{-- Small size borderless (WhatsApp style) --}}
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Small (sm) - Borderless (WhatsApp style)</h3>
        <x-audio-player 
            audio-url="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" 
            size="sm" 
            :outlined="false"
        />
    </div>

    {{-- Medium size with border --}}
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Medium (md) - Outlined</h3>
        <x-audio-player 
            audio-url="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" 
            size="md" 
            :outlined="true"
        />
    </div>

    {{-- Medium size borderless --}}
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Medium (md) - Borderless</h3>
        <x-audio-player 
            audio-url="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" 
            size="md" 
            :outlined="false"
        />
    </div>

    {{-- Large size with border --}}
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Large (lg) - Outlined</h3>
        <x-audio-player 
            audio-url="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" 
            size="lg" 
            :outlined="true"
        />
    </div>

    {{-- Large size borderless --}}
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Large (lg) - Borderless</h3>
        <x-audio-player 
            audio-url="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" 
            size="lg" 
            :outlined="false"
        />
    </div>
</div>