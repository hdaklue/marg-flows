<?php

declare(strict_types=1);

namespace App\Services\Document\BlockConfig;

use App\Services\Document\BlockConfig\DTO\TableConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Table implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'Table';

    private array $config = [
        'rows' => 2,
        'cols' => 2,
        'maxRows' => 5,
        'maxCols' => 5,
        'withHeadings' => false,
        'stretched' => false,
    ];

    private array $tunes = ['commentTune'];

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function rows(int $rows): self
    {
        $this->config['rows'] = $rows;
        return $this;
    }

    public function cols(int $cols): self
    {
        $this->config['cols'] = $cols;
        return $this;
    }

    public function maxRows(int $maxRows): self
    {
        $this->config['maxRows'] = $maxRows;
        return $this;
    }

    public function maxCols(int $maxCols): self
    {
        $this->config['maxCols'] = $maxCols;
        return $this;
    }

    public function withHeadings(bool $enabled = true): self
    {
        $this->config['withHeadings'] = $enabled;
        return $this;
    }

    public function stretched(bool $enabled = true): self
    {
        $this->config['stretched'] = $enabled;
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
        return TableConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
    }
}