<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Concerns\HasCalendarEvents;
use App\Contracts\CalendarInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class ToastCalendarTest extends Component implements CalendarInterface
{
    use HasCalendarEvents;

    public function getEventsCountProperty()
    {
        return $this->getEvents()->count();
    }

    public function mount()
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->viewOnly = false;
        $this->currentView = 'month';
        $this->selectedEvent = null;
    }

    /**
     * Toggle view-only mode for testing
     */
    public function toggleViewOnly(): void
    {
        $this->viewOnly = !$this->viewOnly;
        $this->dispatch('show-notification', [
            'message' => $this->viewOnly
                ? 'Calendar is now in view-only mode'
                : 'Calendar editing is now enabled',
            'type' => 'info',
        ]);
    }

    // Abstract method implementations - DOMAIN SPECIFIC LOGIC

    public function getEvents(): Collection
    {
        return collect([
            [
                'id' => '1',
                'calendarId' => 'personal',
                'title' => 'Team Meeting',
                'start' => Carbon::now()->addDays(2)->format('Y-m-d\TH:i:s'),
                'end' => Carbon::now()
                    ->addDays(2)
                    ->addHour()
                    ->format('Y-m-d\TH:i:s'),
                'category' => 'time',
                'backgroundColor' => '#03bd9e',
                'borderColor' => '#03bd9e',
            ],
            [
                'id' => '2',
                'calendarId' => 'work',
                'title' => 'Project Deadline',
                'start' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'end' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'category' => 'allday',
                'isAllday' => true,
                'backgroundColor' => '#ff5722',
                'borderColor' => '#ff5722',
            ],
            [
                'id' => '3',
                'calendarId' => 'personal',
                'title' => 'Client Call',
                'start' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i:s'),
                'end' => Carbon::now()
                    ->addDays(1)
                    ->addMinutes(30)
                    ->format('Y-m-d\TH:i:s'),
                'category' => 'time',
                'backgroundColor' => '#00a9ff',
                'borderColor' => '#00a9ff',
            ],
            [
                'id' => '4',
                'calendarId' => 'work',
                'title' => 'Code Review',
                'start' => Carbon::now()->addDays(3)->format('Y-m-d\TH:i:s'),
                'end' => Carbon::now()
                    ->addDays(3)
                    ->addHours(2)
                    ->format('Y-m-d\TH:i:s'),
                'category' => 'time',
                'backgroundColor' => '#ffc107',
                'borderColor' => '#ffc107',
            ],
            [
                'id' => '5',
                'calendarId' => 'personal',
                'title' => 'Design Workshop',
                'start' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i:s'),
                'end' => Carbon::now()
                    ->addDays(5)
                    ->addHours(3)
                    ->format('Y-m-d\TH:i:s'),
                'category' => 'time',
                'backgroundColor' => '#9c27b0',
                'borderColor' => '#9c27b0',
            ],
        ]);
    }

    public function getCalendars(): Collection
    {
        return collect([
            [
                'id' => 'personal',
                'name' => 'Personal',
                'color' => '#ffffff',
                'backgroundColor' => '#03bd9e',
                'dragBackgroundColor' => '#03bd9e',
                'borderColor' => '#03bd9e',
            ],
            [
                'id' => 'work',
                'name' => 'Work',
                'color' => '#ffffff',
                'backgroundColor' => '#00a9ff',
                'dragBackgroundColor' => '#00a9ff',
                'borderColor' => '#00a9ff',
            ],
        ]);
    }

    public function onDateSelect(array $data): void
    {
        // Test implementation - you can show a modal, create event, etc.
        $this->dispatch('show-notification', [
            'message' =>
                'Date selected: ' . $data['start'] . ' to ' . $data['end'],
            'type' => 'info',
        ]);
    }

    public function onEventClick(array $event): void
    {
        // Test implementation - event is already set in selectedEvent by trait
        $this->dispatch('show-notification', [
            'message' => 'Event clicked: ' . $event['title'],
            'type' => 'success',
        ]);
    }

    public function onEventCreate(array $data): mixed
    {
        // Test implementation
        $this->dispatch('show-notification', [
            'message' => 'Event created successfully!',
            'type' => 'success',
        ]);
        return ['success' => true, 'id' => uniqid()];
    }

    public function onEventEdit(string $eventId, array $data): mixed
    {
        // Test implementation
        $this->dispatch('show-notification', [
            'message' => "Event {$eventId} updated successfully!",
            'type' => 'success',
        ]);
        return ['success' => true];
    }

    public function onEventDelete(string $eventId): bool
    {
        // Test implementation
        $this->dispatch('show-notification', [
            'message' => "Event {$eventId} deleted successfully!",
            'type' => 'success',
        ]);
        return true;
    }

    public function onViewChange(string $view): void
    {
        // Test implementation
        $this->dispatch('show-notification', [
            'message' => "View changed to: {$view}",
            'type' => 'info',
        ]);
    }

    public function onNavigate(string $direction): void
    {
        // Test implementation
        $this->dispatch('show-notification', [
            'message' => "Navigated: {$direction}",
            'type' => 'info',
        ]);
    }

    public function render()
    {
        return view('livewire.toast-calendar-test');
    }
}
