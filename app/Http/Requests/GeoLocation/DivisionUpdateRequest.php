<?php

namespace App\Http\Requests\GeoLocation;

use Illuminate\Foundation\Http\FormRequest;

class DivisionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', \Illuminate\Validation\Rule::unique('divisions', 'slug')->ignore($this->route('id'))],
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
