<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\General;

use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Document\Templates\BaseDocumentTemplate;
use App\Services\Document\Templates\General\Translations\Ar;
use App\Services\Document\Templates\General\Translations\En;
use BumpCore\EditorPhp\Blocks\Header;
use BumpCore\EditorPhp\Blocks\Paragraph;

final class General extends BaseDocumentTemplate
{
    /**
     * {@inheritDoc}
     */
    public static function getDescription(): string
    {
        return app(DocumentTemplateTranslatorInterface::class)
            ->translateMeta('general', 'description');
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return app(DocumentTemplateTranslatorInterface::class)->translateMeta('general', 'name');
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

    /**
     * {@inheritDoc}
     */
    public function getConfigArray(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getBlocks(): array
    {
        return [
            Header::make([
                'text' => $this->t('blocks.header.text'),
                'level' => 1,
            ]),
            Paragraph::make([
                'text' => $this->t('blocks.intro.description'),
            ]),
            Paragraph::make([
                'text' => $this->t('template_description'), // Keeping backward compatibility
            ]),
            Header::make([
                'text' => $this->t('blocks.intro.name'),
                'level' => 3,
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

    /**
     * {@inheritDoc}
     */
    public function toJson(int $options = 0): string
    {
        return parent::toJson($options);
    }
}
