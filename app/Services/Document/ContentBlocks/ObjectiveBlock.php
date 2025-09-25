<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use Faker\Generator;

final class ObjectiveBlock extends Block
{
    /**
     * Get predefined objective options.
     */
    public static function getPredefinedObjectives(): array
    {
        return [
            'Brand Awareness',
            'Lead Generation',
            'Customer Retention',
            'Market Share',
            'Revenue Growth',
            'Customer Satisfaction',
            'Website Traffic',
            'Social Media Engagement',
            'Conversion Rate',
            'Customer Acquisition Cost',
        ];
    }

    /**
     * Generate fake data for testing.
     */
    public static function fake(Generator $faker): array
    {
        $operators = ['increase', 'decrease', 'equal'];

        return [
            'name' => $faker->randomElement(self::getPredefinedObjectives()),
            'operator' => $faker->randomElement($operators),
            'percentage' => $faker->numberBetween(5, 50),
        ];
    }

    /**
     * Validation rules for the objective block data.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'operator' => ['required', 'string', 'in:increase,decrease,equal'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'unit' => ['sometimes'],
        ];
    }

    /**
     * Allowed HTML tags for content purification.
     */
    public function allows(): array
    {
        return [
            'name' => 'b,i,em,strong', // Allow basic formatting in objective names
        ];
    }

    /**
     * Check if the block has valid objective data.
     */
    public function hasObjective(): bool
    {
        $name = $this->get('name');

        return ! empty($name) && is_string($name) && trim($name) !== '';
    }

    /**
     * Get the objective name.
     */
    public function getName(): string
    {
        return (string) $this->get('name', '');
    }

    /**
     * Get the objective operator.
     */
    public function getOperator(): string
    {
        return (string) $this->get('operator', 'increase');
    }

    /**
     * Get the objective percentage.
     */
    public function getPercentage(): float
    {
        $percentage = $this->get('percentage', 0);

        return is_numeric($percentage) ? (float) $percentage : 0.0;
    }

    /**
     * Check if the block is empty (no meaningful content).
     */
    public function isEmpty(): bool
    {
        return ! $this->hasObjective();
    }

    /**
     * Get the display text for the objective.
     */
    public function getDisplayText(): string
    {
        if (! $this->hasObjective()) {
            return '';
        }

        $operatorText = match ($this->getOperator()) {
            'increase' => 'Increase',
            'decrease' => 'Decrease',
            'equal' => 'Equal',
            default => 'Increase',
        };

        return sprintf('%s: %s %.1f%%', $this->getName(), $operatorText, $this->getPercentage());
    }

    /**
     * Get the display text for the objective in Arabic.
     */
    public function getDisplayTextArabic(): string
    {
        if (! $this->hasObjective()) {
            return '';
        }

        $operatorText = match ($this->getOperator()) {
            'increase' => 'زيادة',
            'decrease' => 'نقص',
            'equal' => 'مساوي',
            default => 'زيادة',
        };

        return sprintf('%s: %s %.1f%%', $this->getName(), $operatorText, $this->getPercentage());
    }

    /**
     * Get the operator icon HTML.
     */
    public function getOperatorIcon(): string
    {
        return match ($this->getOperator()) {
            'increase' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5-5 5 5"/></svg>',
            'decrease' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5"/></svg>',
            'equal' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h14M5 15h14"/></svg>',
            default => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5-5 5 5"/></svg>',
        };
    }

    /**
     * Get the CSS class for the operator.
     */
    public function getOperatorClass(): string
    {
        return match ($this->getOperator()) {
            'increase' => 'objective-block--increase',
            'decrease' => 'objective-block--decrease',
            'equal' => 'objective-block--equal',
            default => 'objective-block--increase',
        };
    }

    /**
     * Check if the objective represents an increase.
     */
    public function isIncrease(): bool
    {
        return $this->getOperator() === 'increase';
    }

    /**
     * Check if the objective represents a decrease.
     */
    public function isDecrease(): bool
    {
        return $this->getOperator() === 'decrease';
    }

    /**
     * Check if the objective represents equality.
     */
    public function isEqual(): bool
    {
        return $this->getOperator() === 'equal';
    }

    /**
     * Render the objective block to HTML.
     */
    public function render(): string
    {
        if (! $this->hasObjective()) {
            return '';
        }

        $name = htmlspecialchars($this->getName(), ENT_QUOTES, 'UTF-8');
        $operatorClass = $this->getOperatorClass();
        $operatorIcon = $this->getOperatorIcon();
        $percentage = $this->getPercentage();
        $displayText = htmlspecialchars($this->getDisplayText(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <div class="objective-block__display {$operatorClass}" data-block-type="objective">
            <div class="objective-block__display-content">
                <div class="objective-block__display-header">
                    <span class="objective-block__display-icon">{$operatorIcon}</span>
                    <span class="objective-block__display-name">{$name}</span>
                </div>
                <div class="objective-block__display-metrics">
                    <span class="objective-block__display-operator">{$this->getOperatorLabel()}</span>
                    <span class="objective-block__display-percentage">{$percentage}%</span>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Render the objective block to HTML with RTL support.
     */
    public function renderRtl(): string
    {
        if (! $this->hasObjective()) {
            return '';
        }

        $name = htmlspecialchars($this->getName(), ENT_QUOTES, 'UTF-8');
        $operatorClass = $this->getOperatorClass();
        $operatorIcon = $this->getOperatorIcon();
        $percentage = $this->getPercentage();

        $operatorLabel = match ($this->getOperator()) {
            'increase' => 'زيادة',
            'decrease' => 'نقص',
            'equal' => 'مساوي',
            default => 'زيادة',
        };

        return <<<HTML
        <div class="objective-block__display {$operatorClass}" data-block-type="objective" dir="rtl">
            <div class="objective-block__display-content">
                <div class="objective-block__display-header">
                    <span class="objective-block__display-icon">{$operatorIcon}</span>
                    <span class="objective-block__display-name">{$name}</span>
                </div>
                <div class="objective-block__display-metrics">
                    <span class="objective-block__display-operator">{$operatorLabel}</span>
                    <span class="objective-block__display-percentage">{$percentage}%</span>
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
            'type' => 'objective',
            'name' => $this->getName(),
            'operator' => $this->getOperator(),
            'percentage' => $this->getPercentage(),
            'display_text' => $this->getDisplayText(),
            'is_empty' => $this->isEmpty(),
        ];
    }

    /**
     * Get the human-readable operator label.
     */
    private function getOperatorLabel(): string
    {
        return match ($this->getOperator()) {
            'increase' => 'Increase',
            'decrease' => 'Decrease',
            'equal' => 'Equal',
            default => 'Increase',
        };
    }
}
