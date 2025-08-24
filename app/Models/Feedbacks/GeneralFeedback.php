<?php

declare(strict_types=1);

namespace App\Models\Feedbacks;

use App\Concerns\Database\LivesInBusinessDB;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\Acknowledgement;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * General feedback model for simple cases that don't fit specialized models
 * Maintains backwards compatibility and handles edge cases.
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
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property array<array-key, mixed>|null $metadata Flexible metadata storage for various feedback types
 * @property string|null $feedback_category Optional category for organization
 * @property array<array-key, mixed>|null $custom_data Additional custom data as needed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $creator
 * @property-read Model|Eloquent $feedbackable
 * @property-read User|null $resolver
 *
 * @method static Builder<static>|GeneralFeedback byCategories(array $categories)
 * @method static Builder<static>|GeneralFeedback byCategory(string $category)
 * @method static Builder<static>|GeneralFeedback hasMetadataKey(string $key)
 * @method static Builder<static>|GeneralFeedback metadataEquals(string $key, ?mixed $value)
 * @method static Builder<static>|GeneralFeedback newModelQuery()
 * @method static Builder<static>|GeneralFeedback newQuery()
 * @method static Builder<static>|GeneralFeedback query()
 * @method static Builder<static>|GeneralFeedback whereContent($value)
 * @method static Builder<static>|GeneralFeedback whereCreatedAt($value)
 * @method static Builder<static>|GeneralFeedback whereCreatorId($value)
 * @method static Builder<static>|GeneralFeedback whereCustomData($value)
 * @method static Builder<static>|GeneralFeedback whereFeedbackCategory($value)
 * @method static Builder<static>|GeneralFeedback whereFeedbackableId($value)
 * @method static Builder<static>|GeneralFeedback whereFeedbackableType($value)
 * @method static Builder<static>|GeneralFeedback whereId($value)
 * @method static Builder<static>|GeneralFeedback whereMetadata($value)
 * @method static Builder<static>|GeneralFeedback whereResolution($value)
 * @method static Builder<static>|GeneralFeedback whereResolvedAt($value)
 * @method static Builder<static>|GeneralFeedback whereResolvedBy($value)
 * @method static Builder<static>|GeneralFeedback whereStatus($value)
 * @method static Builder<static>|GeneralFeedback whereUpdatedAt($value)
 * @method static Builder<static>|GeneralFeedback whereUrgency($value)
 * @method static Builder<static>|GeneralFeedback withCustomData()
 * @method static Builder<static>|GeneralFeedback withMetadata()
 * @method static Builder<static>|GeneralFeedback withoutMetadata()
 *
 * @mixin \Eloquent
 */
final class GeneralFeedback extends Model
{
    use HasFactory, HasUlids, LivesInBusinessDB;

    protected $table = 'general_feedbacks';

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
        'metadata',
        'feedback_category',
        'custom_data',
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

    // Type-specific methods
    public function hasCategory(): bool
    {
        return ! empty($this->feedback_category);
    }

    public function hasMetadata(): bool
    {
        return ! empty($this->metadata);
    }

    public function hasCustomData(): bool
    {
        return ! empty($this->custom_data);
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMetadataValue(string $key, mixed $value): static
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);

        $this->update(['metadata' => $metadata]);

        return $this;
    }

    public function removeMetadataKey(string $key): static
    {
        $metadata = $this->metadata ?? [];
        data_forget($metadata, $key);

        $this->update(['metadata' => $metadata]);

        return $this;
    }

    public function getCustomDataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_data, $key, $default);
    }

    public function setCustomDataValue(string $key, mixed $value): static
    {
        $customData = $this->custom_data ?? [];
        data_set($customData, $key, $value);

        $this->update(['custom_data' => $customData]);

        return $this;
    }

    public function getCategoryDisplay(): string
    {
        if (! $this->hasCategory()) {
            return 'Uncategorized';
        }

        return match ($this->feedback_category) {
            'ui' => 'User Interface',
            'ux' => 'User Experience',
            'content' => 'Content',
            'functionality' => 'Functionality',
            'performance' => 'Performance',
            'accessibility' => 'Accessibility',
            'security' => 'Security',
            'bug' => 'Bug Report',
            'feature' => 'Feature Request',
            'improvement' => 'Improvement',
            'question' => 'Question',
            'other' => 'Other',
            default => ucfirst($this->feedback_category),
        };
    }

    public function getCategoryIcon(): string
    {
        return match ($this->feedback_category) {
            'ui' => '🎨',
            'ux' => '👤',
            'content' => '📝',
            'functionality' => '⚙️',
            'performance' => '⚡',
            'accessibility' => '♿',
            'security' => '🔒',
            'bug' => '🐛',
            'feature' => '💡',
            'improvement' => '📈',
            'question' => '❓',
            'other' => '📋',
            default => '💬',
        };
    }

    public function isBugReport(): bool
    {
        return $this->feedback_category === 'bug';
    }

    public function isFeatureRequest(): bool
    {
        return $this->feedback_category === 'feature';
    }

    public function isImprovement(): bool
    {
        return $this->feedback_category === 'improvement';
    }

    public function isQuestion(): bool
    {
        return $this->feedback_category === 'question';
    }

    public function getFeedbackDescription(): string
    {
        $category = $this->getCategoryDisplay();

        if ($this->hasMetadata()) {
            $type = $this->getMetadataValue('type');
            if ($type) {
                return "{$category} feedback ({$type})";
            }
        }

        return "{$category} feedback";
    }

    public function getFeedbackType(): string
    {
        return 'general';
    }

    public function convertToSpecializedModel(): ?Model
    {
        // Attempt to convert to specialized model based on metadata
        if (! $this->hasMetadata()) {
            return null;
        }

        $type = $this->getMetadataValue('type');

        return match ($type) {
            'video_frame', 'video_region' => $this->convertToVideoFeedback(),
            'audio_region' => $this->convertToAudioFeedback(),
            'document_block' => $this->convertToDocumentFeedback(),
            'image_annotation', 'design_annotation' => $this->convertToDesignFeedback(),
            default => null,
        };
    }

    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    #[Scope]
    protected function byCategory(Builder $query, string $category): Builder
    {
        return $query->where('feedback_category', $category);
    }

    #[Scope]
    protected function byCategories(Builder $query, array $categories): Builder
    {
        return $query->whereIn('feedback_category', $categories);
    }

    protected function scopeWithMetadata(Builder $query): Builder
    {
        return $query->whereNotNull('metadata')
            ->whereJsonLength('metadata', '>', 0);
    }

    protected function scopeWithoutMetadata(Builder $query): Builder
    {
        return $query->whereNull('metadata')
            ->orWhereJsonLength('metadata', '=', 0);
    }

    protected function scopeWithCustomData(Builder $query): Builder
    {
        return $query->whereNotNull('custom_data')
            ->whereJsonLength('custom_data', '>', 0);
    }

    protected function scopeHasMetadataKey(Builder $query, string $key): Builder
    {
        return $query->whereJsonContains('metadata->' . $key, true)
            ->orWhereNotNull('metadata->' . $key);
    }

    protected function scopeMetadataEquals(Builder $query, string $key, mixed $value): Builder
    {
        return $query->whereJson('metadata->' . $key, $value);
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
            'urgency' => FeedbackUrgency::class,
            'resolved_at' => 'datetime',
            'metadata' => 'array',
            'custom_data' => 'array',
        ];
    }

    private function convertToVideoFeedback(): ?VideoFeedback
    {
        $data = $this->getMetadataValue('data', []);

        if (empty($data)) {
            return null;
        }

        return VideoFeedback::create([
            'creator_id' => $this->creator_id,
            'content' => $this->content,
            'feedbackable_type' => $this->feedbackable_type,
            'feedbackable_id' => $this->feedbackable_id,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'feedback_type' => $this->getMetadataValue('type') === 'video_frame' ? 'frame' : 'region',
            'timestamp' => $data['timestamp'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'x_coordinate' => $data['x_coordinate'] ?? null,
            'y_coordinate' => $data['y_coordinate'] ?? null,
            'region_data' => $data['region_data'] ?? null,
        ]);
    }

    private function convertToAudioFeedback(): ?AudioFeedback
    {
        $data = $this->getMetadataValue('data', []);

        if (empty($data['start_time']) || empty($data['end_time'])) {
            return null;
        }

        return AudioFeedback::create([
            'creator_id' => $this->creator_id,
            'content' => $this->content,
            'feedbackable_type' => $this->feedbackable_type,
            'feedbackable_id' => $this->feedbackable_id,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'waveform_data' => $data['waveform_data'] ?? null,
            'peak_amplitude' => $data['peak_amplitude'] ?? null,
            'frequency_data' => $data['frequency_data'] ?? null,
        ]);
    }

    private function convertToDocumentFeedback(): ?DocumentFeedback
    {
        $data = $this->getMetadataValue('data', []);

        if (empty($data['block_id'])) {
            return null;
        }

        return DocumentFeedback::create([
            'creator_id' => $this->creator_id,
            'content' => $this->content,
            'feedbackable_type' => $this->feedbackable_type,
            'feedbackable_id' => $this->feedbackable_id,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'block_id' => $data['block_id'],
            'element_type' => $data['element_type'] ?? null,
            'position_data' => $data['position_data'] ?? null,
            'selection_data' => $data['selection_data'] ?? null,
        ]);
    }

    private function convertToDesignFeedback(): ?DesignFeedback
    {
        $data = $this->getMetadataValue('data', []);

        if (! isset($data['x_coordinate']) || ! isset($data['y_coordinate'])) {
            return null;
        }

        return DesignFeedback::create([
            'creator_id' => $this->creator_id,
            'content' => $this->content,
            'feedbackable_type' => $this->feedbackable_type,
            'feedbackable_id' => $this->feedbackable_id,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'x_coordinate' => $data['x_coordinate'],
            'y_coordinate' => $data['y_coordinate'],
            'annotation_type' => $data['annotation_type'] ?? 'point',
            'annotation_data' => $data['annotation_data'] ?? null,
            'area_bounds' => $data['area_bounds'] ?? null,
            'color' => $data['color'] ?? null,
            'zoom_level' => $data['zoom_level'] ?? null,
        ]);
    }
}
