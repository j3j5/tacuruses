<?php

use App\Http\Controllers\Feed\ActorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Feed (RSS/Atom) Routes
|--------------------------------------------------------------------------
|
| Here is where you can register feed routes for your application. These
| routes are loaded by the RouteServiceProvider within a group.
|
*/
Route::prefix('/')->group(function () {
    Route::get('{actor}.rss', ActorController::class)->name('actor.feed.rss');
});
