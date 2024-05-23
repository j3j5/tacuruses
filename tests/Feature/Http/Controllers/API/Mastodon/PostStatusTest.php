<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\API\Mastodon;

use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\Media;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Stevebauman\Purify\Facades\Purify;
use Tests\TestCase;

class PostStatusTest extends TestCase
{

    use LazilyRefreshDatabase;
    use WithFaker;

    /**
     * A basic feature test posting plain text.
     *
     * @return void
     */
    public function test_post_status_plain_text(): void
    {
        $actor = LocalActor::factory()->create();
        Sanctum::actingAs(
            $actor,
        );

        $status = $this->faker->sentences(random_int(1, 5), true);
        $response = $this->post(route('mastodon.v1.statuses.post'), [
            'status' => $status,
        ]);

        $expectedContent = Purify::config('mastodon')->clean($status);
        $response->assertCreated();
        $response->assertJsonFragment([
            'original_content' => $status,
            'content' => $expectedContent,
            'contentMap' => [$actor->language => $status],
            'replyTo_id' => null,
            'summary' => null,
            // "visibility" => $this->visibility,
            // "to" => $this->to,
            // "cc" => $this->cc,
            // "tags" => $this->tags,
            // 'published_at' => now()->milliseconds(0)->toJSON(),
        ]);

        $note = new LocalNote();
        $note->id = $response->json('id');
        $this->assertModelExists($note);
    }

    /**
     * A basic feature test posting basic text with HTML.
     *
     * @return void
     */
    public function test_post_status_basic_html(): void
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
        $response->assertJsonFragment([
            'original_content' => $status,
            'content' => $status,
            'contentMap' => [$actor->language => $status],
            'replyTo_id' => null,
            'summary' => null,
            // "visibility" => $this->visibility,
            // "to" => $this->to,
            // "cc" => $this->cc,
            // "tags" => $this->tags,
            // 'published_at' => now()->milliseconds(0)->toJSON(),
        ]);

        $note = new LocalNote();
        $note->id = $response->json('id');
        $this->assertModelExists($note);

    }

    /**
     * A basic feature test posting plain text with a URL
     *
     * @return void
     */
    public function test_post_status_plain_text_with_url(): void
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

        $anchor = '<a class="post-url external-url" href="https://example.com" rel="external nofollow noreferrer noopener" target="_blank">https://example.com</a>';

        $response->assertCreated()
            ->assertJsonFragment([
                'original_content' => $status,
                'content' => Purify::config('mastodon')->clean(str_replace($url, $anchor, $status)),
                'contentMap' => [$actor->language => str_replace($url, $anchor, $status)],
                'replyTo_id' => null,
                'summary' => null,
                // "visibility" => $this->visibility,
                // "to" => $this->to,
                // "cc" => $this->cc,
                // "tags" => $this->tags,
                // 'published_at' => now()->milliseconds(0)->toJSON(),
            ]);

        $note = new LocalNote();
        $note->id = $response->json('id');
        $this->assertModelExists($note);
    }

    /**
     * A basic feature test posting plain text.
     *
     * @return void
     */
    public function test_post_status_plain_text_with_hashtag(): void
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

        $hashtagUrl = route('tag.show', $hashtag);
        $anchor = '<a href="' . $hashtagUrl . '" title="#' . $hashtag . '" class="post-url hashtag" target="_blank">#' . $hashtag . '</a>';

        $response->assertCreated();
        $response->assertJsonFragment([
            'original_content' => $status,
            'content' => Purify::config('mastodon')->clean(str_replace("#$hashtag", $anchor, $status)),
            'contentMap' => [$actor->language => str_replace("#$hashtag", $anchor, $status)],
            'replyTo_id' => null,
            'summary' => null,
            // "visibility" => $this->visibility,
            // "to" => $this->to,
            // "cc" => $this->cc,
            // "tags" => $this->tags,
            // 'published_at' => now()->milliseconds(0)->toJSON(),
        ]);

        $note = new LocalNote();
        $note->id = $response->json('id');
        $this->assertModelExists($note);
    }

    public function test_post_status_with_media(): void
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
        $response->assertJsonFragment([
            'original_content' => $status,
            'content' => Purify::config('mastodon')->clean($status),
            'contentMap' => [$actor->language => $status],
            'replyTo_id' => null,
            'summary' => null,
            // "visibility" => $this->visibility,
            // "to" => $this->to,
            // "cc" => $this->cc,
            // "tags" => $this->tags,
            // 'published_at' => now()->milliseconds(0)->toJSON(),
        ]);

        $note = LocalNote::findOrFail($response->json('id'));
        $this->assertSame(count($options['media']), $note->mediaAttachments->count());
        $note->mediaAttachments->each(function (Media $media, int $index) use ($options) {
            $this->assertSame($options['media'][$index]['mediaType'], $media->content_type);
            $this->assertSame($options['media'][$index]['url'], $media->remote_url);
            $this->assertSame($options['media'][$index]['name'], $media->description);
        });
    }
}
