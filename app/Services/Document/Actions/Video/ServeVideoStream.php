<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * High-performance video streaming action optimized for VideoJS players.
 *
 * Features:
 * - HTTP Range request support for video seeking
 * - Optimized chunk sizes for progressive download
 * - Smart caching for video metadata
 * - VideoJS-specific optimizations
 * - Connection keep-alive optimization
 * - Proper CORS headers for video streaming
 * - Security validation for video files
 */
final class ServeVideoStream
{
    use AsAction;

    // Video-specific constants for optimal performance
    private const INITIAL_CHUNK_SIZE = 1 * 1024 * 1024; // 1MB for fast initial load

    private const STREAMING_CHUNK_SIZE = 4 * 1024 * 1024; // 4MB for efficient streaming

    private const MAX_CHUNK_SIZE = 16 * 1024 * 1024; // 16MB max chunk size

    private const METADATA_CACHE_TTL = 3600; // 1 hour cache for metadata

    private const CONTENT_CACHE_TTL = 5 * 60 * 60; // 5 hours cache for small videos

    private const MAX_CACHEABLE_SIZE = 50 * 1024 * 1024; // 50MB max cacheable video

    /**
     * Clear video metadata cache when file is updated.
     */
    public static function clearVideoCache(string $path, int $lastModified): void
    {
        $metadataCacheKey = 'video_metadata:' . md5($path . $lastModified);
        $contentCacheKey = 'video_content:' . md5($path . $lastModified);

        Cache::forget($metadataCacheKey);
        Cache::forget($contentCacheKey);
    }

    /**
     * Preload video metadata for better performance.
     */
    public static function preloadVideoMetadata(string $path, string $disk): void
    {
        try {
            $instance = new self();
            $instance->getVideoMetadata($path, $disk);
        } catch (Exception $e) {
            // Graceful failure for preloading
        }
    }

    /**
     * Handle video streaming with performance optimizations.
     */
    public function handle(
        Document $document,
        string $fileName,
        null|Request $request = null,
    ): Response|StreamedResponse {
        $directoryManager = DocumentDirectoryManager::make($document);
        $actualPath = $directoryManager->videos()->getPath($fileName);
        $diskName = $directoryManager->getDisk();

        // Debug logging to track file serving
        Log::info('ServeVideoStream: Attempting to serve video', [
            'documentId' => $document->id,
            'fileName' => $fileName,
            'actualPath' => $actualPath,
            'diskName' => $diskName,
            'fileExists' => Storage::disk($diskName)->exists($actualPath),
        ]);

        // Fast path: Use X-Sendfile if available for maximum performance
        if ($this->canUseXSendfile($diskName)) {
            return $this->handleXSendfileResponse($actualPath, $diskName, $request);
        }

        // Validate video file security (cached for performance)
        $this->validateVideoFile($actualPath, $diskName);

        // Get cached or fresh video metadata
        $metadata = $this->getVideoMetadata($actualPath, $diskName);

        // Handle range requests for video seeking
        if ($request && $request->hasHeader('Range')) {
            return $this->handleRangeRequest($actualPath, $diskName, $metadata, $request);
        }

        // Handle standard video request with optimization
        return $this->handleStandardVideoRequest($actualPath, $diskName, $metadata);
    }

    /**
     * Check if X-Sendfile can be used for maximum performance.
     */
    private function canUseXSendfile(string $disk): bool
    {
        // Only use X-Sendfile for local disk storage
        if ($disk !== 'local' || config('app.env') === 'testing') {
            return false;
        }

        // Check if we're running under nginx or Apache with X-Sendfile
        $serverSoftware = request()->server('SERVER_SOFTWARE', '');

        return (
            str_contains(strtolower($serverSoftware), 'nginx')
            || str_contains(strtolower($serverSoftware), 'apache')
            || request()->server('HTTP_X_SENDFILE_TYPE') !== null
        );
    }

    /**
     * Handle X-Sendfile response for maximum performance.
     */
    private function handleXSendfileResponse(
        string $path,
        string $disk,
        null|Request $request,
    ): Response {
        $fullPath = Storage::disk($disk)->path($path);

        // Minimal metadata for headers
        $size = filesize($fullPath);
        $lastModified = filemtime($fullPath);
        $etag = md5($path . $lastModified . $size);

        $headers = [
            'Content-Type' => 'video/mp4', // Assume mp4 for speed
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=86400',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'X-Sendfile' => $fullPath, // nginx: X-Accel-Redirect, Apache: X-Sendfile
            'X-Accel-Redirect' => '/protected/' . $path, // nginx internal redirect
        ];

        // Add VideoJS optimizations
        $headers = array_merge($headers, $this->getVideoJSHeaders());

        return response('', 200, $headers);
    }

    /**
     * Get VideoJS-specific headers for optimization.
     */
    private function getVideoJSHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Range, Accept-Encoding',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
            'Connection' => 'keep-alive',
            'Keep-Alive' => 'timeout=5, max=100',
        ];
    }

    /**
     * Validate video file for security and type checking with caching.
     */
    private function validateVideoFile(string $path, string $disk): void
    {
        // Fast security check first (no I/O)
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            abort(403, 'Invalid file path');
        }

        // Cache validation result to avoid repeated Storage API calls
        $validationCacheKey = 'video_validation:' . md5($path);
        $isValid = Cache::remember($validationCacheKey, 300, function () use ($path, $disk) { // 5 min cache
            if (!Storage::disk($disk)->exists($path)) {
                return false;
            }

            $mimeType = Storage::disk($disk)->mimeType($path);

            return $this->isValidVideoMimeType($mimeType);
        });

        if (!$isValid) {
            abort(404, 'Video file not found or invalid type');
        }
    }

    /**
     * Check if mime type is a valid video format.
     */
    private function isValidVideoMimeType(null|string $mimeType): bool
    {
        if (!$mimeType) {
            return false;
        }

        $validVideoTypes = [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/avi',
            'video/mov',
            'video/quicktime',
            'video/x-msvideo',
            'video/3gpp',
            'video/x-flv',
        ];

        return in_array($mimeType, $validVideoTypes, true);
    }

    /**
     * Get video metadata with caching optimization.
     */
    private function getVideoMetadata(string $path, string $disk): array
    {
        $size = Storage::disk($disk)->size($path);
        $lastModified = Storage::disk($disk)->lastModified($path);
        $mimeType = Storage::disk($disk)->mimeType($path);
        $etag = md5($path . $lastModified . $size);

        $cacheKey = 'video_metadata:' . md5($path . $lastModified);

        return Cache::remember($cacheKey, self::METADATA_CACHE_TTL, function () use (
            $path,
            $size,
            $lastModified,
            $mimeType,
            $etag,
        ) {
            // Try to extract additional video metadata if FFmpeg is available
            $duration = null;
            $bitrate = null;

            try {
                $videoMetadata = ExtractVideoMetadata::run($path);
                $duration = $videoMetadata['duration'] ?? null;
                if ($duration && $size) {
                    $bitrate = (int) (($size * 8) / $duration); // bits per second
                }
            } catch (Exception $e) {
                // Fallback gracefully if metadata extraction fails
            }

            return [
                'size' => $size,
                'last_modified' => $lastModified,
                'mime_type' => $mimeType,
                'etag' => $etag,
                'duration' => $duration,
                'bitrate' => $bitrate,
            ];
        });
    }

    /**
     * Handle HTTP Range requests for video seeking.
     */
    private function handleRangeRequest(
        string $path,
        string $disk,
        array $metadata,
        Request $request,
    ): StreamedResponse {
        $rangeHeader = $request->header('Range');
        $fileSize = $metadata['size'];

        // Parse range header (format: bytes=start-end)
        if (!preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            abort(416, 'Invalid range request');
        }

        $start = $matches[1] !== '' ? (int) $matches[1] : 0;
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // Validate range
        if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
            abort(416, 'Range not satisfiable');
        }

        $contentLength = $end - $start + 1;
        // Use larger chunks for range requests to improve seeking performance
        $chunkSize = min(self::MAX_CHUNK_SIZE, max(self::STREAMING_CHUNK_SIZE, $contentLength));

        $headers = $this->getVideoStreamHeaders($metadata, true);
        $headers['Content-Length'] = (string) $contentLength;
        $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";

        return response()->stream(
            function () use ($path, $disk, $start, $end, $chunkSize) {
                // Use direct file access for maximum seek performance
                if ($disk === 'local') {
                    $this->streamRangeDirectly(
                        Storage::disk($disk)->path($path),
                        $start,
                        $end,
                        $chunkSize,
                    );
                } else {
                    $this->streamRangeFromStorage($path, $disk, $start, $end, $chunkSize);
                }
            },
            206,
            $headers,
        );
    }

    /**
     * Handle standard video request (non-range).
     */
    private function handleStandardVideoRequest(
        string $path,
        string $disk,
        array $metadata,
    ): Response|StreamedResponse {
        $fileSize = $metadata['size'];

        // For small videos, consider full caching
        if ($fileSize < self::MAX_CACHEABLE_SIZE) {
            $content = $this->getCachedVideoContent($path, $disk, $metadata);
            if ($content !== null) {
                return response($content, 200, $this->getVideoStreamHeaders($metadata));
            }
        }

        // Stream the video with optimized chunks
        $chunkSize = $this->getOptimalChunkSize($fileSize, $metadata);
        $headers = $this->getVideoStreamHeaders($metadata);

        return response()->stream(
            function () use ($path, $disk, $chunkSize) {
                // Use direct file access for maximum performance
                if ($disk === 'local') {
                    $fullPath = Storage::disk($disk)->path($path);
                    $this->streamFileDirectly($fullPath, $chunkSize);
                } else {
                    // Fallback for cloud storage
                    $this->streamFromStorage($path, $disk, $chunkSize);
                }
            },
            200,
            $headers,
        );
    }

    /**
     * Stream file directly using native PHP for maximum performance.
     */
    private function streamFileDirectly(string $fullPath, int $chunkSize): void
    {
        $handle = fopen($fullPath, 'rb');
        if (!$handle) {
            return;
        }

        try {
            // Disable output buffering for maximum speed
            if (ob_get_level()) {
                ob_end_clean();
            }

            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;

                // Only flush every few chunks to reduce I/O overhead
                static $flushCounter = 0;
                if ((++$flushCounter % 4) === 0) { // Flush every 4 chunks
                    flush();
                }

                // Check for client disconnect
                if (connection_aborted()) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Stream from Laravel Storage (fallback for cloud storage).
     */
    private function streamFromStorage(string $path, string $disk, int $chunkSize): void
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (!$stream) {
            return;
        }

        try {
            // Disable output buffering for maximum speed
            if (ob_get_level()) {
                ob_end_clean();
            }

            while (!feof($stream)) {
                $chunk = fread($stream, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;

                // Only flush every few chunks to reduce I/O overhead
                static $flushCounter = 0;
                if ((++$flushCounter % 4) === 0) { // Flush every 4 chunks
                    flush();
                }

                // Check for client disconnect
                if (connection_aborted()) {
                    break;
                }
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * Stream range directly from file system for maximum seek performance.
     */
    private function streamRangeDirectly(
        string $fullPath,
        int $start,
        int $end,
        int $chunkSize,
    ): void {
        $handle = fopen($fullPath, 'rb');
        if (!$handle) {
            return;
        }

        try {
            // Disable output buffering completely
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Seek to start position - critical for video seeking performance
            if ($start > 0 && fseek($handle, $start) !== 0) {
                return; // Seek failed
            }

            $bytesToRead = $end - $start + 1;
            $bytesRead = 0;

            while ($bytesRead < $bytesToRead && !feof($handle)) {
                $readSize = min($chunkSize, $bytesToRead - $bytesRead);
                $chunk = fread($handle, $readSize);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;
                $bytesRead += strlen($chunk);

                // Minimal flushing for range requests (seeking needs to be instant)
                static $flushCounter = 0;
                if ((++$flushCounter % 8) === 0) { // Flush every 8 chunks for ranges
                    flush();
                }

                // Check for client disconnect
                if (connection_aborted()) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Stream range from Laravel Storage (fallback for cloud storage).
     */
    private function streamRangeFromStorage(
        string $path,
        string $disk,
        int $start,
        int $end,
        int $chunkSize,
    ): void {
        $stream = Storage::disk($disk)->readStream($path);
        if (!$stream) {
            return;
        }

        try {
            // Disable output buffering completely
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Seek to start position
            if ($start > 0 && fseek($stream, $start) !== 0) {
                fclose($stream);

                return; // Seek failed
            }

            $bytesToRead = $end - $start + 1;
            $bytesRead = 0;

            while ($bytesRead < $bytesToRead && !feof($stream)) {
                $readSize = min($chunkSize, $bytesToRead - $bytesRead);
                $chunk = fread($stream, $readSize);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;
                $bytesRead += strlen($chunk);

                // Minimal flushing for range requests
                static $flushCounter = 0;
                if ((++$flushCounter % 8) === 0) { // Flush every 8 chunks for ranges
                    flush();
                }

                // Check for client disconnect
                if (connection_aborted()) {
                    break;
                }
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * Get cached video content for small files.
     */
    private function getCachedVideoContent(string $path, string $disk, array $metadata): null|string
    {
        $cacheKey = 'video_content:' . md5($path . $metadata['last_modified']);

        return Cache::remember($cacheKey, self::CONTENT_CACHE_TTL, function () use (
            $path,
            $disk,
            $metadata,
        ) {
            // Only cache if file is small enough
            if ($metadata['size'] >= self::MAX_CACHEABLE_SIZE) {
                return null;
            }

            try {
                return Storage::disk($disk)->get($path);
            } catch (Exception $e) {
                return null;
            }
        });
    }

    /**
     * Get optimal chunk size based on file size and metadata.
     */
    private function getOptimalChunkSize(int $fileSize, array $metadata): int
    {
        // For initial requests, use smaller chunks for faster start
        if ($fileSize < (10 * 1024 * 1024)) { // < 10MB
            return self::INITIAL_CHUNK_SIZE;
        }

        // For larger files, use dynamic chunk sizing based on bitrate
        if (isset($metadata['bitrate']) && $metadata['bitrate'] > 0) {
            // Calculate chunk size for ~2 seconds of video content
            $optimalSize = (int) (($metadata['bitrate'] / 8) * 2);

            return min(max($optimalSize, self::STREAMING_CHUNK_SIZE), self::MAX_CHUNK_SIZE);
        }

        // Default to standard streaming chunk size
        return self::STREAMING_CHUNK_SIZE;
    }

    /**
     * Get video streaming headers optimized for VideoJS.
     */
    private function getVideoStreamHeaders(array $metadata, bool $isPartialContent = false): array
    {
        $headers = [
            'Content-Type' => $metadata['mime_type'],
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => $isPartialContent
                ? 'no-cache'
                : 'private, max-age=86400, must-revalidate',
            'ETag' => '"' . $metadata['etag'] . '"',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $metadata['last_modified']) . ' GMT',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Connection' => 'keep-alive',
            'Keep-Alive' => 'timeout=10, max=1000',
            // VideoJS-specific optimizations
            'X-Content-Duration' => (string) ($metadata['duration'] ?? 0),
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Range, Content-Length',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
        ];

        // Add content length for non-partial content
        if (!$isPartialContent) {
            $headers['Content-Length'] = (string) $metadata['size'];
        }

        // Add bitrate hint if available
        if (isset($metadata['bitrate']) && $metadata['bitrate'] > 0) {
            $headers['X-Content-Bitrate'] = (string) $metadata['bitrate'];
        }

        return $headers;
    }
}
