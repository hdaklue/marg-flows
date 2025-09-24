<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\HeaderConfigData;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;
use InvalidArgumentException;

final class Header implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'header';

    private array $levels = [1, 2, 3, 4, 5, 6];

    private int $defaultLevel = 2;

    private string $placeholder = 'Enter a header';

    private array $tunes = ['commentTune'];

    private array|bool $inlineToolBar = ['link', 'bold', 'italic'];

    /**
     * {@inheritDoc}
     */
    public function build(): BlockConfigContract
    {
        return HeaderConfigData::fromArray([
            'class' => self::CLASS_NAME,
            'config' => [
                'placeholder' => $this->placeholder,
                'levels' => $this->levels,
                'defaultLevel' => $this->defaultLevel,
            ],
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
        ]);
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

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function levels(array $levels): self
    {
        $this->levels = $levels;

        return $this;
    }

    public function addTune(string $tune): self
    {
        $this->tunes[] = $tune;

        return $this;
    }

    public function defaultLevel(int $level): self
    {
        throw_if(
            $level <= 0 || !in_array($level, $this->levels),
            new InvalidArgumentException("{$level} in an invalid Header level"),
        );

        $this->defaultLevel = $level;

        return $this;
    }

    public function inlineToolBar(array|bool $tools = true): self
    {
        $this->inlineToolBar = $tools;

        return $this;
    }
}
