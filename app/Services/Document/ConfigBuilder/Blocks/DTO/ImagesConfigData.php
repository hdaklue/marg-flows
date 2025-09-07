<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use App\Support\FileSize;
use App\Support\FileTypes;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class ImagesConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        $availableTypes = FileTypes::getWebImageFormatsAsValidationString();
        $maxFileSize = FileSize::fromMB(10); // Default 10MB

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
                'types' => $availableTypes,
                'field' => 'image',
                'captionPlaceholder' => 'Enter image caption...',
                'buttonContent' => 'Select an image',
                'maxFileSize' => $maxFileSize,
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
