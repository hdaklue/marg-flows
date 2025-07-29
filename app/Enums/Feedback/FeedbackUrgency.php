<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

use Filament\Support\Contracts\HasLabel;

enum FeedbackUrgency: int implements HasLabel
{
    case NORMAL = 1;

    case GOODTOHAVE = 3;
    case MUSTHAVE = 2;

    case URGENT = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::MUSTHAVE => 'Must Have',
            self::GOODTOHAVE => 'Good to have',
            self::URGENT => 'Urgent',
        };
    }
}
