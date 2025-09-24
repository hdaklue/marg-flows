<?php

declare(strict_types=1);

namespace App\Contracts\Document;

interface TranslationProviderInterface
{
    /**
     * Get the locale code for this translation provider.
     */
    public static function getLocaleCode(): string;

    /**
     * Get all block translations for this provider.
     */
    public function getBlockTranslations(): array;

    /**
     * Get all meta translations for this provider.
     */
    public function getMetaTranslations(): array;

    /**
     * Get a translation by dot notation key.
     */
    public function getTranslationByKey(string $key): null|string;
}
