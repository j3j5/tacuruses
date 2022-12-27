<?php

namespace App\Providers;

use ActivityPhp\Type\TypeConfiguration;
use Illuminate\Support\ServiceProvider;

class ActivityPubProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        TypeConfiguration::set('undefined_properties', 'include');

        // TODO: Add all my custom types!
    }
}
