<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;

final class Ultimate
{
    public function build(?string $documentId = null): array
    {
        $imagesConfig = EditorConfigBuilder::images()
            ->forPlan('ultimate');
        if ($documentId) {
            $imagesConfig
                ->forDocument($documentId)
                ->baseDirectory(auth()->user()->getActiveTenantId(), $documentId);
        }

        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'images' => $imagesConfig->toArray(),
            'table' => EditorConfigBuilder::table()
                ->maxRows(20)
                ->maxCols(20)
                ->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()
                ->maxLevel(3)
                ->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => EditorConfigBuilder::videoUpload()
                ->forPlan('ultimate')
                ->toArray(),
        ];
    }
}
