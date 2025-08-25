<?php

declare(strict_types=1);

namespace App\Services\Directory\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Path Builder Utility
 * 
 * Provides consistent path building and manipulation using Laravel's File facade
 * and string helpers for better maintainability and consistency.
 */
final class PathBuilder
{
    /**
     * Build a path from array of segments.
     *
     * @param array<string> $segments Path segments
     * @return string Built path with proper separators
     */
    public static function build(array $segments): string
    {
        return collect($segments)
            ->filter()
            ->map(fn(string $segment) => trim($segment, '/'))
            ->filter()
            ->implode('/');
    }
    
    /**
     * Join path segments with forward slashes.
     *
     * @param string ...$segments Variable number of path segments
     * @return string Joined path
     */
    public static function join(string ...$segments): string
    {
        return self::build($segments);
    }
    
    /**
     * Create a secure hash-based directory name.
     *
     * @param string $input Input to hash
     * @param string $algorithm Hash algorithm (default: md5)
     * @return string Hashed directory name
     */
    public static function createSecureDirectoryName(string $input, string $algorithm = 'md5'): string
    {
        return hash($algorithm, $input);
    }
    
    /**
     * Extract filename from a path using Laravel's File facade.
     *
     * @param string $path Full path
     * @return string Filename only
     */
    public static function extractFilename(string $path): string
    {
        return File::basename($path);
    }
    
    /**
     * Get the last segment from a path (similar to afterLast).
     *
     * @param string $path Full path
     * @return string Last path segment
     */
    public static function getLastSegment(string $path): string
    {
        return Str::of($path)->afterLast('/')->toString();
    }
    
    /**
     * Normalize a path by removing duplicate slashes and ensuring proper format.
     *
     * @param string $path Path to normalize
     * @return string Normalized path
     */
    public static function normalize(string $path): string
    {
        // Remove duplicate slashes and normalize
        $normalized = preg_replace('#/+#', '/', $path);
        
        // Remove trailing slash unless it's the root
        return $normalized === '/' ? $normalized : rtrim($normalized, '/');
    }
    
    /**
     * Check if a path is safe (no directory traversal).
     *
     * @param string $path Path to check
     * @return bool True if path is safe
     */
    public static function isSafe(string $path): bool
    {
        return !Str::contains($path, ['../', '..\\', '../', '..\\']);
    }
}