<?php

declare(strict_types=1);

namespace App\Models\Feedbacks;

use App\Concerns\Database\LivesInBusinessDB;
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

/**
 * Document-specific feedback model
 * Handles Editor.js block-level feedback with positioning data.
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
 * @property string $block_id Editor.js block identifier
 * @property string|null $element_type Type of block element (paragraph, header, list, etc.)
 * @property array<array-key, mixed>|null $position_data Position metadata (selection, offset, etc.)
 * @property string|null $block_version Version/hash of the block content when feedback was created
 * @property array<array-key, mixed>|null $selection_data Text selection data (start, end, selected text)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|Eloquent $feedbackable
 *
 * @method static Builder<static>|DocumentFeedback byElementTypes(array $elementTypes)
 * @method static Builder<static>|DocumentFeedback forBlock(string $blockId)
 * @method static Builder<static>|DocumentFeedback forBlockType(string $elementType)
 * @method static Builder<static>|DocumentFeedback forBlocks(array $blockIds)
 * @method static Builder<static>|DocumentFeedback newModelQuery()
 * @method static Builder<static>|DocumentFeedback newQuery()
 * @method static Builder<static>|DocumentFeedback orderByBlockPosition()
 * @method static Builder<static>|DocumentFeedback query()
 * @method static Builder<static>|DocumentFeedback whereBlockId($value)
 * @method static Builder<static>|DocumentFeedback whereBlockVersion($value)
 * @method static Builder<static>|DocumentFeedback whereContent($value)
 * @method static Builder<static>|DocumentFeedback whereCreatedAt($value)
 * @method static Builder<static>|DocumentFeedback whereCreatorId($value)
 * @method static Builder<static>|DocumentFeedback whereElementType($value)
 * @method static Builder<static>|DocumentFeedback whereFeedbackableId($value)
 * @method static Builder<static>|DocumentFeedback whereFeedbackableType($value)
 * @method static Builder<static>|DocumentFeedback whereId($value)
 * @method static Builder<static>|DocumentFeedback wherePositionData($value)
 * @method static Builder<static>|DocumentFeedback whereResolution($value)
 * @method static Builder<static>|DocumentFeedback whereResolvedAt($value)
 * @method static Builder<static>|DocumentFeedback whereResolvedBy($value)
 * @method static Builder<static>|DocumentFeedback whereSelectionData($value)
 * @method static Builder<static>|DocumentFeedback whereStatus($value)
 * @method static Builder<static>|DocumentFeedback whereUpdatedAt($value)
 * @method static Builder<static>|DocumentFeedback whereUrgency($value)
 * @method static Builder<static>|DocumentFeedback withTextSelection()
 * @method static Builder<static>|DocumentFeedback withoutTextSelection()
 *
 * @mixin \Eloquent
 */
final class DocumentFeedback extends Model
{
    use HasFactory, HasUlids, LivesInBusinessDB;

    protected $table = 'document_feedbacks';

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
        'block_id',
        'element_type',
        'position_data',
        'block_version',
        'selection_data',
    ];

    protected $with = ['creator'];

    public static function createForBlock(string $blockId, array $attributes): static
    {
        return self::create([
            ...$attributes,
            'block_id' => $blockId,
            'element_type' => $attributes['element_type'] ?? null,
            'position_data' => $attributes['position_data'] ?? null,
        ]);
    }

    public static function createForTextSelection(
        string $blockId,
        string $selectedText,
        int $start,
        int $end,
        array $attributes,
    ): static {
        return static::create([
            ...$attributes,
            'block_id' => $blockId,
            'selection_data' => [
                'selectedText' => $selectedText,
                'start' => $start,
                'end' => $end,
                'length' => $end - $start,
            ],
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
    public function hasTextSelection(): bool
    {
        return ! empty($this->selection_data) &&
               isset($this->selection_data['selectedText']) &&
               ! empty($this->selection_data['selectedText']);
    }

    public function getSelectedText(): ?string
    {
        return $this->selection_data['selectedText'] ?? null;
    }

    public function getSelectionStart(): ?int
    {
        return $this->selection_data['start'] ?? null;
    }

    public function getSelectionEnd(): ?int
    {
        return $this->selection_data['end'] ?? null;
    }

    public function getSelectionLength(): int
    {
        if (! $this->hasTextSelection()) {
            return 0;
        }

        $start = $this->getSelectionStart();
        $end = $this->getSelectionEnd();

        if ($start === null || $end === null) {
            return mb_strlen($this->getSelectedText() ?? '');
        }

        return $end - $start;
    }

    public function getBlockIndex(): ?int
    {
        return $this->position_data['blockIndex'] ?? null;
    }

    public function getBlockPosition(): ?array
    {
        return $this->position_data['position'] ?? null;
    }

    public function isBlockLevelFeedback(): bool
    {
        return ! $this->hasTextSelection();
    }

    public function isTextLevelFeedback(): bool
    {
        return $this->hasTextSelection();
    }

    public function getElementTypeDisplay(): string
    {
        return match ($this->element_type) {
            'paragraph' => 'Paragraph',
            'header' => 'Header',
            'list' => 'List',
            'quote' => 'Quote',
            'code' => 'Code Block',
            'table' => 'Table',
            'image' => 'Image',
            'embed' => 'Embed',
            'delimiter' => 'Delimiter',
            'warning' => 'Warning Box',
            'checklist' => 'Checklist',
            default => ucfirst($this->element_type ?? 'Unknown'),
        };
    }

    public function getFeedbackScope(): string
    {
        if ($this->hasTextSelection()) {
            return 'Text Selection';
        }

        return 'Block Level';
    }

    public function getFeedbackTypeIcon(): string
    {
        return match ($this->element_type) {
            'paragraph' => '📝',
            'header' => '📋',
            'list' => '📋',
            'quote' => '💬',
            'code' => '💻',
            'table' => '📊',
            'image' => '🖼️',
            'embed' => '🔗',
            'delimiter' => '➖',
            'warning' => '⚠️',
            'checklist' => '✅',
            default => '📄',
        };
    }

    public function getFeedbackDescription(): string
    {
        $scope = $this->getFeedbackScope();
        $element = $this->getElementTypeDisplay();

        if ($this->hasTextSelection()) {
            $selectedText = $this->getSelectedText();
            $preview = mb_strlen($selectedText) > 50
                ? mb_substr($selectedText, 0, 47) . '...'
                : $selectedText;

            return "Text selection in {$element}: \"{$preview}\"";
        }

        return "{$scope} feedback on {$element}";
    }

    public function hasBlockVersion(): bool
    {
        return ! empty($this->block_version);
    }

    public function isBlockVersionCurrent(string $currentVersion): bool
    {
        return $this->block_version === $currentVersion;
    }

    public function markAsOutdated(): static
    {
        $this->update([
            'urgency' => FeedbackUrgency::LOW,
        ]);

        return $this;
    }

    public function getFeedbackType(): string
    {
        return 'document';
    }

    // Editor.js specific helper methods
    public function toEditorJsAnnotation(): array
    {
        return [
            'id' => $this->id,
            'blockId' => $this->block_id,
            'type' => $this->isTextLevelFeedback() ? 'text' : 'block',
            'content' => $this->content,
            'selection' => $this->selection_data,
            'position' => $this->position_data,
            'status' => $this->status->value,
            'urgency' => $this->urgency->value,
            'creator' => [
                'id' => $this->creator_id,
                'name' => $this->creator->name ?? 'Unknown',
            ],
            'createdAt' => $this->created_at->toISOString(),
            'isResolved' => $this->is_resolved,
        ];
    }

    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    // Type-specific scopes
    protected function scopeForBlock(Builder $query, string $blockId): Builder
    {
        return $query->where('block_id', $blockId);
    }

    protected function scopeForBlockType(Builder $query, string $elementType): Builder
    {
        return $query->where('element_type', $elementType);
    }

    protected function scopeWithTextSelection(Builder $query): Builder
    {
        return $query->whereNotNull('selection_data')
            ->whereJsonLength('selection_data', '>', 0);
    }

    protected function scopeWithoutTextSelection(Builder $query): Builder
    {
        return $query->whereNull('selection_data')
            ->orWhereJsonLength('selection_data', '=', 0);
    }

    protected function scopeForBlocks(Builder $query, array $blockIds): Builder
    {
        return $query->whereIn('block_id', $blockIds);
    }

    protected function scopeByElementTypes(Builder $query, array $elementTypes): Builder
    {
        return $query->whereIn('element_type', $elementTypes);
    }

    protected function scopeOrderByBlockPosition(Builder $query): Builder
    {
        // Assuming position_data contains block index or order
        return $query->orderByRaw('JSON_EXTRACT(position_data, "$.blockIndex") ASC');
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
            'urgency' => FeedbackUrgency::class,
            'resolved_at' => 'datetime',
            'position_data' => 'array',
            'selection_data' => 'array',
        ];
    }
}
