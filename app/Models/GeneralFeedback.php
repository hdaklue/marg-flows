<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use App\Concerns\Model\IsBaseModel;
use App\Contracts\Model\BaseModelContract;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
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
 * @property string $content
 * @property string $feedbackable_type
 * @property string $feedbackable_id
 * @property FeedbackStatus $status
 * @property FeedbackUrgency $urgency
 * @property string|null $resolution
 * @property string|null $resolved_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property array|null $metadata Flexible metadata storage for various feedback types
 * @property string|null $feedback_category Optional category for organization
 * @property array|null $custom_data Additional custom data as needed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class GeneralFeedback extends Model implements BaseModelContract
{
    use HasFactory, HasUlids, LivesInBusinessDB, IsBaseModel;

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

    // Migration helpers for converting from old feedback system
    public static function createFromLegacyFeedback(array $attributes): static
    {
        // Extract category from metadata if available
        $category = null;
        $metadata = $attributes['metadata'] ?? null;

        if (is_array($metadata)) {
            $category = $metadata['category'] ?? $metadata['type'] ?? null;
        }

        return self::create([
            ...$attributes,
            'feedback_category' => $category,
            'metadata' => $metadata,
        ]);
    }

    // Type-specific scopes
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('feedback_category', $category);
    }

    public function scopeByCategories(Builder $query, array $categories): Builder
    {
        return $query->whereIn('feedback_category', $categories);
    }

    public function scopeWithMetadata(Builder $query): Builder
    {
        return $query->whereNotNull('metadata')
            ->whereJsonLength('metadata', '>', 0);
    }

    public function scopeWithoutMetadata(Builder $query): Builder
    {
        return $query->whereNull('metadata')
            ->orWhereJsonLength('metadata', '=', 0);
    }

    public function scopeWithCustomData(Builder $query): Builder
    {
        return $query->whereNotNull('custom_data')
            ->whereJsonLength('custom_data', '>', 0);
    }

    public function scopeHasMetadataKey(Builder $query, string $key): Builder
    {
        return $query->whereJsonContains('metadata->' . $key, true)
            ->orWhereNotNull('metadata->' . $key);
    }

    public function scopeMetadataEquals(Builder $query, string $key, mixed $value): Builder
    {
        return $query->whereJson('metadata->' . $key, $value);
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
