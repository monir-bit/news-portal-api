<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SourceLineStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:source_lines,name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
