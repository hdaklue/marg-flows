<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;

final class Ultimate
{
    public function build(): array
    {
        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'images' => EditorConfigBuilder::images()->toArray(),
            'table' => EditorConfigBuilder::table()
                ->maxRows(20)
                ->maxCols(20)
                ->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()
                ->maxLevel(10)
                ->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => EditorConfigBuilder::videoUpload()
                ->maxFileSize(1073741824) // 1GB
                ->toArray(),
        ];
    }
}
