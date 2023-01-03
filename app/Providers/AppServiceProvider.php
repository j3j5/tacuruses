<?php

namespace App\Providers;

use App\Domain\ActivityPub\Contracts\Actor;
use App\Models\ActivityPub\LocalActor;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /** @phpstan-ignore-next-line */
        $this->app->config->set('correlation', Str::uuid()->toString());

        /** @phpstan-ignore-next-line */
        $this->app->log->pushProcessor(static function (array $record) {
            $record['extra']['correlation'] = config('correlation', '');
            return $record;
        });

        $this->app->bind(Actor::class, LocalActor::class);
    }

    public function addLogId(string $id) : void
    {
        /** @phpstan-ignore-next-line */
        $this->app->log->pushProcessor(static function (array $record) use ($id) {
            $record['message'] = "[$id] {$record['message']}";
            return $record;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
