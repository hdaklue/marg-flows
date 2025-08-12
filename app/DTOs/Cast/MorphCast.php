<?php

declare(strict_types=1);

namespace App\DTOs\Cast;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use WendellAdriel\ValidatedDTO\Casting\Castable;

final class MorphCast implements Castable
{
    /** @var array<string, mixed> */
    private array $resolvedDto;

    public function __construct()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        if (!is_array($trace[1]['object']->dtoData ?? null)) {
            throw new InvalidArgumentException(
                'MorphCast: Calling DTO instance does not have accessible dtoData array.',
            );
        }

        $this->resolvedDto = $trace[1]['object']->dtoData;
    }

    public function cast(string $property, mixed $value): Model
    {
        [$morphTypeKey, $morphIdKey] = $this->resolveMorphKeys($property);

        if (!isset($this->resolvedDto[$morphTypeKey])) {
            throw new InvalidArgumentException("MorphCast: Missing morph type key [{$morphTypeKey}] in DTO data.");
        }

        $morphClassAlias = $this->resolvedDto[$morphTypeKey];

        $modelClass = Relation::getMorphedModel($morphClassAlias) ?? $morphClassAlias;

        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("MorphCast: Invalid model class [{$modelClass}].");
        }

        /** @var Model $modelInstance */
        $modelInstance = new $modelClass;
        
        // forceFill is correct here - we're in a DTO casting context with trusted data
        // and need to preserve all attributes (fillable + non-fillable like id, timestamps)
        if (is_array($value) && !empty($value)) {
            $modelInstance->forceFill($value);
        }

        return $modelInstance;
    }

    /**
     * Resolve morph keys following Laravel convention.
     * Override this method to customize key resolution.
     */
    protected function resolveMorphKeys(string $property): array
    {
        return [
            "{$property}_type", // morph type key
            "{$property}_id",   // morph id key
        ];
    }
}