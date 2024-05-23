<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Application\Note;
use App\Jobs\Application\Posts\CreateNewPost;
use App\Jobs\Application\Posts\ParseLinks;
use App\Models\ActivityPub\LocalActor;
use App\Processes\Process;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ParseLinksTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    private object $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = new class() extends Process {
            protected array $tasks = [
                CreateNewPost::class,
                ParseLinks::class,
            ];
        };
    }

    public function test_plain_text_content_replaces_hashtag_with_link(): void
    {
        $actor = LocalActor::factory()->create();
        $url = 'https://example.com/';
        $status = $this->faker->sentences(random_int(1, 4), true) . " $url";
        $dto = new Note(
            actor: $actor,
            attributes: [
                'status' => $status,
            ],
        );

        $note = $this->process->run($dto);
        $anchor = '<a class="post-url external-url" href="' . $url . '" rel="external nofollow noreferrer noopener" target="_blank">' . $url . '</a>';
        $expected = '<p>' . str_replace($url, $anchor, $status) . '</p>';

        $this->assertSame($expected, $note->getModel()->content);
    }

    public function test_html_content_replaces_hashtag_with_link(): void
    {
        $actor = LocalActor::factory()->create();
        $link = 'https://example.com';
        $status = '<p>' . $this->faker->sentences(random_int(1, 4), true) . " $link" . '</p>';
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
