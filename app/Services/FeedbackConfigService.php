<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for working with feedback configuration
 * Provides centralized access to feedback settings and validation.
 */
final class FeedbackConfigService
{
    /**
     * Get all concrete feedback model classes.
     */
    public static function getConcreteModels(): array
    {
        return config('feedback.concrete_models', []);
    }

    /**
     * Get concrete model class for a specific type.
     */
    public static function getModelClass(string $type): ?string
    {
        return config("feedback.concrete_models.{$type}");
    }

    /**
     * Get all available feedback types.
     */
    public static function getAvailableTypes(): array
    {
        return array_keys(self::getConcreteModels());
    }

    /**
     * Check if a feedback type is supported.
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAvailableTypes());
    }

    /**
     * Get default feedback settings.
     */
    public static function getDefaults(): array
    {
        return config('feedback.defaults', []);
    }

    /**
     * Get default value for a specific setting.
     */
    public static function getDefault(string $key, mixed $fallback = null): mixed
    {
        return config("feedback.defaults.{$key}", $fallback);
    }

    /**
     * Get settings for a specific feedback type.
     */
    public static function getTypeSettings(string $type): array
    {
        return config("feedback.{$type}", []);
    }

    /**
     * Get specific setting for a feedback type.
     */
    public static function getTypeSetting(string $type, string $key, mixed $fallback = null): mixed
    {
        return config("feedback.{$type}.{$key}", $fallback);
    }

    /**
     * Get supported formats for a media type.
     */
    public static function getSupportedFormats(string $type): array
    {
        return config("feedback.{$type}.supported_formats", []);
    }

    /**
     * Get validation rules for a feedback type.
     */
    public static function getValidationRules(string $type): array
    {
        $globalRules = config('feedback.validation.required_fields.all', []);
        $typeRules = config("feedback.validation.required_fields.{$type}", []);

        return array_merge($globalRules, $typeRules);
    }

    /**
     * Get factory settings.
     */
    public static function getFactorySettings(): array
    {
        return config('feedback.factory', []);
    }

    /**
     * Get type detection rules for the factory.
     */
    public static function getTypeDetectionRules(): array
    {
        return config('feedback.factory.type_detection_rules', []);
    }

    /**
     * Get migration settings.
     */
    public static function getMigrationSettings(): array
    {
        return config('feedback.migration', []);
    }

    /**
     * Get legacy metadata mapping.
     */
    public static function getLegacyMapping(): array
    {
        return config('feedback.migration.legacy_metadata_mapping', []);
    }

    /**
     * Get performance settings.
     */
    public static function getPerformanceSettings(): array
    {
        return config('feedback.performance', []);
    }

    /**
     * Check if a feature is enabled.
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return config("feedback.features.{$feature}", false);
    }

    /**
     * Get all enabled features.
     */
    public static function getEnabledFeatures(): array
    {
        $features = config('feedback.features', []);

        return array_keys(array_filter($features));
    }

    /**
     * Get integration settings.
     */
    public static function getIntegrationSettings(string $integration): array
    {
        return config("feedback.integrations.{$integration}", []);
    }

    /**
     * Get content limits for validation.
     */
    public static function getContentLimits(): array
    {
        return config('feedback.validation.content_limits', []);
    }

    /**
     * Get coordinate limits for design feedback.
     */
    public static function getCoordinateLimits(): array
    {
        return config('feedback.validation.coordinate_limits', []);
    }

    /**
     * Get time limits for video/audio feedback.
     */
    public static function getTimeLimits(): array
    {
        return config('feedback.validation.time_limits', []);
    }

    /**
     * Validate feedback attributes against configuration rules.
     */
    public static function validateAttributes(string $type, array $attributes): array
    {
        $errors = [];

        // Check required fields
        $requiredFields = self::getValidationRules($type);
        foreach ($requiredFields as $field) {
            if (! isset($attributes[$field]) || $attributes[$field] === null) {
                $errors[] = "Required field '{$field}' is missing";
            }
        }

        // Check content limits
        if (isset($attributes['content'])) {
            $contentLimits = self::getContentLimits();
            $contentLength = mb_strlen($attributes['content']);

            if ($contentLength < ($contentLimits['min_length'] ?? 0)) {
                $errors[] = "Content too short (minimum {$contentLimits['min_length']} characters)";
            }

            if ($contentLength > ($contentLimits['max_length'] ?? PHP_INT_MAX)) {
                $errors[] = "Content too long (maximum {$contentLimits['max_length']} characters)";
            }
        }

        // Type-specific validation
        switch ($type) {
            case 'design':
                $errors = array_merge($errors, self::validateDesignAttributes($attributes));
                break;
            case 'video':
            case 'audio':
                $errors = array_merge($errors, self::validateTimeAttributes($attributes));
                break;
            case 'document':
                $errors = array_merge($errors, self::validateDocumentAttributes($attributes));
                break;
        }

        return $errors;
    }

    /**
     * Get configuration summary for debugging.
     */
    public static function getConfigSummary(): array
    {
        return [
            'concrete_models' => count(self::getConcreteModels()),
            'available_types' => self::getAvailableTypes(),
            'enabled_features' => count(self::getEnabledFeatures()),
            'factory_enabled' => self::getFactorySettings()['auto_detect_type'] ?? false,
            'validation_enabled' => self::getFactorySettings()['strict_validation'] ?? false,
            'caching_enabled' => self::getPerformanceSettings()['caching']['enabled'] ?? false,
        ];
    }

    /**
     * Get type-specific configuration for frontend.
     */
    public static function getFrontendConfig(string $type): array
    {
        return [
            'type' => $type,
            'model_class' => self::getModelClass($type),
            'settings' => self::getTypeSettings($type),
            'validation' => [
                'required_fields' => self::getValidationRules($type),
                'content_limits' => self::getContentLimits(),
            ],
            'features' => array_intersect_key(
                config('feedback.features', []),
                array_flip(["{$type}_*"]),
            ),
        ];
    }

    /**
     * Export configuration for external tools.
     */
    public static function exportConfiguration(): array
    {
        return [
            'version' => '1.0',
            'timestamp' => now()->toISOString(),
            'concrete_models' => self::getConcreteModels(),
            'defaults' => self::getDefaults(),
            'validation' => config('feedback.validation', []),
            'features' => config('feedback.features', []),
            'performance' => self::getPerformanceSettings(),
        ];
    }

    /**
     * Validate design feedback attributes.
     */
    private static function validateDesignAttributes(array $attributes): array
    {
        $errors = [];
        $coordinateLimits = self::getCoordinateLimits();

        if (isset($attributes['x_coordinate'])) {
            $x = $attributes['x_coordinate'];
            if (
                $x < ($coordinateLimits['x']['min'] ?? 0)
                || $x > ($coordinateLimits['x']['max'] ?? PHP_INT_MAX)
            ) {
                $errors[] = 'X coordinate out of bounds';
            }
        }

        if (isset($attributes['y_coordinate'])) {
            $y = $attributes['y_coordinate'];
            if (
                $y < ($coordinateLimits['y']['min'] ?? 0)
                || $y > ($coordinateLimits['y']['max'] ?? PHP_INT_MAX)
            ) {
                $errors[] = 'Y coordinate out of bounds';
            }
        }

        // Validate annotation type
        if (isset($attributes['annotation_type'])) {
            $supportedTypes = self::getTypeSetting('design', 'supported_annotation_types', []);
            if (! in_array($attributes['annotation_type'], $supportedTypes)) {
                $errors[] = 'Unsupported annotation type';
            }
        }

        return $errors;
    }

    /**
     * Validate time-based attributes for video/audio feedback.
     */
    private static function validateTimeAttributes(array $attributes): array
    {
        $errors = [];
        $timeLimits = self::getTimeLimits();

        if (isset($attributes['timestamp'])) {
            $timestamp = $attributes['timestamp'];
            if (
                $timestamp < ($timeLimits['timestamp']['min'] ?? 0)
                || $timestamp > ($timeLimits['timestamp']['max'] ?? PHP_INT_MAX)
            ) {
                $errors[] = 'Timestamp out of bounds';
            }
        }

        if (isset($attributes['start_time'], $attributes['end_time'])) {
            $startTime = $attributes['start_time'];
            $endTime = $attributes['end_time'];
            $duration = $endTime - $startTime;

            if ($duration < ($timeLimits['duration']['min'] ?? 0)) {
                $errors[] = 'Duration too short';
            }

            if ($duration > ($timeLimits['duration']['max'] ?? PHP_INT_MAX)) {
                $errors[] = 'Duration too long';
            }

            if ($startTime >= $endTime) {
                $errors[] = 'Start time must be before end time';
            }
        }

        return $errors;
    }

    /**
     * Validate document feedback attributes.
     */
    private static function validateDocumentAttributes(array $attributes): array
    {
        $errors = [];

        if (isset($attributes['block_id'])) {
            $pattern = self::getTypeSetting('document', 'block_id_pattern');
            if ($pattern && ! preg_match($pattern, $attributes['block_id'])) {
                $errors[] = 'Invalid block ID format';
            }
        }

        if (isset($attributes['element_type'])) {
            $supportedTypes = self::getTypeSetting('document', 'supported_block_types', []);
            if (! in_array($attributes['element_type'], $supportedTypes)) {
                $errors[] = 'Unsupported block element type';
            }
        }

        return $errors;
    }
}
