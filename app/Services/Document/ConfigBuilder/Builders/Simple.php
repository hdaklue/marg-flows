<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;

final class Simple
{
    public function build(): array
    {
        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'table' => EditorConfigBuilder::table()->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()
                ->enablePreview(false)->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
        ];
    }
}
