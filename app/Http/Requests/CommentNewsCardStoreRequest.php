<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentNewsCardStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'max:5120'],
            'commenter_image' => ['nullable', 'file', 'image', 'max:5120'],
            'news_id' => ['nullable', 'exists:news,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0'],
            'date' => ['required', 'date'],
            'is_publish' => ['boolean'],
            'commenter' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_publish' => $this->boolean('is_publish'),
        ]);
    }
}
