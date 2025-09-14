<?php

declare(strict_types=1);

namespace App\Models\Feedbacks;

use App\Concerns\Database\LivesInBusinessDB;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\Acknowledgement;
use App\Models\User;
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
 * Video-specific feedback model
 * Handles both frame comments (specific timestamp + coordinates)
 * and region comments (time range + optional area).
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
 * @property string $feedback_type
 * @property float|null $timestamp Frame timestamp in seconds (for frame feedback)
 * @property float|null $start_time Start time in seconds (for region feedback)
 * @property float|null $end_time End time in seconds (for region feedback)
 * @property int|null $x_coordinate X coordinate on video frame
 * @property int|null $y_coordinate Y coordinate on video frame
 * @property array<array-key, mixed>|null $region_data Additional region metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 * @property-read Model|Eloquent $feedbackable
 * @property-read User|null $resolver
 *
 * @method static Builder<static>|VideoFeedback atCoordinates(int $x, int $y, int $tolerance = 20)
 * @method static Builder<static>|VideoFeedback atTimestamp(float $timestamp, float $tolerance = 0.1)
 * @method static Builder<static>|VideoFeedback frameComments()
 * @method static Builder<static>|VideoFeedback inTimeRange(float $startTime, float $endTime)
 * @method static Builder<static>|VideoFeedback nearCoordinates(int $x, int $y, int $radius = 50)
 * @method static Builder<static>|VideoFeedback newModelQuery()
 * @method static Builder<static>|VideoFeedback newQuery()
 * @method static Builder<static>|VideoFeedback query()
 * @method static Builder<static>|VideoFeedback regionComments()
 * @method static Builder<static>|VideoFeedback whereContent($value)
 * @method static Builder<static>|VideoFeedback whereCreatedAt($value)
 * @method static Builder<static>|VideoFeedback whereCreatorId($value)
 * @method static Builder<static>|VideoFeedback whereEndTime($value)
 * @method static Builder<static>|VideoFeedback whereFeedbackType($value)
 * @method static Builder<static>|VideoFeedback whereFeedbackableId($value)
 * @method static Builder<static>|VideoFeedback whereFeedbackableType($value)
 * @method static Builder<static>|VideoFeedback whereId($value)
 * @method static Builder<static>|VideoFeedback whereRegionData($value)
 * @method static Builder<static>|VideoFeedback whereResolution($value)
 * @method static Builder<static>|VideoFeedback whereResolvedAt($value)
 * @method static Builder<static>|VideoFeedback whereResolvedBy($value)
 * @method static Builder<static>|VideoFeedback whereStartTime($value)
 * @method static Builder<static>|VideoFeedback whereStatus($value)
 * @method static Builder<static>|VideoFeedback whereTimestamp($value)
 * @method static Builder<static>|VideoFeedback whereUpdatedAt($value)
 * @method static Builder<static>|VideoFeedback whereUrgency($value)
 * @method static Builder<static>|VideoFeedback whereXCoordinate($value)
 * @method static Builder<static>|VideoFeedback whereYCoordinate($value)
 *
 * @mixin \Eloquent
 */
final class VideoFeedback extends Model
{
    use HasFactory, HasUlids, LivesInBusinessDB;

    protected $table = 'video_feedbacks';

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
        'feedback_type',
        'timestamp',
        'start_time',
        'end_time',
        'x_coordinate',
        'y_coordinate',
        'region_data',
    ];

    protected $with = ['creator'];

    // Factory methods for creating specific types
    public static function createFrameComment(array $attributes): static
    {
        return self::create([
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
        return
            $this->isRegionComment()
            && $this->start_time !== null
            && $this->end_time !== null;
    }

    public function getDuration(): ?float
    {
        if (! $this->hasTimeRange()) {
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
            return
                $this->formatTime($this->start_time)
                . ' - '
                . $this->formatTime($this->end_time);
        }

        return 'No time specified';
    }

    public function getCoordinatesDisplay(): string
    {
        if (! $this->hasCoordinates()) {
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
            return
                $timestamp >= $this->start_time
                && $timestamp <= $this->end_time;
        }

        return false;
    }

    public function getFeedbackType(): string
    {
        return 'video';
    }

    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    // Type-specific scopes
    protected function scopeFrameComments(Builder $query): Builder
    {
        return $query->where('feedback_type', 'frame');
    }

    protected function scopeRegionComments(Builder $query): Builder
    {
        return $query->where('feedback_type', 'region');
    }

    protected function scopeAtTimestamp(
        Builder $query,
        float $timestamp,
        float $tolerance = 0.1,
    ): Builder {
        return $query->where(function ($q) use ($timestamp, $tolerance) {
            // Frame comments: exact timestamp match (with tolerance)
            $q
                ->where(function ($frameQuery) use ($timestamp, $tolerance) {
                    $frameQuery->where(
                        'feedback_type',
                        'frame',
                    )->whereBetween('timestamp', [
                        $timestamp - $tolerance,
                        $timestamp + $tolerance,
                    ]);
                })
                // Region comments: timestamp falls within range
                ->orWhere(function ($regionQuery) use ($timestamp) {
                    $regionQuery->where('feedback_type', 'region')->where(
                        'start_time',
                        '<=',
                        $timestamp,
                    )->where('end_time', '>=', $timestamp);
                });
        });
    }

    protected function scopeInTimeRange(
        Builder $query,
        float $startTime,
        float $endTime,
    ): Builder {
        return $query->where(function ($q) use ($startTime, $endTime) {
            // Frame comments within the range
            $q
                ->where(function ($frameQuery) use ($startTime, $endTime) {
                    $frameQuery->where(
                        'feedback_type',
                        'frame',
                    )->whereBetween('timestamp', [$startTime, $endTime]);
                })
                // Region comments that overlap with the range
                ->orWhere(function ($regionQuery) use ($startTime, $endTime) {
                    $regionQuery
                        ->where('feedback_type', 'region')
                        ->where(function ($overlap) use ($startTime, $endTime) {
                            $overlap
                                ->whereBetween('start_time', [
                                    $startTime,
                                    $endTime,
                                ])
                                ->orWhereBetween('end_time', [
                                    $startTime,
                                    $endTime,
                                ])
                                ->orWhere(function ($contains) use (
                                    $startTime,
                                    $endTime,
                                ) {
                                    $contains->where(
                                        'start_time',
                                        '<=',
                                        $startTime,
                                    )->where('end_time', '>=', $endTime);
                                });
                        });
                });
        });
    }

    protected function scopeAtCoordinates(
        Builder $query,
        int $x,
        int $y,
        int $tolerance = 20,
    ): Builder {
        return $query->whereBetween('x_coordinate', [
            $x - $tolerance,
            $x + $tolerance,
        ])->whereBetween('y_coordinate', [
            $y - $tolerance,
            $y + $tolerance,
        ]);
    }

    protected function scopeNearCoordinates(
        Builder $query,
        int $x,
        int $y,
        int $radius = 50,
    ): Builder {
        return $query->whereRaw('SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?', [
            $x,
            $y,
            $radius,
        ]);
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
            'urgency' => FeedbackUrgency::class,
            'resolved_at' => 'datetime',
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
