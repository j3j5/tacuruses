<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Application\Note;
use App\Jobs\Application\Posts\CreateNewPost;
use App\Jobs\Application\Posts\ParseNewLines;
use App\Models\ActivityPub\LocalActor;
use App\Processes\Process;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ParseNewLinesTest extends TestCase
{
    use LazilyRefreshDatabase, WithFaker;

    private object $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = new class() extends Process {
            protected array $tasks = [
                CreateNewPost::class,
                ParseNewLines::class,
            ];
        };
    }

    public function test_new_lines_on_plain_text_statuses_get_converted_to_brs(): void
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

    public function test_html_content_with_new_lines_does_not_get_changed(): void
    {
        $actor = LocalActor::factory()->create();
        $status = array_reduce(
            $this->faker->sentences(),
            fn (string $status, string $sentence) => "$status<p>$sentence</p>" . PHP_EOL,
            ''
        );
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
