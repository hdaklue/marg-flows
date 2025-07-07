<x-filament-panels::page>
    <div wire:ignore.self x-data
        class="flex flex-col pb-2 overflow-y-hidden scrollbar-hide gap-x-2 md:flex-row md:overflow-x-auto">

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

    <livewire:role.manage-members-modal />
    <livewire:reusable.side-note-list />
</x-filament-panels::page>
