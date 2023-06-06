<?php

namespace Tests\Feature\API\Mastodon;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostStatusTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * A basic feature test posting plain text.
     *
     * @return void
     */
    public function test_post_status_plain_text()
    {
        $actor = LocalActor::factory()->create();
        Sanctum::actingAs(
            $actor,
        );

        $status = $this->faker->sentences(random_int(1, 5), true);
        $response = $this->post(route('mastodon.v1.statuses'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::latest()->first();

        $expected = '<p>' . $status . '</p>';
        $this->assertSame($expected, $note->content);
    }

    /**
     * A basic feature test posting basic text with HTML.
     *
     * @return void
     */
    public function test_post_status_basic_html()
    {
        $actor = LocalActor::factory()->create();
        Sanctum::actingAs(
            $actor,
        );
        $status = '<p>' . $this->faker->sentences(random_int(1, 5), true) . '</p>';

        $response = $this->post(route('mastodon.v1.statuses'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::latest()->first();

        $expected = $status;
        $this->assertSame($expected, $note->content);
    }

    /**
     * A basic feature test posting plain text with a URL
     *
     * @return void
     */
    public function test_post_status_plain_text_with_url()
    {
        $actor = LocalActor::factory()->create();
        Sanctum::actingAs(
            $actor,
        );
        $url = 'https://example.com';
        $status = trim($this->faker->sentences(random_int(0, 3), true) . ' ' . $url . ' ' . $this->faker->sentences(random_int(0, 3), true));

        $response = $this->post(route('mastodon.v1.statuses'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::latest()->first();

        $anchor = '<a class="post-url external-url" href="https://example.com" rel="external nofollow noreferrer noopener" target="_blank">https://example.com</a>';
        $expected = '<p>' . str_replace($url, $anchor, $status) . '</p>';
        $this->assertSame($expected, $note->content);
    }

        /**
     * A basic feature test posting plain text.
     *
     * @return void
     */
    public function test_post_status_plain_text_with_hashtag()
    {
        $actor = LocalActor::factory()->create();
        Sanctum::actingAs(
            $actor,
        );
        $hashtag = $this->faker->word();
        $status = $this->faker->sentences(random_int(1, 5), true) . ' #' . $hashtag;

        $response = $this->post(route('mastodon.v1.statuses'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::latest()->first();

        $hashtagUrl = route('tag.show', $hashtag);
        $anchor = '<a href="' . $hashtagUrl . '" title="#' . $hashtag . '" class="post-url hashtag" target="_blank" rel="noreferrer noopener">#' . $hashtag . '</a>';
        $expected = '<p>' . str_replace("#$hashtag", $anchor, $status) . '</p>';
        $this->assertSame($expected, $note->content);
    }
}
