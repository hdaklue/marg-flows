<div>
    <x-filament::modal id="manage-participants-modal" width="4xl" slide-over :close-button="true">
        <x-slot name="heading">
            Shared with ..
        </x-slot>
        <livewire:participants.manage-participants-table :roleableEntity="$roleableEntity" />
    </x-filament::modal>
</div>
