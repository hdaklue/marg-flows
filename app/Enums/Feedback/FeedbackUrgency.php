<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

use App\Traits\EnumSelectArrays;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeedbackUrgency: int implements HasColor, HasLabel
{
    use EnumSelectArrays;
    case NORMAL = 1;

    case SUGGESTION = 2;

    case URGENT = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::SUGGESTION => 'Suggestion',
            self::URGENT => 'Urgent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NORMAL => 'zinc',
            self::SUGGESTION => 'indigo',
            self::URGENT => 'orange',
        };
    }
}
