<?php

declare(strict_types=1);

namespace App\Models\Feedbacks;

use App\Concerns\Database\LivesInBusinessDB;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Design-specific feedback model
 * Handles image annotations, design reviews, and visual feedback.
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
 * @property Carbon|null $resolved_at
 * @property int $x_coordinate X coordinate on the design/image
 * @property int $y_coordinate Y coordinate on the design/image
 * @property string|null $annotation_type Type of annotation (point, area, arrow, etc.)
 * @property array|null $annotation_data Additional annotation metadata (shape, color, size, etc.)
 * @property array|null $area_bounds Bounds for area-based annotations (x, y, width, height)
 * @property string|null $color Annotation color/theme
 * @property float|null $zoom_level Zoom level when annotation was created
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class DesignFeedback extends Model
{
    use HasFactory, HasUlids, LivesInBusinessDB;

    protected $table = 'design_feedbacks';

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
        'x_coordinate',
        'y_coordinate',
        'annotation_type',
        'annotation_data',
        'area_bounds',
        'color',
        'zoom_level',
    ];

    protected $with = ['creator'];

    // Factory methods for different annotation types
    public static function createPointAnnotation(int $x, int $y, array $attributes): static
    {
        return self::create([
            ...$attributes,
            'x_coordinate' => $x,
            'y_coordinate' => $y,
            'annotation_type' => 'point',
        ]);
    }

    public static function createAreaAnnotation(
        int $x,
        int $y,
        int $width,
        int $height,
        string $type,
        array $attributes,
    ): static {
        return static::create([
            ...$attributes,
            'x_coordinate' => $x,
            'y_coordinate' => $y,
            'annotation_type' => $type,
            'area_bounds' => [
                'width' => $width,
                'height' => $height,
            ],
        ]);
    }

    public static function createArrowAnnotation(
        int $startX,
        int $startY,
        int $endX,
        int $endY,
        array $attributes,
    ): static {
        return static::create([
            ...$attributes,
            'x_coordinate' => $startX,
            'y_coordinate' => $startY,
            'annotation_type' => 'arrow',
            'annotation_data' => [
                'endX' => $endX,
                'endY' => $endY,
                'length' => sqrt(pow($endX - $startX, 2) + pow($endY - $startY, 2)),
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

    // Type-specific scopes
    public function scopeAtCoordinates(Builder $query, int $x, int $y, int $tolerance = 20): Builder
    {
        return $query->whereBetween('x_coordinate', [$x - $tolerance, $x + $tolerance])
            ->whereBetween('y_coordinate', [$y - $tolerance, $y + $tolerance]);
    }

    public function scopeNearCoordinates(Builder $query, int $x, int $y, int $radius = 50): Builder
    {
        return $query->whereRaw(
            'SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?',
            [$x, $y, $radius],
        );
    }

    public function scopeInArea(Builder $query, int $x, int $y, int $width, int $height): Builder
    {
        return $query->whereBetween('x_coordinate', [$x, $x + $width])
            ->whereBetween('y_coordinate', [$y, $y + $height]);
    }

    public function scopeByAnnotationType(Builder $query, string $type): Builder
    {
        return $query->where('annotation_type', $type);
    }

    public function scopeByAnnotationTypes(Builder $query, array $types): Builder
    {
        return $query->whereIn('annotation_type', $types);
    }

    public function scopePointAnnotations(Builder $query): Builder
    {
        return $query->where('annotation_type', 'point');
    }

    public function scopeAreaAnnotations(Builder $query): Builder
    {
        return $query->whereIn('annotation_type', ['rectangle', 'circle', 'polygon', 'area']);
    }

    public function scopeArrowAnnotations(Builder $query): Builder
    {
        return $query->where('annotation_type', 'arrow');
    }

    public function scopeTextAnnotations(Builder $query): Builder
    {
        return $query->where('annotation_type', 'text');
    }

    public function scopeByColor(Builder $query, string $color): Builder
    {
        return $query->where('color', $color);
    }

    public function scopeAtZoomLevel(Builder $query, float $zoomLevel, float $tolerance = 0.1): Builder
    {
        return $query->whereBetween('zoom_level', [$zoomLevel - $tolerance, $zoomLevel + $tolerance]);
    }

    // Type-specific methods
    public function getCoordinatesDisplay(): string
    {
        return "({$this->x_coordinate}, {$this->y_coordinate})";
    }

    public function getAnnotationTypeDisplay(): string
    {
        return match ($this->annotation_type) {
            'point' => 'Point',
            'rectangle' => 'Rectangle',
            'circle' => 'Circle',
            'arrow' => 'Arrow',
            'text' => 'Text Note',
            'polygon' => 'Polygon',
            'area' => 'Area Selection',
            'line' => 'Line',
            'freehand' => 'Freehand Drawing',
            default => ucfirst($this->annotation_type ?? 'Unknown'),
        };
    }

    public function getAnnotationIcon(): string
    {
        return match ($this->annotation_type) {
            'point' => '📍',
            'rectangle' => '🔲',
            'circle' => '⭕',
            'arrow' => '➡️',
            'text' => '💬',
            'polygon' => '🔷',
            'area' => '🔲',
            'line' => '📏',
            'freehand' => '✏️',
            default => '📌',
        };
    }

    public function isPointAnnotation(): bool
    {
        return $this->annotation_type === 'point';
    }

    public function isAreaAnnotation(): bool
    {
        return in_array($this->annotation_type, ['rectangle', 'circle', 'polygon', 'area']);
    }

    public function isArrowAnnotation(): bool
    {
        return $this->annotation_type === 'arrow';
    }

    public function isTextAnnotation(): bool
    {
        return $this->annotation_type === 'text';
    }

    public function hasAreaBounds(): bool
    {
        return ! empty($this->area_bounds) &&
               isset($this->area_bounds['width'], $this->area_bounds['height']);
    }

    public function getAreaWidth(): ?int
    {
        return $this->area_bounds['width'] ?? null;
    }

    public function getAreaHeight(): ?int
    {
        return $this->area_bounds['height'] ?? null;
    }

    public function getAreaSize(): ?int
    {
        if (! $this->hasAreaBounds()) {
            return null;
        }

        return $this->getAreaWidth() * $this->getAreaHeight();
    }

    public function getDistanceFrom(int $x, int $y): float
    {
        return sqrt(
            pow($this->x_coordinate - $x, 2) +
            pow($this->y_coordinate - $y, 2),
        );
    }

    public function isWithinRadius(int $x, int $y, int $radius): bool
    {
        return $this->getDistanceFrom($x, $y) <= $radius;
    }

    public function containsPoint(int $x, int $y): bool
    {
        if (! $this->hasAreaBounds()) {
            return false;
        }

        $bounds = $this->area_bounds;

        return $x >= $this->x_coordinate &&
               $x <= $this->x_coordinate + $bounds['width'] &&
               $y >= $this->y_coordinate &&
               $y <= $this->y_coordinate + $bounds['height'];
    }

    public function overlapsWithArea(int $x, int $y, int $width, int $height): bool
    {
        if (! $this->hasAreaBounds()) {
            // For point annotations, check if the point is within the area
            return $this->x_coordinate >= $x &&
                   $this->x_coordinate <= $x + $width &&
                   $this->y_coordinate >= $y &&
                   $this->y_coordinate <= $y + $height;
        }

        $bounds = $this->area_bounds;

        // Check if rectangles overlap
        return ! ($this->x_coordinate > $x + $width ||
                $x > $this->x_coordinate + $bounds['width'] ||
                $this->y_coordinate > $y + $height ||
                $y > $this->y_coordinate + $bounds['height']);
    }

    public function getColorDisplay(): string
    {
        return match ($this->color) {
            'red' => '🔴 Red',
            'blue' => '🔵 Blue',
            'green' => '🟢 Green',
            'yellow' => '🟡 Yellow',
            'orange' => '🟠 Orange',
            'purple' => '🟣 Purple',
            'pink' => '🩷 Pink',
            'black' => '⚫ Black',
            'white' => '⚪ White',
            'gray' => '🩶 Gray',
            default => $this->color ? ucfirst($this->color) : 'Default',
        };
    }

    public function hasCustomColor(): bool
    {
        return $this->color !== null &&
               ! in_array($this->color, ['red', 'blue', 'green', 'yellow', 'orange', 'purple']);
    }

    public function getZoomLevelDisplay(): string
    {
        if ($this->zoom_level === null) {
            return 'Unknown zoom';
        }

        return number_format($this->zoom_level * 100, 0) . '%';
    }

    public function getFeedbackDescription(): string
    {
        $type = $this->getAnnotationTypeDisplay();
        $coords = $this->getCoordinatesDisplay();

        if ($this->hasAreaBounds()) {
            $size = $this->getAreaWidth() . 'x' . $this->getAreaHeight();

            return "{$type} annotation at {$coords} (size: {$size})";
        }

        return "{$type} annotation at {$coords}";
    }

    public function getFeedbackType(): string
    {
        return 'design';
    }

    // Analysis methods
    public function findNearbyAnnotations(int $radius = 100): Collection
    {
        return static::where('id', '!=', $this->id)
            ->nearCoordinates($this->x_coordinate, $this->y_coordinate, $radius)
            ->get();
    }

    public function getDensityScore(int $radius = 100): float
    {
        $nearby = $this->findNearbyAnnotations($radius);
        $area = pi() * pow($radius, 2);

        return $nearby->count() / $area * 10000; // Normalize to per 10k pixels
    }

    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
            'urgency' => FeedbackUrgency::class,
            'resolved_at' => 'datetime',
            'x_coordinate' => 'integer',
            'y_coordinate' => 'integer',
            'annotation_data' => 'array',
            'area_bounds' => 'array',
            'zoom_level' => 'float',
        ];
    }
}
