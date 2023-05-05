<?php

namespace App\Http\Requests\API\Mastodon;

use App\Enums\Visibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PostStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'actor' => 'required',
            'status' => 'string|required_unless:media_ids,null',
            // 'media_ids' => 'array|required_unless:status',
            'in_reply_to_id' => 'string|exists:notes,id',
            'sensitive' => 'boolean',
            'spoiler_text' => 'string',
            'visibility' => [new Enum(Visibility::class)],
            'language' => 'string|size:2',
            // 'scheduled_at' => 'date|after:+5minutes'
            'draft' => 'boolean',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge(['actor' => $this->user()]);
    }
}