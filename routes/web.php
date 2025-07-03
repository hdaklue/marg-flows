<?php

declare(strict_types=1);

use App\Http\Controllers\AcceptInvitation;
use App\Http\Controllers\EditorJsUpload;
use App\Livewire\Previewtest;
use App\Livewire\Reusable\Kanban;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Models\Flow;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', action: Previewtest::class)->name('home');
Route::get('/sort', Kanban::class);
// Route::get('/', function () {
//     return Flow::byStatus('pause')->get();
// });
Route::get('/annotation', fn () => view('annotation'));

Route::get('invitation/accept/{token}', AcceptInvitation::class)
    ->middleware([Authenticate::class])
    ->name('invitation.accept');

Route::post('uploader/editorjs', EditorJsUpload::class)->name('uploader');

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
