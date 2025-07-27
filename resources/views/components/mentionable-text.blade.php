@props([
    'model' => 'content',
    'mentionables' => [],
    'hashables' => [],
    'minHeight' => '60px',
    'class' => '',
    'id' => null,
    'hint' => 'Use @ to mention people and # for hashtags',
])

@php
    $id = $id ?? 'mentionable-text-' . uniqid();
    $baseClasses =
        'w-full resize-none rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm placeholder-zinc-400 transition-colors focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 relative dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-sky-400';
    $finalClasses = $class ? $baseClasses . ' ' . $class : $baseClasses;
@endphp

<div x-data="mentionableText(@js($mentionables), @js($hashables))" x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('mentionableText') }}"
    x-load-css="{{ \Filament\Support\Facades\FilamentAsset::getStyleHref('mentionableTextCss') }}"
    x-on:destroy="destroy()" class="relative mentionable-text-container">

    <!-- Hint -->
    <div class="flex items-center gap-2 mb-2 text-xs text-zinc-500 dark:text-zinc-400">
        <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        {{ $hint }}
    </div>

    <div contenteditable="true" x-ref="textarea" x-model="content" wire:model="{{ $model }}"
        id="{{ $id }}" class="{{ $finalClasses }}" style="min-height: {{ $minHeight }};"
        x-init="$watch('$wire.showCommentModal', value => value && setTimeout(() => $el.focus(), 100))" @input="content = $el.innerHTML" required></div>

    <!-- Optional: Display mentioned users and hashtags for debugging -->
    {{-- @if (app()->environment('local'))
        <div x-show="getMentionedUsers().length > 0 || getHashtags().length > 0"
            class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
            <template x-if="getMentionedUsers().length > 0">
                <div>
                    <span class="font-medium">Mentioned:</span>
                    <template x-for="user in getMentionedUsers()" :key="user.id">
                        <span class="ml-1" x-text="user.name"></span>
                    </template>
                </div>
            </template>
            <template x-if="getHashtags().length > 0">
                <div>
                    <span class="font-medium">Hashtags:</span>
                    <template x-for="hashtag in getHashtags()" :key="hashtag.name">
                        <span class="ml-1" x-text="'#' + hashtag.name"></span>
                    </template>
                </div>
            </template>
        </div>
    @endif --}}
</div>
@vite(['resources/css/components/mentionable-text.css'])
