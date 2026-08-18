<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReporterPrintNewsStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth('sanctum')->user();
    }

    public function rules(): array
    {
        return [
            'reporter_news_id' => ['nullable', 'integer', 'exists:reporter_news,id'],
            'title' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'media_paths' => ['nullable', 'array'],
            'media_paths.*' => ['string', 'max:500'],
        ];
    }
}
