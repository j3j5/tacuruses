<?php

namespace App\Console\Commands;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Console\Command;

class AddAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new bot account ActivityPub compatible';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Hey, welcome to the command to add a new bot. Let me ask you a few questions:');

        $bot = new LocalActor();

        $bot->username = $this->ask("What's the username going to be?");
        if (LocalActor::where('username', $bot->username)->exists()) {
            $this->error('Sorry, that username is already taken. Try a differnet one');

            $bot->username = $this->ask("What's the username going to be?");
            if (LocalActor::where('username', $bot->username)->exists()) {
                $this->error('Sorry, that username is already taken. Think better next time!');
                return Command::FAILURE;
            }
        }

        $bot->name = $this->ask("What's the name?");
        $bot->bio = $this->ask('What about the bio?');
        $bot->avatar = $this->ask('Is the avatar already on the repo?');
        $bot->header = $this->ask('What about the header? Is it also on the repo?');

        $bot->save();

        $this->info('I\'m going to generate the private/public keys now');

        $bot->generateKeys();

        $this->info('Success!! Enjoy your new bot');

        return Command::SUCCESS;
    }
}
