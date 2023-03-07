<?php

namespace App\Http\Controllers\ActivityPub\Instance;

use App\Http\Controllers\Controller;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Support\Facades\Cache;

class InstanceController extends Controller
{
    public function apiV1() : array
    {
        $cacheTTL = now()->addHour();

        return [
            'uri' => config('app.url'),
            'title' => 'bots.uy',
            'short_description' => 'A federated service to empower all my bots',
            'description' => '',
            'email' => config('federation.contact_email'),
            'version' => config('app.version' . '1.0.0'),
            'urls' => [
            ],
            'stats' => [
              'user_count' => Cache::remember('total-users', $cacheTTL, function () {
                  return LocalActor::count();
              }),
              'status_count' => Cache::remember('local-posts', $cacheTTL, function () {
                  return LocalNote::count();
              }),
              'domain_count' => 1,
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
                'max_media_attachments' => config('federation.'),
                'characters_reserved_per_url' => config('federation.'),
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
        return [];
    }

    private function getContactAccount() : array
    {
        return [
            'id' => '1',
            'username' => 'j3j5',
            'acct' => 'j3j5',
            'display_name' => 'Julio J.',
            'locked' => false,
            'bot' => false,
            'discoverable' => false,
            'group' => false,
            'created_at' => '2022-12-31T23:59:59.000Z',
            'note' => '',
            'url' => '',
            'avatar' => '',
            'avatar_static' => '',
            'header' => '',
            'header_static' => '',
            'followers_count' => 0,
            'following_count' => 0,
            'statuses_count' => 0,
            'last_status_at' => '2023-02-21',
            'noindex' => true,
            'emojis' => [],
            'roles' => [],
            'fields' => [],
        ];
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
