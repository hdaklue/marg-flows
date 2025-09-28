<div>
    <div x-data>
        {!! $this->renderCrumbSteps() !!}
    </div>

    <livewire:participants.manage-participants-modal :roleableEntity="$this->flow" />
    <x-filament-actions::modals />
</div>
