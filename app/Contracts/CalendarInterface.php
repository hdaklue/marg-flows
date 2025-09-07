<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface CalendarInterface
{
    /**
     * Get all calendar events for the current view/filter
     *
     * @return Collection
     */
    public function getEvents(): Collection;

    /**
     * Get calendar configurations/categories
     *
     * @return Collection
     */
    public function getCalendars(): Collection;

    /**
     * Handle date/time selection event
     *
     * @param array $data Selection data with start and end dates
     * @return void
     */
    public function onDateSelect(array $data): void;

    /**
     * Handle event click
     *
     * @param array $event Event data
     * @return void
     */
    public function onEventClick(array $event): void;

    /**
     * Handle event creation
     *
     * @param array $data Event creation data
     * @return mixed
     */
    public function onEventCreate(array $data): mixed;

    /**
     * Handle event edit/update
     *
     * @param string $eventId Event identifier
     * @param array $data Updated event data
     * @return mixed
     */
    public function onEventEdit(string $eventId, array $data): mixed;

    /**
     * Handle event deletion
     *
     * @param string $eventId Event identifier
     * @return bool
     */
    public function onEventDelete(string $eventId): bool;

    /**
     * Handle view change (month, week, day)
     *
     * @param string $view New view type
     * @return void
     */
    public function onViewChange(string $view): void;

    /**
     * Handle navigation (prev, next, today)
     *
     * @param string $direction Navigation direction
     * @return void
     */
    public function onNavigate(string $direction): void;
}
