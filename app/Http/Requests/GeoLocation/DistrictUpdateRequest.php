<?php

namespace App\Http\Requests\GeoLocation;

use Illuminate\Foundation\Http\FormRequest;

class DistrictUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'division_id' => ['required', 'exists:divisions,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'division_id.required' => 'Please select a division.',
            'division_id.exists' => 'Please select a valid division.',
            'name.required' => 'District name is required.',
        ];
    }
}
