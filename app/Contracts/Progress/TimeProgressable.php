<?php

declare(strict_types=1);

namespace App\Contracts\Progress;

use Illuminate\Support\Carbon;

interface TimeProgressable
{
    public function getKey();

    /**
     * Get the start date for progress calculation.
     */
    public function getProgressStartDate(): Carbon;

    /**
     * Get the due date for progress calculation.
     */
    public function getProgressDueDate(): Carbon;

    /**
     * Get the completed date for progress calculation.
     */
    public function getProgressCompletedDate(): ?Carbon;

    /**
     * Check if the progressable item has valid progress dates.
     */
    public function hasValidProgressDates(): bool;

    /**
     * Get a unique identifier for the progressable item.
     */
    public function getProgressableId(): mixed;

    /**
     * Get the attribute name for the start date.
     */
    public function getProgressStartDateAttribute(): string;

    /**
     * Get the attribute name for the due date.
     */
    public function getProgressDueDateAttribute(): string;

    /**
     * Get the attribute name for the status.
     */
    public function getProgressCompletedDateAttribute(): string;
}
