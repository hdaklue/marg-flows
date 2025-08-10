<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\DocumentBuilders;

use App\Services\Document\Facades\ConfigBuilder;

final class Advanced
{
    public function build(): array
    {
        return [
            'paragraph' => ConfigBuilder::paragraph()->toArray(),
            'header' => ConfigBuilder::header()->toArray(),
            'images' => ConfigBuilder::images()->toArray(),
            'table' => ConfigBuilder::table()->toArray(),
            'nestedList' => ConfigBuilder::nestedList()->toArray(),
            'alert' => ConfigBuilder::alert()->toArray(),
            'hyperlink' => ConfigBuilder::hyperlink()->toArray(),
            'videoEmbed' => ConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => ConfigBuilder::videoUpload()->toArray(),
        ];
    }
}