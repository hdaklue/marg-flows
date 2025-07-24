<div x-load x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('documentEditor') }}"
    x-data="document($wire.content, '{{ route('uploader') }}', {{ $canEdit ? 'true' : 'false' }})" class="w-full">

    <div id="editor-wrap"
        class="max-w-3/4 prose:img:my-0 prose prose-sm prose-zinc mx-auto min-h-96 dark:prose-invert lg:prose-base xl:prose-xl">
    </div>
    @vite(['resources/css/components/editorjs/index.css', 'resources/css/components/editorjs/comment-tune.css', 'resources/css/components/document/document.css'])
</div>
