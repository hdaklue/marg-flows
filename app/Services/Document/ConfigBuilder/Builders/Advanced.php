<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Document\Facades\EditorConfigBuilder;
use App\Services\Upload\ChunkConfigManager;

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
            'videoUpload' => $this->buildVideoUploadConfig($documentId)->toArray(),
        ];
    }

    private function buildVideoUploadConfig(?string $documentId)
    {
        $videoUploadConfig = EditorConfigBuilder::videoUpload()
            ->forPlan('advanced');

        if ($documentId) {
            $videoUploadConfig
                ->forDocument($documentId)
                ->baseDirectory(auth()->user()->getActiveTenantId(), $documentId);
        }

        return $videoUploadConfig;
    }
}
