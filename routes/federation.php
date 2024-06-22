<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityPub\Actors\InboxController;
use App\Http\Controllers\ActivityPub\Actors\OutboxController;
use App\Http\Controllers\ActivityPub\Instance\HostMetaController;
use App\Http\Controllers\ActivityPub\Instance\InstanceController;
use App\Http\Controllers\ActivityPub\Instance\NodeInfoController;
use App\Http\Controllers\ActivityPub\Instance\SharedInboxController;
use App\Http\Controllers\ActivityPub\Instance\WebfingerController;
use App\Http\Controllers\FallbackController;
use App\Http\Middleware\ActivityPub\VerifyHttpSignature;
use App\Http\Middleware\Debug;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\NoCookies;
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

Route::middleware([NoCookies::class, ForceJsonResponse::class])->group(function () {
    // Federation Instance
    Route::get('/.well-known/webfinger/', WebfingerController::class)->name('webfinger');
    Route::get('/.well-known/nodeinfo/', [NodeInfoController::class, 'wellKnown']);
    Route::get('/.well-known/host-meta/', HostMetaController::class);
    Route::get('/nodeinfo/2.0', [NodeInfoController::class, 'get']);

    Route::get('/api/v1/instance', [InstanceController::class, 'apiV1']);

    Route::middleware([VerifyHttpSignature::class, Debug::class])->group(function () {
        Route::post('/f/sharedInbox', SharedInboxController::class)->name('shared-inbox');
        Route::post('/{actor}/inbox', InboxController::class)->name('actor.inbox');
    });

    Route::get('/{actor}/outbox', OutboxController::class)->name('actor.outbox');
});

Route::fallback(FallbackController::class);
