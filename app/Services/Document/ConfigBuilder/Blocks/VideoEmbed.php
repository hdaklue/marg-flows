<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\VideoEmbedConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class VideoEmbed implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'VideoEmbed';

    private array $config = [
        'placeholder' => 'Paste a YouTube URL...',
        'allowDirectUrls' => true,
    ];

    private array $tunes = ['commentTune', 'videoEmbedResizableTune'];

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function placeholder(string $placeholder): self
    {
        $this->config['placeholder'] = $placeholder;

        return $this;
    }

    public function allowDirectUrls(bool $enabled = true): self
    {
        $this->config['allowDirectUrls'] = $enabled;

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
        return VideoEmbedConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
