<x-filament-panels::page>
    <div class="mx-auto w-full lg:w-4/5">
        {{ $this->form }}
        <livewire:page.document :pageId="$this->record->getKey()" />
    </div>
    <div>
    </div>
    <livewire:reusable.side-note-list :sidenoteable="$this->record" />
</x-filament-panels::page>
