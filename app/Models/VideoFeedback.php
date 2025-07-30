<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Video-specific feedback model
 * Handles both frame comments (specific timestamp + coordinates) 
 * and region comments (time range + optional area)
 * 
 * @property string $feedback_type 'frame' or 'region'
 * @property float|null $timestamp Frame timestamp in seconds (for frame feedback)
 * @property float|null $start_time Start time in seconds (for region feedback)
 * @property float|null $end_time End time in seconds (for region feedback)
 * @property int|null $x_coordinate X coordinate on video frame
 * @property int|null $y_coordinate Y coordinate on video frame
 * @property array|null $region_data Additional region metadata (bounds, shape, etc.)
 */
final class VideoFeedback extends BaseFeedback
{
    protected $table = 'video_feedbacks';

    protected $fillable = [
        ...parent::getFillable(),
        'feedback_type',
        'timestamp',
        'start_time',
        'end_time',
        'x_coordinate',
        'y_coordinate',
        'region_data',
    ];

    // Type-specific scopes
    public function scopeFrameComments(Builder $query): Builder
    {
        return $query->where('feedback_type', 'frame');
    }

    public function scopeRegionComments(Builder $query): Builder
    {
        return $query->where('feedback_type', 'region');
    }

    public function scopeAtTimestamp(Builder $query, float $timestamp, float $tolerance = 0.1): Builder
    {
        return $query->where(function ($q) use ($timestamp, $tolerance) {
            // Frame comments: exact timestamp match (with tolerance)
            $q->where(function ($frameQuery) use ($timestamp, $tolerance) {
                $frameQuery->where('feedback_type', 'frame')
                    ->whereBetween('timestamp', [$timestamp - $tolerance, $timestamp + $tolerance]);
            })
            // Region comments: timestamp falls within range
            ->orWhere(function ($regionQuery) use ($timestamp) {
                $regionQuery->where('feedback_type', 'region')
                    ->where('start_time', '<=', $timestamp)
                    ->where('end_time', '>=', $timestamp);
            });
        });
    }

    public function scopeInTimeRange(Builder $query, float $startTime, float $endTime): Builder
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            // Frame comments within the range
            $q->where(function ($frameQuery) use ($startTime, $endTime) {
                $frameQuery->where('feedback_type', 'frame')
                    ->whereBetween('timestamp', [$startTime, $endTime]);
            })
            // Region comments that overlap with the range
            ->orWhere(function ($regionQuery) use ($startTime, $endTime) {
                $regionQuery->where('feedback_type', 'region')
                    ->where(function ($overlap) use ($startTime, $endTime) {
                        $overlap->whereBetween('start_time', [$startTime, $endTime])
                            ->orWhereBetween('end_time', [$startTime, $endTime])
                            ->orWhere(function ($contains) use ($startTime, $endTime) {
                                $contains->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                            });
                    });
            });
        });
    }

    public function scopeAtCoordinates(Builder $query, int $x, int $y, int $tolerance = 20): Builder
    {
        return $query->whereBetween('x_coordinate', [$x - $tolerance, $x + $tolerance])
            ->whereBetween('y_coordinate', [$y - $tolerance, $y + $tolerance]);
    }

    public function scopeNearCoordinates(Builder $query, int $x, int $y, int $radius = 50): Builder
    {
        return $query->whereRaw(
            'SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?',
            [$x, $y, $radius]
        );
    }

    // Type-specific methods
    public function isFrameComment(): bool
    {
        return $this->feedback_type === 'frame';
    }

    public function isRegionComment(): bool
    {
        return $this->feedback_type === 'region';
    }

    public function hasCoordinates(): bool
    {
        return $this->x_coordinate !== null && $this->y_coordinate !== null;
    }

    public function hasTimeRange(): bool
    {
        return $this->isRegionComment() && 
               $this->start_time !== null && 
               $this->end_time !== null;
    }

    public function getDuration(): ?float
    {
        if (!$this->hasTimeRange()) {
            return null;
        }

        return $this->end_time - $this->start_time;
    }

    public function getTimeDisplay(): string
    {
        if ($this->isFrameComment() && $this->timestamp !== null) {
            return $this->formatTime($this->timestamp);
        }

        if ($this->isRegionComment() && $this->hasTimeRange()) {
            return $this->formatTime($this->start_time) . ' - ' . $this->formatTime($this->end_time);
        }

        return 'No time specified';
    }

    public function getCoordinatesDisplay(): string
    {
        if (!$this->hasCoordinates()) {
            return 'No coordinates';
        }

        return "({$this->x_coordinate}, {$this->y_coordinate})";
    }

    public function containsTimestamp(float $timestamp): bool
    {
        if ($this->isFrameComment()) {
            return abs($this->timestamp - $timestamp) < 0.1; // 100ms tolerance
        }

        if ($this->isRegionComment()) {
            return $timestamp >= $this->start_time && $timestamp <= $this->end_time;
        }

        return false;
    }

    public function getFeedbackType(): string
    {
        return 'video';
    }

    // Factory methods for creating specific types
    public static function createFrameComment(array $attributes): static
    {
        return static::create([
            ...$attributes,
            'feedback_type' => 'frame',
        ]);
    }

    public static function createRegionComment(array $attributes): static
    {
        return static::create([
            ...$attributes,
            'feedback_type' => 'region',
        ]);
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'timestamp' => 'float',
            'start_time' => 'float',
            'end_time' => 'float',
            'x_coordinate' => 'integer',
            'y_coordinate' => 'integer',
            'region_data' => 'array',
        ];
    }

    private function formatTime(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%05.2f', $minutes, $seconds);
    }
}