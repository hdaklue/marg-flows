<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Collection;
use Livewire\Attributes\On;

trait HasCalendarEvents
{
    /**
     * Current calendar view (month, week, day).
     */
    public string $currentView = 'month';

    /**
     * Current date being displayed.
     */
    public string $currentDate;

    /**
     * Selected event for modal display.
     */
    public ?array $selectedEvent = null;

    /**
     * View-only mode - prevents create/edit/delete operations.
     */
    public bool $viewOnly = false;

    /**
     * Initialize calendar properties.
     */
    public function initializeHasCalendarEvents(): void
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    /**
     * Handle date selection from calendar.
     */
    #[On('calendar-date-selected')]
    public function handleDateSelection(array $data): void
    {
        // Skip date selection handling in view-only mode
        if ($this->viewOnly) {
            return;
        }

        $this->onDateSelect($data);
    }

    /**
     * Handle event click from calendar.
     */
    #[On('calendar-event-clicked')]
    public function handleEventClick(array $event): void
    {
        $this->selectedEvent = $event;
        $this->onEventClick($event);
    }

    /**
     * Handle view change from calendar.
     */
    #[On('calendar-view-changed')]
    public function handleViewChange(string $view): void
    {
        $this->currentView = $view;
        $this->onViewChange($view);
    }

    /**
     * Handle navigation from calendar.
     */
    #[On('calendar-navigation')]
    public function handleNavigation(string $direction): void
    {
        $this->onNavigate($direction);
    }

    /**
     * Close event modal/selection.
     */
    public function closeEventModal(): void
    {
        $this->selectedEvent = null;
    }

    /**
     * Create new event (calls abstract method).
     */
    public function createEvent(array $data): mixed
    {
        // Prevent creation in view-only mode
        if ($this->viewOnly) {
            $this->dispatch('show-notification', [
                'message' => 'Cannot create events in view-only mode',
                'type' => 'warning',
            ]);

            return false;
        }

        return $this->onEventCreate($data);
    }

    /**
     * Edit existing event (calls abstract method).
     */
    public function editEvent(string $eventId, array $data): mixed
    {
        // Prevent editing in view-only mode
        if ($this->viewOnly) {
            $this->dispatch('show-notification', [
                'message' => 'Cannot edit events in view-only mode',
                'type' => 'warning',
            ]);

            return false;
        }

        return $this->onEventEdit($eventId, $data);
    }

    /**
     * Delete event (calls abstract method).
     */
    public function deleteEvent(string $eventId): bool
    {
        // Prevent deletion in view-only mode
        if ($this->viewOnly) {
            $this->dispatch('show-notification', [
                'message' => 'Cannot delete events in view-only mode',
                'type' => 'warning',
            ]);

            return false;
        }

        $result = $this->onEventDelete($eventId);

        // Close modal if the deleted event was selected
        if ($this->selectedEvent && $this->selectedEvent['id'] === $eventId) {
            $this->closeEventModal();
        }

        return $result;
    }

    /**
     * Get calendar data for JavaScript initialization.
     */
    public function getCalendarData(): array
    {
        return [
            'events' => $this->getEvents()->toArray(),
            'calendars' => $this->getCalendars()->toArray(),
            'currentView' => $this->currentView,
            'currentDate' => $this->currentDate,
            'viewOnly' => $this->viewOnly,
        ];
    }

    // Abstract methods that must be implemented by the parent component

    /**
     * Get all calendar events - DOMAIN SPECIFIC.
     */
    abstract public function getEvents(): Collection;

    /**
     * Get calendar configurations - DOMAIN SPECIFIC.
     */
    abstract public function getCalendars(): Collection;

    /**
     * Handle date selection - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onDateSelect(array $data): void;

    /**
     * Handle event click - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onEventClick(array $event): void;

    /**
     * Handle event creation - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onEventCreate(array $data): mixed;

    /**
     * Handle event edit - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onEventEdit(string $eventId, array $data): mixed;

    /**
     * Handle event deletion - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onEventDelete(string $eventId): bool;

    /**
     * Handle view change - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onViewChange(string $view): void;

    /**
     * Handle navigation - DOMAIN SPECIFIC LOGIC.
     */
    abstract public function onNavigate(string $direction): void;
}
