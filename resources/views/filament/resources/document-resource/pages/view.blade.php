<x-filament-panels::page>
    <div class="mx-auto w-full lg:w-4/5">
        {{ $this->form }}
        <livewire:document.document-component :pageId="$this->record->getKey()" />
    </div>
    <div>
    </div>
    <livewire:reusable.side-note-list :sidenoteable="$this->record" :editable="$this->canEdit" />
</x-filament-panels::page>
