<?php

declare(strict_types=1);

namespace App\Services\Directory\Utils;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Filename Generator Utility
 * 
 * Provides consistent filename generation across all storage strategies
 * using Laravel's File facade and helper functions.
 */
final class FilenameGenerator
{
    /**
     * Generate a unique filename for uploaded files.
     *
     * @param UploadedFile $file The uploaded file
     * @param string|null $prefix Optional prefix for the filename
     * @return string Generated unique filename
     */
    public static function generateFromUpload(UploadedFile $file, ?string $prefix = null): string
    {
        $extension = $file->extension();
        $timestamp = now()->timestamp;
        $unique = Str::random(8);
        
        $filename = $prefix 
            ? "{$prefix}_{$unique}_{$timestamp}.{$extension}"
            : "{$unique}_{$timestamp}.{$extension}";
            
        return $filename;
    }
    
    /**
     * Generate a unique filename from a file path.
     *
     * @param string $filePath Path to the file
     * @param string|null $prefix Optional prefix for the filename
     * @return string Generated unique filename
     */
    public static function generateFromPath(string $filePath, ?string $prefix = null): string
    {
        $extension = File::extension($filePath);
        $timestamp = now()->timestamp;
        $unique = Str::random(8);
        
        $filename = $prefix 
            ? "{$prefix}_{$unique}_{$timestamp}.{$extension}"
            : "{$unique}_{$timestamp}.{$extension}";
            
        return $filename;
    }
    
    /**
     * Generate a hash-based filename for consistent naming.
     *
     * @param string $input Input string to hash
     * @param string $extension File extension
     * @param string|null $prefix Optional prefix
     * @return string Generated filename
     */
    public static function generateHashBased(string $input, string $extension, ?string $prefix = null): string
    {
        $hash = Str::substr(hash('sha256', $input), 0, 16);
        $timestamp = now()->timestamp;
        
        $filename = $prefix 
            ? "{$prefix}_{$hash}_{$timestamp}.{$extension}"
            : "{$hash}_{$timestamp}.{$extension}";
            
        return $filename;
    }
    
    /**
     * Sanitize and normalize a filename.
     *
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    public static function sanitize(string $filename): string
    {
        // Remove directory traversal attempts and normalize
        $filename = basename($filename);
        
        // Replace spaces and special characters
        $filename = Str::slug(File::name($filename)) . '.' . File::extension($filename);
        
        return $filename;
    }
}