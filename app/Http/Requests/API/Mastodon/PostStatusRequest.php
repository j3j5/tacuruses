<?php

declare(strict_types=1);

namespace App\Http\Requests\API\Mastodon;

use App\Domain\Application\Note;

class PostStatusRequest extends ApiRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return Note::$rules;
    }

    public function getDTO() : Note
    {
        /** @var \App\Models\ActivityPub\LocalActor $actor   */
        $actor = $this->user();
        return new Note(
            actor: $actor,
            attributes: $this->validated()
        );
    }

}
