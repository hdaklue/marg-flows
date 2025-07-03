<divs class="w-full">
    <!-- Fixed height container -->
    <div class="h-0.5 w-full cursor-default overflow-hidden rounded-full bg-gray-200"
        x-tooltip="`${progressDetails.status} - ${progressDetails.percentage.display}`">
        <div x-show="progressDetails.percentage.percentage > 0" class="h-full transition-all duration-300 rounded-full"
            :class="progressDetails.percentage.percentage > 0 ? `bg-${color}-500/70` : ''"
            :style="`width: ${progressDetails.percentage.percentage}%;`">
        </div>
    </div>
</divs>
