<div x-data="{
    progressDetails: @js($getState()),
}" x-init="console.log(progressDetails)" class="mx-3 w-full">
    <!-- Fixed height container -->
    <p class="text-3xs w-full rounded border border-red-700 bg-red-500/20 p-0.5 text-center font-semibold uppercase tracking-wider text-red-700 dark:bg-red-500/10 dark:text-red-800"
        x-show="progressDetails.days_remaining < 0" x-cloak>
        Overdue
    </p>
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200" x-show="progressDetails.days_remaining > 0"
        x-tooltip="`${progressDetails.status} - ${progressDetails.percentage.display}`">
        <div class="h-full rounded-full transition-all duration-300"
            :style="`width: ${progressDetails.percentage.percentage}%; background-color: ${progressDetails.color} !important `">
        </div>
    </div>
</div>
