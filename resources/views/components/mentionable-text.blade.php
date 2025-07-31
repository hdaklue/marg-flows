@props([
    'mentionables' => [],
    'hashables' => [],
    'minHeight' => '60px',
    'class' => '',
    'id' => null,
    'hint' => 'Use @ to mention people and # for hashtags',
    'maxLength' => 100,
    'live' => false,
    'statePath',
])

@php
    $id = $id ?? 'mentionable-text-' . uniqid();
    $baseClasses =
        'w-full resize-none rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm placeholder-zinc-400 transition-colors focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-800 relative dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-sky-400';
    $finalClasses = $class ? $baseClasses . ' ' . $class : $baseClasses;
@endphp
<div x-data="{
    state: null,
    live: @js($live)
}" x-init="$watch('state', v => {
    $refs.stateInput.value = v;
    $refs.stateInput.dispatchEvent(new Event('input', { bubbles: true }));
})">

    <input type="hidden" x-ref="stateInput" wire:model='{{ $statePath }}'>

    <div x-data="mentionableText(@js($mentionables), @js($hashables), {{ $maxLength }})" x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('mentionableText') }}"
        x-load-css="{{ \Filament\Support\Facades\FilamentAsset::getStyleHref('mentionableTextCss') }}"
        x-on:destroy="destroy()" class="relative mentionable-text-container">

        <!-- Hint -->
        <div class="flex items-center gap-2 mb-2 text-xs text-zinc-500 dark:text-zinc-400">
            <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ $hint }}
        </div>

        <div contenteditable="true" x-ref="textarea" id="{{ $id }}" class="{{ $finalClasses }}"
            style="min-height: {{ $minHeight }};" @blur.stop="if(!live) state=content" @keydown.space.stop
            @keydown.shift.stop
            @input.stop="

            const newLength = getTextLength($el.innerHTML);
            if (newLength <= maxLength) {
                content = $el.innerHTML;
                if(live) state = content
            } else {
                // Show error only once when limit is exceeded
                if (!showValidationError) {
                    showValidationError = true;
                    validationMessage = `Character limit exceeded! Maximum ${maxLength} characters allowed.`;
                    setTimeout(() => { showValidationError = false; }, 3000);
                }
                // Revert to previous content
                $el.innerHTML = content;
                setCursorToEnd();
                if(live) state = content
            }
        "
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600': showValidationError }"
            required></div>

        <!-- Character Counter -->
        <div class="flex items-center justify-between mt-1 text-xs">
            <div class="flex items-center gap-2">
                <!-- Validation Error -->
                <div x-show="showValidationError" x-cloak x-transition.opacity
                    class="flex items-center gap-1 text-red-600 dark:text-red-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span x-text="validationMessage"></span>
                </div>

                <!-- Paste Warning -->
                <div x-show="showPasteWarning" x-cloak x-transition.opacity
                    class="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span x-text="pasteMessage"></span>
                </div>
            </div>

            <!-- Character Count -->
            <div class="text-zinc-500 dark:text-zinc-400"
                :class="{ 'text-red-600 dark:text-red-400 font-medium': currentLength > maxLength * 0.9 }">
                <span x-text="currentLength"></span>/<span x-text="maxLength"></span>
            </div>
        </div>

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
</div>
@vite(['resources/css/components/mentionable-text.css'])
