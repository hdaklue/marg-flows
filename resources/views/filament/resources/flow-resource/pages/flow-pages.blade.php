<x-filament-panels::page>
    <div class="flex w-full flex-col gap-x-4 lg:flex-row">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-8">
            @foreach ($this->pages as $page)
                <livewire:components.page-card :pageId="$page->id" :key="$page->id" />
            @endforeach
        </div>

        {{-- <div class="w-full lg:w-3/4">
            <x-filament-panels::form id="form" :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()">
                {{ $this->form }}

            </x-filament-panels::form>
        </div> --}}
        {{-- <div class="w-full lg:w-1/4">
            <div @click.prevent="">Item</div>
        </div> --}}
    </div>

    {{-- <livewire:reusable.side-note-list :sidenoteable="$this->flow" /> --}}

    {{-- <x-filament-panels::page.unsaved-data-changes-alert /> --}}
    <livewire:role.manage-members-modal />
</x-filament-panels::page>
