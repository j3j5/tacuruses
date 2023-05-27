<?php

use App\Http\Controllers\API\Mastodon\LookupAccount;
use App\Http\Controllers\API\Mastodon\PostMedia;
use App\Http\Controllers\API\Mastodon\PostStatus;
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
        Route::post('/statuses', PostStatus::class);
    });

    Route::prefix('/api/v2')->group(function () {
        Route::post('/media', PostMedia::class);
    });

});

Route::prefix('/api/v1')->group(function () {
    Route::prefix('/accounts')->group(function () {
        Route::get('/lookup', LookupAccount::class);
    });
});
