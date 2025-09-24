<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use Faker\Generator;

final class BudgetBlock extends Block
{
    /**
     * Get predefined currency options.
     */
    public static function getPredefinedCurrencies(): array
    {
        return [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            'KWD' => 'د.ك',
            'QAR' => 'ر.ق',
            'BHD' => 'د.ب',
            'OMR' => 'ر.ع',
        ];
    }

    /**
     * Generate fake data for testing.
     */
    public static function fake(Generator $faker): array
    {
        $currencies = array_keys(self::getPredefinedCurrencies());

        return [
            'name' => $faker->randomElement([
                'Campaign Budget',
                'Marketing Spend',
                'Advertising Budget',
                'Digital Marketing',
                'Social Media Budget',
                'Content Marketing',
                'PPC Budget',
                'SEO Investment',
            ]),
            'amount' => $faker->numberBetween(1000, 1000000),
            'currency' => $faker->randomElement($currencies),
        ];
    }

    /**
     * Validation rules for the budget block data.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
        ];
    }

    /**
     * Allowed HTML tags for content purification.
     */
    public function allows(): array
    {
        return [
            'name' => 'b,i,em,strong', // Allow basic formatting in budget names
        ];
    }

    /**
     * Check if the block has valid budget data.
     */
    public function hasBudget(): bool
    {
        $name = $this->get('name');
        $amount = $this->get('amount');

        return (
            !empty($name)
            && is_string($name)
            && trim($name) !== ''
            && is_numeric($amount)
            && $amount > 0
        );
    }

    /**
     * Get the budget name.
     */
    public function getName(): string
    {
        return (string) $this->get('name', '');
    }

    /**
     * Get the budget amount.
     */
    public function getAmount(): float
    {
        $amount = $this->get('amount', 0);

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    /**
     * Get the budget currency.
     */
    public function getCurrency(): string
    {
        return (string) $this->get('currency', 'USD');
    }

    /**
     * Get the currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        $currencies = self::getPredefinedCurrencies();

        return $currencies[$this->getCurrency()] ?? '$';
    }

    /**
     * Check if the block is empty (no meaningful content).
     */
    public function isEmpty(): bool
    {
        return !$this->hasBudget();
    }

    /**
     * Get the display text for the budget.
     */
    public function getDisplayText(): string
    {
        if (!$this->hasBudget()) {
            return '';
        }

        $symbol = $this->getCurrencySymbol();
        $amount = $this->getFormattedAmount();

        return sprintf('%s: %s%s', $this->getName(), $symbol, $amount);
    }

    /**
     * Get the display text for the budget in Arabic.
     */
    public function getDisplayTextArabic(): string
    {
        if (!$this->hasBudget()) {
            return '';
        }

        $symbol = $this->getCurrencySymbol();
        $amount = $this->getFormattedAmount();

        return sprintf('%s: %s%s', $this->getName(), $symbol, $amount);
    }

    /**
     * Get formatted amount with proper number formatting.
     */
    public function getFormattedAmount(): string
    {
        $amount = $this->getAmount();

        // Format with commas for thousands
        return number_format($amount, 2, '.', ',');
    }

    /**
     * Get the budget icon HTML.
     */
    public function getBudgetIcon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>';
    }

    /**
     * Get the CSS class for the budget.
     */
    public function getBudgetClass(): string
    {
        return 'budget-block--budget';
    }

    /**
     * Check if the currency is right-to-left.
     */
    public function isRtlCurrency(): bool
    {
        $rtlCurrencies = ['SAR', 'AED', 'KWD', 'QAR', 'BHD', 'OMR'];

        return in_array($this->getCurrency(), $rtlCurrencies);
    }

    /**
     * Render the budget block to HTML.
     */
    public function render(): string
    {
        if (!$this->hasBudget()) {
            return '';
        }

        $name = htmlspecialchars($this->getName(), ENT_QUOTES, 'UTF-8');
        $budgetClass = $this->getBudgetClass();
        $budgetIcon = $this->getBudgetIcon();
        $symbol = htmlspecialchars($this->getCurrencySymbol(), ENT_QUOTES, 'UTF-8');
        $amount = $this->getFormattedAmount();
        $currency = htmlspecialchars($this->getCurrency(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <div class="budget-block__display {$budgetClass}" data-block-type="budget">
            <div class="budget-block__display-content">
                <div class="budget-block__display-header">
                    <span class="budget-block__display-icon">{$budgetIcon}</span>
                    <span class="budget-block__display-name">{$name}</span>
                </div>
                <div class="budget-block__display-amount">
                    <span class="budget-block__display-symbol">{$symbol}</span>
                    <span class="budget-block__display-value">{$amount}</span>
                    <span class="budget-block__display-currency">{$currency}</span>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Render the budget block to HTML with RTL support.
     */
    public function renderRtl(): string
    {
        if (!$this->hasBudget()) {
            return '';
        }

        $name = htmlspecialchars($this->getName(), ENT_QUOTES, 'UTF-8');
        $budgetClass = $this->getBudgetClass();
        $budgetIcon = $this->getBudgetIcon();
        $symbol = htmlspecialchars($this->getCurrencySymbol(), ENT_QUOTES, 'UTF-8');
        $amount = $this->getFormattedAmount();
        $currency = htmlspecialchars($this->getCurrency(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <div class="budget-block__display {$budgetClass}" data-block-type="budget" dir="rtl">
            <div class="budget-block__display-content">
                <div class="budget-block__display-header">
                    <span class="budget-block__display-icon">{$budgetIcon}</span>
                    <span class="budget-block__display-name">{$name}</span>
                </div>
                <div class="budget-block__display-amount">
                    <span class="budget-block__display-symbol">{$symbol}</span>
                    <span class="budget-block__display-value">{$amount}</span>
                    <span class="budget-block__display-currency">{$currency}</span>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Get summary data for analytics or reporting.
     */
    public function getSummary(): array
    {
        return [
            'type' => 'budget',
            'name' => $this->getName(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'currency_symbol' => $this->getCurrencySymbol(),
            'formatted_amount' => $this->getFormattedAmount(),
            'display_text' => $this->getDisplayText(),
            'is_empty' => $this->isEmpty(),
        ];
    }
}
