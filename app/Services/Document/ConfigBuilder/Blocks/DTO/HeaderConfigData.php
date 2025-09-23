<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class HeaderConfigData extends SimpleDTO implements BlockConfigContract
{
    public array $config;

    public string $class;

    public array $tunes;

    public array|bool $inlineToolBar;

    protected function defaults(): array
    {
        return [
            'class' => 'header',
            'tunes' => ['commentTune'],
            'inlineToolBar' => ['link', 'bold', 'italic'],
            'config' => [
                'placeholder' => 'Enter a header',
                'levels' => [1, 2, 3, 4, 5, 6],
                'defaultLevel' => 2,
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
