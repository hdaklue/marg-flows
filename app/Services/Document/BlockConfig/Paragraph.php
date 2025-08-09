<?php

declare(strict_types=1);

namespace App\Services\Document\BlockConfig;

use App\Services\Document\BlockConfig\DTO\ParagraphConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Paragraph implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'paragraph';

    private $config = [
        'preserveBlank' => false,
        'placeholder' => 'write something',
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private $inlineToolBar = false,
    ) {}

    public function placeholder(string $placeholder): self
    {
        $this->config['placeholder'] = $placeholder;

        return $this;
    }

    public function inlineToolBar(bool $enabled = true)
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
        return ParagraphConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}
