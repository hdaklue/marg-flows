<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\DTOException;
use Illuminate\Support\Facades\Log;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * Base DTO with enhanced error handling and debugging capabilities.
 *
 * This class provides better error messages and logging for DTO validation failures,
 * making debugging much easier compared to silent failures.
 */
abstract class BaseDto extends ValidatedDTO
{
    /**
     * Override the failedValidation method to provide better error handling.
     *
     * This method is called when validation fails. Instead of failing silently,
     * it logs detailed information about the validation failure and throws
     * a clear exception with context.
     */
    protected function failedValidation(): void
    {
        $errors = $this->validator->errors();
        $data = $this->validatorData();

        // Log detailed validation failure information
        Log::error('DTO Validation Failed', [
            'dto_class' => static::class,
            'validation_errors' => $errors->toArray(),
            'input_data_keys' => array_keys($data),
            'input_data_preview' => $this->getDataPreview($data),
            'expected_rules' => $this->rules(),
        ]);

        // Create a more informative exception message
        $className = class_basename(static::class);
        $firstError = $errors->first();
        $failedFields = implode(', ', array_keys($errors->toArray()));

        $message = "DTO validation failed for {$className}. ";
        $message .= "Failed fields: [{$failedFields}]. ";
        $message .= "First error: {$firstError}. ";
        $message .= 'Check logs for detailed information.';

        throw new DTOException(
            static::class,
            $this->validator,
            $data,
            $this->rules(),
            $message,
        );
    }

    /**
     * Get a safe preview of the input data for logging (without sensitive information).
     */
    private function getDataPreview(array $data, int $maxLength = 100): array
    {
        $preview = [];
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'api_key'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields, true)) {
                $preview[$key] = '[REDACTED]';
            } elseif (is_string($value)) {
                $preview[$key] = strlen($value) > $maxLength
                    ? substr($value, 0, $maxLength) . '...'
                    : $value;
            } elseif (is_array($value)) {
                $preview[$key] = '[ARRAY:' . count($value) . ' items]';
            } elseif (is_object($value)) {
                $preview[$key] = '[OBJECT:' . get_class($value) . ']';
            } else {
                $preview[$key] = $value;
            }
        }

        return $preview;
    }
}
