<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="container mx-auto px-4 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">
                TOAST UI Calendar - TALL Stack Integration
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400">
                Testing TOAST UI Calendar with Livewire and Alpine.js integration
            </p>
        </div>

        {{-- Calendar Container --}}
        <div 
            x-data="toastCalendar()"
            x-init="initCalendar()"
            @calendar-event-clicked.window="handleEventClick($event.detail)"
            @calendar-date-selected.window="handleDateSelect($event.detail)"
            class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden"
        >
            {{-- Calendar Controls --}}
            <div class="bg-zinc-100 dark:bg-zinc-700 px-6 py-4 border-b border-zinc-200 dark:border-zinc-600">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            TOAST UI Calendar
                        </h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            View: <span x-text="currentView" class="font-medium capitalize"></span> | 
                            Events: {{ $this->eventsCount }} items
                            @if($this->viewOnly)
                                | <span class="inline-flex items-center px-2 py-1 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Only
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- View Switcher --}}
                        <div class="flex items-center space-x-1 bg-zinc-200 dark:bg-zinc-600 rounded-md p-1">
                            <button @click="changeView('month')" 
                                    :class="currentView === 'month' ? 
                                            'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm' : 
                                            'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                                    class="px-3 py-1 text-sm rounded transition-colors">
                                Month
                            </button>
                            <button @click="changeView('week')" 
                                    :class="currentView === 'week' ? 
                                            'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm' : 
                                            'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                                    class="px-3 py-1 text-sm rounded transition-colors">
                                Week
                            </button>
                        </div>

                        {{-- Navigation --}}
                        <div class="flex items-center space-x-2">
                            {{-- View-Only Toggle (Test Only) --}}
                            <button wire:click="toggleViewOnly" 
                                    class="px-3 py-1 text-xs rounded-md {{ $this->viewOnly ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300' }} transition-colors">
                                {{ $this->viewOnly ? 'Enable Editing' : 'View Only' }}
                            </button>
                            
                            <button @click="goPrev()" 
                                    class="p-2 rounded-md hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                                <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            
                            <button @click="goToday()" 
                                    class="px-3 py-1 text-sm rounded-md bg-sky-100 text-sky-700 hover:bg-sky-200 dark:bg-sky-900/50 dark:text-sky-300 dark:hover:bg-sky-900/70 transition-colors">
                                Today
                            </button>
                            
                            <button @click="goNext()" 
                                    class="p-2 rounded-md hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                                <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOAST UI Calendar Container --}}
            <div class="p-6">
                <div id="toast-calendar" style="height: 600px;"></div>
            </div>

            {{-- Event Details Modal --}}
            <div x-show="selectedEvent" 
                 x-cloak
                 @click.self="selectedEvent = null"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto"
                >
                    {{-- Modal Header --}}
                    <div class="bg-zinc-50 dark:bg-zinc-700 px-6 py-4 border-b border-zinc-200 dark:border-zinc-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" 
                                x-text="selectedEvent?.title">
                            </h3>
                            <button @click="selectedEvent = null" 
                                    class="p-2 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Calendar</label>
                                <p class="text-zinc-900 dark:text-zinc-100" x-text="selectedEvent?.calendarId">
                                </p>
                            </div>
                            
                            <div>
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Start Time</label>
                                <p class="text-zinc-900 dark:text-zinc-100" 
                                   x-text="selectedEvent && formatEventDate(selectedEvent.start)">
                                </p>
                            </div>
                            
                            <div x-show="selectedEvent?.end">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">End Time</label>
                                <p class="text-zinc-900 dark:text-zinc-100" 
                                   x-text="selectedEvent?.end && formatEventDate(selectedEvent.end)">
                                </p>
                            </div>

                            <div x-show="selectedEvent?.category">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Category</label>
                                <span class="inline-block mt-1 px-2 py-1 text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-full capitalize"
                                      x-text="selectedEvent?.category">
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-zinc-50 dark:bg-zinc-700 px-6 py-4 border-t border-zinc-200 dark:border-zinc-600">
                        <div class="flex justify-end gap-2">
                            <button @click="selectedEvent = null" 
                                    class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                Close
                            </button>
                            <button @click="editEvent(selectedEvent)" 
                                    x-show="!@json($this->viewOnly)"
                                    class="px-4 py-2 text-sm font-medium bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors">
                                Edit Event
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Debug Info (Development only) --}}
        @if(app()->environment('local'))
        <div class="mt-8 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Debug Information</h3>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <p><strong>Environment:</strong> {{ app()->environment() }}</p>
                <p><strong>Total Events:</strong> {{ $this->eventsCount }}</p>
                <p><strong>Calendars:</strong> {{ $this->getCalendars()->count() }}</p>
                <p><strong>TOAST UI Calendar Version:</strong> 2.1.3</p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function toastCalendar() {
    return {
        calendar: null,
        currentView: 'month',
        selectedEvent: null,
        isProcessingClick: false,
        isProcessingSelect: false,

        initCalendar() {
            // Prevent multiple initializations
            if (this.calendar) {
                return;
            }
            
            // Use globally available Calendar class
            if (window.ToastUICalendar) {
                // Create calendar instance
                this.calendar = new window.ToastUICalendar('#toast-calendar', {
                    defaultView: 'month',
                    useCreationPopup: false,
                    useDetailPopup: false,
                    isReadOnly: @json($this->viewOnly),
                    usageStatistics: false,
                    theme: {
                        common: {
                            backgroundColor: 'transparent',
                            gridSelection: {
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                border: '1px solid #3b82f6',
                            },
                        },
                        month: {
                            weekend: {
                                backgroundColor: 'rgba(156, 163, 175, 0.05)',
                            },
                        },
                        week: {
                            today: {
                                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            },
                            weekend: {
                                backgroundColor: 'rgba(156, 163, 175, 0.05)',
                            },
                        },
                    },
                });

                // Set calendars
                const calendars = @json($this->getCalendars() ?? []);
                this.calendar.setCalendars(calendars);

                // Create events
                const events = @json($this->getEvents() ?? []);
                this.calendar.createEvents(events);

                // Add event listeners with improved debouncing
                let lastClickTime = 0;
                let lastSelectTime = 0;
                
                this.calendar.on('clickEvent', ({ event }) => {
                    const now = Date.now();
                    if (now - lastClickTime < 300) return;
                    
                    lastClickTime = now;
                    this.selectedEvent = event;
                    this.$dispatch('calendar-event-clicked', event);
                });

                this.calendar.on('selectDateTime', ({ start, end }) => {
                    const now = Date.now();
                    if (now - lastSelectTime < 300) return;
                    
                    lastSelectTime = now;
                    console.log('Date selected:', { start, end });
                    
                    // Dispatch the event after selection is complete
                    setTimeout(() => {
                        this.$dispatch('calendar-date-selected', { start, end });
                        // Clear the selection to prevent multiple events
                        this.calendar.clearGridSelections();
                    }, 100);
                });

                console.log('📅 TOAST UI Calendar initialized successfully!');
            } else {
                console.error('TOAST UI Calendar not available. Make sure it is loaded in app.js');
            }
        },

        changeView(view) {
            if (this.calendar) {
                this.calendar.changeView(view);
                this.currentView = view;
            }
        },

        goPrev() {
            if (this.calendar) {
                this.calendar.prev();
            }
        },

        goNext() {
            if (this.calendar) {
                this.calendar.next();
            }
        },

        goToday() {
            if (this.calendar) {
                this.calendar.today();
            }
        },

        handleEventClick(event) {
            console.log('Event clicked:', event);
        },

        handleDateSelect(data) {
            console.log('Date selected:', data);
            // You could open a create event modal here
        },

        editEvent(event) {
            console.log('Edit event:', event);
            // Implement edit functionality
            this.selectedEvent = null;
        },

        formatEventDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString();
        },

        formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }
    };
}
</script>