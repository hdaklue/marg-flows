<div>
    <x-filament::modal id="edit-members-modal" width="xl" slide-over>
        <x-slot name="heading">
            {{ $record?->title }} Members
        </x-slot>
        <livewire:role.manage-members :roleable="$record" wire:key="manage-members-{{ $record?->getKey() }}" />
        {{-- Modal content --}}
    </x-filament::modal>

</div>
