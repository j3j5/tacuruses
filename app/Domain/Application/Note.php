<?php

declare(strict_types=1);

namespace App\Domain\Application;

use App\Enums\Visibility;
use App\Models\ActivityPub\LocalActor;
use App\Models\ActivityPub\Note as ModelsNote;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
        'in_reply_to_id' => 'string|exists:notes,id',
        'sensitive' => 'boolean',
        'spoiler_text' => 'string',
        'visibility' => 'string',
        'language' => 'string|size:2',
        'scheduled_at' => 'date|after:+5minutes',
        'draft' => 'boolean',
    ];

    private ModelsNote $model;

    public function __construct(private LocalActor $actor, $attributes = [])
    {
        self::$rules['visibility'] = [new Enum(Visibility::class)];

        try {
            $attributes = Validator::validate($attributes, self::$rules);
        } catch (ValidationException $e) {
            throw new RuntimeException('Invalid attributes for Media', 0, $e);
        }

        parent::__construct($attributes);
    }

    public function setModel(ModelsNote $model) : self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel() : ModelsNote
    {
        return $this->model;
    }

    public function getActor() : LocalActor
    {
        return $this->actor;
    }
}
