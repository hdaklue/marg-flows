<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class LinkToolConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        return [
            'class' => 'LinkTool',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'endpoint' => route('editorjs.fetch-url'),
                'headers' => [],
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
