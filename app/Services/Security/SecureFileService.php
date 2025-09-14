<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Services\Directory\Contracts\StorageStrategyContract;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;
use Illuminate\Support\Facades\Storage;

/**
 * Secure File Service.
 *
 * Provides a centralized service for managing secure file access across the application.
 * Handles URL generation, tenant validation, and file type-specific security policies.
 */
final class SecureFileService
{
    /**
     * File type mappings for secure URL generation.
     */
    private const array FILE_TYPE_MAPPINGS = [
        'documents' => 'documents',
        'images' => 'documents',
        'videos' => 'videos',
        'avatars' => 'avatars',
    ];

    /**
     * Default expiration times in seconds for different file types.
     */
    private const array DEFAULT_EXPIRATION_TIMES = [
        'avatars' => 3600,     // 1 hour for avatars (cached heavily)
        'documents' => 1800,   // 30 minutes for document files
        'images' => 1800,      // 30 minutes for images
        'videos' => 7200,      // 2 hours for videos (larger files)
    ];

    /**
     * Generate a secure URL for a file.
     *
     * @param  string  $fileName  The filename
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $fileType  The file type (documents, images, videos, avatars)
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(string $fileName, string $tenantId, string $fileType): string
    {
        $mappedType = self::FILE_TYPE_MAPPINGS[$fileType] ?? 'documents';
        $hashedTenantId = LaraPath::base($tenantId, SanitizationStrategy::HASHED)->toString();

        return route('secure-files.show', [
            'tenantId' => $hashedTenantId,
            'type' => $mappedType,
            'path' => $fileName,
        ]);
    }

    /**
     * Generate a temporary URL for a file.
     *
     * @param  string  $fileName  The filename
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $fileType  The file type
     * @param  int|null  $expiresIn  Optional custom expiration time in seconds
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(
        string $fileName,
        string $tenantId,
        string $fileType,
        ?int $expiresIn = null,
    ): string {
        $strategy = $this->getStorageStrategy($tenantId, $fileType);
        $expiration = $expiresIn ?? $this->getDefaultExpirationTime($fileType);

        return $strategy->getTemporaryUrl($fileName, $expiration);
    }

    /**
     * Check if a file exists and user has access.
     *
     * @param  string  $fileName  The filename
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $fileType  The file type
     * @param  string  $userTenantId  The authenticated user's tenant ID
     * @return bool True if file exists and user has access
     */
    public function canAccess(
        string $fileName,
        string $tenantId,
        string $fileType,
        string $userTenantId,
    ): bool {
        // Check tenant access
        $hashedTenantId = LaraPath::base($tenantId, SanitizationStrategy::HASHED)->toString();
        $hashedUserTenantId = LaraPath::base($userTenantId, SanitizationStrategy::HASHED)->toString();

        if ($hashedTenantId !== $hashedUserTenantId) {
            return false;
        }

        // Check if file exists
        return $this->fileExists($fileName, $tenantId, $fileType);
    }

    /**
     * Check if a file exists.
     *
     * @param  string  $fileName  The filename
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $fileType  The file type
     * @return bool True if file exists
     */
    public function fileExists(string $fileName, string $tenantId, string $fileType): bool
    {
        $strategy = $this->getStorageStrategy($tenantId, $fileType);
        $fullPath = $strategy->getRelativePath($fileName);
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->exists($fullPath);
    }

    /**
     * Generate multiple temporary URLs for batch operations.
     *
     * @param  array  $files  Array of files with 'fileName', 'tenantId', 'fileType' keys
     * @param  int|null  $expiresIn  Optional custom expiration time
     * @return array Array of temporary URLs indexed by filename
     */
    public function getBatchTemporaryUrls(array $files, ?int $expiresIn = null): array
    {
        $urls = [];

        foreach ($files as $file) {
            $fileName = $file['fileName'];
            $tenantId = $file['tenantId'];
            $fileType = $file['fileType'];

            $urls[$fileName] = $this->getTemporaryUrl($fileName, $tenantId, $fileType, $expiresIn);
        }

        return $urls;
    }

    /**
     * Get the appropriate storage strategy for the file type.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $fileType  The file type
     * @return StorageStrategyContract The storage strategy
     */
    private function getStorageStrategy(string $tenantId, string $fileType): StorageStrategyContract
    {
        $documentManager = DocumentDirectoryManager::forTenant($tenantId);

        return match ($fileType) {
            'videos' => $documentManager->document($tenantId)->videos(),
            'documents', 'images' => $documentManager->document($tenantId),
            'avatars' => $documentManager->document($tenantId), // Temporary - should have separate manager
            default => $documentManager->document($tenantId),
        };
    }

    /**
     * Get default expiration time for a file type.
     *
     * @param  string  $fileType  The file type
     * @return int Expiration time in seconds
     */
    private function getDefaultExpirationTime(string $fileType): int
    {
        return self::DEFAULT_EXPIRATION_TIMES[$fileType] ?? self::DEFAULT_EXPIRATION_TIMES['documents'];
    }
}
