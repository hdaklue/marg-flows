<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Facades\DocumentManager;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\DocumentService;
use App\Services\Document\Facades\EditorConfigBuilder;

final class Ultimate
{
    private string $tenantId;

    public function build(null|string $documentId = null): array
    {
        $this->tenantId = auth()->user()->getActiveTenantId();
        $imagesConfig = EditorConfigBuilder::images()->forPlan('ultimate');
        $document = DocumentManager::getDocument($documentId);

        $baseDirectory = DocumentDirectoryManager::make($document)->images()->getDirectory();
        if ($documentId) {
            $imagesConfig->forDocument($documentId)->baseDirectory($this->tenantId, $documentId);
        }

        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'images' => $imagesConfig->toArray(),
            'table' => EditorConfigBuilder::table()
                ->maxRows(20)
                ->maxCols(20)
                ->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()->maxLevel(3)->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => $this->buildVideoUploadConfig($documentId)->toArray(),
        ];
    }

    private function buildVideoUploadConfig(null|string $documentId)
    {
        $videoUploadConfig = EditorConfigBuilder::videoUpload()->forPlan('ultimate');

        $document = DocumentManager::getDocument($documentId);

        $baseDirectory = DocumentDirectoryManager::make($document)->videos()->getDirectory();

        if ($documentId) {
            $videoUploadConfig->forDocument($documentId)->baseDirectory(
                $this->tenantId,
                $documentId,
            );
        }

        return $videoUploadConfig;
    }
}
