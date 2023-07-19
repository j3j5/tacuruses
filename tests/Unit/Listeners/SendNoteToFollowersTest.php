<?php

namespace Tests\Unit\Listeners;

use App\Events\LocalNotePublished;
use App\Listeners\SendNoteToFollowers;
use App\Models\ActivityPub\Follow;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendNoteToFollowersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_attached_to_event()
    {
        Event::fake();
        Event::assertListening(
            LocalNotePublished::class,
            SendNoteToFollowers::class
        );
    }

    public function test_it_properly_includes_sends_to_all_recipients()
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();
        $remoteActors = RemoteActor::factory()->count(random_int(2, 10))->create();

        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->for($localActor, 'target')
                ->for($remoteActor, 'actor')
                ->create();
        }

        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = LocalNote::factory()
            ->for($localActor, 'actor')
            ->public()
            ->create(['published_at' => null]);

        Http::fake();
        $note->publish();

        Http::assertSentCount($remoteActors->count());
        $remoteActors->each(function (RemoteActor $actor) {
            Http::assertSent(fn (Request $request) : bool => $request->url() === $actor->sharedInbox);
            Http::assertNotSent(fn (Request $request) : bool => $request->url() === $actor->inbox);
        });

    }

    public function test_it_groups_recipients_with_common_shared_inbox()
    {
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        $localActor = LocalActor::factory()->create();
        $remoteActors = RemoteActor::factory()->count(random_int(2, 10))->create();
        $sharedServerActors = random_int(1, 5);
        for ($i = 0; $i < $sharedServerActors; $i++) {
            $remoteActors->push(RemoteActor::factory()->create(['sharedInbox' => $remoteActors->random()->sharedInbox]));
        }

        $follows = [];
        foreach ($remoteActors as $remoteActor) {
            $follows[] = Follow::factory()
                ->for($localActor, 'target')
                ->for($remoteActor, 'actor')
                ->create();
        }

        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = LocalNote::factory()
            ->for($localActor, 'actor')
            ->public()
            ->create(['published_at' => null]);

        Http::fake();
        $note->publish();

        Http::assertSentCount($remoteActors->count() - $sharedServerActors);
        $remoteActors->each(function (RemoteActor $actor) {
            Http::assertSent(fn (Request $request) : bool => $request->url() === $actor->sharedInbox);
            Http::assertNotSent(fn (Request $request) : bool => $request->url() === $actor->inbox);
        });
    }

    public function test_it_deduplicates_recipients()
    {
        $this->markTestIncomplete();
    }
}
