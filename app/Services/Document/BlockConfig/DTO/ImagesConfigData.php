<?php

declare(strict_types=1);

namespace App\Services\Document\BlockConfig\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class ImagesConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        return [
            'class' => 'ResizableImage',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'endpoints' => [
                    'byFile' => null,
                    'byUrl' => null,
                    'delete' => null,
                ],
                'additionalRequestHeaders' => [
                    'X-CSRF-TOKEN' => '',
                ],
                'types' => 'image/*',
                'field' => 'image',
                'captionPlaceholder' => 'Enter image caption...',
                'buttonContent' => 'Select an image',
            ],
        ];
    }

    protected function casts(): array
    {
        return [
        ];
    }
}
