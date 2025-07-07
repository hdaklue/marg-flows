@php
    $enabled = !$isReadOnly();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>

    @if (!$enabled)
        <p class="w-full p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 dark:text-gray-200">
            {{ $getState() }}
        </p>
    @endif
    @if ($enabled)
        <input placeholder="{{ $getPlaceholder() }}" wire:model.blur="{{ $getStatePath() }}" x-data="{ focused: false }"
            id="{{ $getId() }}" type="text" autocomplete="off" autofocus
            class="w-full p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 placeholder-gray-300 transition-all duration-200 bg-transparent border-0 outline-none disabled:bg-transperant focus:ring-0 disabled:border-0 disabled:text-3xl lg:text-4xl dark:text-gray-300 dark:placeholder-gray-800 dark:focus:ring-0">
        <!-- Interact with the `state` property in Alpine.js -->
        </input>
    @endif

</x-dynamic-component>
