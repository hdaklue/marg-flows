<div x-data="{
        loading: @entangle('loading'),
        currentView: @entangle('currentView'),
        selectedEvent: null,
        
        get calendarData() {
            return @js($this->calendarData);
        },

        changeView(view) {
            this.loading = true;
            $wire.changeView(view).then(() => {
                this.loading = false;
            });
        },

        goToPrevious() {
            this.loading = true;
            $wire.goToPrevious().then(() => {
                this.loading = false;
            });
        },

        goToNext() {
            this.loading = true;
            $wire.goToNext().then(() => {
                this.loading = false;
            });
        },

        goToToday() {
            this.loading = true;
            $wire.goToToday().then(() => {
                this.loading = false;
            });
        },

        selectEvent(eventId) {
            const event = this.getAllEvents().find(e => e.id === eventId);
            if (event) {
                this.selectedEvent = event;
                $wire.selectEvent(eventId);
            }
        },

        selectDate(date) {
            $wire.selectDate(date);
        },

        getAllEvents() {
            const data = this.calendarData;
            let events = [];

            if (data.type === 'month' && data.weeks) {
                data.weeks.forEach(week => {
                    week.forEach(day => {
                        if (day.events) {
                            events = events.concat(day.events);
                        }
                    });
                });
            } else if (data.type === 'week' && data.days) {
                data.days.forEach(day => {
                    if (day.events) {
                        events = events.concat(day.events);
                    }
                });
            } else if (data.type === 'day' && data.events) {
                events = data.events;
            }

            return events;
        },

        formatEventDate(event) {
            if (!event) return '';
            
            const start = new Date(event.startDate);
            const end = event.endDate ? new Date(event.endDate) : null;
            
            if (end && start.toDateString() !== end.toDateString()) {
                return `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;
            }
            
            return start.toLocaleDateString();
        },

        getEventColorClass(event) {
            if (!event.color) return 'bg-sky-500 text-white';
            
            if (event.color.startsWith('#')) {
                return '';
            }
            
            if (event.color.includes('-')) {
                return `bg-${event.color} text-white`;
            }
            
            return 'bg-sky-500 text-white';
        },

        getEventInlineStyle(event) {
            if (!event.color || !event.color.startsWith('#')) {
                return '';
            }
            
            return `background-color: ${event.color}; color: white;`;
        },

        formatEventDuration(event) {
            if (!event.startDate || !event.endDate) return '';
            
            const start = new Date(event.startDate);
            const end = new Date(event.endDate);
            const diffMs = end - start;
            
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            
            if (diffHours > 0) {
                return `${diffHours}h ${diffMinutes}m`;
            }
            
            return `${diffMinutes}m`;
        }
    }"
     class="w-full h-full bg-white dark:bg-zinc-900 rounded-lg border border-zinc-300 dark:border-zinc-700 shadow-sm">
    
    <!-- Calendar Header -->
    <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
        <!-- Navigation -->
        @if($this->config->showNavigation)
        <div class="flex items-center space-x-2">
            <button @click="goToPrevious()" 
                    class="p-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                    :disabled="loading">
                <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            
            @if($this->config->showToday)
            <button @click="goToToday()" 
                    class="px-3 py-1 text-sm rounded-md bg-sky-100 text-sky-700 hover:bg-sky-200 dark:bg-sky-900/50 dark:text-sky-300 dark:hover:bg-sky-900/70 transition-colors"
                    :disabled="loading">
                Today
            </button>
            @endif
            
            <button @click="goToNext()" 
                    class="p-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                    :disabled="loading">
                <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        @endif

        <!-- Current Date Display -->
        <div class="flex-1 text-center">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $this->currentDateFormatted }}
            </h2>
        </div>

        <!-- View Toggles -->
        @if(count($this->config->availableViews) > 1)
        <div class="flex items-center space-x-1 bg-zinc-100 dark:bg-zinc-800 rounded-md p-1">
            @foreach($this->config->availableViews as $view)
            <button @click="changeView('{{ $view }}')" 
                    class="px-3 py-1 text-sm rounded transition-colors"
                    :class="currentView === '{{ $view }}' ? 
                            'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm' : 
                            'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                    :disabled="loading">
                {{ ucfirst($view) }}
            </button>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Loading Indicator -->
    <div x-show="loading" x-cloak class="absolute inset-x-0 top-0 h-1 bg-zinc-200 dark:bg-zinc-700">
        <div class="h-full bg-sky-500 animate-pulse"></div>
    </div>

    <!-- Calendar Content -->
    <div class="flex-1 overflow-hidden">
        <!-- Month View -->
        <div x-show="currentView === 'month'" x-cloak class="h-full">
            @include('livewire.components.calendar.partials.month-view')
        </div>

        <!-- Week View -->
        <div x-show="currentView === 'week'" x-cloak class="h-full">
            @include('livewire.components.calendar.partials.week-view')
        </div>

        <!-- Day View -->
        <div x-show="currentView === 'day'" x-cloak class="h-full">
            @include('livewire.components.calendar.partials.day-view')
        </div>

        <!-- Empty State -->
        <div x-show="!calendarData || ((calendarData.weeks?.length || 0) === 0 && (calendarData.days?.length || 0) === 0 && (calendarData.events?.length || 0) === 0)" 
             x-cloak 
             class="flex flex-col items-center justify-center h-64 text-zinc-500 dark:text-zinc-400">
            <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-sm">No events to display</p>
        </div>
    </div>

    <!-- Event Details Modal/Popup (Optional) -->
    <div x-show="selectedEvent" 
         x-cloak
         @click.self="selectedEvent = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" x-text="selectedEvent?.title"></h3>
                <button @click="selectedEvent = null" 
                        class="p-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                <p><strong>Date:</strong> <span x-text="formatEventDate(selectedEvent)"></span></p>
                <div x-show="selectedEvent?.meta" x-cloak>
                    <strong>Details:</strong>
                    <pre x-text="JSON.stringify(selectedEvent?.meta, null, 2)" class="mt-1 p-2 bg-zinc-100 dark:bg-zinc-700 rounded text-xs overflow-auto max-h-32"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

