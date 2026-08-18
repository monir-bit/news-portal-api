<?php

namespace App\Http\Requests;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => UtilsHelper::SlugMaker($this->string('slug')->value()),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($this->route('id'))],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($this->route('id'))],
            'title' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:100'],
            'seo_og_image' => ['nullable', 'image', 'max:4096'],
            'seo_og_image_path' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
