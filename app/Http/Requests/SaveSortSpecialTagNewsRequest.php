<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSortSpecialTagNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.*.id' => 'required|integer|exists:special_tag_news,id',
            'data.*.news_id' => 'required|integer|exists:news,id',
            'data.*.position' => 'required|integer',
        ];
    }
}
