<?php

declare(strict_types=1);

namespace App\Http\Requests\API\Mastodon;

use App\Domain\Application\Media;

class PostMediaRequest extends ApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return Media::$rules;
    }

    public function getDTO() : Media
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor   */
        $actor = $this->user();
        return new Media(
            actor: $actor,
            attributes: $this->validated()  // @phpstan-ignore-line
        );
    }
}
