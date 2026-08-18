<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporterLocationsStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locations' => ['required', 'array', 'min:1'],
            'locations.*.division_id' => ['required', 'exists:divisions,id'],
            'locations.*.district_id' => ['nullable', 'exists:districts,id'],
            'locations.*.upazila_id' => ['nullable', 'exists:upazilas,id'],
        ];
    }
}
