<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\VideoUploadConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;
use App\Services\Upload\ChunkConfigManager;
use App\Support\FileSize;

final class VideoUpload implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'VideoUpload';

    private array $config = [
        'endpoints' => [
            'byFile' => null,
            'delete' => null,
        ],
        'additionalRequestHeaders' => [
            'X-CSRF-TOKEN' => '',
        ],
        'types' => null,
        'field' => 'video',
        'maxFileSize' => null, // 250MB
        'chunkSize' => null, // 10MB
        'useChunkedUpload' => true,
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
        private string $plan = 'simple'
    ) {
        // Get chunk configuration from ChunkConfigManager
        $chunkConfig = ChunkConfigManager::forVideos($this->plan);
        
        $this->config['endpoints']['byFile'] = route('editorjs.upload-video');
        $this->config['endpoints']['delete'] = route('editorjs.delete-video');
        $this->config['maxFileSize'] = $chunkConfig->maxFileSize;
        $this->config['chunkSize'] = $chunkConfig->chunkSize;
        $this->config['useChunkedUpload'] = $chunkConfig->useChunkedUpload;
        // Note: types/allowedTypes should be handled by FileTypes service
        
        // Add chunk configuration for frontend
        $this->config['chunkConfig'] = $chunkConfig->toArrayForFrontend();
    }

    public function endpoints(array $endpoints): self
    {
        $this->config['endpoints'] = array_merge($this->config['endpoints'], $endpoints);

        return $this;
    }

    public function uploadEndpoint(string $endpoint): self
    {
        $this->config['endpoints']['byFile'] = $endpoint;

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

    public function maxFileSize(?int $size): self
    {
        $this->config['maxFileSize'] = $size;

        return $this;
    }

    public function chunkSize(?int $size): self
    {
        $this->config['chunkSize'] = $size;

        return $this;
    }

    public function useChunkedUpload(?bool $enabled): self
    {
        $this->config['useChunkedUpload'] = $enabled;

        return $this;
    }

    public function additionalRequestHeaders(array $headers): self
    {
        $this->config['additionalRequestHeaders'] = array_merge($this->config['additionalRequestHeaders'], $headers);

        return $this;
    }

    public function inlineToolBar(bool $enabled = true): self
    {
        $this->inlineToolBar = $enabled;

        return $this;
    }

    public function forPlan(string $plan): self
    {
        $this->plan = $plan;
        
        // Reconfigure with new plan
        $chunkConfig = ChunkConfigManager::forVideos($this->plan);
        $this->config['maxFileSize'] = $chunkConfig->maxFileSize;
        $this->config['chunkSize'] = $chunkConfig->chunkSize;
        $this->config['useChunkedUpload'] = $chunkConfig->useChunkedUpload;
        $this->config['chunkConfig'] = $chunkConfig->toArrayForFrontend();

        return $this;
    }

    public function toArray(): array
    {
        return $this->build()->toArray();
    }

    public function toJson($options = 0): string
    {
        return $this->build()->toJson();
    }

    public function toPrettyJson(): string
    {
        return $this->build()->toPrettyJson();
    }

    public function build(): BlockConfigContract
    {
        return VideoUploadConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
