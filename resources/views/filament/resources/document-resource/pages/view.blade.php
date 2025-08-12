<x-filament-panels::page>
    <div class="mx-auto w-full md:w-2/3 lg:w-4/5 xl:w-4/5">
        {{ $this->form }}
        <livewire:document.document-component :documentId="$this->record->getKey()" />
    </div>
    <livewire:reusable.side-note-list :sidenoteable="$this->record" :editable="$this->canEdit" />
</x-filament-panels::page>
