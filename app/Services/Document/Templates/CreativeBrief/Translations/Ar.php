<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\CreativeBrief\Translations;

use App\Services\Document\Templates\Translation\BaseTranslation;

final class Ar extends BaseTranslation
{
    public static function getLocaleCode(): string
    {
        return 'ar';
    }

    public function getTranslations(): array
    {
        return [
            'meta' => [
                'name' => 'مذكره لمشروع ابداعي',
                'description' => '',
            ],
            'blocks' => [
                'header' => 'قالب CreativeBrief',
                'description' => 'هذا قالب CreativeBrief جديد. يمكنك تخصيصه لتلبية احتياجاتك.',
            ],
        ];
    }
}
