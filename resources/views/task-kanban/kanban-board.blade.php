@use(App\Enums\Role\RoleEnum)
<x-filament-panels::page>
    <div class="flex gap-x-2">
        <div class="flex items-center gap-x-1">
            <x-filament::badge size="sm" color="gray" icon="heroicon-o-calendar-days">
                <div class="flex items-center h-full gap-x-1">
                    <div clas="font-bold"> Due:</div>
                    <div class="text-xs">{{ toUserDate($this->flow->due_date, filamentUser()) }}</div>
                </div>
            </x-filament::badge>
            <x-filament::badge size="sm" color="gray" icon="heroicon-o-calendar">
                <div class="flex items-center gap-x-1">
                    <div clas="font-bold text-xs"> Start:</div>
                    <div class="text-xs">{{ toUserDate($this->flow->start_date, filamentUser()) }}</div>
                </div>
            </x-filament::badge>
        </div>
        <div class="flex flex-row -space-x-2">
            @foreach ($this->flow->getParticipants() as $participant)
                <div class="w-6 h-6 rounded-full cursor-default" x-tooltip="name" x-data="{
                    name: '{{ $participant->model->name }} ({{ RoleEnum::from($participant->role->name)->getLabel() }})'
                }">
                    <img class="w-full border rounded-full border-gray-50 dark:border-gray-800"
                        src="{{ filament()->getUserAvatarUrl($participant->model) }}">
                </div>
            @endforeach
        </div>
    </div>

    <div wire:ignore.self x-data class="gap-2 pb-2 scrollbar-hide md:flex md:overflow-x-auto" class="flex flex-col">

        @foreach ($statuses as $status)
            @include(static::$statusView)
        @endforeach

        @if ($this->canManageFlow)
            <div wire:ignore>
                @include(static::$scriptsView)
            </div>
        @endif

    </div>

    @unless ($disableEditModal)
        <x-filament-kanban::edit-record-modal />
    @endunless

    <livewire:reusable.side-note-list :sidenoteable="$this->flow" />
</x-filament-panels::page>
