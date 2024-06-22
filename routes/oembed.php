<?php

declare(strict_types=1);

use App\Http\Controllers\oEmbed\ProviderController;
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

// /api/oembed
Route::get('/oembed', ProviderController::class)->name('api.oembed');
