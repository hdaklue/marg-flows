<?php

declare(strict_types=1);

namespace App\Services\Document\DocumentBuilders;

use App\Services\Document\ConfigManager;

final class Simple
{
    public function __construct(
        private ConfigManager $configManager
    ) {}

    public function build(): array
    {
        return [
            'paragraph' => $this->configManager->paragraph()->toArray(),
            'header' => $this->configManager->header()->toArray(),
            'table' => $this->configManager->table()->toArray(),
            'nestedList' => $this->configManager->nestedList()->toArray(),
            'alert' => $this->configManager->alert()->toArray(),
            'hyperlink' => $this->configManager->hyperlink()->toArray(),
            'videoEmbed' => $this->configManager->videoEmbed()->toArray(),
        ];
    }
}