<?php

declare(strict_types=1);

namespace App\Services\Document\DocumentBuilders;

use App\Services\Document\ConfigManager;

final class Ultimate
{
    public function __construct(
        private ConfigManager $configManager
    ) {}

    public function build(): array
    {
        return [
            'paragraph' => $this->configManager->paragraph()->toArray(),
            'header' => $this->configManager->header()->toArray(),
            'images' => $this->configManager->images()->toArray(),
            'table' => $this->configManager->table()
                ->maxRows(20)
                ->maxCols(20)
                ->toArray(),
            'nestedList' => $this->configManager->nestedList()
                ->maxLevel(10)
                ->toArray(),
            'alert' => $this->configManager->alert()->toArray(),
            'hyperlink' => $this->configManager->hyperlink()->toArray(),
            'videoEmbed' => $this->configManager->videoEmbed()->toArray(),
            'videoUpload' => $this->configManager->videoUpload()
                ->maxFileSize(1073741824) // 1GB
                ->toArray(),
        ];
    }
}