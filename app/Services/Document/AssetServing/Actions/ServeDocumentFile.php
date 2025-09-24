<?php

declare(strict_types=1);

namespace App\Services\Document\AssetServing\Actions;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\AssetUploading\Video\ServeVideoStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ServeDocumentFile
{
    use AsAction;

    /**
     * Clear cached file content when file is deleted/updated.
     */
    public static function clearFileCache(string $path, int $lastModified): void
    {
        $cacheKey = 'file_content:' . md5($path . $lastModified);
        Cache::forget($cacheKey);
    }

    /**
     * Serve file with generalized parameters.
     * Delegates video requests to optimized ServerVideoStream action.
     */
    public function handle(
        Document $document,
        string $type,
        string $fileName,
    ): Response|StreamedResponse|RedirectResponse {
        // Delegate video requests to specialized video streaming action
        if ($type === 'videos') {
            return ServeVideoStream::run($document, $fileName);
        }

        // Handle images and other file types with existing logic
        $directoryManager = DocumentDirectoryManager::make($document);
        $actualPath = $directoryManager->images()->getPath($fileName);
        $diskName = $directoryManager->getDisk();

        // Get file details
        $mimeType = Storage::disk($diskName)->mimeType($actualPath);
        $size = Storage::disk($diskName)->size($actualPath);
        $lastModified = Storage::disk($diskName)->lastModified($actualPath);
        $etag = md5($fileName . $lastModified . $size);

        // Set headers
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Cache-Control' => 'private, max-age=86400, must-revalidate',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Accept-Ranges' => 'bytes',
        ];

        // For images and small files, return direct response with caching
        if ($this->isSmallFile($size) || $this->isImageFile($mimeType)) {
            $content = $this->getCachedFileContent($actualPath, $diskName, $lastModified);

            return response($content, 200, $headers);
        }

        // For large non-video files, use basic streaming
        return $this->streamFileResponse($actualPath, $diskName, $headers);
    }

    /**
     * Handle HTTP controller request with authentication.
     */
    public function asController(
        Request $request,
        string $document,
        string $type,
        string $filename,
    ): Response|StreamedResponse|RedirectResponse {
        // Verify user is authenticated using Filament's authentication
        if (!auth()->check()) {
            return redirect()->route('filament.portal.auth.login');
        }

        $document = Document::whereKey($document)->first();
        if (!$this->validateDocumentAccess($document, $request)) {
            abort(401);
        }

        // For video requests, delegate to specialized video streaming action with request context
        if ($type === 'videos') {
            return ServeVideoStream::run($document, $filename, $request);
        }

        // Handle other file types with existing logic
        return $this->handle($document, $type, $filename);
    }

    private function validateDocumentAccess(Document $document, $request)
    {
        $user = $request->user();

        return (
            $user->isAssignedTo($document)
            && $document->getTenantId() === $user->getActiveTenantId()
        );
    }

    /**
     * Check if file is small enough for direct response.
     */
    private function isSmallFile(int $size): bool
    {
        return $size < (10 * 1024 * 1024); // Files under 10MB
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
        $cacheKey = 'file_content:' . md5($path . $lastModified);
        $content = Cache::get($cacheKey);

        if ($content === null) {
            $content = Storage::disk($disk)->get($path);
            $fileSize = Storage::disk($disk)->size($path);

            if ($fileSize < (5 * 1024 * 1024)) { // Only cache files under 5MB
                Cache::put($cacheKey, $content, 3600); // 1 hour cache
            }
        }

        return $content;
    }

    /**
     * Stream file response with small chunks for faster initial load.
     */
    private function streamFileResponse(
        string $path,
        string $disk,
        array $headers,
    ): StreamedResponse {
        return response()->stream(
            function () use ($path, $disk) {
                $stream = Storage::disk($disk)->readStream($path);
                $chunkSize = 8192; // 8KB chunks for fast initial response

                while (!feof($stream)) {
                    $chunk = fread($stream, $chunkSize);
                    if ($chunk !== false) {
                        echo $chunk;
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                }

                fclose($stream);
            },
            200,
            $headers,
        );
    }
}
