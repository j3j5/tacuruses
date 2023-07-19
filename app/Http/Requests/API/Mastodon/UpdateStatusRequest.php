<?php

namespace App\Http\Requests\API\Mastodon;

use App\Domain\Application\Note as ApplicationNote;

class UpdateStatusRequest extends ApiRequest
{
    /**
     * Only authorize requests for notes that belong to the actor
     * @return bool
     */
    public function authorize() : bool
    {
        $note = $this->route()->parameter('status');

        return parent::authorize() && $note->actor_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'status' => 'string|required_without_all:spoiler_text,sensitive,language,media_ids,media',
            'spoiler_text' => 'string|required_without_all:status,sensitive,language,media_ids,media',
            'sensitive' => 'boolean|required_without_all:status,spoiler_text,language,media_ids,media',
            'language' => 'string|count:2|required_without_all:status,spoiler_text,sensitive,media_ids,media', // ISO 639 language code for the status.
            'media_ids' => 'array|required_without_all:status,spoiler_text,sensitive,language,media',
            'media' => 'array|required_without_all:status,spoiler_text,sensitive,language,media_ids',
            'media.*.mediaType' => 'required_with:media,string', // valid mime
            'media.*.url' => 'required_with:media,url',
            'media.*.name' => 'required_with:media,string',
            // 'poll[options][]' => '', // Array of String. Possible answers to the poll. If provided, media_ids cannot be used, and poll[expires_in] must be provided.
            // 'poll[expires_in]' => '', // Integer. Duration that the poll should be open, in seconds. If provided, media_ids cannot be used, and poll[options] must be provided.
            // 'poll[multiple]' => '', // Boolean. Allow multiple choices? Defaults to false.
            // 'poll[hide_totals]' => '', // Boolean. Hide vote counts until the poll ends? Defaults to false.
        ];
    }

    public function getDTO() : ApplicationNote
    {
        /** @var \App\Models\ActivityPub\LocalNote $note */
        $note = $this->route()->parameter('status');
        /** @var \App\Models\ActivityPub\LocalActor $actor   */
        $actor = $this->user();
        $dto = new ApplicationNote(
            actor: $actor,
            attributes: array_merge($note->toArray(), $this->validated())
        );
        $dto->setModel($note);

        return $dto;
    }
}
