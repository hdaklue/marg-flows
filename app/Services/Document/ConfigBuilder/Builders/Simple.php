<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Builders;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Facades\EditorConfigBuilder;

final class Simple
{
    private string $tenantId;

    public function build(?string $documentId = null): array
    {
        $this->tenantId = auth()->user()->getActiveTenantId();
        $imagesConfig = EditorConfigBuilder::images()->forPlan('simple');

        if ($documentId) {
            $document = Document::find($documentId);
            if ($document) {
                $baseDirectory = DocumentDirectoryManager::make($document)
                    ->images()
                    ->getDirectory();

                $imagesConfig->forDocument($documentId)->baseDirectory($this->tenantId, $documentId);
            }
        }

        return [
            'paragraph' => EditorConfigBuilder::paragraph()->toArray(),
            'header' => EditorConfigBuilder::header()->toArray(),
            'table' => EditorConfigBuilder::table()->toArray(),
            'nestedList' => EditorConfigBuilder::nestedList()->toArray(),
            'alert' => EditorConfigBuilder::alert()->toArray(),
            'linkTool' => EditorConfigBuilder::linkTool()->enablePreview(false)->toArray(),
            'images' => $imagesConfig->toArray(),
            'videoEmbed' => EditorConfigBuilder::videoEmbed()->toArray(),
            'videoUpload' => $this->buildVideoUploadConfig($documentId)->toArray(),
        ];
    }

    private function buildVideoUploadConfig(?string $documentId)
    {
        $videoUploadConfig = EditorConfigBuilder::videoUpload()->forPlan('simple');

        if ($documentId) {
            $document = Document::find($documentId);
            if ($document) {
                $baseDirectory = DocumentDirectoryManager::make($document)
                    ->videos()
                    ->getDirectory();

                $videoUploadConfig->forDocument($documentId)->baseDirectory($this->tenantId, $documentId);
            }
        }

        return $videoUploadConfig;
    }
}
