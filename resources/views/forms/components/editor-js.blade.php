<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>
    {{-- {{ $field->getContainer()->getLivewire()->getId() . '.' . $field->getId() }} --}}
    <div wire:ignore x-load x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('editorJs') }}"
        x-data="editorJs($wire.entangle('{{ $getStatePath() }}').live, '{{ route('uploader') }}', @js($getEditable()))" class="space-y-4">
        <div id="editor-wrap"></div>
    </div>

    @vite(['resources/css/components/editorjs/index.css'])

</x-dynamic-component>
