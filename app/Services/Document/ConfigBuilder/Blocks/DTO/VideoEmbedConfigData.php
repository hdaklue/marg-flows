<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class VideoEmbedConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        return [
            'class' => 'VideoEmbed',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'placeholder' => 'Paste a YouTube URL...',
                'allowDirectUrls' => true,
            ],
        ];
    }

    protected function casts(): array
    {
        return [

        ];
    }
}
