<?php

namespace App\Http\Requests\Api;

use App\Models\Reporter;
use Illuminate\Foundation\Http\FormRequest;

class ReporterNewsStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth('sanctum')->user();
    }

    public function rules(): array
    {
        $user = auth('sanctum')->user();
        $needsLocation = $user instanceof Reporter && $user->has_location;

        $base = [
            'title' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string'],
            'is_special' => ['sometimes', 'boolean'],
            'media_paths' => ['nullable', 'array'],
            'media_paths.*' => ['string', 'max:500'],
        ];

        if ($needsLocation) {
            return array_merge($base, [
                'division_id' => ['required', 'integer', 'exists:divisions,id'],
                'district_id' => ['required', 'integer', 'exists:districts,id'],
                'upazila_id' => ['required', 'integer', 'exists:upazilas,id'],
            ]);
        }

        return array_merge($base, [
            'division_id' => ['sometimes', 'nullable', 'integer', 'exists:divisions,id'],
            'district_id' => ['sometimes', 'nullable', 'integer', 'exists:districts,id'],
            'upazila_id' => ['sometimes', 'nullable', 'integer', 'exists:upazilas,id'],
        ]);
    }
}
