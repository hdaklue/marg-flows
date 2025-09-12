<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\MediaPlanBrief;

use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Document\Templates\BaseDocumentTemplate;
use App\Services\Document\Templates\MediaPlanBrief\Translations\Ar;
use App\Services\Document\Templates\MediaPlanBrief\Translations\En;
use BumpCore\EditorPhp\Blocks\Header;
use BumpCore\EditorPhp\Blocks\Paragraph;

final class MediaPlanBrief extends BaseDocumentTemplate
{
    protected DocumentTemplateTranslatorInterface $translator;

    public static function getDescription(): string
    {
        return app(DocumentTemplateTranslatorInterface::class)
            ->translateMeta(self::getTemplateKey(), 'description');
    }

    public static function getName(): string
    {
        return app(DocumentTemplateTranslatorInterface::class)
            ->translateMeta(self::getTemplateKey(), 'name');
    }

    public static function getAvailableTranslations(): array
    {
        return [
            En::class,
            Ar::class,
        ];
    }

    public function setTranslator(DocumentTemplateTranslatorInterface $translator): static
    {
        $this->translator = $translator;

        return $this;
    }

    public function getConfigArray(): array
    {
        return [];
    }

    public function getBlocks(): array
    {
        return [
            Header::make([
                'text' => $this->t('header'),
                'level' => 1,
            ]),
            Paragraph::make([
                'text' => $this->t('description'),
            ]),
            Header::make([
                'text' => $this->t('header'),
                'level' => 2,
            ]),
        ];
    }

    public function getDataArray(): array
    {
        return [];
    }

    public function getConfingArray(): array
    {
        return [];
    }

    public function toJson(int $options = 0): string
    {
        return parent::toJson($options);
    }

    protected function t(string $blockKey, array $params = []): string
    {
        return $this->translator->translateBlock(static::getTemplateKey(), $blockKey, $params);
    }
}
