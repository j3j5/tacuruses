<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ActivityPub\SendDeleteActorToInstance;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Console\Command;

class DeleteAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:delete-account {account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an existing account';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $actor = LocalActor::where('username', $this->argument('account'))->firstOrFail();

        // It should be sent to all known instances
        $instances = RemoteActor::query()->groupBy('sharedInbox')->pluck('sharedInbox');

        $instances->each(
            fn (string $inbox) => SendDeleteActorToInstance::dispatchSync(actor: $actor, inbox: $inbox)
        );

        return Command::SUCCESS;
    }
}
