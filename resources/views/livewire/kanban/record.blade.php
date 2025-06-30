<div id="{{ $record->getKey() }}"
    class="record dark:bg-{{ $color }}-900/10 dark:hover:bg-{{ $color }}-900/20 bg-{{ $color }}-50 hover:bg-{{ $color }}-200 @can('manageFlows', filament()->getTenant())
     cursor-move
     @endcan relative overflow-hidden rounded-lg p-4 text-base font-medium text-gray-800 transition-all dark:text-gray-200"
    @if ($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3) x-data @endif>


    <div class="flex flex-row justify-between gap-2">
        <div
            class="text-{{ $color }}-800 flex cursor-pointer text-base font-semibold leading-snug lg:text-lg dark:text-gray-300">
            <a>{{ $record->title }}</a>
        </div>
        <div class="flex flex-row -space-x-2">
            @foreach ($record->participants as $participant)
                <div class="h-6 w-6 cursor-default rounded-full" x-tooltip="name" x-data="{
                    name: '{{ $participant->name }}'
                }">
                    <img class="w-full rounded-full border border-gray-50 dark:border-gray-600"
                        src="{{ filament()->getUserAvatarUrl($participant) }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-2 flex flex-row gap-x-2 dark:text-gray-400">
        {{-- <div class="flex items-center text-xs gap-x-1">
            <span class="font-semibold">Start</span>
            <span
                class="rounded border px-1 py-0.5 text-xs dark:border-gray-700">{{ $record->start_date->toDateString() }}</span>
        </div> --}}
        <div class="flex items-center gap-x-1 text-xs">
            <span class="font-semibold">Due :</span>
            <span
                class="rounded border px-1 py-0.5 text-xs dark:border-gray-800/60">{{ $record->due_date->toDateString() }}</span>
        </div>
    </div>
    {{-- <div class="py-2 text-sm font-light prose ps-2 dark:text-gray-400">
        Lorem ipsum, dolor sit amet consectetur adipisicing elit.
    </div> --}}
    <div x-data class="flex justify-end pt-2 text-sm">
        <div x-data>
            @can('manageMembers', $record)
                <button title="Manage members"
                    class="cursor-pointer text-gray-700/70 hover:text-gray-700 dark:text-gray-700 dark:hover:text-gray-500"
                    {{-- wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})" --}}
                    @click.prevent="$dispatch('open-modal',{id: 'edit-members-modal-{{ $record->getKey() }}'})">
                    <x-heroicon-o-users class="h-5 w-5" />
                </button>
                <x-filament::modal id="edit-members-modal-{{ $record->getKey() }}" width="xl">
                    <x-slot name="heading">
                        {{ $record->title }} Members
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
