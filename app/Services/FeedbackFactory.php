<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Feedbacks\AudioFeedback;
use App\Models\Feedbacks\DesignFeedback;
use App\Models\Feedbacks\DocumentFeedback;
use App\Models\Feedbacks\GeneralFeedback;
use App\Models\Feedbacks\VideoFeedback;
use Exception;
use InvalidArgumentException;
use Log;

/**
 * Factory service for creating appropriate feedback types
 * Routes feedback creation to specialized models based on type and data.
 */
final class FeedbackFactory
{
    /**
     * Create feedback using the most appropriate specialized model.
     */
    public static function create(array $attributes): VideoFeedback|AudioFeedback|DocumentFeedback|DesignFeedback|GeneralFeedback {
        $feedbackType = self::determineFeedbackType($attributes);

        return match ($feedbackType) {
            'video' => self::createVideoFeedback($attributes),
            'audio' => self::createAudioFeedback($attributes),
            'document' => self::createDocumentFeedback($attributes),
            'design' => self::createDesignFeedback($attributes),
            'general' => self::createGeneralFeedback($attributes),
            default => throw new InvalidArgumentException(
                "Unsupported feedback type: {$feedbackType}",
            ),
        };
    }

    /**
     * Create video feedback (frame or region).
     */
    public static function createVideoFeedback(array $attributes): VideoFeedback
    {
        self::validateRequiredFields($attributes, [
            'creator_id',
            'content',
            'feedbackable_type',
            'feedbackable_id',
        ]);

        // Determine if it's frame or region feedback
        $feedbackType = 'frame'; // default

        if (isset($attributes['start_time'], $attributes['end_time'])) {
            $feedbackType = 'region';
        } elseif (isset($attributes['timestamp'])) {
            $feedbackType = 'frame';
        } elseif (
            isset($attributes['feedback_type'])
            && in_array($attributes['feedback_type'], ['frame', 'region'])
        ) {
            $feedbackType = $attributes['feedback_type'];
        }

        return VideoFeedback::create([
            ...$attributes,
            'feedback_type' => $feedbackType,
        ]);
    }

    /**
     * Create audio feedback.
     */
    public static function createAudioFeedback(array $attributes): AudioFeedback
    {
        self::validateRequiredFields($attributes, [
            'creator_id',
            'content',
            'feedbackable_type',
            'feedbackable_id',
            'start_time',
            'end_time',
        ]);

        // Validate time range
        throw_if(
            $attributes['start_time'] >= $attributes['end_time'],
            new InvalidArgumentException('Audio feedback end_time must be greater than start_time'),
        );

        return AudioFeedback::create($attributes);
    }

    /**
     * Create document feedback.
     */
    public static function createDocumentFeedback(array $attributes): DocumentFeedback
    {
        self::validateRequiredFields($attributes, [
            'creator_id',
            'content',
            'feedbackable_type',
            'feedbackable_id',
            'block_id',
        ]);

        return DocumentFeedback::create($attributes);
    }

    /**
     * Create design feedback.
     */
    public static function createDesignFeedback(array $attributes): DesignFeedback
    {
        self::validateRequiredFields($attributes, [
            'creator_id',
            'content',
            'feedbackable_type',
            'feedbackable_id',
            'x_coordinate',
            'y_coordinate',
        ]);

        // Set default annotation type if not provided
        if (!isset($attributes['annotation_type'])) {
            $attributes['annotation_type'] = 'point';
        }

        return DesignFeedback::create($attributes);
    }

    /**
     * Create general feedback.
     */
    public static function createGeneralFeedback(array $attributes): GeneralFeedback
    {
        self::validateRequiredFields($attributes, [
            'creator_id',
            'content',
            'feedbackable_type',
            'feedbackable_id',
        ]);

        return GeneralFeedback::create($attributes);
    }

    // /**
    //  * Create feedback from legacy metadata format (for backward compatibility).
    //  */
    // public static function createFromLegacyData(array $attributes): BaseFeedback
    // {
    //     $metadata = $attributes['metadata'] ?? null;

    //     if (! $metadata || ! isset($metadata['type'])) {
    //         return self::createGeneralFeedback($attributes);
    //     }

    //     $legacyType = $metadata['type'];
    //     $data = $metadata['data'] ?? [];

    //     // Convert legacy metadata to new format
    //     $convertedAttributes = self::convertLegacyAttributes($attributes, $legacyType, $data);

    //     return self::create($convertedAttributes);
    // }

    /**
     * Get available feedback types.
     */
    public static function getAvailableTypes(): array
    {
        return FeedbackConfigService::getAvailableTypes();
    }

    /**
     * Get model class for a given feedback type.
     */
    public static function getModelClass(string $type): string
    {
        $modelClass = FeedbackConfigService::getModelClass($type);

        throw_unless($modelClass, new InvalidArgumentException("Unknown feedback type: {$type}"));

        return $modelClass;
    }

    /**
     * Check if a feedback type is supported.
     */
    public static function isValidType(string $type): bool
    {
        return FeedbackConfigService::isValidType($type);
    }

    /**
     * Bulk create multiple feedback entries.
     */
    public static function createBulk(array $feedbackEntries): array
    {
        $createdFeedback = [];

        foreach ($feedbackEntries as $attributes) {
            try {
                $createdFeedback[] = self::create($attributes);
            } catch (Exception $e) {
                // Log error but continue with other entries
                Log::error('Failed to create feedback in bulk operation', [
                    'attributes' => $attributes,
                    'error' => $e->getMessage(),
                ]);

                // Optionally throw or continue based on requirements
                throw $e;
            }
        }

        return $createdFeedback;
    }

    /**
     * Create feedback with automatic type detection and validation.
     */
    public static function createSmart(array $attributes): VideoFeedback|AudioFeedback|DocumentFeedback|DesignFeedback|GeneralFeedback {
        // Automatically clean and validate attributes
        $cleanAttributes = self::cleanAttributes($attributes);

        // Determine the best type
        $type = self::determineFeedbackType($cleanAttributes);

        // Create with the determined type
        return self::create(array_merge($cleanAttributes, [
            'feedback_type' => $type,
        ]));
    }

    /**
     * Determine the most appropriate feedback type based on attributes.
     */
    private static function determineFeedbackType(array $attributes): string
    {
        $availableTypes = self::getAvailableTypes();

        // Explicit type specification
        if (isset($attributes['feedback_type'])) {
            $explicitType = $attributes['feedback_type'];
            if (in_array($explicitType, $availableTypes)) {
                return $explicitType;
            }
        }

        // Use configuration-based detection rules
        $detectionRules = config('feedback.factory.type_detection_rules', []);

        foreach ($detectionRules as $type => $rules) {
            if (!in_array($type, $availableTypes)) {
                continue;
            }

            if (self::matchesDetectionRules($attributes, $rules)) {
                return $type;
            }
        }

        // Legacy metadata detection
        if (isset($attributes['metadata']) && is_array($attributes['metadata'])) {
            $metadata = $attributes['metadata'];
            if (isset($metadata['type'])) {
                $legacyMapping = config('feedback.migration.legacy_metadata_mapping', []);
                $legacyType = $metadata['type'];

                if (isset($legacyMapping[$legacyType])) {
                    return $legacyMapping[$legacyType];
                }
            }
        }

        // Default to general feedback or first available type
        $fallbackEnabled = config('feedback.factory.fallback_to_general', true);
        if ($fallbackEnabled && in_array('general', $availableTypes)) {
            return 'general';
        }

        return $availableTypes[0] ?? 'general';
    }

    /**
     * Check if attributes match the detection rules for a feedback type.
     */
    private static function matchesDetectionRules(array $attributes, array $rules): bool
    {
        // Check required_all fields
        if (isset($rules['required_all'])) {
            foreach ($rules['required_all'] as $field) {
                if (!isset($attributes[$field]) || $attributes[$field] === null) {
                    return false;
                }
            }
        }

        // Check required_any fields (at least one must be present)
        if (isset($rules['required_any'])) {
            $hasAny = false;
            foreach ($rules['required_any'] as $field) {
                if (isset($attributes[$field]) && $attributes[$field] !== null) {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                return false;
            }
        }

        // Check forbidden fields (must not be present)
        if (isset($rules['forbidden'])) {
            foreach ($rules['forbidden'] as $field) {
                if (isset($attributes[$field]) && $attributes[$field] !== null) {
                    return false;
                }
            }
        }

        // Check indicators (presence suggests this type)
        if (isset($rules['indicators'])) {
            $indicatorScore = 0;
            foreach ($rules['indicators'] as $field => $expectedValues) {
                if (isset($attributes[$field])) {
                    if (is_array($expectedValues)) {
                        if (in_array($attributes[$field], $expectedValues)) {
                            $indicatorScore++;
                        }
                    } else {
                        if ($attributes[$field] === $expectedValues) {
                            $indicatorScore++;
                        }
                    }
                }
            }
            // Require at least one indicator match if indicators are specified
            if ($indicatorScore === 0 && !empty($rules['indicators'])) {
                return false;
            }
        }

        // Check patterns (regex validation)
        if (isset($rules['patterns'])) {
            foreach ($rules['patterns'] as $field => $pattern) {
                if (isset($attributes[$field])) {
                    if (!preg_match($pattern, (string) $attributes[$field])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Validate that required fields are present in attributes.
     */
    private static function validateRequiredFields(array $attributes, array $requiredFields): void
    {
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($attributes[$field]) || $attributes[$field] === null) {
                $missingFields[] = $field;
            }
        }

        throw_unless(
            empty($missingFields),
            new InvalidArgumentException('Missing required fields: '
            . implode(', ', $missingFields)),
        );
    }

    /**
     * Clean and normalize attributes.
     */
    private static function cleanAttributes(array $attributes): array
    {
        // Remove null values
        $attributes = array_filter($attributes, fn($value) => $value !== null);

        // Normalize coordinate values
        if (isset($attributes['x_coordinate'])) {
            $attributes['x_coordinate'] = (int) $attributes['x_coordinate'];
        }
        if (isset($attributes['y_coordinate'])) {
            $attributes['y_coordinate'] = (int) $attributes['y_coordinate'];
        }

        // Normalize time values
        if (isset($attributes['timestamp'])) {
            $attributes['timestamp'] = (float) $attributes['timestamp'];
        }
        if (isset($attributes['start_time'])) {
            $attributes['start_time'] = (float) $attributes['start_time'];
        }
        if (isset($attributes['end_time'])) {
            $attributes['end_time'] = (float) $attributes['end_time'];
        }

        return $attributes;
    }
}
