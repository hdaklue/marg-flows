<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;
use App\Services\Upload\ChunkConfigManager;
use App\Support\FileSize;

final class Simple
{
    public function build(?string $documentId = null): array
    {
        $imagesConfig = EditorConfigBuilder::images()
            ->forPlan('simple');

        if ($documentId) {
            $imagesConfig
                ->forDocument($documentId)
                ->baseDirectory(auth()->user()->getActiveTenantId(), $documentId);
        }

        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'table' => EditorConfigBuilder::table()->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()
                ->enablePreview(false)->toArray(),
            'images' => $imagesConfig->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => EditorConfigBuilder::videoUpload()
                ->withChunkConfig(ChunkConfigManager::forVideos('simple'))
                ->toArray(),
        ];
    }
}
