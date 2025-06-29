<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>

    {{-- {{ $field->getContainer()->getLivewire()->getId() . '.' . $field->getId() }} --}}
    <div wire:ignore x-data="editorJs($wire.entangle('{{ $getStatePath() }}').live, '{{ route('uploader') }}')" class="space-y-4">
        <div id="editor-wrap"></div>
    </div>

    @vite(['resources/js/components/editorjs/index.js', 'resources/css/components/editorjs/index.css'])

</x-dynamic-component>
