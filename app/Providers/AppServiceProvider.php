<?php

namespace App\Providers;

use App\Contracts\Snowflake as ContractsSnowflake;
use App\Domain\ActivityPub\Contracts\Actor;
use App\Domain\ActivityPub\Contracts\Signer;
use App\Models\ActivityPub\LocalActor;
use App\Services\ActivityPub\Signer as ActivityPubSigner;
use Godruoyi\Snowflake\Snowflake;
use Godruoyi\Snowflake\LaravelSequenceResolver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

use function Safe\strtotime;

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

        $this->app->bind(Signer::class, ActivityPubSigner::class);

        $this->app->singleton('snowflake', function ($app) {
            return (new Snowflake($app->config->get('snowflake.datacenter_id'), $app->config->get('snowflake.worker_id')))
                ->setStartTimeStamp(strtotime($app->config->get('snowflake.epoch')) * 1000)
                ->setSequenceResolver(new LaravelSequenceResolver($app->get('cache.store')));
        });

        $this->app->bind(ContractsSnowflake::class, fn($app) => $app->make('snowflake'));
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
        JsonResource::withoutWrapping();
    }
}
