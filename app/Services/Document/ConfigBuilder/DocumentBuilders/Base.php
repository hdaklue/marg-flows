<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\DocumentBuilders;

use App\Services\Document\Facades\ConfigBuilder;

final class Base
{
    public function build(): array
    {
        return [
            'paragraph' => ConfigBuilder::paragraph()->toArray(),
            'header' => ConfigBuilder::header()->toArray(),
            'images' => ConfigBuilder::images()->toArray(),
            'table' => ConfigBuilder::table()
                ->maxRows(20)
                ->maxCols(20)
                ->toArray(),
            'nestedList' => ConfigBuilder::nestedList()
                ->maxLevel(10)
                ->toArray(),
            'alert' => ConfigBuilder::alert()->toArray(),
            'hyperlink' => ConfigBuilder::hyperlink()->toArray(),
            'videoEmbed' => ConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => ConfigBuilder::videoUpload()
                ->maxFileSize(1073741824) // 1GB
                ->toArray(),
        ];
    }
}