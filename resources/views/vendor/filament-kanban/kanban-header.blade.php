<div class="flex items-center justify-between px-2">
    <div class="w-2/3">
        <h3
            class="dark:text-{{ $color }}-400 text-{{ $color }}-800 dark:bg-{{ $color }}-800/10 bg-{{ $color }}-200 flex justify-between rounded-xl px-4 py-2 text-sm font-medium capitalize">


            <span class="">{{ $status['title'] }}</span>
            <span
                class="bg-{{ $color }}-900/50 text-{{ $color }}-100/70 rounded-lg px-2 py-0.5 text-xs font-bold">
                {{ count($status['records']) }}
            </span>
        </h3>
    </div>
    <div class="flex items-center justify-center">
        @can('manageFlows', filament()->getTenant())
            <a href="{{ App\Filament\Resources\FlowResource::getUrl('create') }}"
                class="border-{{ $color }}-400 dark:border-{{ $color }}-800/30 dark:hover:bg-{{ $color }}-600/40 text-{{ $color }}-400 dark:text-{{ $color }}-600/50 dark:bg-{{ $color }}-800/10 hover:text-{{ $color }}-700 hover:bg-{{ $color }}-200 dark:hover:text-{{ $color }}-400 bg-{{ $color }}-50 rounded-md border p-1 transition-all active:scale-95">
                <x-heroicon-c-plus class="w-3 h-3" />
            </a>
        @endcan
    </div>
</div>
