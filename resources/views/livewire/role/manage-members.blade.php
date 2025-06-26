<div class="flex flex-col space-y-2">

    <div class="flex flex-col gap-2">
        <form wire:submit.prevent>
            {{ $this->form }}
        </form>
        <div class="justify-self-end">
            <x-filament::button size="xs" color="primary" wire:click="addMember">
                Add Member
            </x-filament::button>
        </div>
    </div>
    <h3>Existing Members:</h3>
    <div class="flex flex-col space-y-2">

        @if (count($managableMembers))
            @foreach ($managableMembers as $member)
                <div class="flex flex-col">
                    <div class="flex justify-between border-gray-200 py-1 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-filament::avatar size="w-6 h-6" src="{{ $member->avatar }}" alt="{{ $member->name }}" />
                            <div>{{ $member->name }}</div>
                        </div>
                        <div>
                            <x-filament::button size="xs" color="danger" icon="heroicon-o-trash" outlined
                                tooltip="remove member"
                                wire:click="mountAction('removeMemberAction', { memberId: '{{ $member->id }}'})">
                                remove
                            </x-filament::button>
                            {{-- {{ ($this->removeMemberAction)(['memberId' => $member->id]) }} --}}
                        </div>
                    </div>
                </div>
            @endforeach

        @endif
    </div>
    <x-filament::button size="xs" color="danger" icon="heroicon-o-trash" outlined tooltip="remove member"
        wire:click="">
        Leave Flow
    </x-filament::button>
    <x-filament-actions::modals />
</div>
