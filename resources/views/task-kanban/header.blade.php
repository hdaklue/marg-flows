<div class="flex items-center justify-between px-1">
    <div class="w-2/3">
        <h3
            class="dark:text-{{ $color }}-400 text-{{ $color }}-800 dark:bg-{{ $color }}-800/10 bg-{{ $color }}-200 flex justify-between rounded-xl px-2 py-1 text-sm font-medium capitalize">
            {{-- <span class="text-primary-400">❖</span> --}}

            <span class="">{{ $status['title'] }}</span>
            <span
                class="bg-{{ $color }}-900/50 text-{{ $color }}-100/70 text-2xs rounded-lg px-1 py-0.5 font-bold">{{ count($status['records']) }}</span>
        </h3>
    </div>
    <div class="flex items-center justify-center">
        @can('manageFlows', filament()->getTenant())
            <a href="{{ App\Filament\Resources\FlowResource::getUrl('create') }}"
                class="border-{{ $color }}-400 dark:border-{{ $color }}-800/30 dark:hover:bg-{{ $color }}-600/40 text-{{ $color }}-400 dark:text-{{ $color }}-600/50 dark:bg-{{ $color }}-800/10 hover:text-{{ $color }}-700 hover:bg-{{ $color }}-200 dark:hover:text-{{ $color }}-400 bg-{{ $color }}-50 rounded-md border p-2 transition-all active:scale-95">
                <x-heroicon-c-plus class="w-4 h-4" />
            </a>
        @endcan
    </div>
</div>
