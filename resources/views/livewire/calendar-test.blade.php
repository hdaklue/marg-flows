<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="container mx-auto px-4 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">
                KluePortal Calendar Component Test
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400">
                Testing the generic calendar component with various configurations and dummy data
            </p>
        </div>

        {{-- Calendar Test with Event Handling --}}
        <div 
            x-data="{
                selectedEvent: null,
                selectedDate: null,
                showEventModal: false,
                showDateModal: false,
                currentView: 'month',
                currentDate: new Date().toISOString()
            }"
            @calendar:event-selected.window="selectedEvent = $event.detail.event; showEventModal = true"
            @calendar:date-selected.window="selectedDate = $event.detail.date; showDateModal = true"
            @calendar:date-double-clicked.window="selectedDate = $event.detail.date; showDateModal = true"
            @calendar:view-changed.window="currentView = $event.detail.view"
            @calendar:date-changed.window="currentDate = $event.detail.date"
            class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden"
        >
            {{-- Calendar Info Bar --}}
            <div class="bg-zinc-100 dark:bg-zinc-700 px-6 py-4 border-b border-zinc-200 dark:border-zinc-600">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            Calendar Test Component
                        </h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Current View: <span x-text="currentView" class="font-medium capitalize"></span> | 
                            Events: {{ count($events) }} items
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="inline-block w-3 h-3 bg-sky-500 rounded-full mr-1"></span> Meetings
                            <span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-1 ml-3"></span> Deadlines
                            <span class="inline-block w-3 h-3 bg-emerald-500 rounded-full mr-1 ml-3"></span> Calls
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calendar Component --}}
            <div class="p-6">
                <livewire:components.calendar.calendar-component 
                    :config="$config"
                    :events="$events"
                />
            </div>

            {{-- Event Details Modal --}}
            <div x-show="showEventModal" 
                 x-cloak
                 @click.self="showEventModal = false"
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
                            <button @click="showEventModal = false" 
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
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Start Date</label>
                                <p class="text-zinc-900 dark:text-zinc-100" 
                                   x-text="selectedEvent && new Date(selectedEvent.startDate).toLocaleString()">
                                </p>
                            </div>
                            
                            <div x-show="selectedEvent?.endDate">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">End Date</label>
                                <p class="text-zinc-900 dark:text-zinc-100" 
                                   x-text="selectedEvent?.endDate && new Date(selectedEvent.endDate).toLocaleString()">
                                </p>
                            </div>

                            <div x-show="selectedEvent?.color">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Color</label>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" 
                                         :class="'bg-' + selectedEvent?.color">
                                    </div>
                                    <span class="text-zinc-900 dark:text-zinc-100" x-text="selectedEvent?.color"></span>
                                </div>
                            </div>

                            <div x-show="selectedEvent?.meta">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Event Details</label>
                                <div class="mt-2 p-3 bg-zinc-100 dark:bg-zinc-700 rounded-lg">
                                    <template x-for="(value, key) in selectedEvent?.meta || {}" :key="key">
                                        <div class="flex justify-between py-1">
                                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400 capitalize" x-text="key"></span>
                                            <span class="text-sm text-zinc-900 dark:text-zinc-100" x-text="value"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-zinc-50 dark:bg-zinc-700 px-6 py-4 border-t border-zinc-200 dark:border-zinc-600">
                        <div class="flex justify-end gap-2">
                            <button @click="showEventModal = false" 
                                    class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                Close
                            </button>
                            <button @click="alert('Edit event: ' + selectedEvent?.title); showEventModal = false" 
                                    class="px-4 py-2 text-sm font-medium bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors">
                                Edit Event
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Date Selection Modal --}}
            <div x-show="showDateModal" 
                 x-cloak
                 @click.self="showDateModal = false"
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
                    class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-sm w-full"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                            Create New Event
                        </h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                            Selected date: <span class="font-medium" x-text="selectedDate && new Date(selectedDate).toLocaleDateString()"></span>
                        </p>
                        <div class="flex justify-end gap-2">
                            <button @click="showDateModal = false" 
                                    class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button @click="alert('Create event for ' + new Date(selectedDate).toLocaleDateString()); showDateModal = false" 
                                    class="px-4 py-2 text-sm font-medium bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors">
                                Create Event
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
                <p><strong>Total Events:</strong> {{ count($events) }}</p>
                <p><strong>Config Keys:</strong> {{ implode(', ', array_keys($config)) }}</p>
                <p><strong>Available Views:</strong> {{ implode(', ', $config['availableViews']) }}</p>
                <p><strong>Default View:</strong> {{ $config['defaultView'] }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global event listeners for debugging
    window.addEventListener('calendar:view-changed', function(event) {
        console.log('📅 Calendar view changed:', event.detail);
    });

    window.addEventListener('calendar:date-changed', function(event) {
        console.log('📅 Calendar date changed:', event.detail);
    });

    window.addEventListener('calendar:event-selected', function(event) {
        console.log('📅 Event selected:', event.detail);
    });

    window.addEventListener('calendar:date-selected', function(event) {
        console.log('📅 Date selected:', event.detail);
    });

    window.addEventListener('calendar:date-double-clicked', function(event) {
        console.log('📅 Date double-clicked:', event.detail);
    });

    console.log('📅 Calendar Test Component loaded successfully!');
});
</script>
@endpush