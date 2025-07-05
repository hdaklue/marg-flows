<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>
    <input placeholder="{{ $getPlaceholder() }}" wire:model.blur="{{ $getStatePath() }}" x-data="{ focused: false }"
        id="{{ $getId() }}" type="text" autocomplete="off" autofocus
        class="w-full border-0 bg-transparent p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 placeholder-gray-300 outline-none transition-all duration-200 focus:ring-0 lg:text-4xl dark:text-gray-300 dark:placeholder-gray-800 dark:focus:ring-0">
    <!-- Interact with the `state` property in Alpine.js -->
    </input>
</x-dynamic-component>
