<?php

declare(strict_types=1);

use App\Http\Controllers\ChunkedUploadController;
use App\Http\Controllers\UploadProgressController;
use App\Models\User;
use App\Services\Directory\Managers\ChunksDirectoryManager;
use App\Services\FileServing\Chunks\ChunkFileResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Chunk File Serving Routes
|--------------------------------------------------------------------------
|
| Routes for chunk-based file upload operations including session management,
| progress tracking, and cleanup. These files are internal to the upload
| system and accessible to authenticated users.
|
*/

// Chunked upload operations
Route::prefix('chunks')->middleware(['auth'])->group(function () {
    // Store chunk
    Route::post('upload', [ChunkedUploadController::class, 'store'])
        ->name('chunks.upload.store');

    // Cleanup chunks
    Route::post('cleanup', [ChunkedUploadController::class, 'cleanup'])
        ->name('chunks.upload.cleanup');

    // Delete chunk
    Route::delete('upload', [ChunkedUploadController::class, 'delete'])
        ->name('chunks.upload.delete');

    // Cancel upload session
    Route::post('cancel', [ChunkedUploadController::class, 'cancel'])
        ->name('chunks.upload.cancel');
});

// Upload progress tracking
Route::prefix('upload/{sessionId}/progress')->middleware(['auth'])->group(function () {
    // Get upload progress
    Route::get('/', [UploadProgressController::class, 'show'])
        ->name('chunks.progress.show');

    // Delete progress tracking
    Route::delete('/', [UploadProgressController::class, 'destroy'])
        ->name('chunks.progress.destroy');
});

// Chunk file management routes
Route::prefix('chunks/{tenantId}/files')->middleware(['auth'])->group(function () {
    // Get chunk session directory
    Route::get('session/{sessionId}/directory', function (string $tenantId, string $sessionId) {
        $resolver = app(ChunkFileResolver::class);

        return response()->json([
            'directory' => $resolver->getSessionDirectory($tenantId, $sessionId),
        ]);
    })->name('chunks.files.session.directory');

    // Get storage statistics for tenant
    Route::get('stats', function (string $tenantId) {
        $resolver = app(ChunkFileResolver::class);

        return response()->json([
            'stats' => $resolver->getStorageStats($tenantId),
        ]);
    })->name('chunks.files.stats');

    // Check if chunk file exists
    Route::get('{type}/{filename}/exists', function (string $tenantId, string $type, string $filename) {
        $resolver = app(ChunkFileResolver::class);

        $chunkEntity = new class($tenantId)
        {
            public function __construct(private string $tenantId) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->tenantId;
            }

            public function getFileEntityType(): string
            {
                return 'chunk';
            }
        };

        return response()->json([
            'exists' => $resolver->fileExists($chunkEntity, $type, $filename),
        ]);
    })->name('chunks.files.exists');

    // Get chunk file info
    Route::get('{type}/{filename}/info', function (string $tenantId, string $type, string $filename) {
        $resolver = app(ChunkFileResolver::class);

        $chunkEntity = new class($tenantId)
        {
            public function __construct(private string $tenantId) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->tenantId;
            }

            public function getFileEntityType(): string
            {
                return 'chunk';
            }
        };

        return response()->json([
            'exists' => $resolver->fileExists($chunkEntity, $type, $filename),
            'size' => $resolver->getFileSize($chunkEntity, $type, $filename),
            'filename' => $filename,
            'type' => $type,
            'tenant_id' => $tenantId,
        ]);
    })->name('chunks.files.info');

    // Delete chunk file
    Route::delete('{type}/{filename}', function (string $tenantId, string $type, string $filename) {
        $resolver = app(ChunkFileResolver::class);

        $chunkEntity = new class($tenantId)
        {
            public function __construct(private string $tenantId) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->tenantId;
            }

            public function getFileEntityType(): string
            {
                return 'chunk';
            }
        };

        $deleted = $resolver->deleteFile($chunkEntity, $type, $filename);

        return response()->json([
            'deleted' => $deleted,
        ]);
    })->name('chunks.files.delete');
});

// Chunk file serving (actual file delivery)
Route::get('chunks/{tenantId}/serve/{type}/{filename}', function (string $tenantId, string $type, string $filename) {
    $resolver = app(ChunkFileResolver::class);

    $chunkEntity = new class($tenantId) implements \HasFiles
    {
        public function __construct(private string $tenantId) {}

        public function userHasFileAccess(User $user): bool
        {
            return true;
        }

        public function getFileStorageIdentifier(): string
        {
            return $this->tenantId;
        }

        public function getFileEntityType(): string
        {
            return 'chunk';
        }
    };

    if (! $resolver->fileExists($chunkEntity, $type, $filename)) {
        abort(404, 'File not found');
    }

    $disk = config('directory-chunks.storage.disk', 'local');
    $directoryManager = app(ChunksDirectoryManager::class);
    $basePath = $directoryManager->getBaseDirectory($tenantId);
    $path = "{$basePath}/{$type}/{$filename}";

    return Storage::disk($disk)->response($path);
})
    ->where('filename', '.*')
    ->middleware(['auth'])
    ->name('chunks.files.serve');

// Admin routes for chunk maintenance
Route::prefix('admin/chunks')->middleware(['auth'])->group(function () {
    // Clean up expired sessions
    Route::post('cleanup/sessions', function () {
        $resolver = app(ChunkFileResolver::class);
        $cleaned = $resolver->cleanupExpiredSessions();

        return response()->json([
            'cleaned' => $cleaned,
            'message' => "Cleaned up {$cleaned} expired chunk sessions",
        ]);
    })->name('admin.chunks.cleanup.sessions');

    // Clean up failed chunks for tenant
    Route::post('cleanup/failed/{tenantId}', function (string $tenantId) {
        $resolver = app(ChunkFileResolver::class);
        $cleaned = $resolver->cleanupFailedChunks($tenantId);

        return response()->json([
            'cleaned' => $cleaned,
            'message' => "Cleaned up {$cleaned} failed chunks for tenant {$tenantId}",
        ]);
    })->name('admin.chunks.cleanup.failed');
});
