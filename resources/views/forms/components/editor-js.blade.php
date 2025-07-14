<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>
    <div wire:ignore x-load x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('editorJs') }}"
        x-data="editorJs(
            $wire.entangle('{{ $getStatePath() }}').live,
            '{{ $getUploadUrl() }}',
            @js($getEditable()),
            '{{ $getHolderId() }}'
        )" class="space-y-4">

        <div id="editor-wrap"></div>
    </div>

    @vite(['resources/css/components/editorjs/index.css', 'resources/css/components/editorjs/resizable-image.css'])

</x-dynamic-component>
