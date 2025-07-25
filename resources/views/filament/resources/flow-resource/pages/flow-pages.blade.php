<x-filament-panels::page>
    <div class="flex w-full flex-col gap-x-4 lg:flex-row">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-8 2xl:grid-cols-8">
            @foreach ($this->pages as $page)
                <livewire:components.page-card :pageableId="$this->flow->getKey()" :pageId="$page->id" wire:key="$page->getKey()" />
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


    <livewire:role.manage-members-modal />

</x-filament-panels::page>
