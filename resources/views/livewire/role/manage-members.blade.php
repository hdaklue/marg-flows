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
            @if (count($this->manageableMembers))
                @foreach ($this->manageableMembers as $member)
                    <div
                        class="flex w-full flex-col items-start justify-start gap-y-2 rounded-lg bg-gray-200/45 p-2 md:flex-row md:items-center md:justify-between md:gap-y-0 dark:bg-gray-800/40">
                        <div class="flex w-2/3 items-center gap-3">
                            <x-filament::avatar size="w-8 h-8" src="{{ $member->model->avatar }}"
                                alt="{{ $member->model->name }}" />

                            <div class="flex flex-col justify-start">
                                <p class="text-sm font-medium">
                                    {{ $member->model->name }}
                                </p>
                                <p class="text-xs capitalize text-gray-700 dark:text-gray-500">
                                    {{ $member->role->name }}
                                </p>
                            </div>
                        </div>
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
                                        <option value="{{ $role['value'] }}">
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
                            <x-filament::icon-button size="xs" color="danger" icon="heroicon-o-trash" outlined
                                tooltip="remove member"
                                wire:click="mountAction('removeMemberAction', { memberId: '{{ $member->model->getKey() }}'})">
                                remove
                            </x-filament::icon-button>
                            {{-- {{ ($this->removeMemberAction)(['memberId' => $member->id]) }} --}}
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
