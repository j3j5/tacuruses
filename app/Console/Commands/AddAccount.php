<?php

namespace App\Console\Commands;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\RSA;

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
        $bot->model = $this->ask('Now I need the full class name of the model this actor will use');

        $bot->save();

        $this->info('I\'m going to generate the private/public keys now');

        $keyLength = 2048;
        $privateKey = RSA::createKey($keyLength);
        Storage::disk('local')->put("keys/local/{$bot->id}/private.pem", $privateKey->toString('PKCS1'));
        Storage::disk('local')->put("keys/local/{$bot->id}/public.pem", $privateKey->getPublicKey()->toString('PKCS1'));

        $this->info('Success!! Enjoy your new bot');

        return Command::SUCCESS;
    }
}
