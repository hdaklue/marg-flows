<x-filament-panels::page>
    <div class="flex flex-col w-full gap-x-4 lg:flex-row" wire:ignore>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-8 2xl:grid-cols-8">
            @foreach ($this->pages() as $page)
                <livewire:components.document-card :pageableId="$this->flow->getKey()" :pageId="$page->id" wire:key="$page->getKey()" />
            @endforeach
        </div>
    </div>


    <livewire:role.manage-members-modal />

</x-filament-panels::page>
