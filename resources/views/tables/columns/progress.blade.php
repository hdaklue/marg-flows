<div x-data="{
    progress: '{{ round($getState()['percentage']) }}',
    color: '{{ $getState()['color'] }}',
    status: '{{ $getState()['status'] }}'
}" class="mx-3 w-full">
    <!-- Fixed height container -->
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200" x-tooltip="`${status} - ${progress}%`">
        <div x-show="progress > 0" class="h-full rounded-full transition-all duration-300"
            :style="`width: ${progress}%; background-color: ${color} !important `"></div>
    </div>
</div>
