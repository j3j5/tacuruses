<?php

declare(strict_types=1);

namespace App\Http\Requests\API\Mastodon;

use Illuminate\Foundation\Http\FormRequest;

class ApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (bool) $this->user();
    }

    public function prepareForValidation(): void
    {
        $this->merge(['actor' => $this->user()]);
    }
}
