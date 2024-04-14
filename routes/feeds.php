<?php

use App\Http\Controllers\Feed\ActorController;
use App\Http\Controllers\Feed\ActorNotificationsController;
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
    Route::get('notifications.{format}', ActorNotificationsController::class)
        ->name('feed.notifications');
    Route::get('{actor}.rss', ActorController::class)->name('feed.actor.rss');
});
