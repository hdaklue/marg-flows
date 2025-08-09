<?php

declare(strict_types=1);

namespace App\Services\Deliverable;

use App\Contracts\Deliverables\DeliverableSpecification;
use App\ValueObjects\Deliverable\DeliverableFormat;
use App\ValueObjects\Deliverable\DeliverableType;
use App\ValueObjects\Deliverable\DesignSpecification;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Resolves deliverable formats based on configuration.
 * Handles different types of deliverables like design, video, etc.
 */
final class DeliverableSpecResolver
{
    private const string CONFIG_PATH = 'deliverables';

    private const string SUPPORTED_FORMATS = 'formats';

    public static function resolve(DeliverableFormat $format, DeliverableType $type): DeliverableSpecification
    {

        return match ($format->key()) {
            'design' => self::handelDesignType($format, $type),
            default => throw new InvalidArgumentException("Unsupported format [{$format->key()}]."),
        };

    }

    public static function getSupportedFormats(): array
    {
        return array_keys(config(self::CONFIG_PATH . '.' . self::SUPPORTED_FORMATS, []));
    }

    protected static function handelDesignType(DeliverableFormat $format, DeliverableType $type): DesignSpecification
    {
        $config = config($type->configPath());

        return new DesignSpecification(
            width: $config['width'],
            height: $config['height'],
            safeArea: Arr::get($config, 'safe_area', []),
            constraints: $config['constraints'] ?? [],
            requirements: $config['requirements'] ?? [],
        );

    }
}
