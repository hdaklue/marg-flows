<div>
    <div x-data>
        {!! $renderedCrumbSteps !!}
    </div>
    <livewire:participants.manage-participants-modal :roleableEntity="$this->document" />
    <x-filament-actions::modals />
</div>
