<div id="{{ $record->getKey() }}"
    class="record dark:bg-{{ $color }}-900/5 bg-{{ $color }}-50 @can('manageFlows', filament()->getTenant())
     cursor-move
     @endcan relative overflow-hidden rounded-lg px-2 py-4 text-base font-medium text-gray-800 dark:text-gray-200"
    @if ($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3) x-data @endif>


    <div class="flex flex-row justify-between gap-2">
        <div
            class="text-{{ $color }}-800 flex cursor-pointer text-base font-semibold leading-snug dark:text-gray-300">
            <a>{{ $record->title }}</a>
        </div>
        <div class="flex flex-row -space-x-2">
            @foreach ($record->participants as $participant)
                <div class="h-6 w-6 cursor-default rounded-full" x-tooltip="name" x-data="{
                    name: '{{ $participant->name }}'
                }">
                    <img class="w-full rounded-full border border-gray-700"
                        src="{{ filament()->getUserAvatarUrl($participant) }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-1 flex flex-row gap-x-1">
        <div class="flex gap-x-1 text-xs font-medium">
            <span class="font-medium">Due:</span>
            <span>{{ $record->due_date->toDateString() }}</span>
        </div>
    </div>
    <div class="prose py-2 ps-2 text-sm font-light dark:text-gray-400">
        Lorem ipsum, dolor sit amet consectetur adipisicing elit.
    </div>
    <div x-data class="b flex justify-end px-2 pt-2 text-sm">
        <div x-data>
            @can('manageMembers', $record)
                <button title="Manage members"
                    class="cursor-pointer text-gray-700/70 hover:text-gray-700 dark:text-gray-700/70 dark:hover:text-gray-700"
                    {{-- wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})" --}}
                    @click.prevent="$dispatch('open-modal',{id: 'edit-members-modal-{{ $record->getKey() }}'})">
                    <x-heroicon-o-users class="h-5 w-5" />
                </button>
                <x-filament::modal id="edit-members-modal-{{ $record->getKey() }}" width="xl">
                    <x-slot name="heading">
                        Manage Member
                    </x-slot>
                    <livewire:role.manage-members :roleable="$record" wire:key="manage-members-{{ $record->getKey() }}" />
                    {{-- Modal content --}}
                </x-filament::modal>
            @endcan
        </div>
    </div>

    {{-- @can('manageMembers', $record)
        <div x-data class="flex justify-end px-2 pt-2 text-sm b">
            @include('role.manage-action')
        </div>
    @endcan --}}

    {{-- <div class="absolute bottom-0 w-full h-1 start-0">
        <div class="relative h-0.5">
            <div class="dark:bg-{{ $color }}-800 bg-{{ $color }}-400 absolute start-0 top-0 h-full"
                style="width: {{ $this->getProgressPercentage($record) }}%">
            </div>
        </div>
    </div> --}}
</div>
