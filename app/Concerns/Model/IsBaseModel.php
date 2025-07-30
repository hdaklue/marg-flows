<?php

declare(strict_types=1);

namespace App\Concerns\Model;

use App\Contracts\Model\BaseModelContract;
use App\Exceptions\AbstractBaseModelException;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for abstract base models that should not be instantiated directly
 * Prevents create, update, delete, and save operations on abstract models
 */
trait IsBaseModel
{
    /**
     * Boot the IsBaseModel trait
     */
    protected static function bootIsBaseModel(): void
    {
        // Prevent direct creation of abstract models
        static::creating(function (Model $model) {
            if ($model instanceof BaseModelContract) {
                throw AbstractBaseModelException::cannotCreate(get_class($model));
            }
        });

        // Prevent direct updates of abstract models
        static::updating(function (Model $model) {
            if ($model instanceof BaseModelContract) {
                throw AbstractBaseModelException::cannotUpdate(get_class($model));
            }
        });

        // Prevent direct deletion of abstract models
        static::deleting(function (Model $model) {
            if ($model instanceof BaseModelContract) {
                throw AbstractBaseModelException::cannotDelete(get_class($model));
            }
        });

        // Prevent direct saving of abstract models
        static::saving(function (Model $model) {
            if ($model instanceof BaseModelContract) {
                throw AbstractBaseModelException::cannotSave(get_class($model));
            }
        });
    }

    /**
     * Validate that the model can be persisted
     * Throws exception for abstract models (BaseModelContract implementers)
     */
    public function validatePersistence(): void
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotPersist(
                get_class($this),
                'persistence validation'
            );
        }
    }

    /**
     * Override the create method to prevent direct creation of abstract models
     */
    public static function create(array $attributes = []): static
    {
        $instance = new static();
        
        if ($instance instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotCreate(static::class);
        }

        return parent::create($attributes);
    }

    /**
     * Override the save method to prevent saving abstract models
     */
    public function save(array $options = []): bool
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotSave(get_class($this));
        }

        return parent::save($options);
    }

    /**
     * Override the update method to prevent updating abstract models
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotUpdate(get_class($this));
        }

        return parent::update($attributes, $options);
    }

    /**
     * Override the delete method to prevent deleting abstract models
     */
    public function delete(): ?bool
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotDelete(get_class($this));
        }

        return parent::delete();
    }

    /**
     * Override the forceDelete method to prevent force deleting abstract models
     */
    public function forceDelete(): ?bool
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotDelete(get_class($this));
        }

        return parent::forceDelete();
    }

    /**
     * Override the restore method to prevent restoring abstract models
     */
    public function restore(): bool
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotPersist(
                get_class($this),
                'restore'
            );
        }

        return parent::restore();
    }

    /**
     * Override the replicate method to prevent replicating abstract models
     */
    public function replicate(array $except = null): static
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotPersist(
                get_class($this),
                'replicate'
            );
        }

        return parent::replicate($except);
    }

    /**
     * Override the fill method to validate before filling
     */
    public function fill(array $attributes): static
    {
        // Allow filling for concrete models or during construction
        if ($this instanceof BaseModelContract && $this->exists) {
            throw AbstractBaseModelException::cannotUpdate(get_class($this));
        }

        return parent::fill($attributes);
    }

    /**
     * Override the newInstance method to prevent creating instances of abstract models
     */
    public function newInstance(array $attributes = [], bool $exists = false): static
    {
        $instance = parent::newInstance($attributes, $exists);
        
        // Only validate if trying to create a persisted instance
        if ($exists && $instance instanceof BaseModelContract) {
            throw AbstractBaseModelException::cannotCreate(get_class($instance));
        }

        return $instance;
    }

    /**
     * Get concrete model classes that extend this base model
     * Should be implemented by concrete base models
     */
    public static function getConcreteModels(): array
    {
        // This method should be overridden in concrete base model classes
        // to return their specific implementations
        return [];
    }

    /**
     * Get the model type identifier
     * Should be implemented by concrete models
     */
    public function getModelType(): string
    {
        if ($this instanceof BaseModelContract) {
            throw AbstractBaseModelException::invalidOperation(
                get_class($this),
                'getModelType',
                static::getConcreteModels()
            );
        }

        // Extract model type from class name by default
        $className = class_basename($this);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
    }

    /**
     * Check if a given class is a concrete implementation of this base model
     */
    public static function isConcrete(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        // Check if it's a subclass and not an abstract base model
        return is_subclass_of($modelClass, static::class) && 
               !is_subclass_of($modelClass, BaseModelContract::class);
    }

    /**
     * Factory method to create instances of concrete models
     */
    public static function createConcrete(string $concreteClass, array $attributes = []): Model
    {
        if (!static::isConcrete($concreteClass)) {
            throw AbstractBaseModelException::invalidOperation(
                static::class,
                "create concrete model [{$concreteClass}]",
                static::getConcreteModels()
            );
        }

        return $concreteClass::create($attributes);
    }

    /**
     * Get all instances of concrete models that extend this base model
     */
    public static function allConcrete(): \Illuminate\Support\Collection
    {
        $results = collect();
        
        foreach (static::getConcreteModels() as $modelClass) {
            if (static::isConcrete($modelClass)) {
                $results = $results->merge($modelClass::all());
            }
        }

        return $results;
    }

    /**
     * Find instances across all concrete models
     */
    public static function findAcrossConcretes($id): ?Model
    {
        foreach (static::getConcreteModels() as $modelClass) {
            if (static::isConcrete($modelClass)) {
                $instance = $modelClass::find($id);
                if ($instance) {
                    return $instance;
                }
            }
        }

        return null;
    }

    /**
     * Count instances across all concrete models
     */
    public static function countAcrossConcretes(): int
    {
        $total = 0;
        
        foreach (static::getConcreteModels() as $modelClass) {
            if (static::isConcrete($modelClass)) {
                $total += $modelClass::count();
            }
        }

        return $total;
    }
}