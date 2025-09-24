<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class FeedbackMetadata implements Castable
{
    public function __construct(
        private array $data,
    ) {}

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes {
            public function get(
                $model,
                string $key,
                $value,
                array $attributes,
            ): null|FeedbackMetadata {
                if ($value === null) {
                    return null;
                }

                $data = is_string($value) ? json_decode($value, true) : $value;

                return new FeedbackMetadata($data ?? []);
            }

            public function set($model, string $key, $value, array $attributes): array
            {
                if ($value === null) {
                    return [$key => null];
                }

                if ($value instanceof FeedbackMetadata) {
                    return [$key => json_encode($value->toArray())];
                }

                return [$key => json_encode($value)];
            }
        };
    }

    public function getType(): string
    {
        return $this->data['type'] ?? 'unknown';
    }

    public function getData(): array
    {
        return $this->data['data'] ?? [];
    }

    public function getSearchable(): array
    {
        return $this->data['searchable'] ?? [];
    }

    public function getTimeRange(): null|array
    {
        if (!$this->isMediaTimestamp()) {
            return null;
        }

        $searchable = $this->getSearchable();

        return [
            'start_time' => $searchable['start_time'] ?? null,
            'end_time' => $searchable['end_time'] ?? null,
            'duration' => $searchable['duration'] ?? null,
        ];
    }

    public function isMediaTimestamp(): bool
    {
        return in_array($this->getType(), [
            'audio_region',
            'video_region',
            'video_frame',
        ]);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
