<div class="flex flex-col space-y-2">
    <div class="px-4 py-3 bg-gray-100 rounded-lg dark:bg-gray-800/30">
        <header class="pb-2 font-semibold border-b border-gray-200 dark:border-gray-800/60">
            Assign new members
        </header>
        <div class="flex flex-col gap-2 mt-2">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
            <div class="flex justify-end mt-1">
                <x-filament::button size="sm" color="primary" outlined wire:click="addMember">
                    Add Member
                </x-filament::button>
            </div>
        </div>
    </div>

    <div>
        <header class="pb-2 mt-2 font-semibold border-b border-gray-200 dark:border-gray-800/60">
            Manage Members
        </header>
        <div class="flex flex-col mt-2 space-y-2">
            @if (count($this->manageableMembers))
                @foreach ($this->manageableMembers as $member)
                    <div class="flex flex-col px-2 py-1 rounded-lg dark:bg-gray-800/40">
                        <div class="flex items-center justify-between py-1 border-gray-200 dark:border-gray-800">
                            <div class="flex flex-col items-center justify-start gap-y-2">
                                <div class="flex w-full gap-x-1">
                                    <x-filament::avatar size="w-6 h-6" src="{{ $member->model->avatar }}"
                                        alt="{{ $member->model->name }}" />
                                    <p class="text-sm">
                                        {{ $member->model->name }}
                                    </p>
                                </div>
                                <div class="text-xs capitalize text-start">
                                    {{ $member->role->name }}
                                </div>
                            </div>
                            <div>
                                <x-filament::button size="xs" color="danger" icon="heroicon-o-trash" outlined
                                    tooltip="remove member"
                                    wire:click="mountAction('removeMemberAction', { memberId: '{{ $member->model->getKey() }}'})">
                                    remove
                                </x-filament::button>
                                {{-- {{ ($this->removeMemberAction)(['memberId' => $member->id]) }} --}}
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div
                    class="flex flex-col justify-center pt-3 text-sm font-normal text-center text-gray-400 dark:text-gray-600">
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
