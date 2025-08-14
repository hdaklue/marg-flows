<div class="h-full flex flex-col">
    <!-- Week Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <div class="grid" :style="`grid-template-columns: repeat(${calendarData.days?.length || 7}, 1fr)`">
            <template x-for="(day, dayIndex) in calendarData.days || []" :key="`week-header-${dayIndex}`">
                <div class="p-3 text-center border-r border-zinc-200 dark:border-zinc-700 last:border-r-0"
                     :class="{
                         'bg-sky-50 dark:bg-sky-900/20': day.isToday
                     }">
                    <div class="text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase tracking-wide mb-1"
                         x-text="day.dayName">
                    </div>
                    <div class="text-lg font-semibold"
                         :class="{
                             'text-sky-600 dark:text-sky-400': day.isToday,
                             'text-zinc-900 dark:text-zinc-100': !day.isToday
                         }"
                         x-text="day.day">
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Week Content -->
    <div class="flex-1 overflow-auto">
        <div class="grid h-full" :style="`grid-template-columns: repeat(${calendarData.days?.length || 7}, 1fr)`">
            <template x-for="(day, dayIndex) in calendarData.days || []" :key="`week-day-${dayIndex}`">
                <div class="border-r border-zinc-200 dark:border-zinc-700 last:border-r-0 p-2 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                     @click="selectDate(day.date)"
                     :class="{
                         'bg-sky-50/50 dark:bg-sky-900/10': day.isToday
                     }">
                    
                    <!-- Events List -->
                    <div class="space-y-2">
                        <template x-for="(event, eventIndex) in day.events || []" :key="`week-event-${day.date}-${eventIndex}`">
                            <div class="p-2 rounded text-xs cursor-pointer transition-all hover:scale-105 hover:shadow-sm"
                                 :class="getEventColorClass(event)"
                                 :style="getEventInlineStyle(event)"
                                 @click.stop="selectEvent(event.id)"
                                 x-tooltip="event.title">
                                <div class="font-medium truncate" x-text="event.title"></div>
                                <div x-show="event.startDate" 
                                     x-cloak
                                     class="opacity-75 text-xs mt-1"
                                     x-text="new Date(event.startDate).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })">
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State for Day -->
                    <div x-show="!day.events || day.events.length === 0" 
                         x-cloak
                         class="h-full flex items-center justify-center text-zinc-400 dark:text-zinc-600 text-xs">
                        No events
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Mobile Week View -->
    <div class="block md:hidden">
        <template x-for="(day, dayIndex) in calendarData.days || []" :key="`mobile-week-${dayIndex}`">
            <div class="border-b border-zinc-200 dark:border-zinc-700 last:border-b-0">
                <div class="p-4"
                     :class="{
                         'bg-sky-50 dark:bg-sky-900/20': day.isToday
                     }">
                    
                    <!-- Date Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="font-medium text-zinc-900 dark:text-zinc-100">
                                <span x-text="day.dayName"></span>
                                <span x-text="day.day" class="ml-1"></span>
                                <span x-show="day.isToday" x-cloak class="text-sky-600 dark:text-sky-400 text-sm ml-1">(Today)</span>
                            </h3>
                        </div>
                        
                        <span x-show="day.events?.length > 0" 
                              x-cloak
                              class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 px-2 py-1 rounded-full"
                              x-text="`${day.events.length} event${day.events.length !== 1 ? 's' : ''}`">
                        </span>
                    </div>

                    <!-- Events List -->
                    <div x-show="day.events?.length > 0" x-cloak class="space-y-2">
                        <template x-for="(event, eventIndex) in day.events || []" :key="`mobile-week-${day.date}-${eventIndex}`">
                            <div class="flex items-center space-x-3 p-3 rounded bg-zinc-50 dark:bg-zinc-800 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                 @click="selectEvent(event.id)">
                                <div class="w-4 h-4 rounded-full flex-shrink-0"
                                     :class="getEventColorClass(event)"
                                     :style="getEventInlineStyle(event)">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate" x-text="event.title"></div>
                                    <div x-show="event.startDate" 
                                         x-cloak
                                         class="text-xs text-zinc-500 dark:text-zinc-400"
                                         x-text="new Date(event.startDate).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!day.events || day.events.length === 0" 
                         x-cloak
                         class="text-center text-zinc-400 dark:text-zinc-600 text-sm py-4">
                        No events scheduled
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>