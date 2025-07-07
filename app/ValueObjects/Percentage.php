<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;
use Livewire\Wireable;
use Stringable;

final class Percentage implements Arrayable, Jsonable, JsonSerializable, Stringable, Wireable
{
    private readonly float $value;

    /**
     * Create a new Percentage instance from a ratio (0.0 - 1.0)
     */
    private function __construct(float $ratio)
    {
        $this->validateRatio($ratio);
        $this->value = $ratio;
    }

    /**
     * Create from ratio (0.0 - 1.0)
     */
    public static function fromRatio(float $ratio): self
    {
        return new self($ratio);
    }

    /**
     * Create from percentage value (0 - 100)
     */
    public static function fromPercentage(float $percentage): self
    {
        if (! is_finite($percentage)) {
            throw new InvalidArgumentException('Percentage must be a finite number');
        }

        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100, got: ' . $percentage);
        }

        return new self($percentage / 100);
    }

    /**
     * Create from integer percentage (0 - 100)
     */
    public static function fromInt(int $percentage): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100, got: ' . $percentage);
        }

        return new self($percentage / 100);
    }

    /**
     * Create from string percentage (supports "90%", "90.5%", "0.9", etc.)
     */
    public static function fromString(string $percentage): self
    {
        $cleaned = trim($percentage);

        if (empty($cleaned)) {
            throw new InvalidArgumentException('Percentage string cannot be empty');
        }

        // Remove percentage symbol if present
        $hasPercentSymbol = str_ends_with($cleaned, '%');
        if ($hasPercentSymbol) {
            $cleaned = rtrim($cleaned, '%');
        }

        if (! is_numeric($cleaned)) {
            throw new InvalidArgumentException('Invalid percentage string: ' . $percentage);
        }

        $numericValue = (float) $cleaned;

        // If it had a % symbol or the number is > 1, treat as percentage (0-100)
        // Otherwise treat as ratio (0-1)
        if ($hasPercentSymbol || $numericValue > 1) {
            return self::fromPercentage($numericValue);
        }

        return self::fromRatio($numericValue);
    }

    /**
     * Create zero percentage
     */
    public static function zero(): self
    {
        return new self(0.0);
    }

    /**
     * Create complete percentage (100%)
     */
    public static function complete(): self
    {
        return new self(1.0);
    }

    /**
     * Wire deserialization for Livewire
     */
    public static function fromLivewire($value): self
    {
        if (is_numeric($value)) {
            // Assume Livewire sends percentage values (0-100)
            return self::fromPercentage((float) $value);
        }

        if (is_string($value)) {
            return self::fromString($value);
        }

        throw new InvalidArgumentException('Cannot create Percentage from Livewire value: ' . gettype($value));
    }

    /**
     * Get as ratio (0.0 - 1.0)
     */
    public function asRatio(): float
    {
        return $this->value;
    }

    /**
     * Get as percentage (0.0 - 100.0)
     */
    public function asPercentage(): float
    {
        return \round($this->value * 100, 1);
    }

    /**
     * Get as integer percentage (0 - 100), rounded
     */
    public function asInt(): int
    {
        return (int) round($this->asPercentage());
    }

    /**
     * Get formatted string with specified decimal places
     */
    public function format(int $decimals = 1, bool $showSymbol = true): string
    {
        $formatted = number_format($this->asPercentage(), $decimals);

        return $showSymbol ? $formatted . '%' : $formatted;
    }

    /**
     * Get display string (default formatting)
     */
    public function display(): string
    {
        return $this->format(1, true);
    }

    /**
     * Check if percentage is zero
     */
    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    /**
     * Check if percentage is complete (100%)
     */
    public function isComplete(): bool
    {
        return $this->value === 1.0;
    }

    /**
     * Check if percentage is greater than given percentage
     */
    public function gt(Percentage $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Check if percentage is less than given percentage
     */
    public function ls(Percentage $other): bool
    {
        return $this->value < $other->value;
    }

    /**
     * Check if percentage equals given percentage
     */
    public function eq(Percentage $other): bool
    {
        return abs($this->value - $other->value) < PHP_FLOAT_EPSILON;
    }

    /**
     * Add percentage (returns new instance)
     */
    public function add(Percentage $other): self
    {
        $newRatio = min(1.0, $this->value + $other->value);

        return new self($newRatio);
    }

    /**
     * Subtract percentage (returns new instance)
     */
    public function subtract(Percentage $other): self
    {
        $newRatio = max(0.0, $this->value - $other->value);

        return new self($newRatio);
    }

    /**
     * Multiply by factor (returns new instance)
     */
    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Factor cannot be negative');
        }

        $newRatio = min(1.0, $this->value * $factor);

        return new self($newRatio);
    }

    /**
     * Get the inverse percentage (100% - this%)
     */
    public function inverse(): self
    {
        return new self(1.0 - $this->value);
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): float
    {
        return $this->asPercentage();
    }

    /**
     * Array representation
     */
    public function toArray(): array
    {
        return [
            'percentage' => $this->asPercentage(),
            'ratio' => $this->asRatio(),
            'display' => $this->display(),
        ];
    }

    /**
     * JSON string representation
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Wire serialization for Livewire
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * Validate ratio value
     */
    private function validateRatio(float $ratio): void
    {
        if (! is_finite($ratio)) {
            throw new InvalidArgumentException('Ratio must be a finite number');
        }

        if ($ratio < 0 || $ratio > 1) {
            throw new InvalidArgumentException('Ratio must be between 0 and 1, got: ' . $ratio);
        }
    }

    /**
     * String representation (for __toString)
     */
    public function __toString(): string
    {
        return $this->format(1, true);
    }
}
