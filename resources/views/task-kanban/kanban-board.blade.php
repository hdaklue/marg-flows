@use(Hdaklue\MargRbac\Enums\Role\RoleEnum)
@php
    $users = $this->getParticipantsArray;
@endphp
<x-filament-panels::page>
    <div class="flex flex-col gap-x-2">
        <div class="flex flex-row items-center gap-x-1">
            <x-user-avatar-stack :users="$users" size="xs" :canEdit="$this->canManageFlow" :roleableType="$this->flow->getMorphClass()"
                :roleableKey="$this->flow->getKey()" />
        </div>
    </div>

    <div wire:ignore.self x-data class="gap-2 pb-2 scrollbar-hide md:flex md:overflow-x-auto" class="flex flex-col">

        @foreach ($statuses as $status)
            @include(static::$statusView)
        @endforeach

        @if ($this->canManageFlow)
            <div wire:ignore>
                @include(static::$scriptsView)
            </div>
        @endif

    </div>

    {{-- @unless ($disableEditModal)
        <x-filament-kanban::edit-record-modal />
    @endunless --}}

    <livewire:reusable.side-note-list :sidenoteable="$this->flow" />
    <livewire:role.manage-members-modal />
</x-filament-panels::page>
