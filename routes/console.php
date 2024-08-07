<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Laravel\Horizon\Console\SnapshotCommand;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Schedule::command(SnapshotCommand::class)->everyFiveMinutes();
