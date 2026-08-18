<?php

namespace App\Http\Requests;

use App\Applications\Enums\PageSeoPageName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageSeoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page_name' => ['required', Rule::enum(PageSeoPageName::class), Rule::unique('page_seos', 'page_name')],
            'title' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:100'],
            'seo_og_image' => ['nullable', 'image', 'max:4096'],
            'seo_og_image_path' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
