<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityPub\Actors\FollowersController;
use App\Http\Controllers\ActivityPub\Actors\FollowingController;
use App\Http\Controllers\ActivityPub\Actors\InboxController;
use App\Http\Controllers\ActivityPub\Actors\NoteActivityController;
use App\Http\Controllers\ActivityPub\Actors\NoteController;
use App\Http\Controllers\ActivityPub\Actors\NoteRepliesController;
use App\Http\Controllers\ActivityPub\Actors\OutboxController;
use App\Http\Controllers\ActivityPub\Actors\ProfileController;
use App\Http\Controllers\ActivityPub\Actors\TagController;
use App\Http\Controllers\ActivityPub\Instance\HostMetaController;
use App\Http\Controllers\ActivityPub\Instance\InstanceController;
use App\Http\Controllers\ActivityPub\Instance\NodeInfoController;
use App\Http\Controllers\ActivityPub\Instance\SharedInboxController;
use App\Http\Controllers\ActivityPub\Instance\WebfingerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['no.cookies'])->group(function () {
    // Federation Instance
    Route::get('/.well-known/webfinger/', WebfingerController::class)->name('webfinger');
    Route::get('/.well-known/nodeinfo/', [NodeInfoController::class, 'wellKnown']);
    Route::get('/.well-known/host-meta/', HostMetaController::class);
    Route::get('/nodeinfo/2.0', [NodeInfoController::class, 'get']);
    Route::get('/api/v1/instance', [InstanceController::class, 'apiV1']);

    Route::middleware(['valid.http.signature', 'debug'])->group(function () {
        Route::post('/f/sharedInbox', SharedInboxController::class)->name('shared-inbox');
        Route::post('/{actor}/inbox', InboxController::class)->name('actor.inbox');
    });

    Route::get('/{actor}/outbox', OutboxController::class)->name('actor.outbox');

    // Federation actors
    Route::get('/{actor}', ProfileController::class)->name('actor.show');
    Route::get('/{actor}/following', FollowingController::class)->name('actor.following');
    Route::get('/{actor}/followers', FollowersController::class)->name('actor.followers');
    Route::get('/{actor}/{note}/activity', NoteActivityController::class)->name('note.activity');
    Route::get('/{actor}/{note}/replies', NoteRepliesController::class)->name('note.replies');
    Route::get('/{actor}/{note}', NoteController::class)->middleware('legacy')->name('legacy.note.show');
    // Add the `/p` to mimick Pixelfed urls so Tusky (and others?) open in-app
    Route::get('/p/{actor}/{note}', NoteController::class)->name('note.show');

    Route::get('/tags/{tag}', TagController::class)->name('tag.show');
});

Route::fallback(function (Request $request) {
    info('fallback', ['request' => $request]);
    abort(418);
});
