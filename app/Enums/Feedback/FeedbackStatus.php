<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

enum FeedbackStatus: string
{
    case OPEN = 'open';
    case RUNNING = 'running';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';
    case URGENT = 'urgent';

    public static function openStatuses(): array
    {
        return [self::OPEN, self::RUNNING, self::URGENT];
    }

    public static function closedStatuses(): array
    {
        return [self::RESOLVED, self::REJECTED];
    }

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::RUNNING => 'Running',
            self::RESOLVED => 'Resolved',
            self::REJECTED => 'Rejected',
            self::URGENT => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'zinc',
            self::RUNNING => 'sky',
            self::RESOLVED => 'emerald',
            self::REJECTED => 'red',
            self::URGENT => 'amber',
        };
    }

    public function isResolved(): bool
    {
        return $this === self::RESOLVED;
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::OPEN, self::RUNNING, self::URGENT]);
    }
}
