<div class="h-full flex flex-col">
    <!-- Day Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 p-4">
        <div class="text-center">
            <h2 class="text-2xl font-bold"
                :class="{
                    'text-sky-600 dark:text-sky-400': calendarData.isToday,
                    'text-zinc-900 dark:text-zinc-100': !calendarData.isToday
                }"
                x-text="calendarData.day">
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1" x-text="calendarData.dayName"></p>
            <p x-show="calendarData.isToday" 
               x-cloak
               class="text-xs text-sky-600 dark:text-sky-400 mt-1 font-medium">
                Today
            </p>
        </div>
    </div>

    <!-- Day Content -->
    <div class="flex-1 overflow-auto p-4">
        <!-- Events List -->
        <div x-show="calendarData.events?.length > 0" x-cloak class="space-y-3">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-4">
                Events (<span x-text="calendarData.events?.length || 0"></span>)
            </h3>
            
            <template x-for="(event, eventIndex) in calendarData.events || []" :key="`day-event-${eventIndex}`">
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 cursor-pointer hover:shadow-md transition-all hover:border-zinc-300 dark:hover:border-zinc-600"
                     @click="selectEvent(event.id)">
                    
                    <!-- Event Header -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="w-4 h-4 rounded-full flex-shrink-0"
                                 :class="getEventColorClass(event)"
                                 :style="getEventInlineStyle(event)">
                            </div>
                            <h4 class="font-medium text-zinc-900 dark:text-zinc-100 truncate" x-text="event.title"></h4>
                        </div>
                        
                        <div x-show="event.allDay" 
                             x-cloak
                             class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 px-2 py-1 rounded-full ml-2 flex-shrink-0">
                            All Day
                        </div>
                    </div>

                    <!-- Event Time -->
                    <div x-show="!event.allDay && event.startDate" 
                         x-cloak
                         class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                        <span x-text="new Date(event.startDate).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })"></span>
                        <span x-show="event.endDate" x-cloak>
                            - <span x-text="new Date(event.endDate).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })"></span>
                        </span>
                    </div>

                    <!-- Event Duration -->
                    <div x-show="event.endDate && event.startDate !== event.endDate" 
                         x-cloak
                         class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                        Duration: <span x-text="formatEventDuration(event)"></span>
                    </div>

                    <!-- Event Meta (if available) -->
                    <div x-show="event.meta && Object.keys(event.meta).length > 0" 
                         x-cloak
                         class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <details class="text-sm">
                            <summary class="cursor-pointer text-zinc-600 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200">
                                Event Details
                            </summary>
                            <div class="mt-2 space-y-1">
                                <template x-for="[key, value] in Object.entries(event.meta || {})" :key="`meta-${key}`">
                                    <div x-show="key !== 'model'" x-cloak class="flex justify-between">
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300 capitalize" x-text="key.replace(/([A-Z])/g, ' $1').trim()"></span>
                                        <span class="text-zinc-600 dark:text-zinc-400" x-text="typeof value === 'object' ? JSON.stringify(value) : value"></span>
                                    </div>
                                </template>
                            </div>
                        </details>
                    </div>

                    <!-- Event URL -->
                    <div x-show="event.url" 
                         x-cloak
                         class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <a :href="event.url" 
                           target="_blank"
                           class="inline-flex items-center text-sm text-sky-600 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            View Details
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!calendarData.events || (calendarData.events?.length || 0) === 0" 
             x-cloak
             class="h-full flex flex-col items-center justify-center text-zinc-500 dark:text-zinc-400">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium mb-2">No events today</h3>
            <p class="text-sm text-center max-w-sm">
                There are no events scheduled for this day. Events will appear here when they are added.
            </p>
        </div>
    </div>

    <!-- Quick Actions (Optional) -->
    <div class="border-t border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex justify-center">
            <button @click="selectDate(calendarData.date)" 
                    class="px-4 py-2 bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300 rounded-md hover:bg-sky-200 dark:hover:bg-sky-900/70 transition-colors text-sm font-medium">
                Add Event to This Day
            </button>
        </div>
    </div>
</div>

