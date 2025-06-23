@use(App\Enums\FlowStatus)
<h3
    class="dark:text-{{ FlowStatus::from($status['id'])->getColor() }}-400 text-{{ $color }}-800 dark:bg-{{ $color }}-800/10 bg-{{ $color }}-200 mb-2 rounded-xl px-4 py-2 text-sm font-medium capitalize">
    {{-- <span class="text-primary-400">❖</span> --}}

    <span class="">{{ $status['title'] }}</span>
    <span class="text-xs">({{ count($status['records']) }})</span>
</h3>
