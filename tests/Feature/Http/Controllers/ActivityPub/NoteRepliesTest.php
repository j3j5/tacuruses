<?php

namespace Tests\Feature\Http\Controllers\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteRepliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_accepting_html_get_404()
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->create();

        $response = $this->get(route('note.replies', [$actor, $note]));
        $response->assertNotFound();
    }

    public function test_requests_accepting_json_get_json_activity_note()
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->create();

        $response = $this->get(route('note.replies', [$actor, $note]), [
            'Accept' => 'application/activity+json',
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($note->getAPActivity()->toArray());
    }
}
