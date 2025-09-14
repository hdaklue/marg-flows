<?php

declare(strict_types=1);

use App\Contracts\File\HasFiles;
use App\Models\User;
use App\Services\Directory\Managers\SystemDirectoryManager;
use App\Services\FileServing\System\SystemFileResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| System File Serving Routes
|--------------------------------------------------------------------------
|
| Routes for system-wide file operations including user avatars and
| temporary files. These files are accessible to any authenticated user
| for simplicity since they're system-managed.
|
*/

// System file serving routes
Route::prefix('system/files')->middleware(['auth'])->group(function () {
    // Get avatar URL for current user
    Route::get('avatar', function () {
        $resolver = app(SystemFileResolver::class);
        $user = auth()->user();

        return response()->json([
            'url' => $resolver->getUserAvatarUrl($user),
            'path' => $resolver->getUserAvatarPath($user),
        ]);
    })->name('system.files.avatar');

    // Get temporary URL for system file
    Route::post('{type}/{filename}/temp-url', function (string $type, string $filename) {
        $resolver = app(SystemFileResolver::class);
        $expires = request()->integer('expires', 3600);

        // Create a simple entity that implements HasFiles for system files
        $systemEntity = new class($type) implements HasFiles
        {
            public function __construct(private string $type) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->type;
            }

            public function getFileEntityType(): string
            {
                return 'system';
            }
        };

        return response()->json([
            'url' => $resolver->resolveTemporaryUrl($systemEntity, $type, $filename, $expires),
        ]);
    })->name('system.files.temp-url');

    // Check if system file exists
    Route::get('{type}/{filename}/exists', function (string $type, string $filename) {
        $resolver = app(SystemFileResolver::class);

        $systemEntity = new class($type) implements HasFiles
        {
            public function __construct(private string $type) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->type;
            }

            public function getFileEntityType(): string
            {
                return 'system';
            }
        };

        return response()->json([
            'exists' => $resolver->fileExists($systemEntity, $type, $filename),
        ]);
    })->name('system.files.exists');

    // Get system file info
    Route::get('{type}/{filename}/info', function (string $type, string $filename) {
        $resolver = app(SystemFileResolver::class);

        $systemEntity = new class($type) implements HasFiles
        {
            public function __construct(private string $type) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->type;
            }

            public function getFileEntityType(): string
            {
                return 'system';
            }
        };

        return response()->json([
            'exists' => $resolver->fileExists($systemEntity, $type, $filename),
            'size' => $resolver->getFileSize($systemEntity, $type, $filename),
            'filename' => $filename,
            'type' => $type,
        ]);
    })->name('system.files.info');

    // Delete system file
    Route::delete('{type}/{filename}', function (string $type, string $filename) {
        $resolver = app(SystemFileResolver::class);

        $systemEntity = new class($type) implements HasFiles
        {
            public function __construct(private string $type) {}

            public function userHasFileAccess(User $user): bool
            {
                return true;
            }

            public function getFileStorageIdentifier(): string
            {
                return $this->type;
            }

            public function getFileEntityType(): string
            {
                return 'system';
            }
        };

        $deleted = $resolver->deleteFile($systemEntity, $type, $filename);

        return response()->json([
            'deleted' => $deleted,
        ]);
    })->name('system.files.delete');
});

// System file serving (actual file delivery)
Route::get('system/serve/{type}/{filename}', function (string $type, string $filename) {
    $resolver = app(SystemFileResolver::class);

    $systemEntity = new class($type) implements HasFiles
    {
        public function __construct(private string $type) {}

        public function userHasFileAccess(User $user): bool
        {
            return true;
        }

        public function getFileStorageIdentifier(): string
        {
            return $this->type;
        }

        public function getFileEntityType(): string
        {
            return 'system';
        }
    };

    if (! $resolver->fileExists($systemEntity, $type, $filename)) {
        abort(404, 'File not found');
    }

    $disk = config('directory-system.storage.disk', 'public');
    $directoryManager = app(SystemDirectoryManager::class);
    $basePath = $directoryManager->getBaseDirectory($type);
    $path = "{$basePath}/{$filename}";

    return Storage::disk($disk)->response($path);
})
    ->where('filename', '.*')
    ->middleware(['auth'])
    ->name('system.files.serve');

// Admin routes for system maintenance
Route::prefix('admin/system')->middleware(['auth'])->group(function () {
    // Clean up temporary files
    Route::post('cleanup/temp', function () {
        $resolver = app(SystemFileResolver::class);
        $cleaned = $resolver->cleanupTempFiles();

        return response()->json([
            'cleaned' => $cleaned,
            'message' => "Cleaned up {$cleaned} temporary files",
        ]);
    })->name('admin.system.cleanup.temp');
});
