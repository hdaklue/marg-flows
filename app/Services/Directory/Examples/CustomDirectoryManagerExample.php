<?php

declare(strict_types=1);

namespace App\Services\Directory\Examples;

use App\Services\Directory\AbstractDirectoryManager;

/**
 * Custom Directory Manager Example.
 *
 * Demonstrates how to extend AbstractDirectoryManager to create custom
 * storage implementations with specific business logic and requirements.
 * This example shows a project-specific directory manager.
 */
final class CustomDirectoryManagerExample extends AbstractDirectoryManager
{
    public function __construct(
        private readonly string $projectType = 'default',
    ) {}

    /**
     * Static factory method for different project types.
     */
    public static function forProjectType(string $projectType): self
    {
        return new self($projectType);
    }

    /**
     * Example custom method leveraging base functionality.
     *
     * Shows how concrete implementations can add business-specific methods
     * while utilizing the common patterns from AbstractDirectoryManager.
     */
    public function getProjectFiles(string $projectId, string $fileType = 'documents'): array
    {
        return $this->getAllFiles($projectId);
    }

    /**
     * Example method showing custom URL generation.
     */
    public function getProjectFileUrl(string $projectId, string $fileName): string
    {
        return $this->getSecureUrl($projectId, 'documents', $fileName);
    }

    /**
     * Get the storage disk for project-specific storage.
     *
     * Could be configured per project type (e.g., 'premium' projects use S3).
     */
    protected function getDisk(): string
    {
        return match ($this->projectType) {
            'premium' => 's3',
            'enterprise' => 'gcs',
            default => 'public',
        };
    }

    /**
     * Get base directory with project-type specific logic.
     *
     * Creates hierarchical directory structure based on project type and identifier.
     */
    protected function getBaseDirectory(null|string $identifier = null): string
    {
        if ($identifier === null) {
            return $this->projectType;
        }

        // Create project-type specific directory structure
        $hashedId = md5($identifier);

        return "{$this->projectType}/{$hashedId[0]}{$hashedId[1]}/{$hashedId}";
    }
}
