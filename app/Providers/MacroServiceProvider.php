<?php

namespace App\Providers;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * @example: return response()->activityJson($data)
         */
        Response::macro('activityJson', function (array $data = [], int $status = HttpResponse::HTTP_OK) {
            return response()->json(
                $data,
                $status,
                ['Content-Type' => 'application/activity+json; charset=utf-8'],
                JSON_HEX_TAG | JSON_UNESCAPED_SLASHES
            );
        });

        /**
         * @example: return response()->activityJson($data)
         */
        Response::macro('jrdJson', function (array $data = [], int $status = HttpResponse::HTTP_OK) {
            return response()->json(
                $data,
                $status,
                ['Content-Type' => 'application/jrd+json; charset=utf-8'],
                JSON_HEX_TAG | JSON_UNESCAPED_SLASHES
            );
        });

        Collection::macro('smoother', function (int $step, string $method = 'avg') {
            /** @var \Illuminate\Support\Collection $this */
            return $this->chunk($step)->mapWithKeys(
                fn (Collection $chunk) => [
                    $chunk->keys()->first() => $chunk->$method(),
                ]
            );
        });

        /**
         * Based on https://www.php.net/manual/en/function.stats-standard-deviation.php#114473
         */
        Collection::macro('stdDev', function () : float {
            /** @var \Illuminate\Support\Collection $this */
            $n = $this->count();
            if ($n === 0) {
                return 0.0;
            }
            if ($n === 1) {
                return 0.0;
            }
            $mean = $this->avg();

            $carry = $this->reduce(function ($carry, $val) use ($mean) {
                $d = ((double) $val) - $mean;
                return $carry + pow($d, 2);
            }, 0.0);

            return sqrt($carry / $n);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
