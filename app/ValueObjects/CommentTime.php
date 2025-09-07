<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;
use Livewire\Wireable;
use Stringable;

final class CommentTime implements
    Arrayable,
    Jsonable,
    JsonSerializable,
    Stringable,
    Wireable
{
    private readonly float $seconds;

    /**
     * Create a new CommentTime instance from seconds
     */
    private function __construct(float $seconds)
    {
        $this->validateSeconds($seconds);
        $this->seconds = $seconds;
    }

    /**
     * Create from seconds (float for precision)
     */
    public static function fromSeconds(float $seconds): self
    {
        return new self($seconds);
    }

    /**
     * Create from frame number and frame rate
     */
    public static function fromFrame(int $frameNumber, float $frameRate): self
    {
        throw_if(
            $frameNumber < 0,
            new InvalidArgumentException('Frame number cannot be negative, got: '
            . $frameNumber),
        );

        throw_if(
            $frameRate <= 0,
            new InvalidArgumentException('Frame rate must be positive, got: '
            . $frameRate),
        );

        $seconds = $frameNumber / $frameRate;
        return new self($seconds);
    }

    /**
     * Create from formatted time string (auto-detects MM:SS or HH:MM:SS)
     */
    public static function fromFormatted(string $time): self
    {
        $cleaned = trim($time);

        throw_if(
            empty($cleaned),
            new InvalidArgumentException('Time string cannot be empty'),
        );

        // Split by colon
        $parts = explode(':', $cleaned);

        throw_if(
            count($parts) < 2 || count($parts) > 3,
            new InvalidArgumentException('Invalid time format. Use MM:SS or HH:MM:SS format: '
            . $time),
        );

        // Validate each part is numeric
        foreach ($parts as $part) {
            throw_unless(
                is_numeric($part),
                new InvalidArgumentException('Invalid time format. All parts must be numeric: '
                . $time),
            );
        }

        $seconds = 0;

        if (count($parts) === 2) {
            // MM:SS format
            [$minutes, $secs] = $parts;
            $seconds = ((float) $minutes * 60) + (float) $secs;
        } else {
            // HH:MM:SS format
            [$hours, $minutes, $secs] = $parts;
            $seconds =
                ((float) $hours * 3600)
                + ((float) $minutes * 60)
                + (float) $secs;
        }

        return new self($seconds);
    }

    /**
     * Create from string (auto-detects seconds or formatted time)
     */
    public static function fromString(string $input): self
    {
        $cleaned = trim($input);

        throw_if(
            empty($cleaned),
            new InvalidArgumentException('Time string cannot be empty'),
        );

        // If it contains colons, treat as formatted time
        if (str_contains($cleaned, ':')) {
            return self::fromFormatted($cleaned);
        }

        // Otherwise treat as seconds
        throw_unless(
            is_numeric($cleaned),
            new InvalidArgumentException('Invalid time string. Must be numeric seconds or formatted time: '
            . $input),
        );

        return self::fromSeconds((float) $cleaned);
    }

    /**
     * Create zero time
     */
    public static function zero(): self
    {
        return new self(0.0);
    }

    /**
     * Wire deserialization for Livewire
     */
    public static function fromLivewire($value): self
    {
        if (is_numeric($value)) {
            return self::fromSeconds((float) $value);
        }

        if (is_string($value)) {
            return self::fromString($value);
        }

        throw new InvalidArgumentException('Cannot create CommentTime from Livewire value: '
        . gettype($value));
    }

    /**
     * Get as seconds (with decimal precision)
     */
    public function asSeconds(): float
    {
        return $this->seconds;
    }

    /**
     * Get as rounded seconds (integer)
     */
    public function asSecondsInt(): int
    {
        return (int) round($this->seconds);
    }

    /**
     * Get as frame number for given frame rate
     */
    public function getFrame(float $frameRate): int
    {
        throw_if(
            $frameRate <= 0,
            new InvalidArgumentException('Frame rate must be positive, got: '
            . $frameRate),
        );

        return (int) round($this->seconds * $frameRate);
    }

    /**
     * Get frame-aligned timestamp for given frame rate
     */
    public function getFrameAlignedTime(float $frameRate): self
    {
        $frameNumber = $this->getFrame($frameRate);
        return self::fromFrame($frameNumber, $frameRate);
    }

    /**
     * Get formatted time string (auto-detects if hours needed)
     */
    public function asFormatted(bool $forceHours = false): string
    {
        $totalSeconds = (int) round($this->seconds);
        $hours = intval($totalSeconds / 3600);
        $minutes = intval(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0 || $forceHours) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted with decimal precision for seconds
     */
    public function asFormattedPrecise(
        int $decimals = 1,
        bool $forceHours = false,
    ): string {
        $totalSeconds = $this->seconds;
        $hours = intval($totalSeconds / 3600);
        $minutes = intval(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0 || $forceHours) {
            return sprintf(
                '%d:%02d:%0' . (3 + $decimals) . '.' . $decimals . 'f',
                $hours,
                $minutes,
                $seconds,
            );
        }

        return sprintf(
            '%d:%0' . (3 + $decimals) . '.' . $decimals . 'f',
            $minutes,
            $seconds,
        );
    }

    /**
     * Get display string (default formatting)
     */
    public function display(): string
    {
        return $this->asFormatted();
    }

    /**
     * Get display string with precision
     */
    public function displayPrecise(int $decimals = 1): string
    {
        return $this->asFormattedPrecise($decimals);
    }

    /**
     * Get display string with frame information
     */
    public function displayWithFrame(float $frameRate): string
    {
        $frameNumber = $this->getFrame($frameRate);
        return "Frame {$frameNumber} ({$this->display()})";
    }

    /**
     * Check if time is zero
     */
    public function isZero(): bool
    {
        return abs($this->seconds) < PHP_FLOAT_EPSILON;
    }

    /**
     * Check if time has hours (>= 1 hour)
     */
    public function hasHours(): bool
    {
        return $this->seconds >= 3600;
    }

    /**
     * Check if time is greater than given time
     */
    public function gt(CommentTime $other): bool
    {
        return $this->seconds > $other->seconds;
    }

    /**
     * Check if time is less than given time
     */
    public function lt(CommentTime $other): bool
    {
        return $this->seconds < $other->seconds;
    }

    /**
     * Check if time equals given time (with float precision tolerance)
     */
    public function eq(CommentTime $other): bool
    {
        return abs($this->seconds - $other->seconds) < PHP_FLOAT_EPSILON;
    }

    /**
     * Add time (returns new instance)
     */
    public function add(CommentTime $other): self
    {
        return new self($this->seconds + $other->seconds);
    }

    /**
     * Subtract time (returns new instance, minimum 0)
     */
    public function subtract(CommentTime $other): self
    {
        return new self(max(0.0, $this->seconds - $other->seconds));
    }

    /**
     * Get difference between times (absolute value)
     */
    public function diff(CommentTime $other): self
    {
        return new self(abs($this->seconds - $other->seconds));
    }

    /**
     * JSON serialization (returns seconds for API compatibility)
     */
    public function jsonSerialize(): float
    {
        return $this->asSeconds();
    }

    /**
     * Array representation
     */
    public function toArray(): array
    {
        return [
            'seconds' => $this->asSeconds(),
            'seconds_int' => $this->asSecondsInt(),
            'formatted' => $this->asFormatted(),
            'formatted_precise' => $this->asFormattedPrecise(),
            'display' => $this->display(),
            'has_hours' => $this->hasHours(),
        ];
    }

    /**
     * Array representation with frame data
     */
    public function toArrayWithFrames(float $frameRate): array
    {
        return array_merge($this->toArray(), [
            'frame_number' => $this->getFrame($frameRate),
            'frame_rate' => $frameRate,
            'frame_aligned' =>
                $this->getFrameAlignedTime($frameRate)->asSeconds(),
        ]);
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
     * Validate seconds value
     */
    private function validateSeconds(float $seconds): void
    {
        throw_unless(
            is_finite($seconds),
            new InvalidArgumentException('Seconds must be a finite number'),
        );

        throw_if(
            $seconds < 0,
            new InvalidArgumentException('Seconds cannot be negative, got: '
            . $seconds),
        );
    }

    /**
     * String representation (for __toString)
     */
    public function __toString(): string
    {
        return $this->display();
    }
}
