<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebStoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'news_id' => 'required|integer|exists:news,id',
            'items' => 'required|array|min:1',
            'items.*.title' => 'required|string|max:500',
            'items.*.image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }
}
