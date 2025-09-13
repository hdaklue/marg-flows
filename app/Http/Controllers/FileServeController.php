<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Directory\DirectoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileServeController extends Controller
{
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
        $type = $pathParts[count($pathParts) - 2]; // second to last part
        $filename = $pathParts[count($pathParts) - 1]; // last part

        // Verify user has access to this tenant
        $userTenantId = auth()->user()->getActiveTenantId();
        $hashedUserTenantId = DirectoryManager::baseDirectiry($userTenantId);

        if ($hashedUserTenantId !== $tenantFromPath) {
            abort(403, 'Access denied to this tenant');
        }

        // Validate file type (handle both direct types and nested video types)
        $allowedTypes = ['images', 'videos', 'documents', 'prev'];
        if (! in_array($type, $allowedTypes)) {
            abort(400, 'Invalid file type');
        }

        // Use the full path directly
        $disk = config('document.storage.disk', 'public');

        // Check if file exists
        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found');
        }

        // Get file details
        $mimeType = Storage::disk($disk)->mimeType($path);
        $size = Storage::disk($disk)->size($path);

        // Set appropriate headers
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Cache-Control' => 'private, max-age=3600', // 1 hour cache
            'X-Robots-Tag' => 'noindex, nofollow',
        ];

        // For images and small files, return direct response
        if ($this->isSmallFile($size) || $this->isImageFile($mimeType)) {
            $content = Storage::disk($disk)->get($path);

            return response($content, 200, $headers);
        }

        // For large files (videos), use streamed response for better performance
        return Storage::disk($disk)->response($path, $filename, $headers);
    }
}
