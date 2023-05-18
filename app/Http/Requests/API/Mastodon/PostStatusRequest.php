<?php

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
        return new Note(
            actor: $this->user(),
            attributes: $this->validated()
        );
    }

}
