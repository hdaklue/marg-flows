<div wire:ignore x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('documentEditor') }}"
    x-data="documentEditor(@js($content), '{{ route('documents.upload-image', ['document' =>$documentId]) }}', @js($this->canEditComputed), $wire.saveDocument, 0, '{{ $this->updatedAtString }}', @js($this->getFullToolsConfig()), @js($this->getAllowedTools()))" class="w-full">

    <!-- Intersection Observer Target -->
    <div x-intersect:leave.margin.-80px="isSticky = true" x-intersect:enter.margin.-80px="isSticky = false" class="h-4">
    </div>

    @if($this->document->isArchived())
        @include('livewire.document.document-archived-toolbar')
    @else
        @include('livewire.document.document-toolbar')
    @endif

    <!-- Document Editor -->
    <div id="editor-wrap" wire:ignore @keydown.window.ctrl.k.prevent="saveDoument()" @keydown.meta.k="saveDocument()"
        class="w-full rounded-2xl p-4">
    </div>

    <livewire:role.manage-members-modal />

    {{-- Pass translations to JavaScript --}}
    <script>
        window.Laravel = window.Laravel || {};
        window.Laravel.translations = @js($this->getJavaScriptTranslations());
    </script>

    @vite(['resources/css/components/editorjs/index.css', 'resources/css/components/editorjs/comment-tune.css', 'resources/css/components/document/document.css'])
</div>
