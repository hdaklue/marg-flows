<div class="h-full flex flex-col">
    <!-- Weekday Headers -->
    <div class="grid border-b border-zinc-200 dark:border-zinc-700" 
         :style="`grid-template-columns: repeat(${calendarData.weekdays?.length || 7}, 1fr)`">
        <template x-for="weekday in calendarData.weekdays || ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="weekday">
            <div class="p-2 text-center text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                <span x-text="weekday"></span>
            </div>
        </template>
    </div>

    <!-- Calendar Grid -->
    <div class="flex-1 overflow-auto">
        <template x-for="(week, weekIndex) in calendarData.weeks || []" :key="weekIndex">
            <div class="grid border-b border-zinc-200 dark:border-zinc-700 min-h-24 last:border-b-0" 
                 :style="`grid-template-columns: repeat(${week.length}, 1fr)`">
                <template x-for="(day, dayIndex) in week" :key="`${weekIndex}-${dayIndex}`">
                    <div class="border-r border-zinc-200 dark:border-zinc-700 last:border-r-0 p-1 relative overflow-hidden"
                         :class="{
                             'bg-zinc-50 dark:bg-zinc-800/50': !day.isCurrentMonth,
                             'bg-sky-50 dark:bg-sky-900/20': day.isToday,
                             'cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800': true
                         }"
                         @click="selectDate(day.date)">
                        
                        <!-- Day Number -->
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium"
                                  :class="{
                                      'text-zinc-400 dark:text-zinc-600': !day.isCurrentMonth,
                                      'text-sky-600 dark:text-sky-400 font-bold': day.isToday,
                                      'text-zinc-900 dark:text-zinc-100': day.isCurrentMonth && !day.isToday
                                  }"
                                  x-text="day.day">
                            </span>
                            
                            <!-- Event Count Indicator -->
                            <span x-show="day.events && day.events.length > 3" 
                                  x-cloak
                                  class="text-xs text-zinc-500 dark:text-zinc-400"
                                  x-text="`+${day.events.length - 3}`">
                            </span>
                        </div>

                        <!-- Events -->
                        <div class="space-y-1">
                            <template x-for="(event, eventIndex) in (day.events || []).slice(0, 3)" :key="`${day.date}-${eventIndex}`">
                                <div class="text-xs p-1 rounded truncate cursor-pointer transition-all hover:scale-105"
                                     :class="getEventColorClass(event)"
                                     :style="getEventInlineStyle(event)"
                                     @click.stop="selectEvent(event.id)"
                                     x-tooltip="event.title">
                                    <span x-text="event.title"></span>
                                </div>
                            </template>
                        </div>

                        <!-- More Events Indicator -->
                        <div x-show="day.events && day.events.length > 3" 
                             x-cloak
                             class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer"
                             @click.stop="selectDate(day.date)">
                            <span x-text="`+${day.events.length - 3} more`"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Mobile Month View -->
    <div class="block md:hidden">
        <template x-for="(week, weekIndex) in calendarData.weeks || []" :key="`mobile-${weekIndex}`">
            <div class="border-b border-zinc-200 dark:border-zinc-700">
                <template x-for="(day, dayIndex) in week" :key="`mobile-${weekIndex}-${dayIndex}`">
                    <div x-show="day.isCurrentMonth || day.events?.length > 0" 
                         x-cloak
                         class="p-3 border-b border-zinc-100 dark:border-zinc-800 last:border-b-0"
                         :class="{
                             'bg-sky-50 dark:bg-sky-900/20': day.isToday
                         }">
                        
                        <!-- Date Header -->
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-zinc-900 dark:text-zinc-100">
                                <span x-text="new Date(day.date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })"></span>
                                <span x-show="day.isToday" x-cloak class="text-sky-600 dark:text-sky-400 text-sm ml-1">(Today)</span>
                            </h3>
                            
                            <span x-show="day.events?.length > 0" 
                                  x-cloak
                                  class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 px-2 py-1 rounded-full"
                                  x-text="`${day.events.length} event${day.events.length !== 1 ? 's' : ''}`">
                            </span>
                        </div>

                        <!-- Events List -->
                        <div x-show="day.events?.length > 0" x-cloak class="space-y-2">
                            <template x-for="(event, eventIndex) in day.events || []" :key="`mobile-${day.date}-${eventIndex}`">
                                <div class="flex items-center space-x-2 p-2 rounded bg-zinc-50 dark:bg-zinc-800 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                     @click="selectEvent(event.id)">
                                    <div class="w-3 h-3 rounded-full flex-shrink-0"
                                         :class="getEventColorClass(event)"
                                         :style="getEventInlineStyle(event)">
                                    </div>
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100 truncate" x-text="event.title"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>