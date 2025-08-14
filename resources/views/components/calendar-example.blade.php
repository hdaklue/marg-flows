{{-- 
    Calendar Component Usage Examples
    
    This file demonstrates various ways to use the KluePortal Calendar Component
    Delete this file after reviewing integration patterns
--}}

{{-- Example 1: Basic Calendar with Static Events --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold mb-4">Basic Calendar with Static Events</h2>
    <div class="h-96 border rounded-lg">
        <livewire:components.calendar.calendar-component 
            :config="[
                'titleField' => 'title',
                'dateField' => 'date',
                'defaultView' => 'month',
                'availableViews' => ['month', 'week', 'day'],
                'showWeekends' => true,
                'enableEventClick' => true
            ]"
            :static-events="[
                [
                    'id' => '1',
                    'title' => 'Team Meeting',
                    'startDate' => now()->addDays(2)->toISOString(),
                    'color' => 'emerald-500',
                    'meta' => ['type' => 'meeting', 'attendees' => 5]
                ],
                [
                    'id' => '2', 
                    'title' => 'Project Deadline',
                    'startDate' => now()->addDays(7)->toISOString(),
                    'color' => 'red-500',
                    'meta' => ['type' => 'deadline', 'priority' => 'high']
                ]
            ]"
        />
    </div>
</div>

{{-- Example 2: Calendar with Model Data (Documents) --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold mb-4">Calendar with Document Events</h2>
    <div class="h-96 border rounded-lg">
        <livewire:components.calendar.calendar-component 
            :config="[
                'titleField' => 'name',
                'dateField' => 'created_at',
                'endDateField' => 'updated_at',
                'defaultView' => 'month',
                'availableViews' => ['month', 'week'],
                'showWeekends' => false,
                'enableEventClick' => true
            ]"
            model-class="App\Models\Document"
            :event-query="App\Models\Document::query()->where('tenant_id', auth()->user()->current_team_id)"
        />
    </div>
</div>

{{-- Example 3: Custom Event Handling --}}
<div class="mb-8" 
     x-data="{
         selectedEvent: null,
         selectedDate: null,
         showEventModal: false,
         showDateModal: false
     }"
     @calendar:event-selected.window="selectedEvent = $event.detail.event; showEventModal = true"
     @calendar:date-selected.window="selectedDate = $event.detail.date; showDateModal = true"
     @calendar:date-double-clicked.window="selectedDate = $event.detail.date; showDateModal = true">
    
    <h2 class="text-lg font-semibold mb-4">Calendar with Custom Event Handling</h2>
    <div class="h-96 border rounded-lg">
        <livewire:components.calendar.calendar-component 
            :config="[
                'titleField' => 'title',
                'dateField' => 'due_date',
                'colorField' => 'priority_color',
                'defaultView' => 'week',
                'enableEventClick' => true
            ]"
            model-class="App\Models\Task"
        />
    </div>

    {{-- Event Details Modal --}}
    <div x-show="showEventModal" 
         x-cloak
         @click.self="showEventModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4" x-text="selectedEvent?.title"></h3>
            <div class="space-y-2 text-sm">
                <p><strong>Date:</strong> <span x-text="selectedEvent && new Date(selectedEvent.startDate).toLocaleDateString()"></span></p>
                <div x-show="selectedEvent?.meta">
                    <strong>Details:</strong>
                    <pre x-text="JSON.stringify(selectedEvent?.meta, null, 2)" class="mt-1 p-2 bg-zinc-100 rounded text-xs"></pre>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button @click="showEventModal = false" 
                        class="px-4 py-2 bg-zinc-500 text-white rounded hover:bg-zinc-600">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Date Selection Modal --}}
    <div x-show="showDateModal" 
         x-cloak
         @click.self="showDateModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Selected Date</h3>
            <p class="mb-4">You selected: <span x-text="selectedDate && new Date(selectedDate).toLocaleDateString()"></span></p>
            <div class="flex justify-end space-x-2">
                <button @click="showDateModal = false" 
                        class="px-4 py-2 bg-zinc-500 text-white rounded hover:bg-zinc-600">
                    Cancel
                </button>
                <button @click="alert('Create event for ' + selectedDate); showDateModal = false" 
                        class="px-4 py-2 bg-sky-500 text-white rounded hover:bg-sky-600">
                    Create Event
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Example 4: Mini Calendar Widget --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold mb-4">Mini Calendar Widget</h2>
    <div class="w-80 h-64 border rounded-lg">
        <livewire:components.calendar.calendar-component 
            :config="[
                'titleField' => 'name',
                'dateField' => 'scheduled_at',
                'defaultView' => 'month',
                'availableViews' => ['month'],
                'showWeekends' => true,
                'showNavigation' => true,
                'showToday' => true,
                'enableEventClick' => false
            ]"
            :static-events="[]"
        />
    </div>
</div>

{{-- Example 5: Calendar with Timezone Support --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold mb-4">Calendar with Timezone Support</h2>
    <div class="h-96 border rounded-lg">
        <livewire:components.calendar.calendar-component 
            :config="[
                'titleField' => 'title',
                'dateField' => 'start_time',
                'endDateField' => 'end_time',
                'defaultView' => 'day',
                'availableViews' => ['day', 'week'],
                'timezone' => 'America/New_York',
                'enableEventClick' => true,
                'restrictions' => [
                    'minDate' => now()->subMonth()->toDateString(),
                    'maxDate' => now()->addMonths(3)->toDateString()
                ]
            ]"
            model-class="App\Models\Meeting"
        />
    </div>
</div>

@push('scripts')
<script>
// Global event listeners for calendar events
document.addEventListener('DOMContentLoaded', function() {
    // Listen for view changes
    window.addEventListener('calendar:view-changed', function(event) {
        console.log('Calendar view changed:', event.detail);
    });

    // Listen for date changes
    window.addEventListener('calendar:date-changed', function(event) {
        console.log('Calendar date changed:', event.detail);
    });

    // Listen for event selections
    window.addEventListener('calendar:event-selected', function(event) {
        console.log('Event selected:', event.detail);
    });

    // Listen for date selections
    window.addEventListener('calendar:date-selected', function(event) {
        console.log('Date selected:', event.detail);
    });
});
</script>
@endpush