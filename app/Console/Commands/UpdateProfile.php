<?php

namespace App\Console\Commands;

use App\Jobs\ActivityPub\SendUpdateToInstance;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Console\Command;

class UpdateProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:update-profile {account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send and update activity for the given actor to all remote servers';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $actor = LocalActor::where('username', $this->argument('account'))->firstOrFail();

        // It should be sent to all known instances
        $instances = RemoteActor::query()->groupBy('sharedInbox')->pluck('sharedInbox');

        $instances->each(
            fn (string $inbox) => SendUpdateToInstance::dispatchSync(actor: $actor, inbox: $inbox)
        );

        return Command::SUCCESS;
    }
}
