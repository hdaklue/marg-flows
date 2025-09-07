<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\AlertConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Alert implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'Alert';

    private array $config = [
        'alertTypes' => [
            'primary',
            'secondary',
            'info',
            'success',
            'warning',
            'danger',
            'light',
            'dark',
        ],
        'defaultType' => 'primary',
        'messagePlaceholder' => 'Enter something',
    ];

    private array $tunes = ['commentTune'];

    private null|string $shortcut = 'CMD+SHIFT+A';

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function alertTypes(array $types): self
    {
        $this->config['alertTypes'] = $types;
        return $this;
    }

    public function defaultType(string $type): self
    {
        $this->config['defaultType'] = $type;
        return $this;
    }

    public function messagePlaceholder(string $placeholder): self
    {
        $this->config['messagePlaceholder'] = $placeholder;
        return $this;
    }

    public function shortcut(null|string $shortcut): self
    {
        $this->shortcut = $shortcut;
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
        return AlertConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
            'shortcut' => $this->shortcut,
        ]);
    }
}
