<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\HyperlinkConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Hyperlink implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'HyperLink';

    private array $config = [
        'target' => '_blank',
        'rel' => 'nofollow',
        'availableTargets' => ['_blank', '_self'],
        'availableRels' => ['author', 'noreferrer'],
        'validate' => false,
    ];

    private array $tunes = ['commentTune'];

    private ?string $shortcut = 'CMD+L';

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function target(string $target): self
    {
        $this->config['target'] = $target;
        return $this;
    }

    public function rel(string $rel): self
    {
        $this->config['rel'] = $rel;
        return $this;
    }

    public function availableTargets(array $targets): self
    {
        $this->config['availableTargets'] = $targets;
        return $this;
    }

    public function availableRels(array $rels): self
    {
        $this->config['availableRels'] = $rels;
        return $this;
    }

    public function validate(bool $enabled = true): self
    {
        $this->config['validate'] = $enabled;
        return $this;
    }

    public function shortcut(?string $shortcut): self
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
        return HyperlinkConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
            'shortcut' => $this->shortcut,
        ]);
    }
}