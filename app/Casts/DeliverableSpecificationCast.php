<?php

declare(strict_types=1);

namespace App\Casts;

use App\Contracts\Deliverables\DeliverableSpecification;
use App\Enums\Deliverable\DeliverableFormat;
use App\ValueObjects\Deliverable\AudioSpecification;
use App\ValueObjects\Deliverable\DesignSpecification;
use App\ValueObjects\Deliverable\DocumentSpecification;
use App\ValueObjects\Deliverable\VideoSpecification;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class DeliverableSpecificationCast implements CastsAttributes
{
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): ?DeliverableSpecification {
        if ($value === null) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($data) || empty($data)) {
            return null;
        }

        // Get the format from the model to determine which specification class to use
        $format = $model->getAttribute('format');

        if (! $format instanceof DeliverableFormat) {
            // Try to cast it if it's a string
            $format = is_string($format) ? DeliverableFormat::tryFrom($format) : null;
        }

        throw_unless(
            $format,
            new InvalidArgumentException(
                'Cannot determine deliverable format for specification casting',
            ),
        );

        return match ($format) {
            DeliverableFormat::DESIGN => DesignSpecification::fromConfig($data),
            DeliverableFormat::VIDEO => VideoSpecification::fromConfig($data),
            DeliverableFormat::AUDIO => AudioSpecification::fromConfig($data),
            DeliverableFormat::DOCUMENT => DocumentSpecification::fromConfig($data),
        };
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof DeliverableSpecification) {
            return [$key => json_encode($value->toArray())];
        }

        if (is_array($value)) {
            return [$key => json_encode($value)];
        }

        return [$key => json_encode($value)];
    }
}
