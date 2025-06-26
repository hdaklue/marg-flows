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
        {{-- @unless ($status['records'])
            <div class="flex items-center justify-center h-24 border border-gray-100 rounded-xl dark:border-gray-800">
                <p class="w-2/3 text-sm text-center text-gray-400 dark:text-gray-500">
                    No items assinged, you can drag and drop here!
                </p>
            </div>
        @endunless --}}
        <div data-status-id="{{ $status['id'] }}" class="flex flex-1 flex-col gap-y-1.5 rounded-xl">
            @foreach ($status['records'] as $record)
                @include(static::$recordView)
            @endforeach
        </div>
    </div>
</div>
