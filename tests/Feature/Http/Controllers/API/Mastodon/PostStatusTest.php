<?php

namespace Tests\Feature\Http\Controllers\API\Mastodon;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\Media;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostStatusTest extends TestCase
{

    use LazilyRefreshDatabase;
    use WithFaker;

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
        $response = $this->post(route('mastodon.v1.statuses.post'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::findOrFail($response->json('id'));

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

        $response = $this->post(route('mastodon.v1.statuses.post'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::findOrFail($response->json('id'));

        $expected = $status;
        $this->assertSame($expected, $note->content);
        $this->assertNotNull($note->published_at);
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

        $response = $this->post(route('mastodon.v1.statuses.post'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::findOrFail($response->json('id'));

        $anchor = '<a class="post-url external-url" href="https://example.com" rel="external nofollow noreferrer noopener" target="_blank">https://example.com</a>';
        $expected = '<p>' . str_replace($url, $anchor, $status) . '</p>';
        $this->assertSame($expected, $note->content);
        $this->assertNotNull($note->published_at);
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

        $response = $this->post(route('mastodon.v1.statuses.post'), [
            'status' => $status,
        ]);

        $response->assertCreated();
        $note = LocalNote::findOrFail($response->json('id'));

        $hashtagUrl = route('tag.show', $hashtag);
        $anchor = '<a href="' . $hashtagUrl . '" title="#' . $hashtag . '" class="post-url hashtag" target="_blank" rel="noreferrer noopener">#' . $hashtag . '</a>';
        $expected = '<p>' . str_replace("#$hashtag", $anchor, $status) . '</p>';
        $this->assertSame($expected, $note->content);
        $this->assertNotNull($note->published_at);
    }

    public function test_post_status_with_media()
    {
        $actor = LocalActor::factory()->create();
        Sanctum::actingAs(
            $actor,
        );

        $status = $this->faker->sentences(random_int(1, 5), true);

        $options = ['media' => []];
        for ($i = 0; $i < random_int(2, 4); $i++) {
            $options['media'][] = [
                'mediaType' => random_int(0, 1) ? 'image/jpeg' : 'image/png',
                'url' => fake()->imageUrl(),
                'name' => fake()->sentences(3, true),
            ];
        }
        $response = $this->post(route('mastodon.v1.statuses.post'), array_merge([
            'status' => $status,
        ], $options));

        $response->assertCreated();

        $note = LocalNote::findOrFail($response->json('id'));
        $this->assertSame(count($options['media']), $note->mediaAttachments->count());
        $note->mediaAttachments->each(function (Media $media, int $index) use ($options) {
            $this->assertSame($options['media'][$index]['mediaType'], $media->content_type);
            $this->assertSame($options['media'][$index]['url'], $media->remote_url);
            $this->assertSame($options['media'][$index]['name'], $media->description);
        });
    }
}
