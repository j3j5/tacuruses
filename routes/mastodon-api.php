<?php

declare(strict_types=1);

use App\Http\Controllers\API\Mastodon\LookupAccount;
use App\Http\Controllers\API\Mastodon\PostMedia;
use App\Http\Controllers\API\Mastodon\PostStatus;
use App\Http\Controllers\API\Mastodon\UpdateStatus;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // /api/v1
    Route::prefix('v1')->group(function () {
        Route::post('/statuses', PostStatus::class)->name('mastodon.v1.statuses.post');
        Route::put('/statuses/{status}', UpdateStatus::class)->name('mastodon.v1.statuses.update');
    });

    // /api/v2
    Route::prefix('/v2')->group(function () {
        Route::post('/media', PostMedia::class)->name('mastodon.v2.media');
    });

});

// /api/v1
Route::prefix('/v1')->group(function () {
    Route::prefix('/accounts')->group(function () {
        Route::get('/lookup', LookupAccount::class)->name('mastodon.v1.accounts.lookup');
    });
});
