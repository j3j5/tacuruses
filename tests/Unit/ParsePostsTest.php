<?php

namespace Tests\Unit;

use App\Domain\Application\Note;
use App\Models\ActivityPub\LocalActor;
use App\Processes\PublishPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ParsePostsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private PublishPost $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = app(PublishPost::class);
    }

    public function test_new_lines_on_plain_text_statuses_get_converted_to_brs()
    {
        $actor = LocalActor::factory()->create();
        $sentences = $this->faker->sentences();
        $status = implode("\n", $sentences);
        $dto = new Note(
            actor: $actor,
            attributes: [
                'status' => $status,
            ],
        );
        $note = $this->process->run($dto);

        $this->assertSame('<p>' . str_replace(PHP_EOL, '', nl2br($status, false)) . '</p>', $note->getModel()->content);
    }

    public function test_html_content_with_new_lines_does_not_get_changed()
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
