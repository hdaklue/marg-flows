<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

use App\Concerns\Enums\EnumSelectArrays;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeedbackStatus: string implements HasColor, HasLabel
{
    use EnumSelectArrays;
    case OPEN = 'open';
    case RUNNING = 'running';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';

    public static function openStatuses(): array
    {
        return [self::OPEN, self::RUNNING];
    }

    public static function closedStatuses(): array
    {
        return [self::RESOLVED, self::REJECTED];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => __('app.feedback_status.open'),
            self::RUNNING => __('app.feedback_status.running'),
            self::RESOLVED => __('app.feedback_status.resolved'),
            self::REJECTED => __('app.feedback_status.rejected'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => 'zinc',
            self::RUNNING => 'sky',
            self::RESOLVED => 'emerald',
            self::REJECTED => 'red',
        };
    }

    public function isResolved(): bool
    {
        return $this === self::RESOLVED;
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::OPEN, self::RUNNING]);
    }
}
