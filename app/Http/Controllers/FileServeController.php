<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileServeController extends Controller
{
    /**
     * Clear cached file content when file is deleted/updated.
     */
    public static function clearFileCache(string $path, int $lastModified): void
    {
        $cacheKey = 'file_content:' . md5($path . $lastModified);
        Cache::forget($cacheKey);
    }

    /**
     * Check if file is small enough for direct response.
     */
    private function isSmallFile(int $size): bool
    {
        // Files under 10MB
        return $size < 10 * 1024 * 1024;
    }

    /**
     * Check if file is an image.
     */
    private function isImageFile(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Get file content with server-side caching for small files.
     */
    private function getCachedFileContent(string $path, string $disk, int $lastModified): string
    {
        // Create cache key based on path and modification time
        $cacheKey = 'file_content:' . md5($path . $lastModified);

        // Try to get from cache first
        $content = Cache::get($cacheKey);

        if ($content === null) {
            // Not in cache, load from storage
            $content = Storage::disk($disk)->get($path);

            // Cache for 1 hour (only cache small files to avoid memory issues)
            $fileSize = Storage::disk($disk)->size($path);
            if ($fileSize < 5 * 1024 * 1024) { // Only cache files under 5MB
                Cache::put($cacheKey, $content, 3600); // 1 hour cache
            }
        }

        return $content;
    }

    /**
     * Handle HTTP range requests for video streaming.
     */
    private function handleRangeRequest(
        Request $request,
        string $disk,
        string $path,
        int $size,
        string $mimeType,
        array $headers,
    ): Response|StreamedResponse {
        $rangeHeader = $request->header('Range');

        // Parse range header (e.g., "bytes=0-1024" or "bytes=1024-")
        if (preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            $start = $matches[1] === '' ? 0 : intval($matches[1]);
            $end = $matches[2] === '' ? $size - 1 : intval($matches[2]);

            // Ensure valid range
            $start = max(0, min($start, $size - 1));
            $end = max($start, min($end, $size - 1));

            $contentLength = $end - $start + 1;

            // Update headers for partial content
            $headers['Content-Length'] = $contentLength;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";

            // Stream the requested range
            return response()->stream(function () use ($disk, $path, $start, $contentLength) {
                $stream = Storage::disk($disk)->readStream($path);
                if ($stream) {
                    fseek($stream, $start);
                    echo fread($stream, $contentLength);
                    fclose($stream);
                }
            }, 206, $headers); // 206 Partial Content
        }

        // Invalid range, return full file
        return Storage::disk($disk)->response($path, basename($path), $headers);
    }

    /**
     * Serve files with authentication and tenant isolation.
     */
    public function __invoke(
        Request $request,
        string $path,
    ): Response|StreamedResponse|RedirectResponse {
        // Verify user is authenticated using Filament's authentication
        if (! auth()->check()) {
            // Redirect to Filament login instead of throwing 401
            return redirect()->route('filament.portal.auth.login');
        }

        // Parse the path to extract tenant and validate access
        // Path format: {hashedTenantId}/documents/{documentId}/{type}/{filename}
        $pathParts = explode('/', $path);
        if (count($pathParts) < 4) {
            abort(400, 'Invalid file path');
        }

        $tenantFromPath = $pathParts[0];
        $documentId = $pathParts[2]; // extract document ID from path
        $type = $pathParts[count($pathParts) - 2]; // second to last part
        $filename = $pathParts[count($pathParts) - 1]; // last part

        // Verify user has access to this tenant
        $userTenantId = auth()->user()->getActiveTenantId();
        $hashedUserTenantId = (string) LaraPath::base($userTenantId, SanitizationStrategy::HASHED);

        if ($hashedUserTenantId !== $tenantFromPath) {
            abort(403, 'Access denied to this tenant');
        }

        // Validate file type (handle both direct types and nested video types)
        $allowedTypes = ['images', 'videos', 'documents', 'prev'];
        if (! in_array($type, $allowedTypes)) {
            abort(400, 'Invalid file type');
        }

        // Build the actual storage path using hashed document ID (to match DocumentStorageStrategy)
        $hashedDocumentId = md5($documentId);
        $actualPath = "{$tenantFromPath}/documents/{$hashedDocumentId}/{$type}/{$filename}";
        $disk = config('document.storage.disk', 'public');

        // Check if file exists
        if (! Storage::disk($disk)->exists($actualPath)) {
            abort(404, 'File not found');
        }

        // Get file details
        $mimeType = Storage::disk($disk)->mimeType($actualPath);
        $size = Storage::disk($disk)->size($actualPath);

        // Get file modification time for ETag and Last-Modified headers
        $lastModified = Storage::disk($disk)->lastModified($actualPath);
        $etag = md5($actualPath . $lastModified . $size);

        // Check if client has cached version
        $clientEtag = $request->header('If-None-Match');
        $clientLastModified = $request->header('If-Modified-Since');

        if ($clientEtag === $etag ||
            ($clientLastModified && strtotime($clientLastModified) >= $lastModified)) {
            return response('', 304);
        }

        // Set appropriate headers with improved caching
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Cache-Control' => 'private, max-age=86400, must-revalidate', // 24 hour cache with validation
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Accept-Ranges' => 'bytes', // Enable range requests for video streaming
        ];

        // Handle range requests for video streaming
        $rangeHeader = $request->header('Range');
        if ($rangeHeader && str_starts_with($mimeType, 'video/')) {
            return $this->handleRangeRequest($request, $disk, $actualPath, $size, $mimeType, $headers);
        }

        // For images and small files, return direct response with caching
        if ($this->isSmallFile($size) || $this->isImageFile($mimeType)) {
            $content = $this->getCachedFileContent($actualPath, $disk, $lastModified);

            return response($content, 200, $headers);
        }

        // For large files (videos), use streamed response for better performance
        return Storage::disk($disk)->response($actualPath, $filename, $headers);
    }
}
