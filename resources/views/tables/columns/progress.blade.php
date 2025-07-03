<div x-data="{
    progress: '{{ $getState()['percentage']['percentage'] }}',
    color: '{{ $getState()['color'] }}',
    status: '{{ $getState()['status'] }}'
}" class="w-full mx-3">
    <!-- Fixed height container -->
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200" x-tooltip="`${status} - ${progress}%`">
        <div x-show="progress > 0" class="h-full transition-all duration-300 rounded-full"
            :style="`width: ${progress}%; background-color: ${color} !important `"></div>
    </div>
</div>
