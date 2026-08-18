<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebStoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:web_story_items,id',
            'items.*.title' => 'required|string|max:500',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }
}
