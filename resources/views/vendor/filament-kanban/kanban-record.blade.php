@props(['color'])
<div id="{{ $record->getKey() }}"
    class="record dark:bg-{{ $color }}-900/5 bg-{{ $color }}-50 relative overflow-hidden rounded-lg px-2 py-4 text-base font-medium text-gray-800 dark:text-gray-200"
    @if ($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3) x-data
        x-init="
            $el.classList.add('animate-pulse-twice', 'bg-primary-100', 'dark:bg-primary-800')
            $el.classList.remove('bg-white', 'dark:bg-gray-700')
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        " @endif>

    <div class="flex flex-row justify-between gap-2">
        <div class="flex text-base font-semibold leading-snug hover:cursor-pointer"
            wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})">
            <a>{{ $record->{static::$recordTitleAttribute} }}</a>
        </div>
        <div class="flex flex-row -space-x-2">
            @foreach ($record->participants as $participant)
                <div class="h-6 w-6 rounded-full" x-tooltip="name" x-data="{
                    name: '{{ $participant->name }}'
                }">
                    <img class="w-full rounded-full border border-gray-600"
                        src="{{ filament()->getUserAvatarUrl($participant) }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-1 flex flex-row gap-x-1">
        <div class="flex gap-x-1 text-xs font-medium">
            <span class="font-medium">Due:</span>
            <span>{{ $record->due_date->toDateString() }}</span>
        </div>
    </div>
    <div class="prose py-2 text-sm font-light dark:text-gray-400">
        Lorem ipsum, dolor sit amet consectetur adipisicing elit.
    </div>

    <div class="absolute bottom-0 start-0 h-1 w-full">
        <div class="relative h-1 rounded-lg">
            <div class="dark:bg-{{ $color }}-800 bg-{{ $color }}-400 absolute start-0 top-0 h-full rounded-full"
                style="width: {{ random_int(10, 70) }}%">
            </div>
        </div>
    </div>
</div>
