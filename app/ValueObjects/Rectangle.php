<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;
use Livewire\Wireable;
use Stringable;

final class Rectangle implements Arrayable, Jsonable, JsonSerializable, Stringable, Wireable
{
    public function __construct(
        private readonly int $x,
        private readonly int $y,
        private readonly int $width,
        private readonly int $height,
    ) {
        $this->validateDimensions();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            x: (int) ($data['x'] ?? 0),
            y: (int) ($data['y'] ?? 0),
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
        );
    }

    public static function fromLivewire($value): self
    {
        throw_unless(
            is_array($value),
            new InvalidArgumentException(
                'Rectangle fromLivewire expects array',
            ),
        );

        return self::fromArray($value);
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getArea(): int
    {
        return $this->width * $this->height;
    }

    public function getCenterX(): int
    {
        return $this->x + (int) ($this->width / 2);
    }

    public function getCenterY(): int
    {
        return $this->y + (int) ($this->height / 2);
    }

    public function contains(int $x, int $y): bool
    {
        return
            $x >= $this->x
            && $x
            <= ($this->x + $this->width)
            && $y >= $this->y
            && $y
            <= ($this->y + $this->height);
    }

    public function overlaps(Rectangle $other): bool
    {
        return ! (
            ($this->x + $this->width)
            < $other->x
            || ($other->x + $other->width)
            < $this->x
            || ($this->y + $this->height)
            < $other->y
            || ($other->y + $other->height)
            < $this->y
        );
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'area' => $this->getArea(),
            'center_x' => $this->getCenterX(),
            'center_y' => $this->getCenterY(),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function toLivewire(): array
    {
        return $this->jsonSerialize();
    }

    private function validateDimensions(): void
    {
        throw_if(
            $this->width < 0,
            new InvalidArgumentException('Width cannot be negative'),
        );

        throw_if(
            $this->height < 0,
            new InvalidArgumentException('Height cannot be negative'),
        );
    }

    public function __toString(): string
    {
        return "Rectangle({$this->x}, {$this->y}, {$this->width}x{$this->height})";
    }
}
