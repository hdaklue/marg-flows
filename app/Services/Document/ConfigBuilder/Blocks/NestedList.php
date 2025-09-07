<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\NestedListConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class NestedList implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'EditorJsList';

    private array $config = [
        'defaultStyle' => 'unordered',
        'placeholder' => 'Add an item',
        'maxLevel' => null,
        'counterTypes' => null,
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function defaultStyle(string $style): self
    {
        $this->config['defaultStyle'] = $style;
        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->config['placeholder'] = $placeholder;
        return $this;
    }

    public function maxLevel(int $level): self
    {
        $this->config['maxLevel'] = $level;
        return $this;
    }

    public function counterTypes(array $types): self
    {
        $this->config['counterTypes'] = $types;
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
        return NestedListConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
