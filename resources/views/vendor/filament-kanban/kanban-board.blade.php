<x-filament-panels::page>

    <div wire:ignore.self x-data class="scrollbar-hide gap-2 pb-2 md:flex md:overflow-x-auto" class="flex flex-col">

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

</x-filament-panels::page>
