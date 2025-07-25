@php
    $enabled = $getEditable();

@endphp
<script src="https://cdn.jsdelivr.net/npm/@marcreichel/alpine-autosize@latest/dist/alpine-autosize.min.js" defer>
</script>
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">

    <x-slot name="label">
        @if ($getShowLabel())
            {{ $getName() }}
        @endif
    </x-slot>



    @if (!$enabled)
        <p class="w-full p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 dark:text-gray-200">
            {{ $getState() }}
        </p>
    @endif
    @if ($enabled)
        <textarea x-autosize style="resize: none" placeholder="{{ $getPlaceholder() }}"
            {{ $applyStateBindingModifiers('wire:model') . '=' . $getStatePath() }} x-data="{ focused: false, erros: null }"
            id="{{ $getId() }}" autocomplete="off" autofocus
            class="max-w-full p-0 text-3xl font-bold leading-tight tracking-tight text-gray-900 placeholder-gray-300 transition-all duration-200 bg-transparent border-0 outline-none disabled:bg-transperant focus:ring-0 disabled:border-0 disabled:text-3xl dark:text-gray-300 dark:placeholder-gray-800 dark:focus:ring-0 lg:text-4xl">
        </textarea>
    @endif

</x-dynamic-component>
