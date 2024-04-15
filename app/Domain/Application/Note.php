<?php

declare(strict_types=1);

namespace App\Domain\Application;

use App\Enums\Visibility;
use App\Exceptions\AppException;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

/**
 *
 * @property \App\Models\ActivityPub\LocalActor $actor
 * @property ?string $status
 * @property ?array $media_ids
 * @property ?array $media
 * @property ?string $in_reply_to_id
 * @property ?bool $sensitive
 * @property ?string $spoiler_text
 * @property ?string $visibility
 * @property ?string $language
 * @property ?string $scheduled_at
 * @property ?bool $draft
 * @property bool $plain_text
 */
class Note extends Fluent
{
    public static array $rules = [
        'actor' => 'required',
        'status' => 'string|required_without_all:media_ids,media',
        'media_ids' => 'array|required_without_all:status,media',
        'media' => 'array|required_without_all:status,media_ids',
        'media.*.mediaType' => 'required_with:media,string', // valid mime
        'media.*.url' => 'required_with:media,url',
        'media.*.name' => 'required_with:media,string',
        'inReplyTo' => 'string',    // activityId in case we're replying to a note from another server that does not exist yet
        'in_reply_to_id' => 'string|exists:notes,id',
        'replyTo_id' => 'string|exists:notes,id', // ☝️ alias
        'sensitive' => 'boolean',
        'spoiler_text' => 'string',
        'visibility' => 'string',
        'language' => 'string|size:2',
        'scheduled_at' => 'date|after:+5minutes',
        'draft' => 'boolean',
    ];

    private LocalNote $model;

    public function __construct(private LocalActor $actor, array $attributes)
    {
        self::$rules['visibility'] = [new Enum(Visibility::class)];

        if (!isset($attributes['actor'])) {
            $attributes['actor'] = $actor;
        }

        try {
            /** @throws \Illuminate\Validation\ValidationException */
            $attributes = Validator::validate($attributes, self::$rules);
        } catch (ValidationException $e) {
            throw new AppException('Invalid attributes for Note', 0, $e);
        }

        $attributes['plain_text'] = false;
        if (strip_tags($attributes['status']) === $attributes['status']) {
            $attributes['plain_text'] = true;
        }

        parent::__construct($attributes);
    }

    public function setModel(LocalNote $model) : self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel() : LocalNote
    {
        return $this->model;
    }

    public function getActor() : LocalActor
    {
        return $this->actor;
    }
}
