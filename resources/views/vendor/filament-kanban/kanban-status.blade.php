@props(['status'])
@use(App\Enums\FlowStatus)
@php
    $color = FlowStatus::from($status['id'])->getColor();
@endphp


<div class="flex flex-col" x-data="{
    height: window.innerHeight,
    init() {
        this.updateHeight();
        window.addEventListener('resize', () => this.updateHeight());
    },
    updateHeight() {
        this.height = window.innerHeight - 300;
    }
}">
    @include(static::$headerView)
    <div class="md:scrollbar-hide mb-5 flex h-auto max-h-screen flex-shrink-0 flex-col overflow-y-auto overscroll-y-contain rounded-xl p-2 md:w-[22rem]"
        x-bind:style="'height:' + height + 'px'">
        <div data-status-id="{{ $status['id'] }}" class="flex flex-1 flex-col gap-y-1.5 rounded-xl">
            @foreach ($status['records'] as $record)
                @include(static::$recordView)
            @endforeach
        </div>
    </div>
</div>
