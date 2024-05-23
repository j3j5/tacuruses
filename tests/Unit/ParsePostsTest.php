<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Application\Note;
use App\Models\ActivityPub\LocalActor;
use App\Processes\PublishPost;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ParsePostsTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    private PublishPost $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = app(PublishPost::class);
    }

    public function test_plain_text_note_with_hashtag_and_link(): void
    {
        $actor = LocalActor::factory()->create();
        $hashtag = 'humblebrag';
        $url = 'https://example.com/example/link';
        $status = 'just setting up my fedibot' . PHP_EOL . "#$hashtag $url";
        $dto = new Note(
            actor: $actor,
            attributes: [
                'status' => $status,
            ],
        );
        $note = $this->process->run($dto);

        $hashtagAnchor = '<a href="https://example.com/tags/' . $hashtag . '" title="#' . $hashtag . '" class="post-url hashtag" target="_blank" rel="noreferrer noopener">#' . $hashtag . '</a>';
        $urlAnchor = '<a class="post-url external-url" href="' . $url . '" rel="external nofollow noreferrer noopener" target="_blank">' . $url . '</a>';
        $expected = '<p>just setting up my fedibot'
            . '<br>'
            . $hashtagAnchor
            . ' '
            . $urlAnchor
            . '</p>';

        $this->assertSame($expected, $note->getModel()->content);
    }

    public function test_html_note_with_hashtag_and_link(): void
    {
        $actor = LocalActor::factory()->create();
        $status = '<p>' . implode("\n", $this->faker->sentences()) . '</p>';
        $dto = new Note(
            actor: $actor,
            attributes: [
                'status' => $status,
            ],
        );
        $note = $this->process->run($dto);

        $this->assertSame($status, $note->getModel()->content);
    }
}
