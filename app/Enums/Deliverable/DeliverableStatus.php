<?php

declare(strict_types=1);

namespace App\Enums\Deliverable;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliverableStatus: int implements HasColor, HasLabel
{
    case REQUESTED = 1;
    case IN_PROGRESS = 2;
    case REVIEW = 3;

    case COMPLETED = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::REQUESTED => 'Requested',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW => 'Under Review',
            self::COMPLETED => 'Completed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::REQUESTED => 'zinc',
            self::IN_PROGRESS => 'sky',
            self::REVIEW => 'amber',
            self::COMPLETED => 'emerald',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::REQUESTED => 'Initial state, not yet started',
            self::IN_PROGRESS => 'Currently being worked on',
            self::REVIEW => 'Submitted for review by team members',
            self::COMPLETED => 'Finished and approved',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::REQUESTED => 'heroicon-o-document',
            self::IN_PROGRESS => 'heroicon-o-clock',
            self::REVIEW => 'heroicon-o-eye',
            self::COMPLETED => 'heroicon-o-check-circle',
        };
    }
}
