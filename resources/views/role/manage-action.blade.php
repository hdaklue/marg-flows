<div x-data>
    <button title="Manage members"
        class="cursor-pointer text-gray-700/70 hover:text-gray-700 dark:text-gray-700/70 dark:hover:text-gray-700"
        {{-- wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})" --}}
        @click.prevent="$dispatch('open-modal',{id: 'edit-members-modal-{{ $record->getKey() }}'})">
        <x-heroicon-o-users class="w-5 h-5" />
    </button>
    <x-filament::modal id="edit-members-modal-{{ $record->getKey() }}">
        <x-slot name="heading">
            Manage Member
        </x-slot>
        <livewire:role.manage-members :roleable="$record" wire:key="manage-members-{{ $record->getKey() }}" />
        {{-- Modal content --}}
    </x-filament::modal>
</div>
