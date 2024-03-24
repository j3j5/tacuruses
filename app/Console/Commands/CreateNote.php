<?php

namespace App\Console\Commands;

use App\Models\ActivityPub\LocalActor;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;

class CreateNote extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:create-note {account} {--text=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post a note to the fedi';

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $laravel;

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $actor = LocalActor::where('username', $this->argument('account'))->firstOrFail();

        $status = $this->option('text');

        Sanctum::actingAs(
            $actor,
        );

        // Simulate a post request to the api
        $req = Request::create(route('mastodon.v1.statuses.post'), 'POST', ['status' => $status]);
        $response = $this->laravel->handle($req);

        $this->info($response->getStatusCode() . ' - ' . $response->getContent());

        return Command::SUCCESS;

    }
}
