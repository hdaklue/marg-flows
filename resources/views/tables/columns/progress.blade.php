<div x-data="{
    progressDetails: @js($getState()),
}" class="w-full mx-3">
    <!-- Fixed height container -->
    <p class="text-3xs w-full rounded border border-red-700 bg-red-500/20 p-0.5 text-center font-semibold uppercase tracking-wider text-red-700 dark:bg-red-500/10 dark:text-red-800"
        x-show="progressDetails.days_remaining < 0" x-cloak>
        Overdue
    </p>
    <p class="text-3xs w-full rounded border border-sky-700 bg-sky-500/20 p-0.5 text-center font-semibold uppercase tracking-wider text-sky-700 dark:bg-sky-500/10 dark:text-sky-600"
        x-show="progressDetails.days_remaining === 0" x-cloak>
        Due Today
    </p>
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
        x-show="progressDetails.days_remaining > 0"
        x-tooltip="`${progressDetails.status} - ${progressDetails.percentage.display}`">
        <div class="h-full transition-all duration-300 rounded-full"
            :style="`width: ${progressDetails.percentage.percentage}%; background-color: ${progressDetails.color} !important `">
        </div>
    </div>
</div>
