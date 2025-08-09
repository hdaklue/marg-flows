<?php

declare(strict_types=1);

namespace App\Services\Document\BlockConfig\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class ParagraphConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        return [
            'class' => 'paragraph',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'placeholder' => 'Write something ..',
                'preserveBlank' => false,
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
