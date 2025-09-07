<?php

declare(strict_types=1);

namespace App\Services\Document\Templates;

use BumpCore\EditorPhp\Blocks\Header;
use BumpCore\EditorPhp\Blocks\Paragraph;

final class General extends BaseDocumentTemplate
{
    /**
     * {@inheritDoc}
     */
    /**
     * {@inheritDoc}
     */
    public static function getDescription(): string
    {
        return 'General Porpose Template';
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'General';
    }
    /**
     * {@inheritDoc}
     */

    /**
     * {@inheritDoc}
     */

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
                'text' => 'Welcome!',
                'level' => 1,
            ]),

            Paragraph::make([
                'text' => 'Today is always the perfect time to start.',
            ]),

            Paragraph::make([
                'text' => 'This is your new document template. You can edit, add blocks, and make it your own.',
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
