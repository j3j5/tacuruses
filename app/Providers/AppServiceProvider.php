<?php

namespace App\Providers;

use App\Contracts\Snowflake as ContractsSnowflake;
use Godruoyi\Snowflake\LaravelSequenceResolver;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use function Safe\strtotime;

use Twitter\Text\Autolink;

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

        $this->app->singleton('snowflake', function ($app) {
            return (new Snowflake($app->config->get('snowflake.datacenter_id'), $app->config->get('snowflake.worker_id')))
                ->setStartTimeStamp(strtotime($app->config->get('snowflake.epoch')) * 1000)
                ->setSequenceResolver(new LaravelSequenceResolver($app->get('cache.store')));
        });

        $this->app->bind(ContractsSnowflake::class, fn ($app) => $app->make('snowflake'));

        $this->app->bind(Autolink::class, function ($app) {
            $autolink = Autolink::create();
            // Replace Twitter's URLs with my own
            $autolink->setUrlBaseUser(config('app.url') . '/');
            $autolink->setUrlBaseList(config('app.url') . '/');
            $autolink->setUrlBaseHash(config('app.url') . '/tags/');
            $autolink->setUrlBaseCash(config('app.url') . '/tags/');
            $autolink->setToAllLinkClasses('post-url');
            $autolink->setUsernameIncludeSymbol(true);

            return $autolink;
        });

        Blade::directive('icon', function (string $expression) {
            $args = explode(', ', $expression);
            $icon = $args[0];
            $class = $args[1] ?? "''";
            $iconsUrl = asset('img/icons.svg');
            return '
            <?php
                echo \'<svg class="\' . ' . $class . ' . \'"><use xlink:href="\' . \'' . $iconsUrl . '\' . \'#\' . ' . $icon . ' .' . ' \'">
                </use></svg>\';
            ?>';
        });
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
        Model::preventLazyLoading(!$this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());
        Model::preventAccessingMissingAttributes(!$this->app->isProduction());
    }
}
