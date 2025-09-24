<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;
use App\Services\Upload\ChunkConfigManager;

final class Base
{
    public function build(null|string $documentId = null): array
    {
        $imagesConfig = EditorConfigBuilder::images();
        if ($documentId) {
            $imagesConfig->forDocument($documentId)->baseDirectory(
                auth()->user()->getActiveTenantId(),
                $documentId,
            );
        }

        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'images' => $imagesConfig->toArray(),
            'table' => EditorConfigBuilder::table()
                ->maxRows(20)
                ->maxCols(20)
                ->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()->maxLevel(10)->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => $this->buildVideoUploadConfig($documentId)->toArray(),
            'objective' => EditorConfigBuilder::objective()->toArray(),
            'budget' => EditorConfigBuilder::budget()->toArray(),
            'persona' => EditorConfigBuilder::persona()->toArray(),
        ];
    }

    private function buildVideoUploadConfig(null|string $documentId)
    {
        $videoUploadConfig = EditorConfigBuilder::videoUpload()->withChunkConfig(ChunkConfigManager::forVideos(
            'simple',
        ));

        if ($documentId) {
            $videoUploadConfig->forDocument($documentId)->baseDirectory(
                auth()->user()->getActiveTenantId(),
                $documentId,
            );
        }

        return $videoUploadConfig;
    }
}
