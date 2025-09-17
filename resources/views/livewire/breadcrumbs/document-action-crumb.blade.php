<div>
    <div x-data>
        {!! $renderedActioncrumbs !!}
    </div>
    <livewire:participants.manage-participants-modal :roleableEntity="$this->document" />
    <x-filament-actions::modals />
</div>
