<?php

namespace App\Services\Flow;

use App\Enums\FlowStatus;
use App\Models\Flow;
use Carbon\Carbon;

class FlowProgressService
{
    // Status constants
    private const STATUS_COMPLETED = 'completed';

    private const STATUS_OVERDUE = 'overdue';

    private const STATUS_PAUSED = 'paused';

    private const STATUS_SCHEDULED = 'scheduled';

    private const STATUS_IN_PROGRESS = 'in-progress';

    private const STATUS_INVALID = 'invalid';

    private const STATUS_UNKNOWN = 'unknown';

    // Color constants
    private const COLOR_GREEN_500 = '#10b981';    // Completed

    private const COLOR_RED_500 = '#ef4444';      // Overdue

    private const COLOR_AMBER_500 = '#f59e0b';    // Paused

    private const COLOR_INDIGO_500 = '#6366f1';   // Scheduled

    private const COLOR_GRAY_600 = '#71717a';     // Default, Invalid

    private const COLOR_SKY_600 = '#0284c7';      // In-progress >= 80%

    private const COLOR_CYAN_600 = '#0891b2';     // In-progress >= 50%

    private const COLOR_CYAN_400 = '#22d3ee';     // In-progress >= 20%

    private const COLOR_CYAN_300 = '#67e8f9';     // In-progress < 20%

    /**
     * Calculate time-based progress percentage for a flow
     */
    public function calculateTimeProgress(Flow $flow): float
    {
        if (! $this->hasValidDates($flow)) {
            return 0.0;
        }

        if ($this->isCompleted($flow)) {
            return 100.0;
        }

        $totalDays = $this->getTotalDays($flow);

        // Handle edge cases
        if ($totalDays <= 0) {
            return $this->handleSameDayProject($flow);
        }

        return $this->calculateProgressPercentage($flow);
    }

    /**
     * Get progress status based on flow state and dates
     */
    public function getProgressStatus(Flow $flow): string
    {
        if (! $this->hasValidDates($flow)) {
            return self::STATUS_INVALID;
        }

        // Check business status first
        $businessStatus = $this->getBusinessStatus($flow);
        if ($businessStatus) {
            // Special case: ACTIVE flows can become overdue
            if ($businessStatus === self::STATUS_IN_PROGRESS && $this->isOverdue($flow)) {
                return self::STATUS_OVERDUE;
            }

            return $businessStatus;
        }

        // Fallback to date-based calculation
        return $this->calculateStatusFromDates($flow);
    }

    /**
     * Get progress color based on status and percentage
     */
    public function getProgressColor(Flow $flow): string
    {
        $status = $this->getProgressStatus($flow);
        $percentage = 0.0;

        if ($status === self::STATUS_IN_PROGRESS) {
            $percentage = $this->calculateTimeProgress($flow);
        }

        return $this->mapStatusToColor($status, $percentage);
    }

    /**
     * Get comprehensive progress information
     */
    public function getProgressDetails(Flow $flow): array
    {
        if (! $this->hasValidDates($flow)) {
            return $this->getInvalidProgressDetails();
        }

        $status = $this->getProgressStatus($flow);
        $percentage = $this->isCompleted($flow) ? 100.0 : $this->calculateProgressPercentage($flow);
        $color = $this->mapStatusToColor($status, $percentage);

        return [
            'percentage' => round($percentage, 1),
            'status' => $status,
            'color' => $color,
            'days_remaining' => $this->getDaysRemainingPositive($flow),
            'days_elapsed' => $this->getDaysElapsed($flow),
            'total_days' => $this->getTotalDays($flow),
            'is_overdue' => $this->isOverdue($flow),
            'is_completed' => $this->isCompleted($flow),
            'is_paused' => $this->isPaused($flow),
            'is_scheduled' => $this->isScheduled($flow),
            'start_date' => $flow->start_date->format('Y-m-d'),
            'due_date' => $flow->due_date->format('Y-m-d'),
        ];
    }

    /**
     * Public Date Calculation Methods
     */

    /**
     * Get total project duration in days
     */
    public function getTotalDays(Flow $flow): int
    {
        if (! $this->hasValidDates($flow)) {
            return 0;
        }

        return max(0, $flow->start_date->startOfDay()->diffInDays($flow->due_date->startOfDay()));
    }

    /**
     * Get days elapsed since project start
     */
    public function getDaysElapsed(Flow $flow): int
    {
        if (! $this->hasValidDates($flow)) {
            return 0;
        }

        return max(0, $flow->start_date->startOfDay()->diffInDays(today()));
    }

    /**
     * Get days remaining until due date (can be negative for overdue)
     */
    public function getDaysRemaining(Flow $flow): int
    {
        if (! $this->hasValidDates($flow)) {
            return 0;
        }

        return today()->diffInDays($flow->due_date->startOfDay(), false);
    }

    /**
     * Get days remaining until due date (always positive, 0 if overdue)
     */
    public function getDaysRemainingPositive(Flow $flow): int
    {
        return max(0, $this->getDaysRemaining($flow));
    }

    /**
     * Get normalized start date
     */
    public function getStartDate(Flow $flow): ?Carbon
    {
        if (! $this->hasValidDates($flow)) {
            return null;
        }

        return $flow->start_date->startOfDay();
    }

    /**
     * Get normalized due date
     */
    public function getDueDate(Flow $flow): ?Carbon
    {
        if (! $this->hasValidDates($flow)) {
            return null;
        }

        return $flow->due_date->startOfDay();
    }

    /**
     * Check if project has started
     */
    public function hasStarted(Flow $flow): bool
    {
        if (! $this->hasValidDates($flow)) {
            return false;
        }

        return today()->gte($flow->start_date->startOfDay());
    }

    /**
     * Check if project is past due date
     */
    public function isPastDue(Flow $flow): bool
    {
        if (! $this->hasValidDates($flow)) {
            return false;
        }

        return today()->gt($flow->due_date->startOfDay());
    }

    /**
     * Status check methods
     */
    public function isOverdue(Flow $flow): bool
    {
        if (! $this->hasValidDates($flow) || $this->isTerminalStatus($flow)) {
            return false;
        }

        return $this->isPastDue($flow);
    }

    public function isPaused(Flow $flow): bool
    {
        return $flow->status === FlowStatus::PAUSED->value;
    }

    public function isCompleted(Flow $flow): bool
    {
        return $flow->status === FlowStatus::COMPLETED->value;
    }

    public function isScheduled(Flow $flow): bool
    {
        return $flow->status === FlowStatus::SCHEDULED->value;
    }

    public function isActive(Flow $flow): bool
    {
        return $flow->status === FlowStatus::ACTIVE->value;
    }

    /**
     * UI helper methods
     */
    public function getStatusDisplayName(Flow $flow): string
    {
        $status = $this->getProgressStatus($flow);

        return match ($status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_INVALID => 'Invalid',
            default => 'Unknown',
        };
    }

    public function getStatusIcon(Flow $flow): string
    {
        $status = $this->getProgressStatus($flow);

        return match ($status) {
            self::STATUS_SCHEDULED => 'heroicon-o-calendar',
            self::STATUS_IN_PROGRESS => 'heroicon-o-play',
            self::STATUS_COMPLETED => 'heroicon-o-check-circle',
            self::STATUS_OVERDUE => 'heroicon-o-exclamation-triangle',
            self::STATUS_PAUSED => 'heroicon-o-pause',
            self::STATUS_INVALID => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Private helper methods
     */
    private function hasValidDates(Flow $flow): bool
    {
        return isset($flow->start_date, $flow->due_date) &&
               $flow->start_date instanceof Carbon &&
               $flow->due_date instanceof Carbon;
    }

    private function isTerminalStatus(Flow $flow): bool
    {
        return in_array($flow->status, [
            FlowStatus::COMPLETED->value,
            FlowStatus::PAUSED->value,
            FlowStatus::SCHEDULED->value,
        ]);
    }

    private function getBusinessStatus(Flow $flow): ?string
    {
        return match ($flow->status) {
            FlowStatus::COMPLETED->value => self::STATUS_COMPLETED,
            FlowStatus::PAUSED->value => self::STATUS_PAUSED,
            FlowStatus::ACTIVE->value => self::STATUS_IN_PROGRESS,
            FlowStatus::SCHEDULED->value => self::STATUS_SCHEDULED,
            default => null,
        };
    }

    private function calculateStatusFromDates(Flow $flow): string
    {
        if ($this->isPastDue($flow)) {
            return self::STATUS_OVERDUE;
        }

        if (! $this->hasStarted($flow)) {
            return self::STATUS_SCHEDULED;
        }

        return self::STATUS_IN_PROGRESS;
    }

    private function handleSameDayProject(Flow $flow): float
    {
        // For same-day projects, consider them 100% if today or past due date
        return today()->gte($this->getDueDate($flow)) ? 100.0 : 0.0;
    }

    private function calculateProgressPercentage(Flow $flow): float
    {
        // Project hasn't started
        if (! $this->hasStarted($flow)) {
            return 0.0;
        }

        // Project is past due
        if ($this->isPastDue($flow)) {
            return 100.0;
        }

        // Calculate percentage based on elapsed time
        $totalDays = $this->getTotalDays($flow);
        $daysElapsed = $this->getDaysElapsed($flow);

        if ($totalDays <= 0) {
            return 100.0;
        }

        $percentage = ($daysElapsed / $totalDays) * 100;

        return max(0.0, min(100.0, $percentage));
    }

    private function mapStatusToColor(string $status, float $percentage): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => self::COLOR_GREEN_500,
            self::STATUS_OVERDUE => self::COLOR_RED_500,
            self::STATUS_PAUSED => self::COLOR_AMBER_500,
            self::STATUS_SCHEDULED => self::COLOR_INDIGO_500,
            self::STATUS_IN_PROGRESS => $this->getInProgressColor($percentage),
            default => self::COLOR_GRAY_600,
        };
    }

    private function getInProgressColor(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => self::COLOR_SKY_600,
            $percentage >= 50 => self::COLOR_CYAN_600,
            $percentage >= 20 => self::COLOR_CYAN_400,
            default => self::COLOR_CYAN_300,
        };
    }

    private function getInvalidProgressDetails(): array
    {
        return [
            'percentage' => 0.0,
            'status' => self::STATUS_INVALID,
            'color' => self::COLOR_GRAY_600,
            'days_remaining' => 0,
            'days_elapsed' => 0,
            'total_days' => 0,
            'is_overdue' => false,
            'is_completed' => false,
            'is_paused' => false,
            'is_scheduled' => false,
            'start_date' => null,
            'due_date' => null,
        ];
    }
}
