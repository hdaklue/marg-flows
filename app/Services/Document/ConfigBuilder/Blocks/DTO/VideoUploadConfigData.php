<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use App\Support\FileSize;
use App\Support\FileTypes;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class VideoUploadConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        $maxFileSize = FileSize::fromMB(250);
        $chunkSize = FileSize::fromMB(5);
        $supportedTypes = FileTypes::getStreamVideoFormatsAsValidationString();

        return [
            'class' => 'VideoUpload',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'endpoints' => [
                    'byFile' => null,
                    'delete' => null,
                ],
                'additionalRequestHeaders' => [
                    'X-CSRF-TOKEN' => '',
                ],
                'types' => $supportedTypes,
                'field' => 'video',
                'maxFileSize' => $maxFileSize,
                'chunkSize' => $chunkSize,
                'useChunkedUpload' => true,
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
