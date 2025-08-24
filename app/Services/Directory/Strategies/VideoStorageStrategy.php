<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\StorageStrategyContract;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Video Storage Strategy.
 *
 * Handles video file storage with thumbnail support.
 * Stores videos and thumbnails in organized subdirectories.
 */
final class VideoStorageStrategy implements StorageStrategyContract
{
    // Obfuscated directory names - don't expose actual purpose
    private const array VARIANT_DIRECTORIES = [
        'original' => 'src',         // source files
        'conversions' => 'proc',     // processed/converted files
        'thumbnails' => 'prev',      // preview files
        'previews' => 'temp',        // temporary/preview files
        'clips' => 'seg',            // segments/clips
    ];

    private ?UploadedFile $file = null;

    private ?string $storedPath = null;

    private ?string $variant = null;

    /**
     * Constructor receives the full base directory path for videos.
     *
     * @param  string  $baseDirectory  The full path to the video storage directory
     */
    public function __construct(private readonly string $baseDirectory) {}

    /**
     * Set variant for original/source video files.
     */
    public function asOriginal(): self
    {
        $this->variant = 'original';

        return $this;
    }

    /**
     * Set variant for converted video files (different resolutions, formats, etc.).
     */
    public function asConversions(): self
    {
        $this->variant = 'conversions';

        return $this;
    }

    /**
     * Set variant for video thumbnails.
     */
    public function asThumbnails(): self
    {
        $this->variant = 'thumbnails';

        return $this;
    }

    /**
     * Set variant for video previews/clips.
     */
    public function asPreviews(): self
    {
        $this->variant = 'previews';

        return $this;
    }

    /**
     * Set variant for video clips/segments.
     */
    public function asClips(): self
    {
        $this->variant = 'clips';

        return $this;
    }

    /**
     * Store a video file.
     *
     * @param  UploadedFile  $file  The video file to store
     * @return string The stored file path
     */
    public function store(UploadedFile $file): string
    {
        $this->file = $file;

        $filename = $this->generateFilename();
        $directory = $this->variant ? $this->buildVariantDirectory() : $this->baseDirectory;
        $this->storedPath = $file->storeAs($directory, $filename);

        return $this->storedPath;
    }

    /**
     * Save a video thumbnail from a file path.
     *
     * @param  string  $filePath  Path to the thumbnail file
     * @return string The stored thumbnail path
     *
     * @throws InvalidArgumentException If thumbnail file doesn't exist
     */
    public function saveVideoThumbnail(string $filePath): string
    {
        throw_unless(file_exists($filePath), new InvalidArgumentException("Thumbnail file does not exist: {$filePath}"));

        $thumbnailDirectory = $this->baseDirectory . '/thumbnail';
        $filename = $this->generateThumbnailFilename($filePath);

        $this->storedPath = Storage::putFileAs($thumbnailDirectory, new File($filePath), $filename);

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
        throw_unless($this->storedPath, new InvalidArgumentException('Cannot generate URL: File must be stored first.'));

        return Storage::url($this->storedPath);
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
        return Storage::get($this->baseDirectory . "/{$fileName}");
    }

    /**
     * Get variant-aware file contents as string.
     *
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function getVariant(string $fileName): ?string
    {
        return Storage::get($this->buildVariantDirectory() . "/{$fileName}");
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

        if (Storage::getDefaultDriver() === 'local') {
            return Storage::path($fullPath);
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

        if (Storage::getDefaultDriver() === 'local') {
            return Storage::path($fullPath);
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
        return Storage::delete($this->baseDirectory . "/{$fileName}");
    }

    /**
     * Delete a variant file from storage.
     *
     * @param  string  $fileName  The filename to delete
     * @return bool True if deletion was successful
     */
    public function deleteVariant(string $fileName): bool
    {
        return Storage::delete($this->buildVariantDirectory() . "/{$fileName}");
    }

    /**
     * Get public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getFileUrl(string $fileName): string
    {
        return Storage::url($this->baseDirectory . "/{$fileName}");
    }

    /**
     * Get variant-aware public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getVariantFileUrl(string $fileName): string
    {
        return Storage::url($this->buildVariantDirectory() . "/{$fileName}");
    }

    /**
     * Build directory path including variant subdirectory.
     *
     * @return string The complete directory path with variant
     */
    private function buildVariantDirectory(): string
    {
        $directory = $this->baseDirectory;

        if ($this->variant && isset(self::VARIANT_DIRECTORIES[$this->variant])) {
            $directory .= '/' . self::VARIANT_DIRECTORIES[$this->variant];
        }

        return $directory;
    }

    /**
     * Generate a unique filename for the video file.
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

    /**
     * Generate a unique filename for the thumbnail file.
     *
     * @param  string  $filePath  The original file path
     * @return string The generated thumbnail filename
     */
    private function generateThumbnailFilename(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $timestamp = time();
        $unique = uniqid();

        return "thumb_{$unique}_{$timestamp}.{$extension}";
    }
}
