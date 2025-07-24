<x-filament-panels::page>
    <div class="mx-auto w-full lg:w-3/4">
        {{ $this->form }}
    </div>
    <livewire:reusable.side-note-list :sidenoteable="$this->record" />
</x-filament-panels::page>
