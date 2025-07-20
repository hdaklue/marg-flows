<div x-data="{
    color: $wire.entangle('color'),
    progressDetails: $wire.entangle('progressDetails'),
}" id="{{ $record->getKey() }}"
    class="record dark:bg-{{ $this->color }}-900/10 dark:hover:bg-{{ $this->color }}-900/20 bg-{{ $this->color }}-50 hover:bg-{{ $this->color }}-200 @if ($this->userPermissions['canManageFlows']) cursor-move @endif relative rounded-lg p-2 text-base font-medium text-gray-800 transition-all dark:text-gray-200"
    @if ($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3) x-data @endif>

    <div class="flex flex-row justify-between gap-2">
        <div
            class="text-{{ $this->color }}-800 flex cursor-pointer text-sm font-semibold leading-snug dark:text-gray-300">
            <a href="{{ App\Filament\Pages\ViewFlow::getUrl(['record' => $record->getKey()]) }}">{{ $record->title }}</a>
        </div>
        <div class="flex flex-row -space-x-2">
            <x-user-avatar-stack :canEdit="$this->userPermissions['canManageMembers']" :users="$this->participantsArray" size="2xs" :editable-key="$this->record->getKey()"
                :showTooltip="false" />
            {{-- @foreach ($this->participants as $participant)
                <div class="w-5 h-5 rounded-full cursor-default" x-tooltip="name" x-data="{
                    name: '{{ $participant->model->name }} - ({{ $participant->role->name }})'
                }">
                    <img class="w-full border rounded-full border-gray-50 dark:border-gray-800"
                        src="{{ filament()->getUserAvatarUrl($participant->model) }}">
                </div>
            @endforeach --}}
        </div>
    </div>

    @if ($shouldShowProgressDetails)
        <div class="flex flex-row flex-wrap mt-2 gap-x-2 dark:text-gray-400">
            {{-- <div class="flex items-center text-xs gap-x-1">
            <span class="font-semibold">Start</span>
            <span
                class="rounded border px-1 py-0.5 text-xs dark:border-gray-700">{{ $record->start_date->toDateString() }}</span>
        </div> --}}
            <div class="flex items-center w-full text-3xs gap-x-1">
                {{-- <span class="font-medium">Due :</span> --}}
                <span x-data="{ hint: '{{ $record->due_date->toDateString() }}' }" x-tooltip="hint"
                    class="text-3xs grow-0 cursor-default rounded border border-gray-400 bg-gray-300/20 px-1 py-0.5 font-semibold dark:border-gray-700 dark:bg-gray-700/30">{{ $record->due_date->format('M j') }}</span>
                <div class="grow">
                    <divs class="w-full">
                        <!-- Fixed height container -->
                        <div class="h-0.5 w-full cursor-default overflow-hidden rounded-full bg-gray-200"
                            x-tooltip="`${progressDetails.status} - ${progressDetails.percentage.display}`">
                            <div x-show="progressDetails.percentage.percentage > 0"
                                class="h-full transition-all duration-300 rounded-full"
                                :class="progressDetails.percentage.percentage > 0 ? `bg-${color}-500/70` : ''"
                                :style="`width: ${progressDetails.percentage.percentage}%;`">
                            </div>
                        </div>
                    </divs>
                </div>
            </div>
            <div class="mt-2 cursor-default">
                <p class="w-full text-gray-500 text-2xs dark:text-gray-300" x-show="progressDetails.days_remaining > 0"
                    x-text="`Due in: ${progressDetails.days_remaining_display}`" x-cloak></p>
                <p class="text-3xs w-full rounded border border-red-700 bg-red-500/20 p-0.5 font-semibold uppercase tracking-wider text-red-700 dark:bg-red-500/10 dark:text-red-800"
                    x-show="progressDetails.days_remaining < 0" x-cloak>
                    Overdue
                </p>
            </div>
        </div>
    @endif

    @if ($this->userPermissions['canManageMembers'])
        <div x-data class="flex justify-end pt-2 text-sm">
            <div x-data>
                <button title="Manage members"
                    class="cursor-pointer text-gray-700/70 hover:text-gray-700 dark:text-gray-700 dark:hover:text-gray-500"
                    wire:click="$dispatch('open-members-modal',{roleable: '{{ $record->getKey() }}' })">
                    <x-heroicon-o-users class="w-4 h-4" />
                </button>
            </div>
        </div>
    @endif

</div>
