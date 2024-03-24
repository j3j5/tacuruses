<?php

namespace App\Providers;

use App\Contracts\Snowflake as ContractsSnowflake;
use Godruoyi\Snowflake\LaravelSequenceResolver;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use Nyholm\Psr7\Factory\Psr17Factory;
use function Safe\preg_replace;

use function Safe\strtotime;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

use Twitter\Text\Autolink;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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

        $this->app->bind(PsrHttpFactory::class, function ($app) {
            $psr17Factory = $app->make(Psr17Factory::class);
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

            return $psrHttpFactory;
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

        /** @var \Monolog\Logger $logger */
        $logger = $this->app->log->driver()->getLogger();
        $logger->pushProcessor(new MemoryPeakUsageProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new UidProcessor(16));
        $logger->pushProcessor(new IntrospectionProcessor(skipClassesPartials: [
            \Illuminate\Support\Facades\Facade::class,
            \Illuminate\Log\LogManager::class,
            \Illuminate\Log\Logger::class,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Model::preventSilentlyDiscardingAttributes();
        Model::preventAccessingMissingAttributes();
        Model::preventLazyLoading(!$this->app->isProduction());

        // Add support for logging (slow) DB queries
        DB::listen(function (QueryExecuted $query) {
            if (config('database.log_all_queries')) {
                $level = 'debug';
            }

            if (
                (isset($level) || config('database.log_slow_queries')) &&
                $query->time >= config('database.slow_query_time')
            ) {
                $level = 'notice';
            }

            if (isset($level)) {
                $message = $query->time / 1000 . 's; ';
                $message .= preg_replace(array_pad([], count($query->bindings), '/\?/'), $query->bindings, $query->sql, 1);

                if (config('database.enable_query_backtrace')) {
                    $message .= $this->createStackTrace();
                }

                Log::channel('slowqueries')->$level($message);
            }
        });

    }

    /**
     * The first six elements on the stack trace are:
     *  #0 app/Providers/AppServiceProvider.php(108): App\Providers\AppServiceProvider->createStackTrace
     *  #1 vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php(421): App\Providers\AppServiceProvider->App\Providers\{closure}
     *  #2 vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php(249): Illuminate\Events\Dispatcher->Illuminate\Events\{closure}
     *  #3 vendor/laravel/framework/src/Illuminate/Database/Connection.php(996): Illuminate\Events\Dispatcher->dispatch
     *  #4 vendor/laravel/framework/src/Illuminate/Database/Connection.php(778): Illuminate\Database\Connection->event
     *  #5 vendor/laravel/framework/src/Illuminate/Database/Connection.php(731): Illuminate\Database\Connection->logQuery
     *  #6 vendor/laravel/framework/src/Illuminate/Database/Connection.php(422): Illuminate\Database\Connection->run
     * @return string
     */
    private function createStackTrace() : string
    {
        $backtrace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ->skip(6)   // the first six are related to the DB event all the way here, see above
            ->values()
            ->map(function (array $element, int $index) : string {
                $line = "#$index ";
                if (isset($element['file'], $element['line'])) {
                    $line .= "{$element['file']}({$element['line']}): ";
                }
                if (isset($element['class'], $element['type'])) {
                    $line .= "{$element['class']}{$element['type']}";
                }
                $line .= $element['function'];

                return  $line;
            })->implode(PHP_EOL);
        $stacktrace = PHP_EOL . '[stacktrace]' . PHP_EOL . $backtrace . PHP_EOL;

        return $stacktrace;
    }

}
