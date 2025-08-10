<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class HyperlinkConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    public ?string $shortcut;

    protected function defaults(): array
    {
        return [
            'class' => 'HyperLink',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'shortcut' => 'CMD+L',
            'config' => [
                'target' => '_blank',
                'rel' => 'nofollow',
                'availableTargets' => ['_blank', '_self'],
                'availableRels' => ['author', 'noreferrer'],
                'validate' => false,
            ],
        ];
    }

    protected function casts(): array
    {
        return [

        ];
    }
}
