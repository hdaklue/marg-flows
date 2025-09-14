<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            'document_id' => 'required|string', // Add document_id requirement
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $documentId = $request->input('document_id');

        // Generate secure URL using the correct document serve route
        $secureUrl = route('documents.serve', [
            'document' => $documentId,
            'type' => $type,
            'filename' => $filename,
        ]);

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
            'document_id' => 'required|string', // Add document_id requirement
            'expires_in' => 'sometimes|integer|min:60|max:7200', // 1 minute to 2 hours
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $documentId = $request->input('document_id');
        $expiresIn = $request->input('expires_in', 1800); // Default 30 minutes

        // Generate temporary URL using the correct document serve route
        $tempUrl = route('documents.serve', [
            'document' => $documentId,
            'type' => $type,
            'filename' => $filename,
        ]);

        return response($tempUrl, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'private, no-cache', // Don't cache temporary URLs
        ]);
    }
}
