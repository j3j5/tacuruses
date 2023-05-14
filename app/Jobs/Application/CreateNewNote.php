<?php

namespace App\Jobs\Application;

use App\Enums\Visibility;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\Media;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class CreateNewNote
{
    use Dispatchable, SerializesModels;

    private LocalActor $actor;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected array $data)
    {
        $this->actor = $this->data['actor'];
    }

    /**
     * Execute the job.
     */
    public function handle() : LocalNote
    {
        $note = new LocalNote(['type' => 'Note']);
        $note->actor_id = $this->actor->id;
        $note->setRelation('actor', $this->actor);

        $note->content = $this->data['content'] ?? '';
        if (empty($note->content)) {
            $note->content = $this->data['status'] ?? '';
        }

        $note->replyTo_id = $this->data['replyTo_id'] ?? null;
        if (empty($note->replyTo_id)) {
            $note->replyTo_id = $this->data['in_reply_to_id'] ?? null;
        }

        $note->sensitive = $this->data['sensitive'] ?? false;
        $note->summary = $this->data['spoiler_text'] ?? null;
        $note->visibility = $this->data['visibility'] ?? Visibility::PRIVATE;
        $note->fillRecipients();
        // $note->language = $this->actor->language;
        // MEDIA!!

        $note->save();

        Media::whereIn('id', Arr::get($this->data, 'media_ids', ''))->update(['note_id' => $note->id]);

        return $note;
    }
}
