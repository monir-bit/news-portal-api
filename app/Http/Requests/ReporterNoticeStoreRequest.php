<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporterNoticeStoreRequest extends FormRequest
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
            'from' => ['required', 'in:online,print'],
            'is_active' => ['boolean'],
            'is_for_all' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'reporter_ids' => ['nullable', 'array'],
            'reporter_ids.*' => ['exists:reporters,id'],
            'media' => ['nullable', 'array'],
            'media.*' => ['file', 'max:10240'], // 10MB max per file
        ];
    }
}
