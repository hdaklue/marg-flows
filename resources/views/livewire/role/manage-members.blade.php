<div class="flex flex-col space-y-2">
    @if ($canEdit)
        <div class="px-4 py-3 rounded-lg bg-zinc-100 dark:bg-zinc-800/30">
            <header class="pb-2 font-semibold border-b border-zinc-200 dark:border-zinc-800/60">
                Assign new members
            </header>
            <div class="flex flex-col gap-2 mt-2">
                <form wire:submit.prevent>
                    {{ $this->form }}
                </form>
                <div class="flex justify-end mt-1">
                    <x-filament::button size="xs" color="primary" outlined wire:click="addMember">
                        Add Member
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    <div>
        {{-- <header class="pb-2 mt-2 font-semibold border-b border-zinc-200 dark:border-zinc-800/60">
            Manage Members
        </header> --}}
        <div class="flex flex-col mt-2 space-y-2">
            @if (count($this->manageableMembers))
                @foreach ($this->manageableMembers as $member)
                    <div
                        class="flex flex-col items-start justify-start w-full p-2 rounded-lg gap-y-2 bg-zinc-200/45 dark:bg-zinc-800/40 md:flex-row md:items-center md:justify-between md:gap-y-0">
                        <div class="flex items-center w-2/3 gap-3">
                            <x-filament::avatar size="w-8 h-8" src="{{ $member->model->avatar }}"
                                alt="{{ $member->model->name }}" />

                            <div class="flex flex-col justify-start">
                                <p class="text-sm font-medium">
                                    {{ $member->model->name }}
                                </p>
                                <p class="text-xs capitalize text-zinc-700 dark:text-zinc-500">
                                    {{ $member->role->name }}
                                </p>
                            </div>
                        </div>
                        @if ($canEdit)
                            <div class="flex items-center justify-between w-full gap-2 mt-2 md:mt-0 md:justify-end">
                                <div x-data="{
                                    role: '',
                                    id: '{{ $member->model->getKey() }}',
                                    {{-- init() {
                                $watch('role', () => this.updateRole())
                            } --}}
                                    updateRole() {
                                        $wire.changeRole(this.role, this.id);
                                        {{-- $nextTick(() => this.role = '') --}}
                                    },
                                }">
                                    <x-filament::input.wrapper class="!rounded-md">
                                        <x-filament::input.select x-model="role" class="!py-1.5 !pe-6 !ps-2 !text-sm"
                                            @change="role = $event.target.value; $nextTick(()=>updateRole())">
                                            <option value="" disabled>Update role</option>

                                            @foreach ($this->authedUserAssignableRoles as $role)
                                                <option value="{{ $role['value'] }}"
                                                    @if ($role['value'] === $member->role->name) disabled @endif>
                                                    {{ $role['label'] }}
                                                </option>
                                            @endforeach
                                            {{-- <option value="draft">Draft</option>
                                        <option value="reviewing" selected>Reviewing</option>
                                        <option value="published">Published</option> --}}
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <x-filament::icon-button size="xs" color="danger" icon="heroicon-o-trash"
                                        outlined tooltip="remove member"
                                        wire:click="mountAction('removeMemberAction', { memberId: '{{ $member->model->getKey() }}'})">
                                        remove
                                    </x-filament::icon-button>
                                    {{-- {{ ($this->removeMemberAction)(['memberId' => $member->id]) }} --}}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div
                    class="flex flex-col justify-center pt-3 text-sm font-normal text-center text-zinc-400 dark:text-zinc-600">
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
