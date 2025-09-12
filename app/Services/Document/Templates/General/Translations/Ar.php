<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\General\Translations;

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
                'name' => 'عام',
                'description' => 'قالب عام الغرض',
            ],
            'blocks' => [
                'welcome_header' => 'مرحباً!',
                'welcome_description' => 'اليوم هو دائماً الوقت المثالي للبدء.',
                'template_description' => 'هذا هو قالب المستند الجديد الخاص بك. يمكنك التحرير والإضافة وجعله خاصاً بك.',
                'header' => [
                    'text' => 'مرحباً بك في القالب الخاص بك!',
                    'level' => 1,
                ],
                'intro' => [
                    'name' => 'قسم المقدمة',
                    'description' => 'هذه هي مقدمة مستندك.',
                ],
            ],
        ];
    }
}
