<?php

declare(strict_types=1);

use App\Http\Controllers\AcceptInvitation;
use App\Http\Controllers\ChunkedUploadController;
use App\Http\Controllers\DocumentImageUploadController;
use App\Http\Controllers\EditorJsImageDelete;
use App\Http\Controllers\EditorJsVideoDelete;
use App\Http\Controllers\EditorJsVideoUpload;
use App\Http\Controllers\UploadProgressController;
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

Route::get('/imagePreview', action: PreviewImage::class)->name('home');

Route::get('/', SortableDemo::class);

Route::get('/annotation', fn () => view('annotation'));
Route::get('/videoPreview', PreviewVideo::class)->name('video.preview');
Route::get('/audioPreview', PreviewAudio::class)->name('audio.preview');
Route::get('/videoRecord', VideoRecorder::class)->name('video.record');

Route::get('invitation/accept/{token}', AcceptInvitation::class)
    ->middleware([Authenticate::class])
    ->name('invitation.accept');

Route::post('documents/{document}/upload-image', DocumentImageUploadController::class)
    ->middleware(['auth'])
    ->name('editorjs.upload-image');
Route::delete('delete-image', EditorJsImageDelete::class)
    ->middleware(['auth'])
    ->name('editorjs.delete-image');
Route::delete('documents/{document}/delete-image', EditorJsImageDelete::class)
    ->middleware(['auth'])
    ->name('editorjs.document.delete-image');

// Video upload routes for EditorJS
Route::post('documents/{document}/upload-video', EditorJsVideoUpload::class)
    ->middleware(['auth'])
    ->name('editorjs.upload-video');
Route::delete('delete-video', EditorJsVideoDelete::class)
    ->middleware(['auth'])
    ->name('editorjs.delete-video');
Route::delete('documents/{document}/delete-video', EditorJsVideoDelete::class)
    ->middleware(['auth'])
    ->name('editorjs.document.delete-video');

// URL fetch route for EditorJS LinkTool
Route::get('editor/fetch-url', [UrlFetchController::class, 'fetchUrl'])
    ->middleware(['auth'])
    ->name('editorjs.fetch-url');

// Chunked upload routes
Route::post('chunked-upload', [ChunkedUploadController::class, 'store'])
    ->middleware(['auth'])
    ->name('chunked-upload.store');

Route::post('chunked-upload/cleanup', [ChunkedUploadController::class, 'cleanup'])
    ->middleware(['auth'])
    ->name('chunked-upload.cleanup');
Route::delete('chunked-upload', [ChunkedUploadController::class, 'delete'])
    ->middleware(['auth'])
    ->name('chunked-upload.delete');
Route::post('chunked-upload/cancel', [ChunkedUploadController::class, 'cancel'])
    ->middleware(['auth'])
    ->name('chunked-upload.cancel');

// Upload progress routes
Route::get('upload/{sessionId}/progress', [UploadProgressController::class, 'show'])
    ->middleware(['auth'])
    ->name('upload.progress.show');
Route::delete('upload/{sessionId}/progress', [UploadProgressController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('upload.progress.destroy');

// Test route for chunked upload
Route::get('test-chunked-upload', TestChunkedUpload::class)
    // ->middleware(['auth'])
    ->name('test-chunked-upload');

// Demo route for sortable
Route::get('sortable-demo', SortableDemo::class)
    ->name('sortable-demo');

// Calendar test route
Route::get('calendar-test', CalendarTest::class)
    ->name('calendar-test');

// TOAST UI Calendar test route
Route::get('toast-calendar-test', ToastCalendarTest::class)
    ->name('toast-calendar-test');

// Language switching route
Route::get('/language/{locale}', function (string $locale) {
    if (!array_key_exists($locale, config('app.available_locales'))) {
        abort(400);
    }
    
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
