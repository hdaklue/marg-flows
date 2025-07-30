<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use App\Concerns\Model\IsBaseModel;
use App\Contracts\Model\BaseModelContract;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Base abstract class for all feedback types
 * 
 * @property string $id
 * @property string $creator_id
 * @property string $content
 * @property string $feedbackable_type
 * @property string $feedbackable_id
 * @property FeedbackStatus $status
 * @property FeedbackUrgency $urgency
 * @property string|null $resolution
 * @property string|null $resolved_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
abstract class BaseFeedback extends Model implements BaseModelContract
{
    use HasFactory, HasUlids, LivesInBusinessDB, IsBaseModel;

    protected $fillable = [
        'creator_id',
        'content',
        'feedbackable_type',
        'feedbackable_id',
        'status',
        'urgency',
        'resolution',
        'resolved_by',
        'resolved_at',
    ];

    protected $with = ['creator'];

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

    // Common Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', FeedbackStatus::openStatuses());
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', FeedbackStatus::closedStatuses());
    }

    public function scopeByCreator($query, User $creator)
    {
        return $query->where('creator_id', $creator->id);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    // Common Accessors
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

    public function getUrgencyColorAttribute(): string
    {
        return $this->urgency->color();
    }

    public function getUrgencyLabelAttribute(): string
    {
        return $this->urgency->label();
    }

    // Common Methods
    public function resolve(User $resolver, ?string $resolution = null): static
    {
        $this->update([
            'status' => FeedbackStatus::RESOLVED,
            'resolution' => $resolution,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function reject(User $resolver, ?string $reason = null): static
    {
        $this->update([
            'status' => FeedbackStatus::REJECTED,
            'resolution' => $reason,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function reopen(): static
    {
        $this->update([
            'status' => FeedbackStatus::OPEN,
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        return $this;
    }

    public function markAsUrgent(): static
    {
        $this->update(['urgency' => FeedbackUrgency::URGENT]);

        return $this;
    }

    public function markAsNormal(): static
    {
        $this->update(['urgency' => FeedbackUrgency::NORMAL]);

        return $this;
    }

    public function markInProgress(): static
    {
        $this->update(['status' => FeedbackStatus::IN_PROGRESS]);

        return $this;
    }

    public function isUrgent(): bool
    {
        return $this->urgency === FeedbackUrgency::URGENT;
    }

    public function canBeResolved(): bool
    {
        return in_array($this->status, [
            FeedbackStatus::OPEN,
            FeedbackStatus::IN_PROGRESS,
            FeedbackStatus::URGENT,
        ]);
    }

    public function canBeReopened(): bool
    {
        return in_array($this->status, [
            FeedbackStatus::RESOLVED,
            FeedbackStatus::REJECTED,
        ]);
    }

    /**
     * Get the feedback type identifier
     * Override in child classes to provide specific type
     */
    abstract public function getFeedbackType(): string;

    /**
     * Get the model type identifier (required by BaseModelContract)
     */
    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    /**
     * Get concrete model classes that extend this base model
     */
    public static function getConcreteModels(): array
    {
        return array_values(config('feedback.concrete_models', []));
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
            'urgency' => FeedbackUrgency::class,
            'resolved_at' => 'datetime',
        ];
    }
}