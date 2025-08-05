<x-filament-panels::page @class([
    'fi-resource-create-record-page',
    'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
])>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>
    {{-- <div>
        <button wire:click="begin">Start count-down</button>

        <h1>Count: <span wire:stream="count">{{ $start }}</span></h1>
    </div>

    <x-filament-panels::form id="form" :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
        wire:submit="create">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form> --}}

    <x-filament-panels::page.unsaved-data-changes-alert />
</x-filament-panels::page>
