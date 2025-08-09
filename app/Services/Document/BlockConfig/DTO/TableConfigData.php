<?php

declare(strict_types=1);

namespace App\Services\Document\BlockConfig\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class TableConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    protected function defaults(): array
    {
        return [
            'class' => 'Table',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'config' => [
                'rows' => 2,
                'cols' => 2,
                'maxRows' => 5,
                'maxCols' => 5,
                'withHeadings' => false,
                'stretched' => false,
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
