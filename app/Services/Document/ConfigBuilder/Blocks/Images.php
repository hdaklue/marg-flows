<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\ImagesConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

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
        'types' => 'image/*',
        'field' => 'image',
        'captionPlaceholder' => 'Enter image caption...',
        'buttonContent' => 'Select an image',
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
    ) {
        $this->config['endpoints']['byFile'] = route('editorjs.uploade-image');
        $this->config['endpoints']['byUrl'] = route('editorjs.uploade-image');
        $this->config['endpoints']['delete'] = route('editorjs.delete-image');
    }

    public function endpoints(array $endpoints): self
    {
        $this->config['endpoints'] = array_merge($this->config['endpoints'], $endpoints);

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
        $this->config['additionalRequestHeaders'] = array_merge($this->config['additionalRequestHeaders'], $headers);

        return $this;
    }

    public function inlineToolBar(bool $enabled = true): self
    {
        $this->inlineToolBar = $enabled;

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
        return ImagesConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
