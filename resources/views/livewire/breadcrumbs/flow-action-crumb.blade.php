<div>
    <div x-data>
        {!! $this->renderActioncrumbs() !!}
    </div>

    <livewire:participants.manage-participants-modal :roleableEntity="$this->flow" />
    <x-filament-actions::modals />
</div>
