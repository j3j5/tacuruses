<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ActivityPub;

use ActivityPhp\Type\Core\Collection;
use ActivityPhp\Type\Core\CollectionPage;
use App\Domain\ActivityPub\Mastodon\Note as MastodonNote;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\Note;
use App\Services\ActivityPub\Context;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteRepliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_accepting_html_get_404(): void
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $response = $this->get(route('note.replies', [$actor, $note]));
        $response->assertNotFound();
    }

    public function test_requests_accepting_json_get_json_activity_note(): void
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $response = $this->get(route('note.replies', [$actor, $note]), [
            'Accept' => 'application/activity+json',
        ]);

        $collection = new Collection();
        $collection->set('@context', Context::ACTIVITY_STREAMS);
        $collection->set('id', route('note.replies', [$actor, $note]));
        $page = new CollectionPage();
        $page->set('id', route('note.replies', [$actor, $note, 'page' => 1]));
        $page->set('next', route('note.replies', [$actor, $note, 'page' => 1]));
        $page->set('partOf', route('note.replies', [$actor, $note]));
        $page->set('items', []);
        $collection->set('first', $page);
        $expected = $collection->toArray();

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected);
    }

    public function test_requests_accepting_json_get_json_activity_note_resource(): void
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();
        $reply = LocalNote::factory()
            ->for($actor, 'actor')
            ->unlisted()
            ->create(['replyTo_id' => $note->id]);

        $response = $this->get(route('note.replies', [$actor, $note, 'page' => 1]), [
            'Accept' => 'application/activity+json',
        ]);

        $context = [
            Context::ACTIVITY_STREAMS,
            Context::$status,
        ];

        $collection = new Collection();
        $collection->id = route('note.replies', [$actor, $note]);
        $collection->set('@context', $context);

        $page = new CollectionPage();
        $page->id = route('note.replies', [$actor, $note, 'page' => 1]);
        $page->next = route('note.replies', [$actor, $note, 'page' => 1]);
        $page->partOf = route('note.replies', [$actor, $note]);
        $page->items = $note->directReplies->map(fn (Note $n) : MastodonNote => $n->getAPNote())->toArray();

        $collection->first = $page;
        $expected = $collection->toArray();

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($expected);
    }
}
