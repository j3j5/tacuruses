<?php

declare(strict_types=1);

namespace App\Domain\Application;

use App\Exceptions\AppException;
use App\Models\ActivityPub\LocalActor;
use App\Models\Media as ModelsMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationException;

/**
 *
 * @property \Illuminate\Http\UploadedFile $file
 * @property ?\Illuminate\Http\UploadedFile $thumbnail
 * @property ?string $description
 * @property ?string $focus
 * @extends Fluent<string, string>
 */
class Media extends Fluent
{
    /** @var array<string, string> $rules */
    public static array $rules = [
        'file' => 'required|file',
        'thumbnail' => 'file|image',
        'description' => 'string',
        // Two floating points (x,y), comma-delimited, ranging from -1.0 to 1.0
        'focus' => 'string|regex:/-?\d(?:\.?\d*),-?\d(?:\.?\d*)/',
    ];

    private ModelsMedia $model;

    /**
     * @param \App\Models\ActivityPub\LocalActor $actor
     * @param array<string, string> $attributes
     * @phpstan-param array{file: \Illuminate\Http\UploadedFile, thumbnail?: \Illuminate\Http\UploadedFile, description?: string, focus?: string}  $attributes
     * @throws \App\Exceptions\AppException
     * @return void
     */
    public function __construct(private LocalActor $actor, array $attributes)
    {
        try {
            /** @throws \Illuminate\Validation\ValidationException */
            $attributes = Validator::validate($attributes, self::$rules);
        } catch (ValidationException $e) {
            throw new AppException('Invalid attributes for Media', 0, $e);
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

    /**
     *
     * @return array<string, int|string|\Illuminate\Support\Carbon|null>
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Mime\Exception\LogicException
     * @throws \RuntimeException
     */
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
