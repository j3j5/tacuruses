<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ActivityPub;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_accepting_html_get_404(): void
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $response = $this->get(route('note.activity', [$actor, $note]));
        $response->assertNotFound();
    }

    public function test_requests_accepting_json_get_json_activity_note(): void
    {
        $actor = LocalActor::factory()->create();
        $note = LocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create();

        $response = $this->get(route('note.activity', [$actor, $note]), [
            'Accept' => 'application/activity+json',
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json; charset=UTF-8')
            ->assertExactJson($note->getAPActivity()->toArray());
    }
}
