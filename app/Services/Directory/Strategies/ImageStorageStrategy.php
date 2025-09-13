<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\StorageStrategyContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Image Storage Strategy.
 *
 * Handles image file storage with potential for image-specific operations
 * like thumbnail generation, resizing, etc.
 */
final class ImageStorageStrategy implements StorageStrategyContract
{
    // Obfuscated directory names - don't expose actual purpose
    private const VARIANT_DIRECTORIES = [
        'original' => 'raw', // raw/original files
        'thumbnails' => 'sm', // small/thumbnail files
        'optimized' => 'opt', // optimized files
        'resized' => 'adj', // adjusted/resized files
        'watermarked' => 'mark', // marked/watermarked files
    ];

    private ?UploadedFile $file = null;

    private ?string $storedPath = null;

    private ?string $variant = null;

    /**
     * Constructor receives the full base directory path for images.
     *
     * @param  string  $baseDirectory  The full path to the image storage directory
     */
    public function __construct(
        private readonly string $baseDirectory,
    ) {}

    /**
     * Set variant for original/raw image files.
     */
    public function asOriginal(): self
    {
        $this->variant = 'original';

        return $this;
    }

    /**
     * Set variant for thumbnail images.
     */
    public function asThumbnails(): self
    {
        $this->variant = 'thumbnails';

        return $this;
    }

    /**
     * Set variant for optimized images.
     */
    public function asOptimized(): self
    {
        $this->variant = 'optimized';

        return $this;
    }

    /**
     * Set variant for resized images.
     */
    public function asResized(): self
    {
        $this->variant = 'resized';

        return $this;
    }

    /**
     * Set variant for watermarked images.
     */
    public function asWatermarked(): self
    {
        $this->variant = 'watermarked';

        return $this;
    }

    /**
     * Store an image file.
     *
     * @param  UploadedFile  $file  The image file to store
     * @return string The stored file path
     */
    public function store(UploadedFile $file): string
    {
        $this->file = $file;

        $filename = $this->generateFilename();
        $directory = $this->variant
            ? $this->buildVariantDirectory()
            : $this->baseDirectory;
        $disk = config('document.storage.disk', 'public');

        $this->storedPath = $file->storeAs($directory, $filename, [
            'disk' => $disk,
        ]);

        return $this->storedPath;
    }

    /**
     * Get the URL for the stored file.
     *
     * @return string The file URL
     *
     * @throws InvalidArgumentException If no file has been stored
     */
    public function getUrl(): string
    {
        throw_unless(
            $this->storedPath,
            new InvalidArgumentException(
                'Cannot generate URL: File must be stored first.',
            ),
        );

        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->url($this->storedPath);
    }

    /**
     * Get the storage directory path.
     *
     * @return string The directory path
     */
    public function getDirectory(): string
    {
        return $this->buildVariantDirectory();
    }

    /**
     * Get storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get relative path for
     * @return string Storage-relative path (directory/filename)
     */
    public function getRelativePath(string $fileName): string
    {
        return $this->baseDirectory . "/{$fileName}";
    }

    /**
     * Get variant-aware storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get relative path for
     * @return string Variant storage-relative path (directory/variant/filename)
     */
    public function getVariantRelativePath(string $fileName): string
    {
        return $this->buildVariantDirectory() . "/{$fileName}";
    }

    /**
     * Get file contents as string.
     *
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function get(string $fileName): ?string
    {
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->get($this->baseDirectory . "/{$fileName}");
    }

    /**
     * Get variant-aware file contents as string.
     *
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function getVariant(string $fileName): ?string
    {
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->get($this->buildVariantDirectory() . "/{$fileName}");
    }

    /**
     * Get absolute or storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get path for
     * @return string|null File path or null if not accessible
     */
    public function getPath(string $fileName): ?string
    {
        $fullPath = $this->baseDirectory . "/{$fileName}";
        $disk = config('document.storage.disk', 'public');

        if (Storage::disk($disk)->getDriver()->getName() === 'local') {
            return Storage::disk($disk)->path($fullPath);
        }

        return $fullPath;
    }

    /**
     * Get variant-aware absolute or storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get path for
     * @return string|null File path or null if not accessible
     */
    public function getVariantPath(string $fileName): ?string
    {
        $fullPath = $this->buildVariantDirectory() . "/{$fileName}";
        $disk = config('document.storage.disk', 'public');

        if (Storage::disk($disk)->getDriver()->getName() === 'local') {
            return Storage::disk($disk)->path($fullPath);
        }

        return $fullPath;
    }

    /**
     * Delete a file from storage.
     *
     * @param  string  $fileName  The filename to delete
     * @return bool True if deletion was successful
     */
    public function delete(string $fileName): bool
    {
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->delete($this->baseDirectory . "/{$fileName}");
    }

    /**
     * Delete a variant file from storage.
     *
     * @param  string  $fileName  The filename to delete
     * @return bool True if deletion was successful
     */
    public function deleteVariant(string $fileName): bool
    {
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->delete($this->buildVariantDirectory() . "/{$fileName}");
    }

    /**
     * Get public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getFileUrl(string $fileName): string
    {
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->url($this->baseDirectory . "/{$fileName}");
    }

    /**
     * Get variant-aware public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getVariantFileUrl(string $fileName): string
    {
        $disk = config('document.storage.disk', 'public');

        return Storage::disk($disk)->url($this->buildVariantDirectory() . "/{$fileName}");
    }

    /**
     * Get secure URL for accessing a file with authentication.
     *
     * @param  string  $fileName  The filename to get secure URL for
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $type  The file type (documents, videos, etc.)
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(string $fileName, string $tenantId, string $type): string
    {
        return route('file.serve', [
            'tenant' => $tenantId,
            'type' => $type,
            'filename' => $fileName,
        ]);
    }

    /**
     * Get temporary URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get temporary URL for
     * @param  int  $expiresIn  Expiration time in seconds (default 30 minutes)
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(string $fileName, int $expiresIn = 1800): string
    {
        $disk = config('document.storage.disk', 'public');
        $path = $this->variant
            ? $this->buildVariantDirectory() . "/{$fileName}"
            : $this->baseDirectory . "/{$fileName}";

        return Storage::disk($disk)->temporaryUrl($path, now()->addSeconds($expiresIn));
    }

    /**
     * Build directory path including variant subdirectory.
     *
     * @return string The complete directory path with variant
     */
    private function buildVariantDirectory(): string
    {
        $directory = $this->baseDirectory;

        if (
            $this->variant
            && isset(self::VARIANT_DIRECTORIES[$this->variant])
        ) {
            $directory .= '/' . self::VARIANT_DIRECTORIES[$this->variant];
        }

        return $directory;
    }

    /**
     * Generate a unique filename for the image file.
     *
     * @return string The generated filename
     */
    private function generateFilename(): string
    {
        $extension = $this->file->extension();
        $timestamp = time();
        $unique = uniqid();

        return "{$unique}_{$timestamp}.{$extension}";
    }
}
