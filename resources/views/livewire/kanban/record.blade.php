<div x-data="{
    color: $wire.entangle('color'),
    progressDetails: $wire.entangle('progressDetails'),
}" id="{{ $record->getKey() }}"
    class="record dark:bg-{{ $this->color }}-900/10 dark:hover:bg-{{ $this->color }}-900/20 bg-{{ $this->color }}-50 hover:bg-{{ $this->color }}-200 @if ($this->userPermissions['canManageFlows']) cursor-move @endif relative rounded-lg p-2 text-base font-medium text-zinc-800 transition-all dark:text-zinc-200"
    @if ($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3) x-data @endif>

    <div class="flex flex-row justify-between gap-2">
        <div
            class="text-{{ $this->color }}-800 flex cursor-pointer flex-col text-sm font-semibold leading-snug dark:text-zinc-300">
            <a href="{{ App\Filament\Pages\ViewFlow::getUrl(['record' => $record->getKey()]) }}">{{ $record->title }}</a>
        </div>
        <div class="flex flex-row -space-x-2">
            <x-user-avatar-stack :canEdit="$this->userPermissions['canManageMembers']" :users="$this->participantsArray" size="2xs" :roleableType="$this->record->getMorphClass()" :roleableKey="$this->record->getKey()"
                :showTooltip="false" />
        </div>
    </div>
    <div class="text-2xs text-{{ $this->color }}-900 mt-2 w-full py-1 dark:text-zinc-400">
        <p>
            {{ $record->description }}
        </p>
    </div>


    {{-- @if ($this->userPermissions['canManageMembers'])
        <div x-data class="flex justify-end pt-2 text-sm">
            <div x-data>
                <button title="Manage members"
                    class="cursor-pointer text-zinc-700/70 hover:text-zinc-700 dark:text-zinc-700 dark:hover:text-zinc-500"
                    wire:click="$dispatch('open-members-modal',{roleable: '{{ $record->getKey() }}' })">
                    <x-heroicon-o-users class="w-4 h-4" />
                </button>
            </div>
        </div>
    @endif --}}

</div>
