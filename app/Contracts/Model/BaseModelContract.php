<?php

declare(strict_types=1);

namespace App\Contracts\Model;

/**
 * Contract for base models that should not be instantiated directly
 * Enforces that abstract base models cannot be created, updated, or deleted directly
 */
interface BaseModelContract
{
    /**
     * Get the model type identifier
     * Must be implemented by concrete models to identify their type
     */
    public function getModelType(): string;

    /**
     * Check if the model is abstract (cannot be instantiated directly)
     */
    public function isAbstractModel(): bool;

    /**
     * Get the concrete model classes that extend this base model
     */
    public static function getConcreteModels(): array;

    /**
     * Validate that the model can be persisted
     * Should throw exception for abstract models
     */
    public function validatePersistence(): void;
}