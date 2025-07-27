<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use App\Enums\FeedbackStatus;
use App\ValueObjects\FeedbackMetadata;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $creator_id
 * @property FeedbackStatus $status
 * @property FeedbackMetadata $metadata
 */
final class Feedback extends Model
{
    use HasFactory, HasUlids, LivesInBusinessDB;

    protected $table = 'feedbacks';

    protected $fillable = [
        'creator_id',
        'content',
        'metadata',
        'feedbackable_type',
        'feedbackable_id',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => FeedbackMetadata::class,
        'status' => FeedbackStatus::class,
        'resolved_at' => 'datetime',
    ];

    protected $with = ['creator'];

    // Relationships
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

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', FeedbackStatus::openStatuses());
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', FeedbackStatus::closedStatuses());
    }

    public function scopeForType($query, string $type)
    {
        return $query->whereJsonContains('metadata->type', $type);
    }

    public function scopeForTimeRange($query, float $startTime, float $endTime)
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->whereJsonContains('metadata->searchable->start_time', '>=', $startTime)
                ->whereJsonContains('metadata->searchable->end_time', '<=', $endTime);
        });
    }

    public function scopeForDocumentBlock($query, string $blockId)
    {
        return $query->whereJsonContains('metadata->data->block_id', $blockId);
    }

    public function scopeByCreator($query, User $creator)
    {
        return $query->where('creator_id', $creator->id);
    }

    // Accessors & Mutators
    public function getIsOpenAttribute(): bool
    {
        return $this->status->isOpen();
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->status->isResolved();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getMetadataTypeAttribute(): string
    {
        return $this->metadata->getType();
    }

    public function getTimeRangeAttribute(): ?array
    {
        return $this->metadata->getTimeRange();
    }

    // Methods
    public function resolve(User $resolver, ?string $resolution = null): self
    {
        $this->update([
            'status' => FeedbackStatus::RESOLVED,
            'resolution' => $resolution,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function reject(User $resolver, ?string $reason = null): self
    {
        $this->update([
            'status' => FeedbackStatus::REJECTED,
            'resolution' => $reason,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function reopen(): self
    {
        $this->update([
            'status' => FeedbackStatus::OPEN,
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        return $this;
    }

    public function markAsUrgent(): self
    {
        $this->update(['status' => FeedbackStatus::URGENT]);

        return $this;
    }

    public function markInProgress(): self
    {
        $this->update(['status' => FeedbackStatus::IN_PROGRESS]);

        return $this;
    }

    public function hasTimeRange(): bool
    {
        return $this->metadata->isMediaTimestamp();
    }

    public function isForDocumentBlock(): bool
    {
        return $this->metadata->getType() === 'document_block';
    }

    public function isForMedia(): bool
    {
        return in_array($this->metadata->getType(), ['audio_region', 'video_region', 'video_frame']);
    }
}
