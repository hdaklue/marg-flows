<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\MediaPlanBrief\Translations;

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
                'name' => 'مذكرة خطه اعلانيه',
                'description' => 'مذكره للخطه الاعلانيه و اهدافها',
            ],
            'blocks' => [
                'header' => 'قالب MediaPlanBrief',
                'description' => 'هذا قالب MediaPlanBrief جديد. يمكنك تخصيصه لتلبية احتياجاتك.',
            ],
        ];
    }
}
