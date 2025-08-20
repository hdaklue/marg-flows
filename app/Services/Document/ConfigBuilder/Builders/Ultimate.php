<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Services\Directory\DirectoryManager;
use App\Services\Document\Facades\EditorConfigBuilder;

final class Ultimate
{
    private string $tenantId;

    public function build(?string $documentId = null): array
    {
        $this->tenantId = auth()->user()->getActiveTenantId();
        $imagesConfig = EditorConfigBuilder::images()
            ->forPlan('ultimate');
        $baseDirectory = DirectoryManager::document($this->tenantId)
            ->forDocument($documentId)
            ->images()
            ->getDirectory();
        if ($documentId) {
            $imagesConfig
                ->forDocument($documentId)
                ->baseDirectory($baseDirectory);
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
            'videoUpload' => $this->buildVideoUploadConfig($documentId)->toArray(),
        ];
    }

    private function buildVideoUploadConfig(?string $documentId)
    {
        $videoUploadConfig = EditorConfigBuilder::videoUpload()
            ->forPlan('ultimate');

        $baseDirectory = DirectoryManager::document($this->tenantId)
            ->forDocument($documentId)
            ->videos()
            ->getDirectory();

        if ($documentId) {
            $videoUploadConfig
                ->forDocument($documentId)
                ->baseDirectory($baseDirectory);
        }

        return $videoUploadConfig;
    }
}
