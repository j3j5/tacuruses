<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebFingerRequest extends FormRequest
{
    public const RESOURCE_REGEX = '/^acct:(?<handle>[\w\-\.]+)@(?<server>[\w\-\.]+)$/i';

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
            'resource' => ['required', 'string', 'regex:' . self::RESOURCE_REGEX],
        ];
    }
}
