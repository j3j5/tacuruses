<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Application\Note;
use App\Jobs\Application\Posts\CreateNewPost;
use App\Jobs\Application\Posts\ParseHashtags;
use App\Models\ActivityPub\LocalActor;
use App\Processes\Process;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ParseHashtagsTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    private object $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = new class() extends Process {
            protected array $tasks = [
                CreateNewPost::class,
                ParseHashtags::class,
            ];
        };
    }

    public function test_plain_text_content_replaces_hashtag_with_link(): void
    {
        $actor = LocalActor::factory()->create();
        $hashtag = 'hashtag';
        $status = $this->faker->sentences(random_int(1, 4), true) . " #$hashtag";
        $dto = new Note(
            actor: $actor,
            attributes: [
                'status' => $status,
            ],
        );

        $note = $this->process->run($dto);

        $hashtagLink = '<a href="https://example.com/tags/' . $hashtag . '" title="#' . $hashtag . '" class="post-url hashtag" target="_blank" rel="noreferrer noopener">#' . $hashtag . '</a>';
        $expected = '<p>' . str_replace(
            '#hashtag',
            $hashtagLink,
            $status
        ) . '</p>';

        $this->assertSame($expected, $note->getModel()->content);
    }

    public function test_html_content_replaces_hashtag_with_link(): void
    {
        $actor = LocalActor::factory()->create();
        $hashtag = 'hashtag';
        $status = '<p>' . $this->faker->sentences(random_int(1, 4), true) . " #$hashtag" . '</p>';
        $dto = new Note(
            actor: $actor,
            attributes: [
                'status' => $status,
            ],
        );

        $note = $this->process->run($dto);

        $hashtagLink = '<a href="https://example.com/tags/' . $hashtag . '" title="#' . $hashtag . '" class="post-url hashtag" target="_blank" rel="noreferrer noopener">#' . $hashtag . '</a>';
        $expected = str_replace(
            '#hashtag',
            $hashtagLink,
            $status
        );

        $this->assertSame($expected, $note->getModel()->content);
    }
}
