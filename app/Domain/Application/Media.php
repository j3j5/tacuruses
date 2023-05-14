<?php

declare(strict_types=1);

namespace App\Domain\Application;

use App\Models\ActivityPub\LocalActor;
use App\Models\Media as ModelsMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class Media extends Fluent
{
    public static array $rules = [
        'file' => 'required|file',
        'thumbnail' => 'file|image',
        'description' => 'string',
        // Two floating points (x,y), comma-delimited, ranging from -1.0 to 1.0
        'focus' => 'string|regex:/-?\d(?:\.?\d*),-?\d(?:\.?\d*)/',
    ];

    private ModelsMedia $model;

    public function __construct(private LocalActor $actor, $attributes = [])
    {
        try {
            $attributes = Validator::validate($attributes, self::$rules);
        } catch (ValidationException $e) {
            throw new RuntimeException('Invalid attributes for Media', 0, $e);
        }

        parent::__construct($attributes);
    }

    private function getPath() : string
    {
        return $this->actor->username . '/files/';
    }

    public function getFilename() : string
    {
        return $this->file->hashName($this->getPath());
    }

    public function getDataForModel() : array
    {
        return [
            'actor_id' => $this->actor->id,
            'description' => $this->description ?? '',
            'filename' => $this->getFilename(),
            'content_type' => $this->file->getMimeType(),
            'file_updated_at' => now(),
            'remote_url' => Storage::cloud()->url($this->getFilename()),
            // 'filesize' => $this->file->
            // 'hash' => ,
        ];
    }

    public function setModel(ModelsMedia $model) : self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel() : ModelsMedia
    {
        return $this->model;
    }
}
