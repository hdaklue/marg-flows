<?php

declare(strict_types=1);

namespace App\Services\Flow;

use App\Contracts\Progress\TimeProgressable;
use App\ValueObjects\Percentage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class TimeProgressService
{
    // Status constants
    private const STATUS_COMPLETED = 'completed';

    private const STATUS_OVERDUE = 'overdue';

    private const STATUS_SCHEDULED = 'scheduled';

    private const STATUS_IN_PROGRESS = 'in-progress';

    // Color constants
    private const COLOR_GREEN_500 = '#10b981';    // Completed

    private const COLOR_RED_500 = '#ef4444';      // Overdue

    private const COLOR_INDIGO_500 = '#6366f1';   // Scheduled

    private const COLOR_GRAY_600 = '#71717a';     // Default

    private const COLOR_SKY_600 = '#0284c7';      // In-progress >= 80%

    private const COLOR_CYAN_600 = '#0891b2';     // In-progress >= 50%

    private const COLOR_CYAN_400 = '#22d3ee';     // In-progress >= 20%

    private const COLOR_CYAN_300 = '#67e8f9';     // In-progress < 20%

    /**
     * Calculate time-based progress percentage for a progressable item.
     */
    public function calculateTimeProgress(TimeProgressable $item): Percentage
    {
        if ($this->isCompleted($item)) {
            return Percentage::complete();
        }

        $totalDays = $this->getTotalDays($item);

        // Handle edge cases
        if ($totalDays <= 0) {
            return $this->handleSameDayProject($item);
        }

        return $this->calculateProgressPercentage($item);
    }

    /**
     * Get progress status based on item state and dates.
     */
    public function getProgressStatus(TimeProgressable $item): string
    {
        return $this->calculateStatusFromDates($item);
    }

    /**
     * Get progress color based on status and percentage.
     */
    public function getProgressColor(TimeProgressable $item): string
    {
        $status = $this->getProgressStatus($item);
        $percentage = Percentage::zero();

        if ($status === self::STATUS_IN_PROGRESS) {
            $percentage = $this->calculateTimeProgress($item);
        }

        return $this->mapStatusToColor($status, $percentage);
    }

    /**
     * Get comprehensive progress information.
     */
    public function getProgressDetails(TimeProgressable $item): array
    {
        $status = $this->getProgressStatus($item);
        $percentage = $this->isCompleted($item) ? Percentage::complete() : $this->calculateProgressPercentage($item);
        $color = $this->mapStatusToColor($status, $percentage);

        return Cache::remember(
            $this->getCacheKey($item),
            (int) now()->endOfDay()->diffInSeconds(now()),
            function () use ($item, $percentage, $status, $color) {
                return [
                    'percentage' => $percentage->toArray(),
                    'color' => $color,
                    'status' => $status,
                    'days_remaining' => $this->getDaysRemaining($item),
                    'days_elapsed' => $this->getDaysElapsed($item),
                    'total_days' => $this->getTotalDays($item),
                    'is_overdue' => $this->isPastDue($item),
                    'is_completed' => $this->isCompleted($item),
                    'is_scheduled' => $this->isScheduled($item),
                    'start_date' => $item->getProgressStartDate()->format('Y-m-d'),
                    'due_date' => $item->getProgressDueDate()->format('Y-m-d'),
                ];
            });
    }

    /**
     * Public Date Calculation Methods.
     */

    /**
     * Get total project duration in days.
     */
    public function getTotalDays(TimeProgressable $item): int|float
    {
        if ($item->getProgressStartDate()->startOfDay()->equalTo($item->getProgressDueDate()->startOfDay())) {
            return 1;
        }

        return $item->getProgressStartDate()->startOfDay()->diffInDays($item->getProgressDueDate()->startOfDay());
    }

    /**
     * Get days elapsed since project start.
     */
    public function getDaysElapsed(TimeProgressable $item): float
    {
        return $item->getProgressStartDate()->startOfDay()->diffInDays(today());
    }

    /**
     * Check if item is due today.
     */
    public function isDueToday(TimeProgressable $item): bool
    {
        return today()->isSameDay($item->getProgressDueDate()->startOfDay());
    }

    /**
     * Get days remaining until due date (can be negative for overdue).
     */
    public function getDaysRemaining(TimeProgressable $item): int|float
    {
        $cacheKey = $this->getCacheKey($item) . 'days_remaining';

        return Cache::remember(
            $cacheKey,
            (int) now()->endOfDay()->diffInSeconds(now()),
            function () use ($item) {
                return today()->diffInDays($item->getProgressDueDate()->startOfDay());
            });
    }

    /**
     * Get days remaining until due date (always positive, 0 if overdue).
     */
    public function getDaysRemainingPositive(TimeProgressable $item): int
    {
        return max(0, $this->getDaysRemaining($item));
    }

    /**
     * Get normalized start date.
     */
    public function getStartDate(TimeProgressable $item): Carbon
    {
        return $item->getProgressStartDate()->startOfDay();
    }

    /**
     * Get normalized due date.
     */
    public function getDueDate(TimeProgressable $item): Carbon
    {
        return $item->getProgressDueDate()->startOfDay();
    }

    /**
     * Check if project has started.
     */
    public function hasStarted(TimeProgressable $item): bool
    {
        return today()->gte($item->getProgressStartDate()->startOfDay());
    }

    /**
     * Check if item is scheduled (not started yet).
     */
    public function isScheduled(TimeProgressable $item): bool
    {
        return $item->getProgressStartDate()->startOfDay()->gt(today());
    }

    /**
     * Check if project is past due date.
     */
    public function isPastDue(TimeProgressable $item): bool
    {
        return today()->gt($item->getProgressDueDate()->startOfDay());
    }

    /**
     * Check if item is completed.
     */
    public function isCompleted(TimeProgressable $item): bool
    {
        if ($item->getProgressCompletedDate() === null) {
            return false;
        }

        return today()->gte($item->getProgressCompletedDate()->startOfDay());
    }

    /**
     * UI helper methods.
     */
    public function getStatusDisplayName(TimeProgressable $item): string
    {
        $status = $this->getProgressStatus($item);

        return match ($status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
            default => 'Unknown',
        };
    }

    public function getStatusIcon(TimeProgressable $item): string
    {
        $status = $this->getProgressStatus($item);

        return match ($status) {
            self::STATUS_SCHEDULED => 'heroicon-o-calendar',
            self::STATUS_IN_PROGRESS => 'heroicon-o-play',
            self::STATUS_COMPLETED => 'heroicon-o-check-circle',
            self::STATUS_OVERDUE => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    private function getCacheKey(TimeProgressable $item): string
    {
        $itemId = get_class($item) . '_' . $item->getKey();

        return sprintf(
            'progress_%s_%s_%s_%s_%s',
            $itemId,
            $item->getProgressStartDate()->format('Ymd'),
            $item->getProgressDueDate()->format('Ymd'),
            $item->getProgressCompletedDate()?->format('Ymd') ?? 'pending',
            now()->endOfDay()->timestamp,
        );
    }

    /**
     * Private helper methods.
     */
    private function calculateStatusFromDates(TimeProgressable $item): string
    {
        // Check completed first
        if ($this->isCompleted($item)) {
            return self::STATUS_COMPLETED;
        }

        if ($this->isPastDue($item)) {
            return self::STATUS_OVERDUE;
        }

        if (! $this->hasStarted($item)) {
            return self::STATUS_SCHEDULED;
        }

        return self::STATUS_IN_PROGRESS;
    }

    private function handleSameDayProject(TimeProgressable $item): Percentage
    {
        // For same-day projects, consider them 100% if today or past due date
        return today()->gte($this->getDueDate($item)) ? Percentage::complete() : Percentage::zero();
    }

    private function calculateProgressPercentage(TimeProgressable $item): Percentage
    {
        // Project hasn't started
        if (! $this->hasStarted($item)) {
            return Percentage::zero();
        }

        // Project is past due
        if ($this->isPastDue($item)) {
            return Percentage::complete();
        }

        // Calculate percentage based on elapsed time
        $totalDays = $this->getTotalDays($item);
        $daysElapsed = $this->getDaysElapsed($item);

        if ($totalDays <= 0) {
            return Percentage::complete();
        }

        $progressRatio = $daysElapsed / $totalDays;
        $clampedRatio = max(0.0, min(1.0, $progressRatio));

        return Percentage::fromRatio($clampedRatio);
    }

    private function mapStatusToColor(string $status, Percentage $percentage): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => self::COLOR_GREEN_500,
            self::STATUS_OVERDUE => self::COLOR_RED_500,
            self::STATUS_SCHEDULED => self::COLOR_INDIGO_500,
            self::STATUS_IN_PROGRESS => $this->getInProgressColor($percentage),
            default => self::COLOR_GRAY_600,
        };
    }

    private function getInProgressColor(Percentage $percentage): string
    {
        $percentageValue = $percentage->asPercentage();

        return match (true) {
            $percentageValue >= 80 => self::COLOR_SKY_600,
            $percentageValue >= 50 => self::COLOR_CYAN_600,
            $percentageValue >= 20 => self::COLOR_CYAN_400,
            default => self::COLOR_CYAN_300,
        };
    }
}
