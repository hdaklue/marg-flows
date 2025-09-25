<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\Translation;

use App\Contracts\Document\TranslationProviderInterface;

abstract class BaseTranslation implements TranslationProviderInterface
{
    abstract public function getTranslations(): array;

    abstract public static function getLocaleCode(): string;

    public function getBlockTranslations(): array
    {
        return $this->getTranslations()['blocks'] ?? [];
    }

    public function getMetaTranslations(): array
    {
        return $this->getTranslations()['meta'] ?? [];
    }

    /**
     * Get translation value using dot notation.
     */
    public function getTranslationByKey(string $key): ?string
    {
        return data_get($this->getTranslations(), $key);
    }
}
