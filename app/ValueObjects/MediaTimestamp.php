<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;
use Livewire\Wireable;
use Stringable;

abstract class MediaTimestamp implements
    Arrayable,
    Jsonable,
    JsonSerializable,
    Stringable,
    Wireable
{
    /**
     * Wire deserialization for Livewire.
     */
    public static function fromLivewire($value): self
    {
        throw_if(
            !is_array($value) || !isset($value['type']),
            new InvalidArgumentException(
                'Invalid Livewire value for MediaTimestamp',
            ),
        );

        return match ($value['type']) {
            'audio_region' => AudioRegion::fromArray($value),
            'video_frame' => VideoFrame::fromArray($value),
            'video_region' => VideoRegion::fromArray($value),
            default => throw new InvalidArgumentException(
                'Unknown MediaTimestamp type: ' . $value['type'],
            ),
        };
    }

    abstract public function getType(): string;

    abstract public function getStartTime(): CommentTime;

    abstract public function getEndTime(): CommentTime;

    abstract public function getDuration(): CommentTime;

    abstract public function getFrameRate(): null|float;

    /**
     * JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * JSON string representation.
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Wire serialization for Livewire.
     */
    public function toLivewire(): array
    {
        return $this->toArray();
    }

    /**
     * Create instance from array data.
     */
    abstract public static function fromArray(array $data): self;

    /**
     * String representation.
     */
    public function __toString(): string
    {
        $startTime = $this->getStartTime()->display();
        $endTime = $this->getEndTime()->display();

        if ($endTime) {
            return "{$this->getType()}: {$startTime} - {$endTime}";
        }

        return "{$this->getType()}: {$startTime}";
    }
}
