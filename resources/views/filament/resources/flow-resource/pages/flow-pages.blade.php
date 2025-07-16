<x-filament-panels::page>
    <div class="flex w-full flex-col gap-x-4 lg:flex-row">
        <div class="w-full lg:w-3/4">
            <x-filament-panels::form id="form" :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()">
                {{ $this->form }}

                {{-- <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" /> --}}
            </x-filament-panels::form>
        </div>
        <div class="w-full lg:w-1/4">
            <div @click.prevent="">Item</div>
        </div>
    </div>

    <livewire:reusable.side-note-list :sidenoteable="$this->flow" />

    {{-- <x-filament-panels::page.unsaved-data-changes-alert /> --}}
</x-filament-panels::page>
