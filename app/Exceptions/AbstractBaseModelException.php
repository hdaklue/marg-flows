<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to perform operations on abstract base models
 */
class AbstractBaseModelException extends Exception
{
    public static function cannotCreate(string $modelClass): self
    {
        return new self(
            "Cannot create instances of abstract base model [{$modelClass}]. " .
            "Use a concrete implementation instead."
        );
    }

    public static function cannotUpdate(string $modelClass): self
    {
        return new self(
            "Cannot update abstract base model [{$modelClass}]. " .
            "Use a concrete implementation instead."
        );
    }

    public static function cannotDelete(string $modelClass): self
    {
        return new self(
            "Cannot delete abstract base model [{$modelClass}]. " .
            "Use a concrete implementation instead."
        );
    }

    public static function cannotSave(string $modelClass): self
    {
        return new self(
            "Cannot save abstract base model [{$modelClass}]. " .
            "Use a concrete implementation instead."
        );
    }

    public static function cannotPersist(string $modelClass, string $operation): self
    {
        return new self(
            "Cannot perform [{$operation}] operation on abstract base model [{$modelClass}]. " .
            "Use a concrete implementation instead."
        );
    }

    public static function invalidOperation(string $modelClass, string $operation, array $availableModels = []): self
    {
        $message = "Invalid operation [{$operation}] on abstract base model [{$modelClass}].";
        
        if (!empty($availableModels)) {
            $modelList = implode(', ', $availableModels);
            $message .= " Available concrete models: {$modelList}";
        }

        return new self($message);
    }
}