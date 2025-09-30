<?php

declare(strict_types=1);

use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\UrlFetchController;
use App\Livewire\CalendarTest;
use App\Livewire\PreviewAudio;
use App\Livewire\PreviewImage;
use App\Livewire\PreviewVideo;
use App\Livewire\Reusable\VideoRecorder;
use App\Livewire\SortableDemo;
use App\Livewire\TestChunkedUpload;
use App\Livewire\ToastCalendarTest;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
 |--------------------------------------------------------------------------
 | File Serving Routes
 |--------------------------------------------------------------------------
 |
 | File serving routes are now managed by FileServingServiceProvider.
 | Each FileResolver service handles its own routes for better organization.
 |
 */

Route::get('/imagePreview', action: PreviewImage::class)->name('home');

// Route::get('/', SortableDemo::class);

Route::get('/annotation', fn () => view('annotation'));
Route::get('/videoPreview', PreviewVideo::class)->name('video.preview');
Route::get('/audioPreview', PreviewAudio::class)->name('audio.preview');
Route::get('/videoRecord', VideoRecorder::class)->name('video.record');

// Route::get(
//    'invitation/accept',
//    \App\Filament\Pages\AcceptInvitation::class,
// )->middleware([Authenticate::class])->name('invitation.accept');

// URL fetch route for EditorJS LinkTool
Route::get('editor/fetch-url', [UrlFetchController::class, 'fetchUrl'])->middleware(['auth'])->name(
    'editorjs.fetch-url',
);

// Secure file access routes (for non-document files)
Route::get('secure-files/{tenantId}/{type}/{path}', [SecureFileController::class, 'show'])
    ->middleware(['auth'])
    ->where('path', '.*')
    ->name('secure-files.show');

Route::post('secure-files/temp-url', [SecureFileController::class, 'generateTemporaryUrl'])
    ->middleware(['auth'])
    ->name('secure-files.temp-url');

// Chunked upload routes (temporarily commented out)
// Route::post('chunked-upload', [\ChunkedUploadController::class, 'store'])
//     ->middleware(['auth'])
//     ->name('chunked-upload.store');
// Route::post('chunked-upload/cleanup', [\ChunkedUploadController::class, 'cleanup'])
//     ->middleware(['auth'])
//     ->name('chunked-upload.cleanup');
// Route::delete('chunked-upload', [\ChunkedUploadController::class, 'delete'])
//     ->middleware(['auth'])
//     ->name('chunked-upload.delete');
// Route::post('chunked-upload/cancel', [\ChunkedUploadController::class, 'cancel'])
//     ->middleware(['auth'])
//     ->name('chunked-upload.cancel');

// Upload progress routes (temporarily commented out due to route registration issue)
// Route::get('upload/{sessionId}/progress', [\UploadProgressController::class, 'show'])
//     ->middleware(['auth'])
//     ->name('upload.progress.show');
// Route::delete('upload/{sessionId}/progress', [\UploadProgressController::class, 'destroy'])
//     ->middleware(['auth'])
//     ->name('upload.progress.destroy');

// Test route for chunked upload
Route::get('test-chunked-upload', TestChunkedUpload::class)
    // ->middleware(['auth'])
    ->name('test-chunked-upload');

// Demo route for sortable
Route::get('sortable-demo', SortableDemo::class)->name('sortable-demo');

// Calendar test route
Route::get('calendar-test', CalendarTest::class)->name('calendar-test');

// TOAST UI Calendar test route
Route::get('toast-calendar-test', ToastCalendarTest::class)->name('toast-calendar-test');

// Language switching route
Route::get('/language/{locale}', function (string $locale) {
    abort_unless(array_key_exists($locale, config('app.available_locales')), 400);

    Session::put('locale', $locale);

    return redirect()->back();
})->name('language.switch');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

// Route::middleware(['auth'])->group(function () {
//     Route::redirect('settings', 'settings/profile');

//     Route::get('settings/profile', Profile::class)->name('settings.profile');
//     Route::get('settings/password', Password::class)->name('settings.password');
//     Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
// });

// require __DIR__.'/auth.php';
