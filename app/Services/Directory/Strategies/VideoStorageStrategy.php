<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\StorageStrategyContract;
use App\Services\Video\Video;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

/**
 * Video Storage Strategy.
 *
 * Handles video file storage with thumbnail support.
 * Stores videos and thumbnails in organized subdirectories.
 */
final class VideoStorageStrategy implements StorageStrategyContract
{
    private const string ROOT_DIRECTORY = 'videos';

    private ?UploadedFile $file = null;

    private ?string $storedPath = null;

    private PathBuilder $pathBuilder;

    /**
     * Constructor receives a base directory path for flexible path building.
     *
     * @param  PathBuilder|string  $basePath  PathBuilder instance or base directory string
     */
    public function __construct(
        PathBuilder|string $basePath,
        private readonly string $disk,
    ) {
        if ($basePath instanceof PathBuilder) {
            $this->pathBuilder = $basePath;
        } else {
            // Check if the base path already ends with 'videos' to avoid duplication
            if (str_ends_with($basePath, '/videos') || str_ends_with($basePath, 'videos')) {
                $this->pathBuilder = LaraPath::base($basePath)->validate();
            } else {
                $this->pathBuilder = LaraPath::base(
                    $basePath,
                )->add(self::ROOT_DIRECTORY)->validate();
            }
        }
    }

    /**
     * Create a new instance with 'src' directory for original/source video files.
     */
    public function asOriginal(): self
    {
        return new self($this->pathBuilder->add('src'));
    }

    /**
     * Create a new instance with 'proc' directory for converted video files.
     */
    public function asConversions(): self
    {
        return new self($this->pathBuilder->add('proc'));
    }

    /**
     * Create a new instance with 'prev' directory for video thumbnails.
     */
    public function asThumbnails(): self
    {
        return new self($this->pathBuilder->add('prev'));
    }

    /**
     * Create a new instance with 'temp' directory for video previews/clips.
     */
    public function asPreviews(): self
    {
        return new self($this->pathBuilder->add('temp'));
    }

    /**
     * Create a new instance with 'seg' directory for video clips/segments.
     */
    public function asClips(): self
    {
        return new self($this->pathBuilder->add('seg'));
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
        $directory = $this->pathBuilder->toString();
        $disk = $this->disk;

        $this->storedPath = $file->storeAs($directory, $filename, [
            'disk' => $disk,
        ]);

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
        throw_unless(
            file_exists($filePath),
            new InvalidArgumentException(
                "Thumbnail file does not exist: {$filePath}",
            ),
        );

        $thumbnailDirectory = $this->pathBuilder->toString();
        $filename = $this->generateThumbnailFilename($filePath);

        $disk = $this->disk;

        $this->storedPath = Storage::disk($disk)->putFileAs(
            $thumbnailDirectory,
            new File($filePath),
            $filename,
        );

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

        $disk = $this->disk;

        return Storage::disk($disk)->url($this->storedPath);
    }

    /**
     * Get the storage directory path.
     *
     * @return string The directory path
     */
    public function getDirectory(): string
    {
        return $this->pathBuilder->toString();
    }

    public function getDirectoryForVideo(Video $video): string
    {
        return $this->pathBuilder->add($video->getFileNameWithoutExt())->toString();
    }

    /**
     * Get storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get relative path for
     * @return string Storage-relative path (directory/filename)
     */
    public function getRelativePath(string $fileName): string
    {
        return $this->pathBuilder->add($fileName)->toString();
    }

    /**
     * Get variant-aware storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get relative path for
     * @return string Variant storage-relative path (directory/variant/filename)
     */
    public function getVariantRelativePath(string $fileName): string
    {
        return (clone $this->pathBuilder)->add($fileName)->toString();
    }

    /**
     * Get file contents as string.
     *
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function get(string $fileName): ?string
    {
        $path = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->get($path);
    }

    /**
     * Get variant-aware file contents as string.
     *
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function getVariant(string $fileName): ?string
    {
        $path = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->get($path);
    }

    /**
     * Get absolute or storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get path for
     * @return string|null File path or null if not accessible
     */
    public function getPath(string $fileName): ?string
    {
        $fullPath = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

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
        $fullPath = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

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
        $path = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->delete($path);
    }

    /**
     * Delete a variant file from storage.
     *
     * @param  string  $fileName  The filename to delete
     * @return bool True if deletion was successful
     */
    public function deleteVariant(string $fileName): bool
    {
        $path = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->delete($path);
    }

    /**
     * Get public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getFileUrl(string $fileName): string
    {
        $path = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->url($path);
    }

    /**
     * Get variant-aware public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getVariantFileUrl(string $fileName): string
    {
        $path = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->url($path);
    }

    /**
     * Get secure URL for accessing a file with authentication.
     *
     * @param  string  $fileName  The filename to get secure URL for
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $type  The file type (documents, videos, etc.)
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(
        string $route,
        string $fileName,
        string $tenantId,
        string $type,
    ): string {
        return route($route, [
            'tenant' => $tenantId,
            'type' => $type,
            'filename' => $fileName,
        ]);
    }

    /**
     * Get temporary URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get temporary URL for
     * @param  int  $expiresIn  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(string $fileName, int $expiresIn = 1800): string
    {
        $fullPath = (clone $this->pathBuilder)->add($fileName)->toString();
        $disk = $this->disk;

        return Storage::disk($disk)->temporaryUrl(
            $fullPath,
            now()->addSeconds($expiresIn),
        );
    }

    /**
     * Generate a secure unique filename for the video file.
     *
     * @return string The generated secure filename
     */
    private function generateFilename(): string
    {
        $extension = $this->file->extension();
        $timestamp = time();
        $unique = uniqid();
        $originalName = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);

        $filename = "{$originalName}_{$unique}_{$timestamp}.{$extension}";

        return PathBuilder::base('')
            ->addFile($filename, SanitizationStrategy::SLUG)
            ->getFilename();
    }

    /**
     * Generate a secure unique filename for the thumbnail file.
     *
     * @param  string  $filePath  The original file path
     * @return string The generated secure thumbnail filename
     */
    private function generateThumbnailFilename(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $timestamp = time();
        $unique = uniqid();
        $originalName = pathinfo($filePath, PATHINFO_FILENAME);

        $filename = "thumb_{$originalName}_{$unique}_{$timestamp}.{$extension}";

        return PathBuilder::base('')
            ->addFile($filename, SanitizationStrategy::SLUG)
            ->getFilename();
    }
}
