<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\Validator;

/**
 * Custom exception for DTO validation failures.
 * 
 * Provides enhanced error information and context for debugging
 * DTO validation issues.
 */
class DTOException extends Exception
{
    public function __construct(
        private readonly string $dtoClass,
        private readonly Validator $validator,
        private readonly array $inputData,
        private readonly array $rules,
        ?string $customMessage = null
    ) {
        $errors = $this->validator->errors();
        $className = class_basename($this->dtoClass);
        $firstError = $errors->first();
        $failedFields = implode(', ', array_keys($errors->toArray()));
        
        $message = $customMessage ?? $this->buildDefaultMessage($className, $failedFields, $firstError);
        
        parent::__construct($message, 422); // 422 Unprocessable Entity
    }
    
    /**
     * Get the DTO class that failed validation.
     */
    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }
    
    /**
     * Get the validator instance with all validation errors.
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }
    
    /**
     * Get all validation errors.
     */
    public function getValidationErrors(): array
    {
        return $this->validator->errors()->toArray();
    }
    
    /**
     * Get the input data that failed validation.
     */
    public function getInputData(): array
    {
        return $this->inputData;
    }
    
    /**
     * Get the validation rules that were applied.
     */
    public function getRules(): array
    {
        return $this->rules;
    }
    
    /**
     * Get the first validation error message.
     */
    public function getFirstError(): ?string
    {
        return $this->validator->errors()->first();
    }
    
    /**
     * Get all failed field names.
     */
    public function getFailedFields(): array
    {
        return array_keys($this->validator->errors()->toArray());
    }
    
    /**
     * Build a detailed error message for debugging.
     */
    private function buildDefaultMessage(string $className, string $failedFields, ?string $firstError): string
    {
        $message = "DTO validation failed for {$className}.";
        
        if (!empty($failedFields)) {
            $message .= " Failed fields: [{$failedFields}].";
        }
        
        if ($firstError) {
            $message .= " First error: {$firstError}.";
        }
        
        $message .= " Check logs for detailed information.";
        
        return $message;
    }
    
    /**
     * Convert the exception to an array for JSON responses.
     */
    public function toArray(): array
    {
        return [
            'error' => 'DTO Validation Failed',
            'dto_class' => class_basename($this->dtoClass),
            'message' => $this->getMessage(),
            'validation_errors' => $this->getValidationErrors(),
            'failed_fields' => $this->getFailedFields(),
            'code' => $this->getCode(),
        ];
    }
    
    /**
     * Convert the exception to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}