<?php

namespace App\Providers;

use Illuminate\Http\Response as HttpResponse;
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
                ['Content-Type' => 'application/activity+json; charset=UTF-8'],
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
                ['Content-Type' => 'application/jrd+json; charset=UTF-8'],
                JSON_HEX_TAG | JSON_UNESCAPED_SLASHES
            );
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
