<?php

declare(strict_types=1);

namespace App\ValueObjects\Dimension;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;

final class Dimension implements Arrayable, Jsonable
{
    private $width;

    private $height;

    /**
     * @param  int  $width
     * @param  int  $height
     *
     * @throws InvalidArgumentException when one of the parameteres is invalid
     */
    public function __construct($width, $height)
    {
        throw_if(
            $width <= 0 || $height <= 0,
            new InvalidArgumentException('Width and height should be positive integer'),
        );

        $this->width = (int) $width;
        $this->height = (int) $height;
    }

    public static function from(int $width, int $height): self
    {
        return new self($width, $height);
    }

    /**
     * Returns width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->getAspectRatio()->getRatio(),
            'aspect_ratio_name' => $this->getAspectRatio()->getAspectRatio(),
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Returns the ratio.
     *
     *
     * @return AspectRatio
     */
    public function getAspectRatio()
    {
        return AspectRatio::from($this->width, $this->height);
    }
}
