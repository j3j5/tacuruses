<?php

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
        return new Media(
            actor: $this->user(),
            attributes: $this->validated()
        );
    }
}
