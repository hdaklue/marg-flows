@php
    $enabled = $getEditable();

@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>


    @if (!$enabled)
        <p class="w-full p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 dark:text-gray-200">
            {{ $getState() }}
        </p>
    @endif
    @if ($enabled)
        <textarea style="resize: none" placeholder="{{ $getPlaceholder() }}"
            {{ $applyStateBindingModifiers('wire:model') . '=' . $getStatePath() }} x-data="{ focused: false, erros: null }"
            id="{{ $getId() }}" type="text" autocomplete="off" autofocus
            class="disabled:bg-transperant max-w-full border-0 bg-transparent p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 placeholder-gray-300 outline-none transition-all duration-200 focus:ring-0 disabled:border-0 disabled:text-3xl dark:text-gray-300 dark:placeholder-gray-800 dark:focus:ring-0 lg:text-4xl">
        </textarea>
    @endif

</x-dynamic-component>
