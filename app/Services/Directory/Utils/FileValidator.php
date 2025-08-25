<?php

declare(strict_types=1);

namespace App\Services\Directory\Utils;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * File Validator Utility
 * 
 * Provides comprehensive file validation using Laravel's File facade
 * and security best practices.
 */
final class FileValidator
{
    /**
     * Validate that a file path exists and is readable.
     *
     * @param string $path File path to validate
     * @throws InvalidArgumentException If file doesn't exist or isn't readable
     */
    public static function validatePath(string $path): void
    {
        throw_unless(File::exists($path), new InvalidArgumentException("File does not exist: {$path}"));
        throw_unless(File::isReadable($path), new InvalidArgumentException("File is not readable: {$path}"));
    }
    
    /**
     * Validate uploaded file meets basic requirements.
     *
     * @param UploadedFile $file File to validate
     * @param array<string> $allowedExtensions Allowed file extensions
     * @param int|null $maxSizeBytes Maximum file size in bytes
     * @throws InvalidArgumentException If validation fails
     */
    public static function validateUploadedFile(
        UploadedFile $file, 
        array $allowedExtensions = [], 
        ?int $maxSizeBytes = null
    ): void {
        throw_unless($file->isValid(), new InvalidArgumentException('Uploaded file is not valid'));
        
        if (!empty($allowedExtensions)) {
            $extension = strtolower($file->extension());
            $allowedExtensions = array_map('strtolower', $allowedExtensions);
            
            throw_unless(
                in_array($extension, $allowedExtensions, true), 
                new InvalidArgumentException("File extension '{$extension}' is not allowed")
            );
        }
        
        if ($maxSizeBytes !== null) {
            throw_unless(
                $file->getSize() <= $maxSizeBytes, 
                new InvalidArgumentException("File size exceeds maximum allowed size")
            );
        }
    }
    
    /**
     * Validate that a path is safe for file operations.
     *
     * @param string $path Path to validate
     * @throws InvalidArgumentException If path is unsafe
     */
    public static function validateSafePath(string $path): void
    {
        throw_unless(
            PathBuilder::isSafe($path), 
            new InvalidArgumentException("Unsafe path detected: {$path}")
        );
    }
    
    /**
     * Get file MIME type using Laravel's File facade.
     *
     * @param string $path File path
     * @return string|false MIME type or false if cannot be determined
     */
    public static function getMimeType(string $path): string|false
    {
        self::validatePath($path);
        return File::mimeType($path);
    }
    
    /**
     * Check if file is an image based on MIME type.
     *
     * @param string $path File path
     * @return bool True if file is an image
     */
    public static function isImage(string $path): bool
    {
        $mimeType = self::getMimeType($path);
        return $mimeType !== false && str_starts_with($mimeType, 'image/');
    }
    
    /**
     * Check if file is a video based on MIME type.
     *
     * @param string $path File path
     * @return bool True if file is a video
     */
    public static function isVideo(string $path): bool
    {
        $mimeType = self::getMimeType($path);
        return $mimeType !== false && str_starts_with($mimeType, 'video/');
    }
}