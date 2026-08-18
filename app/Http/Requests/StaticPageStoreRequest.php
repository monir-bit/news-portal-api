<?php

namespace App\Http\Requests;

use App\Models\StaticPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaticPageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'in:' . implode(',', StaticPage::ALLOWED_NAMES),
                'unique:static_pages,name',
            ],
            'content' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This page has already been created.',
        ];
    }
}
