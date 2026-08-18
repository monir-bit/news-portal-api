<?php

namespace App\Http\Requests\GeoLocation;

use Illuminate\Foundation\Http\FormRequest;

class DivisionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:divisions,slug'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Division name is required.',
            'slug.unique' => 'This slug is already in use.',
        ];
    }
}
