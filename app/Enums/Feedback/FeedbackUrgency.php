<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

use App\Concerns\Enums\EnumSelectArrays;
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
            self::NORMAL => __('app.feedback_urgency.normal'),
            self::SUGGESTION => __('app.feedback_urgency.suggestion'),
            self::URGENT => __('app.feedback_urgency.urgent'),
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
