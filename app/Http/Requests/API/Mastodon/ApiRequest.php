<?php

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

    public function prepareForValidation()
    {
        $this->merge(['actor' => $this->user()]);
    }
}
