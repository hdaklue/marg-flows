<div>
    <div x-data>
        {!! $this->renderActioncrumbs() !!}
    </div>
    <livewire:participants.manage-participants-modal :roleableEntity="$this->document" />
    <x-filament-actions::modals />
</div>