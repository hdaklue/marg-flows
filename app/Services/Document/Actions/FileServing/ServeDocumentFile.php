<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\FileServing;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;
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
     */
    public function handle(
        Document $document,
        string $type,
        string $fileName,
    ): Response|StreamedResponse|RedirectResponse {
        $directoryManager = DocumentDirectoryManager::make($document);
        $actualPath = match ($type) {
            'images' => $directoryManager->images()->getPath($fileName),
            'videos' => $directoryManager->videos()->getPath($fileName),
        };

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

        // For large files (videos), use streamed response
        return Storage::disk($diskName)->response($actualPath, $fileName, $headers);
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

        $userTenantId = auth()->user()->getActiveTenantId();
        $document = Document::whereKey($document)->first();
        if (!$this->validateDocumentAccess($document, $request)) {
            abort(401);
        }

        // Build the path in the format expected by handle method
        // $hashedTenantId = (string) LaraPath::base($userTenantId, SanitizationStrategy::HASHED)->add(
        //     $documentFilePath,
        // );

        //Should be

        // $path = "{$hashedTenantId}/documents/{$document}/{$type}/{$filename}";

        // Check if client has cached version
        $clientEtag = $request->header('If-None-Match');
        $clientLastModified = $request->header('If-Modified-Since');

        // Handle range requests for video streaming
        $rangeHeader = $request->header('Range');
        if ($rangeHeader) {
            return $this->handleRangeRequest($document, $type, $filename);
        }

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
     * Handle HTTP range requests for video streaming.
     */
    private function handleRangeRequest(
        Document $document,
        string $type,
        string $fileName,
    ): Response|StreamedResponse {
        // For now, delegate to the main handler - range request logic can be added later
        return $this->handle($document, $type, $fileName);
    }
}
