<x-filament-panels::page>
    <div class="flex gap-x-2">
        <x-filament::badge size="xs" color="gray" icon="heroicon-o-calendar-days" class="px-1">
            <div class="flex items-center gap-x-1">
                <span clas="font-bold"> Due:</span>
                <span class="">{{ toUserDate($this->flow->due_date, filamentUser()) }}</span>
            </div>
        </x-filament::badge>
        <x-filament::badge size="xs" color="gray" icon="heroicon-o-calendar" class="px-1">
            <div class="flex items-center gap-x-1">
                <span clas="font-bold"> Start:</span>
                <span class="">{{ toUserDate($this->flow->start_date, filamentUser()) }}</span>
            </div>
        </x-filament::badge>
        <div class="flex flex-row -space-x-2">
            @foreach ($this->flow->participants as $participant)
                <div class="w-6 h-6 rounded-full cursor-default" x-tooltip="name" x-data="{
                    name: '{{ $participant->name }}'
                }">
                    <img class="w-full border rounded-full border-gray-50 dark:border-gray-800"
                        src="{{ filament()->getUserAvatarUrl($participant) }}">
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
