<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\CreativeBrief\Translations;

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
                'name' => 'Creative Brief',
                'description' => 'Creative Brief Template',
            ],
            'blocks' => [
                'header' => 'CreativeBrief Template',
                'description' => 'This is a new CreativeBrief template. Customize it to meet your needs.',
            ],
        ];
    }
}
