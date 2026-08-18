<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BreakingNewsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('breaking_news', 'title')->ignore($id),
            ],
        ];
    }
}
