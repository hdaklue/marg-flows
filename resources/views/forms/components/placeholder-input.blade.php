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
        <input type="text" x-autosize style="resize: none" placeholder="{{ $getPlaceholder() }}"
            {{ $applyStateBindingModifiers('wire:model') . '=' . $getStatePath() }} x-data="{ focused: false, erros: null }"
            id="{{ $getId() }}" autocomplete="off" autofocus
            class="disabled:bg-transperant line rounded-non max-w-full items-center border-0 bg-transparent px-2 py-2 text-2xl font-bold leading-none tracking-tight text-gray-900 placeholder-gray-300 outline-none transition-all duration-200 focus:ring-0 disabled:border-0 disabled:text-3xl lg:text-2xl dark:border-b dark:border-zinc-900 dark:text-gray-300 dark:placeholder-gray-800 dark:focus:ring-0">
        </input>
    @endif

</x-dynamic-component>
