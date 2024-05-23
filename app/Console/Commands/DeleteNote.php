<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ActivityPub\SendDeleteNoteToInstance;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Console\Command;

class DeleteNote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:delete-note {note}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a note from the server and the fedi';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = LocalNote::findOrFail($this->argument('note'));

        // It should be sent to all known instances
        $instances = RemoteActor::query()->groupBy('sharedInbox')->pluck('sharedInbox');

        $instances->each(
            fn (string $inbox) => SendDeleteNoteToInstance::dispatch(note: $note, inbox: $inbox)
        );

        $note->delete();

        return Command::SUCCESS;
    }
}
