<?php

declare(strict_types=1);

namespace App\Services\Document\Templates;

use App\Services\Document\Contracts\DocumentTemplateContract;
use InvalidArgumentException;

final class DocumentTemplateManager
{
    private array $templateInstances = [];

    public function __construct()
    {
        // Public constructor for Laravel container
    }

    public function templatesAsSelectArray(): array
    {
        $templates = config('document.templates');

        return collect($templates)->mapWithKeys(fn(
            $class,
            $key,
        ) => [$key => $class::getName()])->toArray();
    }

    public function make(string $templateKey): DocumentTemplateContract
    {
        // Return cached instance if exists
        if (isset($this->templateInstances[$templateKey])) {
            return $this->templateInstances[$templateKey];
        }

        $templates = config('document.templates');

        if (!isset($templates[$templateKey])) {
            throw new InvalidArgumentException(
                "Template '{$templateKey}' not found in configuration.",
            );
        }

        $templateClass = $templates[$templateKey];

        if (!class_exists($templateClass)) {
            throw new InvalidArgumentException("Template class '{$templateClass}' does not exist.");
        }

        // Create and cache the instance
        $this->templateInstances[$templateKey] = $templateClass::make();

        return $this->templateInstances[$templateKey];
    }

    public function __call(string $method, array $arguments): DocumentTemplateContract
    {
        // Support calling templates by key: DocumentTemplate::general(), DocumentTemplate::media_plan_brief(), etc.
        return $this->make($method);
    }
}
