<?php

declare(strict_types=1);

namespace App\Http\Requests\oEmbed;

use App\Models\ActivityPub\LocalNote;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class PreviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // required String. URL of a status.
            'url' => [
                'required',
                'string',
                'url:https',
                function (string $attribute, string $value, Closure $fail) {
                    $urlHost = parse_url($value, PHP_URL_HOST);

                    if ($urlHost !== $this->getHost()) {
                        $fail('The URL must be on the same host as the request to the API.');
                    }
                },
                'regex:' . LocalNote::NOTE_REGEX,
            ],
            'maxwidth' => ['integer', 'min:1'],
            'maxheight' => ['integer', 'min:1'],
            'format' => ['in:json'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => 'oEmbed is only supported for messages at this time',
        ];
    }
}
