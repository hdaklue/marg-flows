<?php

declare(strict_types=1);

namespace App\Services\FileServing;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * Abstract File Resolver.
 *
 * Base class for all file serving resolvers. Provides common functionality
 * while enforcing entity-specific access validation through abstract methods.
 * Each concrete resolver handles its own security model and file resolution logic.
 */
abstract class AbstractFileResolver
{
    /**
     * Resolve secure URL with access validation.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return string Secure URL requiring authentication
     *
     * @throws HttpResponseException When access is denied
     */
    public function resolveSecureUrl($entity, string $type, string $filename): string
    {
        if (! $this->validateAccess($entity)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN),
            );
        }

        return $this->generateSecureUrl($entity, $type, $filename);
    }

    /**
     * Resolve temporary URL with access validation.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @param  int|null  $expires  Expiration time in seconds
     * @param  User|null  $user  The user requesting access (defaults to authenticated user)
     * @return string Temporary URL with expiration
     *
     * @throws HttpResponseException When access is denied
     */
    public function resolveTemporaryUrl($entity, string $type, string $filename, ?int $expires = null, ?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user || ! $this->validateAccess($entity)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN),
            );
        }

        return $this->generateTemporaryUrl($entity, $type, $filename, $expires);
    }

    /**
     * Check if file exists for the entity.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return bool True if file exists, false otherwise
     */
    abstract public function fileExists($entity, string $type, string $filename): bool;

    /**
     * Get file size for the entity.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return int|null File size in bytes, null if file doesn't exist
     */
    abstract public function getFileSize($entity, string $type, string $filename): ?int;

    /**
     * Delete file for the entity.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @param  User|null  $user  The user requesting deletion (defaults to authenticated user)
     * @return bool True if deletion was successful, false otherwise
     *
     * @throws HttpResponseException When access is denied
     */
    public function deleteFile($entity, string $type, string $filename, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user || ! $this->validateAccess($entity)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN),
            );
        }

        return $this->performFileDelete($entity, $type, $filename);
    }

    /**
     * Validate if current user has access to files for the given entity.
     *
     * Each resolver implements its own access validation using Gates/Policies:
     * - DocumentFileResolver: Use Gate::allows('view-document-files', $document)
     * - SystemFileResolver: Use Gate::allows('view-system-files')
     * - ChunkFileResolver: Use Gate::allows('view-chunk-files', $entity)
     *
     * Can access current request and user via request() helper.
     *
     * @param  mixed  $entity  The entity that has files
     * @return bool True if current user has access, false otherwise
     */
    abstract protected function validateAccess($entity): bool;

    /**
     * Generate secure URL for a file requiring authentication.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return string Secure URL requiring authentication
     */
    abstract protected function generateSecureUrl($entity, string $type, string $filename): string;

    /**
     * Generate temporary URL for a file with expiration.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @param  int|null  $expires  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    abstract protected function generateTemporaryUrl($entity, string $type, string $filename, ?int $expires = null): string;

    /**
     * Perform the actual file deletion.
     *
     * @param  mixed  $entity  The entity that owns the file
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return bool True if deletion was successful, false otherwise
     */
    abstract protected function performFileDelete($entity, string $type, string $filename): bool;
}
