<?php

namespace App\Console\Commands;

use App\Jobs\ActivityPub\FindActorInfo;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdateRemoteProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fedi:update-remote-profiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update information on all remote profiles from the db';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $total = RemoteActor::count();
        $this->info($total . ' ' . Str::plural('actor', $total) . ' found, launching jobs on 1000 chunks');
        RemoteActor::lazy()->each(
            fn (RemoteActor $actor) => FindActorInfo::dispatch($actor->activityId, false)
        );
        $this->info('Done');
        return Command::SUCCESS;
    }
}
