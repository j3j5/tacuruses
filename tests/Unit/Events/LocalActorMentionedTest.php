<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\RemoteActor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class LocalActorMentionedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic unit test example.
     */
    public function test_event_is_dispatched(): void
    {
        $this->markTestIncomplete('todo');
        /** @var \App\Models\ActivityPub\LocalActor $localActor */
        // $localActor = LocalActor::factory()->create();
        // $remoteActors = RemoteActor::factory()->count(random_int(2, 10))->create();

    }
}
