<?php

declare(strict_types=1);

namespace App\Contracts\Document;

interface DocumentTemplateTranslatorInterface
{
    public function setLocale(string $locale): static;

    public function translateBlock(
        string $templateKey,
        string $blockKey,
        array $params = [],
    ): string;

    public function translateMeta(string $templateKey, string $metaKey): string;

    public function getLocale(): string;
}
