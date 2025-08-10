<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class NestedListConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        return [
            'class' => 'EditorJsList',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'defaultStyle' => 'unordered',
                'placeholder' => 'Add an item',
                'maxLevel' => null,
                'counterTypes' => null,
            ],
        ];
    }

    protected function casts(): array
    {
        return [

        ];
    }
}
