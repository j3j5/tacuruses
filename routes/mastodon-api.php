<?php

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

    Route::prefix('/api/v1')->group(function () {
        Route::post('/statuses', PostStatus::class)->name('mastodon.v1.statuses.post');
        Route::put('/statuses/{status}', UpdateStatus::class)->name('mastodon.v1.statuses.update');
    });

    Route::prefix('/api/v2')->group(function () {
        Route::post('/media', PostMedia::class)->name('mastodon.v2.media');
    });

});

Route::prefix('/api/v1')->group(function () {
    Route::prefix('/accounts')->group(function () {
        Route::get('/lookup', LookupAccount::class)->name('mastodon.v1.accounts.lookup');
    });
});
