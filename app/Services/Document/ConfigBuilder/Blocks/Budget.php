<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\BudgetConfigData;
use App\Services\Document\ContentBlocks\BudgetBlock;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Budget implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'BudgetBlock';

    private array $config = [
        'namePlaceholder' => 'Enter budget name...',
        'amountPlaceholder' => '0.00',
        'defaultCurrency' => 'USD',
        'allowCustomCurrency' => true,
        'amountMin' => 0,
        'amountStep' => 0.01,
        'predefinedBudgets' => [],
        'predefinedCurrencies' => [],
        'validation' => [
            'nameRequired' => true,
            'nameMaxLength' => 255,
            'amountRequired' => true,
            'currencyRequired' => true,
        ],
    ];

    private array $tunes = ['commentTune'];

    private ?string $shortcut = 'CMD+SHIFT+B';

    public function __construct(
        private bool $inlineToolBar = false,
    ) {}

    public function namePlaceholder(string $placeholder): self
    {
        $this->config['namePlaceholder'] = $placeholder;

        return $this;
    }

    public function amountPlaceholder(string $placeholder): self
    {
        $this->config['amountPlaceholder'] = $placeholder;

        return $this;
    }

    public function defaultCurrency(string $currency): self
    {
        $this->config['defaultCurrency'] = $currency;

        return $this;
    }

    public function allowCustomCurrency(bool $allow = true): self
    {
        $this->config['allowCustomCurrency'] = $allow;

        return $this;
    }

    public function amountRange(float $min, float $step = 0.01): self
    {
        $this->config['amountMin'] = $min;
        $this->config['amountStep'] = $step;

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

    public function amountRequired(bool $required = true): self
    {
        $this->config['validation']['amountRequired'] = $required;

        return $this;
    }

    public function currencyRequired(bool $required = true): self
    {
        $this->config['validation']['currencyRequired'] = $required;

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
        // Add predefined data from BudgetBlock class
        $this->config['predefinedBudgets'] = $this->getPredefinedBudgets();
        $this->config['predefinedCurrencies'] = BudgetBlock::getPredefinedCurrencies();

        return BudgetConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
            'shortcut' => $this->shortcut,
        ]);
    }

    private function getPredefinedBudgets(): array
    {
        return [
            'Campaign Budget',
            'Marketing Spend',
            'Advertising Budget',
            'Digital Marketing',
            'Social Media Budget',
            'Content Marketing',
            'PPC Budget',
            'SEO Investment',
            'Brand Awareness',
            'Product Launch',
            'Event Marketing',
            'Influencer Budget',
        ];
    }
}
