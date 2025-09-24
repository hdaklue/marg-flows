<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class BudgetConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    public null|string $shortcut;

    protected function defaults(): array
    {
        return [
            'class' => 'BudgetBlock',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'shortcut' => 'CMD+SHIFT+B',
            'config' => [
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
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
