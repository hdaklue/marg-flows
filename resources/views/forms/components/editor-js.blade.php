@php
    $statePath = $getStatePath();
    $stringPath = '$wire.entangle("' . $getStatePath() . '")';

    $resolvedStatePath = $applyStateBindingModifiers($stringPath);
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :required="false">
    <x-slot name="label"> </x-slot>

    <div wire:ignore x-load x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('editorJs') }}"
        x-data="editorJs(
            {{ $resolvedStatePath }},
            '{{ $getUploadUrl() }}',
            @js($getEditable()),
        )">

        <div id="editor-wrap" class="mx-auto max-w-6xl"></div>
    </div>

    @vite(['resources/css/components/editorjs/index.css', 'resources/css/components/editorjs/resizable-image.css', 'resources/css/components/editorjs/video-embed.css'])

</x-dynamic-component>
