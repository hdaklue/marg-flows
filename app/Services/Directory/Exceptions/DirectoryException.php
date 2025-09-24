<?php

declare(strict_types=1);

namespace App\Services\Directory\Exceptions;

use Exception;

/**
 * Directory Service Exception.
 *
 * Base exception for all directory service operations
 */
final class DirectoryException extends Exception
{
    public static function fileNotFound(string $path): self
    {
        return new self("File not found: {$path}");
    }

    public static function invalidFile(string $reason): self
    {
        return new self("Invalid file: {$reason}");
    }

    public static function storageError(string $operation, string $details = ''): self
    {
        $message = "Storage operation failed: {$operation}";
        if ($details) {
            $message .= " - {$details}";
        }

        return new self($message);
    }

    public static function configurationError(string $details): self
    {
        return new self("Configuration error: {$details}");
    }
}
