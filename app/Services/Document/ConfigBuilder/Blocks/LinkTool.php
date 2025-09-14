<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\LinkToolConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class LinkTool implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'LinkTool';

    private array $config = [
        'endpoint' => '',
        'headers' => [],
        'enablePreview' => true,
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
    ) {
        $this->config['endpoint'] = route('editorjs.fetch-url');
    }

    public function endpoint(string $endpoint): self
    {
        $this->config['endpoint'] = $endpoint;

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->config['headers'] = $headers;

        return $this;
    }

    public function inlineToolBar(bool $enabled = true): self
    {
        $this->inlineToolBar = $enabled;

        return $this;
    }

    public function enablePreview(bool $enabled = true): self
    {
        $this->config['enablePreview'] = $enabled;

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
        return LinkToolConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
