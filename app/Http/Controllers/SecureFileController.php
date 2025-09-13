<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Directory\DirectoryManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Secure File Access Controller.
 *
 * Provides authenticated and tenant-aware file access for sensitive files.
 * All file access is validated for proper authentication and tenant ownership.
 */
final class SecureFileController extends Controller
{
    /**
     * Serve a secure file with authentication and tenant validation.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $tenantId  The tenant identifier (MD5 hashed)
     * @param  string  $type  The file type (documents, videos, etc.)
     * @param  string  $path  The file path within the tenant directory
     */
    public function show(Request $request, string $tenantId, string $type, string $path): StreamedResponse|Response
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            abort(401, 'Authentication required');
        }

        $user = auth()->user();
        $userTenantId = DirectoryManager::baseDirectiry($user->getActiveTenantId());

        // Validate tenant access - user must belong to the tenant
        if ($tenantId !== $userTenantId) {
            abort(403, 'Access denied: Invalid tenant');
        }

        // Construct the full file path
        $fullPath = "{$tenantId}/{$type}/{$path}";
        $disk = config('document.storage.disk', 'public');

        // Verify file exists
        if (! Storage::disk($disk)->exists($fullPath)) {
            abort(404, 'File not found');
        }

        // Get file info
        $mimeType = Storage::disk($disk)->mimeType($fullPath) ?? 'application/octet-stream';
        $size = Storage::disk($disk)->size($fullPath);
        $filename = basename($path);

        // Stream the file with appropriate headers
        return Storage::disk($disk)->response($fullPath, $filename, [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Cache-Control' => 'private, max-age=3600', // 1 hour cache for authenticated users
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ]);
    }

    /**
     * Generate a temporary signed URL for a file.
     *
     * @param  Request  $request  The incoming request
     */
    public function generateTemporaryUrl(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string',
            'type' => 'required|string|in:documents,videos,avatars',
            'path' => 'required|string',
            'expires_in' => 'sometimes|integer|min:1|max:3600', // Max 1 hour
        ]);

        // Ensure user is authenticated
        if (! auth()->check()) {
            abort(401, 'Authentication required');
        }

        $user = auth()->user();
        $userTenantId = DirectoryManager::baseDirectiry($user->getActiveTenantId());

        // Validate tenant access
        if ($request->input('tenant_id') !== $userTenantId) {
            abort(403, 'Access denied: Invalid tenant');
        }

        $type = $request->input('type');
        $path = $request->input('path');
        $expiresIn = $request->input('expires_in', $this->getDefaultExpirationTime($type));

        // Verify file exists
        $fullPath = "{$userTenantId}/{$type}/{$path}";
        $disk = config('document.storage.disk', 'public');

        if (! Storage::disk($disk)->exists($fullPath)) {
            abort(404, 'File not found');
        }

        // Generate temporary URL
        $tempUrl = Storage::disk($disk)->temporaryUrl(
            $fullPath,
            now()->addSeconds($expiresIn),
        );

        return response()->json([
            'url' => $tempUrl,
            'expires_at' => now()->addSeconds($expiresIn)->toISOString(),
            'expires_in' => $expiresIn,
        ]);
    }

    /**
     * Get default expiration time based on file type.
     *
     * @param  string  $type  The file type
     * @return int Expiration time in seconds
     */
    private function getDefaultExpirationTime(string $type): int
    {
        return match ($type) {
            'avatars' => 3600, // 1 hour for avatars (cached heavily)
            'documents' => 1800, // 30 minutes for document images
            'videos' => 7200, // 2 hours for videos (larger files, longer processing)
            default => 1800, // 30 minutes default
        };
    }
}
