<?php

namespace Tests\Unit\Models;

use App\Events\LocalNotePublished;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote as ActivityPubLocalNote;
use App\Services\ActivityPub\Context;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\TestCase;

class LocalNoteTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_fill_recipients_works_as_expected(): void
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor */
        $actor = LocalActor::factory()->create();
        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = ActivityPubLocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create(['published_at' => null]);

        $note->fillRecipients();

        $this->assertSame([Context::ACTIVITY_STREAMS_PUBLIC], $note->to);
        $this->assertSame([$note->actor->followers_url], $note->cc);

        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = ActivityPubLocalNote::factory()
            ->for($actor, 'actor')
            ->unlisted()
            ->create(['published_at' => null]);
        $this->assertSame([$note->actor->followers_url], $note->to);
        $this->assertSame([Context::ACTIVITY_STREAMS_PUBLIC], $note->cc);

        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = ActivityPubLocalNote::factory()
            ->for($actor, 'actor')
            ->private()
            ->create(['published_at' => null]);
        $this->assertSame([$note->actor->followers_url], $note->to);
        $this->assertSame([], $note->cc);
    }

    public function test_it_properly_fires_the_event_when_publishing()
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor */
        $actor = LocalActor::factory()->create();
        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = ActivityPubLocalNote::factory()
            ->for($actor, 'actor')
            ->public()
            ->create(['published_at' => null]);

        Event::fake();
        $note->publish();

        Event::assertDispatched(
            LocalNotePublished::class,
            fn (LocalNotePublished $event) => $event->note->is($note)
        );
    }
}
