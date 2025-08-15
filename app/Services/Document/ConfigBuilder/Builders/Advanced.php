<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;

final class Advanced
{
    public function build(?string $documentId = null): array
    {
        $imagesConfig = EditorConfigBuilder::images()
            ->forPlan('advanced');
        if ($documentId) {
            $imagesConfig
                ->forDocument($documentId)
                ->baseDirectory(auth()->user()->getActiveTenantId(), $documentId);
        }

        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'images' => $imagesConfig->toArray(),
            'table' => EditorConfigBuilder::table()->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => EditorConfigBuilder::videoUpload()
                ->forPlan('advanced')
                ->toArray(),
        ];
    }
}
