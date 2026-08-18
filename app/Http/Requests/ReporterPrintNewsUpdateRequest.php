<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporterPrintNewsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'is_complete' => ['boolean'],
        ];
    }
}
