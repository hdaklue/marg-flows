<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Directory\DirectoryManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FileResolverController extends Controller
{
    /**
     * Resolve secure file URLs for authenticated users.
     *
     * This endpoint allows frontend components to get secure URLs
     * without having to know about tenant IDs or URL structure.
     */
    public function resolve(Request $request): Response
    {
        // Verify user is authenticated
        if (! auth()->check()) {
            abort(401, 'Authentication required');
        }

        // Validate required parameters
        $request->validate([
            'type' => 'required|string|in:images,videos,documents,prev',
            'filename' => 'required|string',
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $tenantId = auth()->user()->getActiveTenantId();

        // Generate secure URL using DirectoryManager
        $secureUrl = DirectoryManager::getSecureUrl($tenantId, $type, $filename);

        return response($secureUrl, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'private, max-age=300', // 5 minute cache
        ]);
    }

    /**
     * Generate temporary URLs with expiration.
     */
    public function temporary(Request $request): Response
    {
        // Verify user is authenticated
        if (! auth()->check()) {
            abort(401, 'Authentication required');
        }

        // Validate required parameters
        $request->validate([
            'type' => 'required|string|in:images,videos,documents,prev',
            'filename' => 'required|string',
            'expires_in' => 'sometimes|integer|min:60|max:7200', // 1 minute to 2 hours
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $expiresIn = $request->input('expires_in', 1800); // Default 30 minutes
        $tenantId = auth()->user()->getActiveTenantId();

        // Generate temporary URL using DirectoryManager
        $tempUrl = DirectoryManager::getTemporaryUrl($tenantId, $type, $filename, $expiresIn);

        return response($tempUrl, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'private, no-cache', // Don't cache temporary URLs
        ]);
    }
}
