<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThankNewsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'news_id' => ['required', 'integer', 'exists:news,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
