<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\ImagesConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;
use App\Services\Upload\ChunkConfigManager;
use App\Support\FileSize;
use App\Support\FileTypes;
use Storage;

final class Images implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'ResizableImage';

    private array $config = [
        'endpoints' => [
            'byFile' => null,
            'byUrl' => null,
            'delete' => null,
        ],
        'additionalRequestHeaders' => [
            'X-CSRF-TOKEN' => '',
        ],
        'types' => null, // Will be set from FileTypes utility
        'field' => 'image',
        'captionPlaceholder' => 'Enter image caption...',
        'buttonContent' => 'Select an image',
        'maxFileSize' => null, // Will be set from FileSize utility
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
        private string $plan = 'simple',
    ) {
        // Get chunk configuration from ChunkConfigManager for images
        $chunkConfig = ChunkConfigManager::forImages($this->plan);

        // Default endpoints - will be overridden by forDocument() method
        $this->config['endpoints']['byFile'] = null;
        $this->config['endpoints']['byUrl'] = null;
        $this->config['endpoints']['delete'] = null;
        $this->config['types'] =
            FileTypes::getWebImageFormatsAsValidationString();
        $this->config['maxFileSize'] = $chunkConfig->maxFileSize;

        // Add chunk configuration for frontend (even if not used, provides consistency)
        $this->config['chunkConfig'] = $chunkConfig->toArrayForFrontend();
    }

    public function endpoints(array $endpoints): self
    {
        $this->config['endpoints'] = array_merge(
            $this->config['endpoints'],
            $endpoints,
        );

        return $this;
    }

    public function uploadEndpoint(string $endpoint): self
    {
        $this->config['endpoints']['byFile'] = $endpoint;
        $this->config['endpoints']['byUrl'] = $endpoint;

        return $this;
    }

    public function deleteEndpoint(string $endpoint): self
    {
        $this->config['endpoints']['delete'] = $endpoint;

        return $this;
    }

    public function types(string $types): self
    {
        $this->config['types'] = $types;

        return $this;
    }

    public function field(string $field): self
    {
        $this->config['field'] = $field;

        return $this;
    }

    public function captionPlaceholder(string $placeholder): self
    {
        $this->config['captionPlaceholder'] = $placeholder;

        return $this;
    }

    public function buttonContent(string $content): self
    {
        $this->config['buttonContent'] = $content;

        return $this;
    }

    public function additionalRequestHeaders(array $headers): self
    {
        $this->config['additionalRequestHeaders'] = array_merge(
            $this->config['additionalRequestHeaders'],
            $headers,
        );

        return $this;
    }

    public function maxFileSize(int $bytes): self
    {
        $this->config['maxFileSize'] = $bytes;

        return $this;
    }

    public function maxFileSizeMB(float $megabytes): self
    {
        $this->config['maxFileSize'] = FileSize::fromMB($megabytes);

        return $this;
    }

    public function inlineToolBar(bool $enabled = true): self
    {
        $this->inlineToolBar = $enabled;

        return $this;
    }

    public function forDocument(string $documentId): self
    {
        $this->config['endpoints']['byFile'] = route('editorjs.upload-image', [
            'document' => $documentId,
        ]);
        $this->config['endpoints']['byUrl'] = route('editorjs.upload-image', [
            'document' => $documentId,
        ]);
        $this->config['endpoints']['delete'] = route('editorjs.document.delete-image', [
            'document' => $documentId,
        ]);

        return $this;
    }

    public function baseDirectory(string $baseDirectory): self
    {
        $this->config['baseDirectory'] = Storage::get($baseDirectory);

        return $this;
    }

    public function forPlan(string $plan): self
    {
        $this->plan = $plan;

        // Reconfigure with new plan
        $chunkConfig = ChunkConfigManager::forImages($this->plan);
        $this->config['maxFileSize'] = $chunkConfig->maxFileSize;
        $this->config['chunkConfig'] = $chunkConfig->toArrayForFrontend();

        return $this;
    }

    public function toArray(): array
    {
        return $this->build()->toArray();
    }

    public function toJson($options = 0): string
    {
        return $this->build()->toJson($options);
    }

    public function toPrettyJson(): string
    {
        return $this->build()->toPrettyJson();
    }

    public function build(): BlockConfigContract
    {
        return ImagesConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
