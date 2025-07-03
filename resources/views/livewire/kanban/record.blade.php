<div x-data="{
    color: $wire.entangle('color'),
    progressDetails: $wire.entangle('progressDetails'),

}" id="{{ $record->getKey() }}"
    class="record dark:bg-{{ $color }}-900/10 dark:hover:bg-{{ $color }}-900/20 bg-{{ $color }}-50 hover:bg-{{ $color }}-200 @can('manageFlows', filament()->getTenant())
     cursor-move
     @endcan relative overflow-hidden rounded-lg p-2 text-base font-medium text-gray-800 transition-all dark:text-gray-200"
    @if ($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3) x-data @endif>

    <div class="flex flex-row justify-between gap-2">
        <div
            class="text-{{ $color }}-800 flex cursor-pointer text-sm font-semibold leading-snug dark:text-gray-300">
            <a href="{{ App\Filament\Pages\ViewFlow::getUrl(['record' => $record->getKey()]) }}">{{ $record->title }}</a>
        </div>
        <div class="flex flex-row -space-x-2">
            @foreach ($record->participants as $participant)
                <div class="w-5 h-5 rounded-full cursor-default" x-tooltip="name" x-data="{
                    name: '{{ $participant->name }}'
                }">
                    <img class="w-full border rounded-full border-gray-50 dark:border-gray-800"
                        src="{{ filament()->getUserAvatarUrl($participant) }}">
                </div>
            @endforeach
        </div>
    </div>

    @if ($shouldShowProgressDetails)
        <div class="flex flex-row flex-wrap mt-2 gap-x-2 dark:text-gray-400">
            {{-- <div class="flex items-center text-xs gap-x-1">
            <span class="font-semibold">Start</span>
            <span
                class="rounded border px-1 py-0.5 text-xs dark:border-gray-700">{{ $record->start_date->toDateString() }}</span>
        </div> --}}
            <div class="flex items-center w-full text-3xs gap-x-1">
                {{-- <span class="font-medium">Due :</span> --}}
                <span x-data="{ hint: '{{ $record->due_date->toDateString() }}' }" x-tooltip="hint"
                    class="text-3xs grow-0 cursor-default rounded border border-gray-400 bg-gray-300/20 px-1 py-0.5 font-semibold dark:border-gray-700 dark:bg-gray-700/30">{{ $record->due_date->format('M j') }}</span>
                <div class="grow">
                    @include('reusable.line-progress')
                </div>
            </div>
            <div class="mt-2 cursor-default">
                <p class="w-full text-gray-300 text-2xs" x-show="progressDetails.days_remaining > 0"
                    x-text="`Days remaining: ${progressDetails.days_remaining}`" x-cloak></p>
                <p class="text-3xs w-full rounded border border-red-700 bg-red-500/20 p-0.5 font-semibold uppercase tracking-wider text-red-700 dark:bg-red-500/10 dark:text-red-800"
                    x-show="progressDetails.days_remaining < 0" x-cloak>
                    Overdue
                </p>
            </div>
        </div>
    @endif
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
                    <x-heroicon-o-users class="w-4 h-4" />
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
