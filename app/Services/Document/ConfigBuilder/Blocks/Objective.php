<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\ObjectiveConfigData;
use App\Services\Document\ContentBlocks\ObjectiveBlock;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Objective implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'ObjectiveBlock';

    private array $config = [
        'namePlaceholder' => 'Enter objective name...',
        'operators' => [
            'increase' => 'Increase',
            'decrease' => 'Decrease',
            'equal' => 'Equal',
        ],
        'defaultOperator' => 'increase',
        'percentageMin' => 0,
        'percentageMax' => 100,
        'percentageStep' => 0.1,
        'predefinedObjectives' => [],
        'validation' => [
            'nameRequired' => true,
            'nameMaxLength' => 255,
            'percentageRequired' => true,
        ],
    ];

    private array $tunes = ['commentTune'];

    private ?string $shortcut = 'CMD+SHIFT+O';

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function namePlaceholder(string $placeholder): self
    {
        $this->config['namePlaceholder'] = $placeholder;

        return $this;
    }

    public function operators(array $operators): self
    {
        $this->config['operators'] = $operators;

        return $this;
    }

    public function defaultOperator(string $operator): self
    {
        $this->config['defaultOperator'] = $operator;

        return $this;
    }

    public function percentageRange(int $min, int $max, float $step = 0.1): self
    {
        $this->config['percentageMin'] = $min;
        $this->config['percentageMax'] = $max;
        $this->config['percentageStep'] = $step;

        return $this;
    }

    public function nameMaxLength(int $length): self
    {
        $this->config['validation']['nameMaxLength'] = $length;

        return $this;
    }

    public function nameRequired(bool $required = true): self
    {
        $this->config['validation']['nameRequired'] = $required;

        return $this;
    }

    public function percentageRequired(bool $required = true): self
    {
        $this->config['validation']['percentageRequired'] = $required;

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

    public function withTunes(array $tunes): self
    {
        $this->tunes = $tunes;

        return $this;
    }

    public function addTune(string $tune): self
    {
        if (! in_array($tune, $this->tunes)) {
            $this->tunes[] = $tune;
        }

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
        // Add predefined objectives from ObjectiveBlock class
        $this->config['predefinedObjectives'] = ObjectiveBlock::getPredefinedObjectives();

        return ObjectiveConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
            'shortcut' => $this->shortcut,
        ]);
    }
}
