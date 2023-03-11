<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityPub\Actors\FollowersController;
use App\Http\Controllers\ActivityPub\Actors\FollowingController;
use App\Http\Controllers\ActivityPub\Actors\InboxController;
use App\Http\Controllers\ActivityPub\Actors\OutboxController;
use App\Http\Controllers\ActivityPub\Actors\ProfileController;
use App\Http\Controllers\ActivityPub\Actors\StatusActivityController;
use App\Http\Controllers\ActivityPub\Actors\StatusController;
use App\Http\Controllers\ActivityPub\Actors\StatusRepliesController;
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
    Route::get('/.well-known/webfinger/', WebfingerController::class);
    Route::get('/.well-known/nodeinfo/', [NodeInfoController::class, 'wellKnown']);
    Route::get('/.well-known/host-meta/', HostMetaController::class);
    Route::get('/nodeinfo/2.0', [NodeInfoController::class, 'get']);
    Route::get('/api/v1/instance', [InstanceController::class, 'apiV1']);

    Route::middleware('valid.http.signature')->group(function () {
        Route::post('/f/sharedInbox', SharedInboxController::class)->name('shared-inbox');
        Route::post('/{user}/inbox', InboxController::class)->name('user.inbox');
    });

    Route::get('/{user}/outbox', OutboxController::class)->name('user.outbox');
});

// Federation Users
Route::get('/{user}', ProfileController::class)->name('user.show');
// Route::get('/{user}/following', BotFollowingController::class)->name('user.following');
Route::get('/{user}/following', FollowingController::class)->name('user.following');
Route::get('/{user}/followers', FollowersController::class)->name('user.followers');
Route::get('/{user}/{status}/activity', StatusActivityController::class)->name('status.activity');
Route::get('/{user}/{status}/replies', StatusRepliesController::class)->name('status.replies');
Route::get('/{user}/{status}', StatusController::class)->name('status.show');

Route::fallback(function (Request $request) {
    info('fallback', ['request' => $request]);
    abort(418);
});
