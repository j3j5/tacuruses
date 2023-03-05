<?php

namespace App\Providers;

use ActivityPhp\Type;
use ActivityPhp\Type\TypeConfiguration;
use App\Domain\ActivityPub\Mastodon\Emoji;
use App\Domain\ActivityPub\Mastodon\Hashtag;
use App\Domain\ActivityPub\Mastodon\Note;
use App\Domain\ActivityPub\Mastodon\Person;
use App\Domain\ActivityPub\Mastodon\PropertyValue;
use App\Domain\ActivityPub\Mastodon\Service;
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
        // TypeConfiguration::set('undefined_properties', 'include');

        Type::add('Hashtag', Hashtag::class); # new one
        Type::add('Emoji', Emoji::class); # new one
        Type::add('PropertyValue', PropertyValue::class); # new one
        Type::add('Person', Person::class);
        Type::add('Service', Service::class);
        Type::add('Note', Note::class);
    }
}
