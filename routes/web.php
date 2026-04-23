<?php

use App\Models\TimetableUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('onboarding/role', 'pages::onboarding-role')->name('onboarding.role');

    Route::get('timetable-uploads/{upload}/preview', function (TimetableUpload $upload) {
        abort_unless($upload->user_id === Auth::id(), 403);
        abort_unless(str_starts_with(strtolower((string) $upload->mime_type), 'image/'), 404);

        $path = Storage::disk('local')->path($upload->file_path);

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    })->name('timetable.preview');
});

Route::middleware(['auth', 'verified', 'role.selected'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('scheduler', 'pages::scheduler')->name('scheduler');
    Route::livewire('to-dos', 'pages::to-dos')->name('todos');
});

require __DIR__.'/settings.php';
