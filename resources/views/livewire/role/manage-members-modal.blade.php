<div>
    <x-filament::modal id="edit-members-modal" width="xl" slide-over>
        <x-slot name="heading">
            {{ $roleableEntity?->getTypeTitle() }} Members
        </x-slot>
        <livewire:role.manage-members :roleableEntity="$roleableEntity" :scopeToEntity="$scopeToEntity"
            wire:key="manage-members-{{ $roleableEntity?->getKey() }}" />
        {{-- Modal content --}}
    </x-filament::modal>

</div>
