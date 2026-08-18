<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReporterProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth('sanctum')->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
