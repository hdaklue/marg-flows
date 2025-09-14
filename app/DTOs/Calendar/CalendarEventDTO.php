<?php

declare(strict_types=1);

namespace App\DTOs\Calendar;

use Carbon\Carbon;
use WendellAdriel\ValidatedDTO\Casting\ArrayCast;
use WendellAdriel\ValidatedDTO\Casting\BooleanCast;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\StringCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * Event DTO for Calendar Component.
 *
 * @property string $id Unique identifier for the event
 * @property string $title Event title/name
 * @property Carbon $startDate Event start date
 * @property Carbon|null $endDate Event end date (optional)
 * @property string|null $color Event color (hex, css class, or tailwind color)
 * @property array $meta Additional metadata for the event
 * @property string|null $url Optional URL for event linking
 * @property bool $allDay Whether the event is all-day
 */
final class CalendarEventDTO extends ValidatedDTO
{
    public string $id;

    public string $title;

    public Carbon $startDate;

    public ?Carbon $endDate;

    public ?string $color;

    public array $meta;

    public ?string $url;

    public bool $allDay;

    /**
     * Check if event spans multiple days.
     */
    public function isMultiDay(): bool
    {
        if (! $this->endDate) {
            return false;
        }

        return $this->startDate->startOfDay()->ne($this->endDate->startOfDay());
    }

    /**
     * Get event duration in days.
     */
    public function getDurationInDays(): int
    {
        if (! $this->endDate) {
            return 1;
        }

        return
            $this->startDate
                ->startOfDay()
                ->diffInDays($this->endDate->endOfDay()) + 1;
    }

    /**
     * Check if event occurs on a specific date.
     */
    public function occursOnDate(Carbon $date): bool
    {
        $checkDate = $date->startOfDay();

        if (! $this->endDate) {
            return $this->startDate->startOfDay()->eq($checkDate);
        }

        return $checkDate->between(
            $this->startDate->startOfDay(),
            $this->endDate->endOfDay(),
        );
    }

    /**
     * Get CSS color class based on color value.
     */
    public function getColorClass(): string
    {
        if (! $this->color) {
            return 'bg-sky-500 text-white';
        }

        // If it's a hex color, return as CSS variable
        if (str_starts_with($this->color, '#')) {
            return '';
        }

        // If it's a Tailwind color
        if (str_contains($this->color, '-')) {
            return "bg-{$this->color} text-white";
        }

        // Default to sky color
        return 'bg-sky-500 text-white';
    }

    /**
     * Get inline CSS style for custom colors.
     */
    public function getInlineStyle(): string
    {
        if (! $this->color || ! str_starts_with($this->color, '#')) {
            return '';
        }

        return "background-color: {$this->color}; color: white;";
    }

    protected function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'color' => ['nullable', 'string', 'max:50'],
            'meta' => ['array'],
            'url' => ['nullable', 'string', 'url', 'max:500'],
            'allDay' => ['required', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'endDate' => null,
            'color' => null,
            'meta' => [],
            'url' => null,
            'allDay' => true,
        ];
    }

    protected function casts(): array
    {
        return [
            'id' => new StringCast,
            'title' => new StringCast,
            'startDate' => new CarbonCast,
            'endDate' => new CarbonCast,
            'color' => new StringCast,
            'meta' => new ArrayCast,
            'url' => new StringCast,
            'allDay' => new BooleanCast,
        ];
    }
}
