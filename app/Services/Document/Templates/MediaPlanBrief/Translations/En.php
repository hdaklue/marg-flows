<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\MediaPlanBrief\Translations;

use App\Services\Document\Templates\Translation\BaseTranslation;

final class En extends BaseTranslation
{
    public static function getLocaleCode(): string
    {
        return 'en';
    }

    public function getTranslations(): array
    {
        return [
            'meta' => [
                'name' => 'Media Plan Brief',
                'description' => 'MediaPlanBrief Template',
            ],
            'blocks' => [
                'header' => 'Media Plan Brief Template',
                'description' => 'This is a new MediaPlanBrief template. Customize it to meet your needs.',
            ],
        ];
    }
}
