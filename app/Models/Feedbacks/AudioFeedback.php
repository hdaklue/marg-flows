<?php

declare(strict_types=1);

namespace App\Models\Feedbacks;

use App\Concerns\Database\LivesInBusinessDB;
use App\Concerns\Mentions\HasMentionsContract;
use App\Contracts\Mentions\HasMentions;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Audio-specific feedback model
 * Handles time-based audio feedback with waveform data.
 *
 * @property string $id
 * @property string $creator_id
 * @property FeedbackStatus $status
 * @property FeedbackUrgency $urgency
 * @property string $content
 * @property string $feedbackable_type
 * @property string $feedbackable_id
 * @property string|null $resolution
 * @property string|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property float $start_time Start time in seconds
 * @property float $end_time End time in seconds
 * @property array<array-key, mixed>|null $waveform_data Waveform visualization data
 * @property float|null $peak_amplitude Peak amplitude in the selection (0.0-1.0)
 * @property array<array-key, mixed>|null $frequency_data Frequency analysis data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent $feedbackable
 *
 * @method static Builder<static>|AudioFeedback atTimestamp(float $timestamp)
 * @method static Builder<static>|AudioFeedback byDuration(float $minDuration, ?float $maxDuration = null)
 * @method static Builder<static>|AudioFeedback inTimeRange(float $startTime, float $endTime)
 * @method static Builder<static>|AudioFeedback longClips(float $minDuration = 30)
 * @method static Builder<static>|AudioFeedback newModelQuery()
 * @method static Builder<static>|AudioFeedback newQuery()
 * @method static Builder<static>|AudioFeedback overlapping(float $startTime, float $endTime)
 * @method static Builder<static>|AudioFeedback query()
 * @method static Builder<static>|AudioFeedback shortClips(float $maxDuration = 5)
 * @method static Builder<static>|AudioFeedback whereContent($value)
 * @method static Builder<static>|AudioFeedback whereCreatedAt($value)
 * @method static Builder<static>|AudioFeedback whereCreatorId($value)
 * @method static Builder<static>|AudioFeedback whereEndTime($value)
 * @method static Builder<static>|AudioFeedback whereFeedbackableId($value)
 * @method static Builder<static>|AudioFeedback whereFeedbackableType($value)
 * @method static Builder<static>|AudioFeedback whereFrequencyData($value)
 * @method static Builder<static>|AudioFeedback whereId($value)
 * @method static Builder<static>|AudioFeedback wherePeakAmplitude($value)
 * @method static Builder<static>|AudioFeedback whereResolution($value)
 * @method static Builder<static>|AudioFeedback whereResolvedAt($value)
 * @method static Builder<static>|AudioFeedback whereResolvedBy($value)
 * @method static Builder<static>|AudioFeedback whereStartTime($value)
 * @method static Builder<static>|AudioFeedback whereStatus($value)
 * @method static Builder<static>|AudioFeedback whereUpdatedAt($value)
 * @method static Builder<static>|AudioFeedback whereUrgency($value)
 * @method static Builder<static>|AudioFeedback whereWaveformData($value)
 * @method static Builder<static>|AudioFeedback withHighAmplitude(float $minAmplitude = 0.7)
 *
 * @mixin \Eloquent
 */
final class AudioFeedback extends Model implements HasMentionsContract
{
    use HasFactory, HasMentions, HasUlids, LivesInBusinessDB;

    protected $table = 'audio_feedbacks';

    protected $fillable = [
        'id',
        'creator_id',
        'content',
        'feedbackable_type',
        'feedbackable_id',
        'status',
        'urgency',
        'resolution',
        'resolved_by',
        'resolved_at',
        'start_time',
        'end_time',
        'waveform_data',
        'peak_amplitude',
        'frequency_data',
    ];

    protected $with = ['creator'];

    public static function getConcreteModels(): array
    {
        return array_values(config('feedback.concrete_models', []));
    }

    // Common Relationships
    public function feedbackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function acknowledgments(): MorphMany
    {
        return $this->morphMany(Acknowledgement::class, 'acknowlegeable');
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
        return ! empty($this->waveform_data);
    }

    public function hasFrequencyData(): bool
    {
        return ! empty($this->frequency_data);
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
            default => 'very low',
        };
    }

    public function getFeedbackType(): string
    {
        return 'audio';
    }

    // Analysis methods
    public function analyzeOverlaps(): array
    {
        $overlapping = self::where('id', '!=', $this->id)
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

    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    // Type-specific scopes
    protected function scopeAtTimestamp(Builder $query, float $timestamp): Builder
    {
        return $query->where('start_time', '<=', $timestamp)->where('end_time', '>=', $timestamp);
    }

    protected function scopeInTimeRange(Builder $query, float $startTime, float $endTime): Builder
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            // Feedback that overlaps with the given range
            $q
                ->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($contains) use ($startTime, $endTime) {
                    // Feedback that completely contains the range
                    $contains->where('start_time', '<=', $startTime)->where(
                        'end_time',
                        '>=',
                        $endTime,
                    );
                });
        });
    }

    protected function scopeByDuration(
        Builder $query,
        float $minDuration,
        ?float $maxDuration = null,
    ): Builder {
        $query->whereRaw('(end_time - start_time) >= ?', [$minDuration]);

        if ($maxDuration !== null) {
            $query->whereRaw('(end_time - start_time) <= ?', [$maxDuration]);
        }

        return $query;
    }

    protected function scopeShortClips(Builder $query, float $maxDuration = 5.0): Builder
    {
        return $query->whereRaw('(end_time - start_time) <= ?', [$maxDuration]);
    }

    protected function scopeLongClips(Builder $query, float $minDuration = 30.0): Builder
    {
        return $query->whereRaw('(end_time - start_time) >= ?', [$minDuration]);
    }

    protected function scopeWithHighAmplitude(Builder $query, float $minAmplitude = 0.7): Builder
    {
        return $query->where('peak_amplitude', '>=', $minAmplitude);
    }

    protected function scopeOverlapping(Builder $query, float $startTime, float $endTime): Builder
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)->where('end_time', '>', $startTime);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
            'urgency' => FeedbackUrgency::class,
            'resolved_at' => 'datetime',
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
