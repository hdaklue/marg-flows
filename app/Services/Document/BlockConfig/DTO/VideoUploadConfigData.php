<?php

declare(strict_types=1);

namespace App\Services\Document\BlockConfig\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class VideoUploadConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
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
                'types' => 'video/*',
                'field' => 'video',
                'maxFileSize' => 262144000, // 250MB
                'chunkSize' => 10485760, // 10MB
                'useChunkedUpload' => true,
            ],
        ];
    }

    protected function casts(): array
    {
        return [

        ];
    }
}
