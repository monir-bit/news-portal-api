<?php

namespace App\Http\Requests\GeoLocation;

use Illuminate\Foundation\Http\FormRequest;

class UpazilaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'district_id' => ['required', 'exists:districts,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'district_id.required' => 'Please select a district.',
            'district_id.exists' => 'Please select a valid district.',
            'name.required' => 'Upazila name is required.',
        ];
    }
}
