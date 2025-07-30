<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Audio-specific feedback model
 * Handles time-based audio feedback with waveform data
 * 
 * @property float $start_time Start time in seconds
 * @property float $end_time End time in seconds
 * @property array|null $waveform_data Waveform visualization data
 * @property float|null $peak_amplitude Peak amplitude in the selection
 * @property array|null $frequency_data Frequency analysis data
 */
final class AudioFeedback extends BaseFeedback
{
    protected $table = 'audio_feedbacks';

    protected $fillable = [
        ...parent::getFillable(),
        'start_time',
        'end_time',
        'waveform_data',
        'peak_amplitude',
        'frequency_data',
    ];

    // Type-specific scopes
    public function scopeAtTimestamp(Builder $query, float $timestamp): Builder
    {
        return $query->where('start_time', '<=', $timestamp)
            ->where('end_time', '>=', $timestamp);
    }

    public function scopeInTimeRange(Builder $query, float $startTime, float $endTime): Builder
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            // Feedback that overlaps with the given range
            $q->whereBetween('start_time', [$startTime, $endTime])
              ->orWhereBetween('end_time', [$startTime, $endTime])
              ->orWhere(function ($contains) use ($startTime, $endTime) {
                  // Feedback that completely contains the range
                  $contains->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
              });
        });
    }

    public function scopeByDuration(Builder $query, float $minDuration, ?float $maxDuration = null): Builder
    {
        $query->whereRaw('(end_time - start_time) >= ?', [$minDuration]);
        
        if ($maxDuration !== null) {
            $query->whereRaw('(end_time - start_time) <= ?', [$maxDuration]);
        }

        return $query;
    }

    public function scopeShortClips(Builder $query, float $maxDuration = 5.0): Builder
    {
        return $query->whereRaw('(end_time - start_time) <= ?', [$maxDuration]);
    }

    public function scopeLongClips(Builder $query, float $minDuration = 30.0): Builder
    {
        return $query->whereRaw('(end_time - start_time) >= ?', [$minDuration]);
    }

    public function scopeWithHighAmplitude(Builder $query, float $minAmplitude = 0.7): Builder
    {
        return $query->where('peak_amplitude', '>=', $minAmplitude);
    }

    public function scopeOverlapping(Builder $query, float $startTime, float $endTime): Builder
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
        });
    }

    // Type-specific methods
    public function getDuration(): float
    {
        return $this->end_time - $this->start_time;
    }

    public function getTimeDisplay(): string
    {
        return $this->formatTime($this->start_time) . ' - ' . $this->formatTime($this->end_time);
    }

    public function getDurationDisplay(): string
    {
        $duration = $this->getDuration();
        
        if ($duration < 60) {
            return number_format($duration, 1) . 's';
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        
        return sprintf('%dm %ds', $minutes, $seconds);
    }

    public function containsTimestamp(float $timestamp): bool
    {
        return $timestamp >= $this->start_time && $timestamp <= $this->end_time;
    }

    public function overlaps(float $startTime, float $endTime): bool
    {
        return $this->start_time < $endTime && $this->end_time > $startTime;
    }

    public function isWithinRange(float $startTime, float $endTime): bool
    {
        return $this->start_time >= $startTime && $this->end_time <= $endTime;
    }

    public function isShortClip(float $threshold = 5.0): bool
    {
        return $this->getDuration() <= $threshold;
    }

    public function isLongClip(float $threshold = 30.0): bool
    {
        return $this->getDuration() >= $threshold;
    }

    public function hasWaveformData(): bool
    {
        return !empty($this->waveform_data);
    }

    public function hasFrequencyData(): bool
    {
        return !empty($this->frequency_data);
    }

    public function hasAmplitudeData(): bool
    {
        return $this->peak_amplitude !== null;
    }

    public function isHighAmplitude(float $threshold = 0.7): bool
    {
        return $this->peak_amplitude !== null && $this->peak_amplitude >= $threshold;
    }

    public function isLowAmplitude(float $threshold = 0.3): bool
    {
        return $this->peak_amplitude !== null && $this->peak_amplitude <= $threshold;
    }

    public function getAmplitudeLevel(): string
    {
        if ($this->peak_amplitude === null) {
            return 'unknown';
        }

        return match (true) {
            $this->peak_amplitude >= 0.8 => 'very high',
            $this->peak_amplitude >= 0.6 => 'high',
            $this->peak_amplitude >= 0.4 => 'medium',
            $this->peak_amplitude >= 0.2 => 'low',
            default => 'very low'
        };
    }

    public function getFeedbackType(): string
    {
        return 'audio';
    }

    // Analysis methods
    public function analyzeOverlaps(): array
    {
        $overlapping = static::where('id', '!=', $this->id)
            ->overlapping($this->start_time, $this->end_time)
            ->get();

        return [
            'count' => $overlapping->count(),
            'feedback' => $overlapping,
            'total_overlap_duration' => $overlapping->sum(function ($feedback) {
                $overlapStart = max($this->start_time, $feedback->start_time);
                $overlapEnd = min($this->end_time, $feedback->end_time);
                return max(0, $overlapEnd - $overlapStart);
            }),
        ];
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'start_time' => 'float',
            'end_time' => 'float',
            'peak_amplitude' => 'float',
            'waveform_data' => 'array',
            'frequency_data' => 'array',
        ];
    }

    private function formatTime(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%05.2f', $minutes, $seconds);
    }
}