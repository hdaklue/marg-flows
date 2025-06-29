<div class="flex flex-col space-y-2">

    <div class="rounded-lg bg-gray-100 px-4 py-3 dark:bg-gray-800/30">
        <header class="border-b border-gray-200 pb-2 font-semibold dark:border-gray-800/60">
            Assign new members
        </header>
        <div class="mt-2 flex flex-col gap-2">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
            <div class="mt-1 flex justify-end">
                <x-filament::button size="sm" color="primary" outlined wire:click="addMember">
                    Add Member
                </x-filament::button>
            </div>
        </div>
    </div>

    <div>
        <header class="mt-2 border-b border-gray-200 pb-2 font-semibold dark:border-gray-800/60">
            Manage Members
        </header>
        <div class="mt-2 flex flex-col space-y-2">
            @if (count($managableMembers))
                @foreach ($managableMembers as $member)
                    <div class="flex flex-col">
                        <div class="flex justify-between border-gray-200 py-1 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                <x-filament::avatar size="w-6 h-6" src="{{ $member->avatar }}"
                                    alt="{{ $member->name }}" />
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
            @else
                <div
                    class="flex flex-col justify-center pt-3 text-center text-sm font-normal text-gray-400 dark:text-gray-600">
                    <p class="font-medium">Projects are not lonely planets 😵‍💫</p>
                    <p class="mt-1 text-sm">Add some fellows to get this mission started!</p>
                </div>
            @endif
        </div>
    </div>
    {{-- <x-filament::button size="xs" color="danger" icon="heroicon-o-trash" outlined tooltip="remove member"
        wire:click="">
        Leave Flow
    </x-filament::button> --}}
    <x-filament-actions::modals />
</div>
