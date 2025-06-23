<x-filament-panels::page>
    <div x-data wire:ignore.self class="scrollbar-hide gap-2 pb-2 md:flex md:overflow-x-auto" class="flex flex-col">

        @foreach ($statuses as $status)
            @include(static::$statusView)
        @endforeach


        {{-- <div wire:ignore>
            @include(static::$scriptsView)
        </div> --}}
    </div>

    @unless ($disableEditModal)
        <x-filament-kanban::edit-record-modal />
    @endunless
</x-filament-panels::page>
