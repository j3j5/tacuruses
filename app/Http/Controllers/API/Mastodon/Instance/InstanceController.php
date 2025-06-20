<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Mastodon\Instance;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use App\Models\ActivityPub\RemoteActor;
use App\Scopes\Actors\IsActive;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class InstanceController extends Controller
{
    public readonly Carbon $cacheTTLmin;
    public readonly Carbon $cacheTTLmax;

    public function __construct()
    {
        $this->cacheTTLmin = now()->addHour();
        $this->cacheTTLmax = now()->addHours(24);
    }

    public function apiV1() : array
    {

        return [
            'uri' => config('app.url'),
            'title' => config('instance.title'),
            'short_description' => config('instance.short_description'),
            'description' => config('instance.description'),
            'email' => config('federation.contact_email'),
            'version' => config('instance.software_version'),
            'urls' => [

            ],
            'stats' => [
                'user_count' => Cache::flexible('total-users', [$this->cacheTTLmin, $this->cacheTTLmax], function () {
                    return LocalActor::count();
                }),
                'status_count' => Cache::flexible('local-notes', [$this->cacheTTLmin, $this->cacheTTLmax], function () {
                    return LocalNote::count();
                }),
                'domain_count' => Cache::flexible('total-domains', [$this->cacheTTLmin, $this->cacheTTLmax], function () {
                    return RemoteActor::distinct('sharedInbox')->count();
                }),
            ],
            'thumbnail' => '',
            'languages' => config('federation.languages'),
            'registrations' => false,
            'approval_required' => true,
            'invites_enabled' => false,
            'configuration' => [
                'accounts' => [
                  'max_featured_tags' => config('federation.max_featured_tags'),
                ],
                'statuses' => [
                  'max_characters' => config('federation.max_characters'),
                  'max_media_attachments' => config('federation.max_media_attachments'),
                  'characters_reserved_per_url' => config('federation.characters_reserved_per_url'),
                ],
                'media_attachments' => [
                  'supported_mime_types' => config('federation.supported_mime_types'),
                  'image_size_limit' => config('federation.media_attachments.image_size_limit'),
                  'image_matrix_limit' => config('federation.media_attachments.image_matrix_limit'),
                  'video_size_limit' => config('federation.media_attachments.video_size_limit'),
                  'video_frame_rate_limit' => config('federation.media_attachments.video_frame_rate_limit'),
                  'video_matrix_limit' => config('federation.media_attachments.video_matrix_limit'),
                ],
                'polls' => [
                  'max_options' => 4,
                  'max_characters_per_option' => 50,
                  'min_expiration' => 300,
                  'max_expiration' => 2629746,
                ],
            ],
            'contact_account' => $this->getContactAccount(),
            'rules' => $this->getRules(),
        ];
    }

    public function apiV2() : array
    {
        return [
            'domain' => config('app.url'),
            'title' => 'Tacuruses',
            'version' => config('instance.software_version'),
            'source_url' => 'https://gitlab.com/j3j5/tacuruses',
            'description' => config('instance.description'),
            'usage' => [
                'users' => [
                    'active_month' => Cache::flexible('active-users', [$this->cacheTTLmin, $this->cacheTTLmax], fn () => LocalActor::query()->tap(new IsActive(now()->subMonth()))->count()),
                ],
            ],
            'thumbnail' => [
                'url' => '',
                'blurhash' => '',
                'versions' => [
                    '@1x' => '',
                    '@2x' => '',
                ],
            ],
            'icon' => [
                // [
                //     "src" => "",
                //     "size" => "36x36"
                // ],
                // [
                //     "src" => "",
                //     "size" => "72x72"
                // ],
                // [
                //     "src" => "",
                //     "size" => "192x192"
                // ],
                // [
                //     "src" => "",
                //     "size" => "512x512"
                // ]
            ],
            'languages' => config('federation.languages'),
            'configuration' => [
                'urls' => [],
                'vapid' => [
                    // "public_key" => ""
                ],
                'accounts' => [
                  'max_featured_tags' => config('federation.max_featured_tags'),
                  'max_pinned_statuses' => config('federation.max_pinned_statuses'),
                ],
                'statuses' => [
                  'max_characters' => config('federation.max_characters'),
                  'max_media_attachments' => config('federation.max_media_attachments'),
                  'characters_reserved_per_url' => config('federation.characters_reserved_per_url'),
                ],
                'media_attachments' => [
                  'supported_mime_types' => config('federation.supported_mime_types'),
                  'description_limit' => config('federation.media_attachments.description_limit'),
                  'image_size_limit' => config('federation.media_attachments.image_size_limit'),
                  'image_matrix_limit' => config('federation.media_attachments.image_matrix_limit'),
                  'video_size_limit' => config('federation.media_attachments.video_size_limit'),
                  'video_frame_rate_limit' => config('federation.media_attachments.video_frame_rate_limit'),
                  'video_matrix_limit' => config('federation.media_attachments.video_matrix_limit'),
                ],

                'polls' => [
                  'max_options' => 4,
                  'max_characters_per_option' => 50,
                  'min_expiration' => 300,
                  'max_expiration' => 2629746,
                ],

                'translation' => [
                    'enabled' => false,
                ],
            ],

            'registrations' => [
                'enabled' => false,
                'approval_required' => true,
                'message' => null,
            ],
            'contact' => [
                'email' => config('federation.contact_email'),
                // 'account' => [
                //     'id' => '1',
                //     'username' => 'Gargron',
                //     'acct' => 'Gargron',
                //     'display_name' => 'Eugen 💀',
                //     'locked' => false,
                //     'bot' => false,
                //     'discoverable' => true,
                //     'group' => false,
                //     'created_at' => '2016-03-16T00:00:00.000Z',
                //     'note' => '<p>Founder, CEO and lead developer <span class="h-card"><a href="https://mastodon.social/@Mastodon" class="u-url mention">@<span>Mastodon</span></a></span>, Germany.</p>',
                //     'url' => 'https://mastodon.social/@Gargron',
                //     'avatar' => 'https://files.mastodon.social/accounts/avatars/000/000/001/original/dc4286ceb8fab734.jpg',
                //     'avatar_static' => 'https://files.mastodon.social/accounts/avatars/000/000/001/original/dc4286ceb8fab734.jpg',
                //     'header' => 'https://files.mastodon.social/accounts/headers/000/000/001/original/3b91c9965d00888b.jpeg',
                //     'header_static' => 'https://files.mastodon.social/accounts/headers/000/000/001/original/3b91c9965d00888b.jpeg',
                //     'followers_count' => 133026,
                //     'following_count' => 311,
                //     'statuses_count' => 72605,
                //     'last_status_at' => '2022-10-31',
                //     'noindex' => false,
                //     'emojis' => [],
                //     'fields' => [
                //         [
                //         'name' => 'Patreon',
                //         'value' => '<a href="https://www.patreon.com/mastodon" target="_blank" rel="nofollow noopener noreferrer me"><span class="invisible">https://www.</span><span class="">patreon.com/mastodon</span><span class="invisible"></span></a>',
                //         'verified_at' => null,
                //         ],
                //     ],
                // ],
            ],
            'rules' => $this->getRules(),
        ];
    }

    private function getContactAccount() : array
    {
        // TODO: Create a way to mark who is or isn't an admin
        try {
            return LocalActor::firstOrFail()->getAPActor()->toArray();
        } catch (ModelNotFoundException) {
            return [];
        }
    }

    private function getRules() : array
    {
        return [
            [
              'id' => '1',
              'text' => "No humans, good bots only.\r\n",
            ],
        ];
    }
}
