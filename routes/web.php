<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityPub\Actors\FollowersController;
use App\Http\Controllers\ActivityPub\Actors\FollowingController;
use App\Http\Controllers\ActivityPub\Actors\NoteActivityController;
use App\Http\Controllers\ActivityPub\Actors\NoteController;
use App\Http\Controllers\ActivityPub\Actors\NoteRepliesController;
use App\Http\Controllers\ActivityPub\Actors\ProfileController as ActorsProfileController;
use App\Http\Controllers\ActivityPub\Actors\TagController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\oEmbed\EmbedController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\NoCookies;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::get('/', HomeController::class);

// Logged in users
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['verified'])->name('dashboard');
});

// Actors and notes
Route::middleware([])->group(function () {
    // Federation actors
    Route::get('/{actor}', ActorsProfileController::class)->name('actor.show');
    Route::get('/{actor}/following', FollowingController::class)->name('actor.following');
    Route::get('/{actor}/followers', FollowersController::class)->name('actor.followers');
    Route::get('/{actor}/{note}/activity', NoteActivityController::class)->name('note.activity');
    Route::get('/{actor}/{note}/replies', NoteRepliesController::class)->name('note.replies');
    Route::get('/{actor}/{note}', NoteController::class)->middleware('legacy')->name('legacy.note.show');
    // Add the `/p` to mimick Pixelfed urls so Tusky (and others?) open in-app
    Route::get('/p/{actor}/{note}', NoteController::class)->name('note.show');

    Route::get('/{actor}/{note}/embed', EmbedController::class)
        ->middleware(NoCookies::class)
        ->name('note.show.embed');

    Route::get('/tags/{tag}', TagController::class)->name('tag.show');
});
