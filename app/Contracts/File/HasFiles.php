<?php

declare(strict_types=1);

namespace App\Contracts\File;

use App\Models\User;

/**
 * HasFiles Contract.
 *
 * Interface for entities that support file operations and access validation.
 * Provides a standardized way to validate file access across different entity types.
 */
interface HasFiles
{
    /**
     * Validate if a user has access to files for this entity.
     *
     * @param  User  $user  The user requesting access
     * @return bool True if user has access, false otherwise
     */
    public function userHasFileAccess(User $user): bool;

    /**
     * Get the identifier used for file storage paths.
     *
     * @return string The identifier for file storage (tenant ID, user ID, etc.)
     */
    public function getFileStorageIdentifier(): string;

    /**
     * Get the entity type for file operations.
     *
     * @return string The entity type (document, user, etc.)
     */
    public function getFileEntityType(): string;
}
