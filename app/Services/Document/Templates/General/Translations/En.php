<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\General\Translations;

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
                'name' => 'General',
                'description' => 'General Purpose Template',
            ],
            'blocks' => [
                'welcome_header' => 'Welcome!',
                'welcome_description' => 'Today is always the perfect time to start.',
                'template_description' => 'This is your new document template. You can edit, add blocks, and make it your own.',
                'header' => [
                    'text' => 'Welcome to Your Template!',
                    'level' => 1,
                ],
                'intro' => [
                    'name' => 'Introduction Section',
                    'description' => 'This is the introduction to your document.',
                ],
            ],
        ];
    }
}
