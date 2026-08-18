<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSortWebStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'web_story_id' => 'required|integer|exists:web_stories,id',
            'data' => 'required|array',
            'data.*.id' => 'required|integer|exists:web_story_items,id',
            'data.*.position' => 'required|integer',
        ];
    }
}
