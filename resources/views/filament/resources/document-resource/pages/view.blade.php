<x-filament-panels::page>
    <livewire:document.document-action-crumb :record="$this->record" :parent="$this" />
    <div class="w-full mx-auto max-w-7xl lg:w-4/5 xl:w-4/5 2xl:w-3/5">
        {{ $this->form }}
        <livewire:document.document-component :documentId="$this->record->getKey()" />
    </div>
    <livewire:reusable.side-note-list :sidenoteable="$this->record" :editable="$this->canEdit" />
</x-filament-panels::page>
