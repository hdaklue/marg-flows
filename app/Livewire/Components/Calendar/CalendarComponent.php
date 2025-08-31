<?php

declare(strict_types=1);

namespace App\Livewire\Components\Calendar;

use App\DTOs\Calendar\CalendarConfigDTO;
use App\DTOs\Calendar\CalendarEventDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Generic Calendar Component for KluePortal
 * 
 * @property-read CalendarConfigDTO $config
 * @property-read array $calendarData
 * @property-read array $currentEvents
 * @property-read string $currentMonthName
 * @property-read string $currentDateFormatted
 */
final class CalendarComponent extends Component
{
    #[Locked]
    public array $configArray = [];

    public string $currentView = 'month';
    public string $currentDate;
    public bool $loading = false;

    // Event source data
    public array $events = [];

    public function mount(
        array $config = [],
        array $events = []
    ): void {
        $this->configArray = array_merge([
            'titleField' => 'title',
            'dateField' => 'startDate',
        ], $config);

        $this->events = $events;

        $this->currentDate = now()->toISOString();
        $this->currentView = $this->config->defaultView;
    }

    #[Computed]
    public function config(): CalendarConfigDTO
    {
        return CalendarConfigDTO::fromArray($this->configArray);
    }

    #[Computed]
    public function calendarData(): array
    {
        return match ($this->currentView) {
            'month' => $this->buildMonthView(),
            'week' => $this->buildWeekView(),
            'day' => $this->buildDayView(),
            default => $this->buildMonthView()
        };
    }

    #[Computed]
    public function currentEvents(): array
    {
        $events = collect($this->events);

        // Load additional events using loadEvents method (can be overridden)
        $additionalEvents = $this->loadEvents();
        if (!empty($additionalEvents)) {
            $events = $events->merge($additionalEvents);
        }

        return $events->map(fn($event) => $this->ensureEventDTO($event))->toArray();
    }

    /**
     * Load additional events - override this method in child components
     * to load events from database or external sources
     */
    protected function loadEvents(): array
    {
        return [];
    }

    #[Computed]
    public function currentMonthName(): string
    {
        return Carbon::parse($this->currentDate)->format('F Y');
    }

    #[Computed]
    public function currentDateFormatted(): string
    {
        $date = Carbon::parse($this->currentDate);
        return match ($this->currentView) {
            'day' => $date->format('l, F j, Y'),
            'week' => sprintf(
                'Week of %s - %s',
                $date->copy()->startOfWeek()->format('M j'),
                $date->copy()->endOfWeek()->format('M j, Y')
            ),
            default => $this->currentMonthName
        };
    }

    public function changeView(string $view): void
    {
        if (!in_array($view, $this->config->availableViews)) {
            return;
        }

        $this->currentView = $view;
        $this->dispatch('calendar:view-changed', view: $view, date: $this->currentDate);
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->toISOString();
        $this->dispatch('calendar:date-changed', date: $this->currentDate);
    }

    public function goToPrevious(): void
    {
        $currentDateCarbon = Carbon::parse($this->currentDate);
        $newDate = match ($this->currentView) {
            'month' => $currentDateCarbon->subMonth(),
            'week' => $currentDateCarbon->subWeek(),
            'day' => $currentDateCarbon->subDay(),
            default => $currentDateCarbon->subMonth()
        };

        $this->currentDate = $newDate->toISOString();
        $this->dispatch('calendar:date-changed', date: $this->currentDate);
    }

    public function goToNext(): void
    {
        $currentDateCarbon = Carbon::parse($this->currentDate);
        $newDate = match ($this->currentView) {
            'month' => $currentDateCarbon->addMonth(),
            'week' => $currentDateCarbon->addWeek(),
            'day' => $currentDateCarbon->addDay(),
            default => $currentDateCarbon->addMonth()
        };

        $this->currentDate = $newDate->toISOString();
        $this->dispatch('calendar:date-changed', date: $this->currentDate);
    }

    public function goToDate(string $date): void
    {
        try {
            $this->currentDate = Carbon::parse($date)->toISOString();
            $this->dispatch('calendar:date-changed', date: $this->currentDate);
        } catch (\Exception $e) {
            // Invalid date, ignore
        }
    }

    public function selectEvent(string $eventId): void
    {
        if (!$this->config->enableEventClick) {
            return;
        }

        $event = collect($this->currentEvents)->firstWhere('id', $eventId);
        
        if ($event) {
            $this->dispatch('calendar:event-selected', eventId: $eventId, event: $event);
        }
    }

    public function selectDate(string $date): void
    {
        try {
            $selectedDate = Carbon::parse($date);
            $this->dispatch('calendar:date-selected', date: $selectedDate->toISOString());
        } catch (\Exception $e) {
            // Invalid date, ignore
        }
    }

    public function refreshEvents(): void
    {
        $this->loading = true;
        
        // Reset computed properties
        unset($this->currentEvents);
        
        $this->loading = false;
        $this->dispatch('calendar:events-refreshed');
    }

    private function buildMonthView(): array
    {
        $currentDateCarbon = Carbon::parse($this->currentDate);
        $startOfMonth = $currentDateCarbon->copy()->startOfMonth();
        $endOfMonth = $currentDateCarbon->copy()->endOfMonth();
        
        // Get first day of week for the month view (start from Sunday or Monday based on locale)
        $startDate = $startOfMonth->copy()->startOfWeek();
        $endDate = $endOfMonth->copy()->endOfWeek();

        $weeks = [];
        $currentWeekStart = $startDate->copy();

        while ($currentWeekStart->lte($endDate)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $currentWeekStart->copy()->addDays($i);
                
                if (!$this->config->showWeekends && in_array($date->dayOfWeek, [0, 6])) {
                    continue;
                }

                $dayEvents = collect($this->currentEvents)->filter(
                    fn($event) => $this->ensureEventDTO($event)->occursOnDate($date)
                )->values()->toArray();

                $week[] = [
                    'date' => $date->toDateString(),
                    'day' => $date->day,
                    'isCurrentMonth' => $date->month === $currentDateCarbon->month,
                    'isToday' => $date->isToday(),
                    'isWeekend' => in_array($date->dayOfWeek, [0, 6]),
                    'events' => $dayEvents,
                    'dayOfWeek' => $date->dayOfWeek,
                ];
            }
            
            if (!empty($week)) {
                $weeks[] = $week;
            }
            
            $currentWeekStart->addWeek();
        }

        return [
            'type' => 'month',
            'weeks' => $weeks,
            'weekdays' => $this->getWeekdayLabels(),
        ];
    }

    private function buildWeekView(): array
    {
        $currentDateCarbon = Carbon::parse($this->currentDate);
        $startOfWeek = $currentDateCarbon->copy()->startOfWeek();
        $endOfWeek = $currentDateCarbon->copy()->endOfWeek();

        $days = [];
        $current = $startOfWeek->copy();

        while ($current->lte($endOfWeek)) {
            if (!$this->config->showWeekends && in_array($current->dayOfWeek, [0, 6])) {
                $current->addDay();
                continue;
            }

            $dayEvents = collect($this->currentEvents)->filter(
                fn($event) => $this->ensureEventDTO($event)->occursOnDate($current)
            )->values()->toArray();

            $days[] = [
                'date' => $current->toDateString(),
                'day' => $current->day,
                'dayName' => $current->format('D'),
                'isToday' => $current->isToday(),
                'isWeekend' => in_array($current->dayOfWeek, [0, 6]),
                'events' => $dayEvents,
            ];

            $current->addDay();
        }

        return [
            'type' => 'week',
            'days' => $days,
        ];
    }

    private function buildDayView(): array
    {
        $currentDateCarbon = Carbon::parse($this->currentDate);
        $dayEvents = collect($this->currentEvents)->filter(
            fn($event) => $this->ensureEventDTO($event)->occursOnDate($currentDateCarbon)
        )->values()->toArray();

        return [
            'type' => 'day',
            'date' => $currentDateCarbon->toDateString(),
            'day' => $currentDateCarbon->day,
            'dayName' => $currentDateCarbon->format('l'),
            'isToday' => $currentDateCarbon->isToday(),
            'events' => $dayEvents,
        ];
    }

    private function getWeekdayLabels(): array
    {
        $labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        if (!$this->config->showWeekends) {
            return array_slice($labels, 1, 5); // Mon-Fri only
        }

        return $labels;
    }


    private function ensureEventDTO($event): CalendarEventDTO
    {
        if ($event instanceof CalendarEventDTO) {
            return $event;
        }

        if (is_array($event)) {
            return CalendarEventDTO::fromArray($event);
        }

        throw new \InvalidArgumentException('Event must be CalendarEventDTO or array');
    }


    public function render()
    {
        return view('livewire.components.calendar.calendar-component');
    }
}