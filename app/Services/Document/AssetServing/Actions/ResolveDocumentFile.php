<?php

declare(strict_types=1);

namespace App\Services\Document\AssetServing\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveDocumentFile
{
    use AsAction;

    /**
     * Generate secure URL for document file with generalized parameters.
     */
    public function handle(
        string $documentId,
        string $type,
        string $filename,
        ?int $expiresIn = null,
    ): string {
        // Generate URL using the correct document serve route
        return route('documents.serve', [
            'document' => $documentId,
            'type' => $type,
            'filename' => $filename,
        ]);
    }

    /**
     * Resolve secure file URLs for authenticated users.
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
            'document_id' => 'required|string',
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $documentId = $request->input('document_id');

        // Call the generalized handler
        $secureUrl = $this->handle($documentId, $type, $filename);

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
            'document_id' => 'required|string',
            'expires_in' => 'sometimes|integer|min:60|max:7200', // 1 minute to 2 hours
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $documentId = $request->input('document_id');
        $expiresIn = $request->input('expires_in', 1800); // Default 30 minutes

        // Call the generalized handler
        $tempUrl = $this->handle($documentId, $type, $filename, $expiresIn);

        return response($tempUrl, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'private, no-cache', // Don't cache temporary URLs
        ]);
    }
}
