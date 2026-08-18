<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BreakingNewsLinkNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('news_id') && ($this->news_id === '' || $this->news_id === null)) {
            $this->merge(['news_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'news_id' => ['nullable', 'integer', 'exists:news,id'],
        ];
    }
}
