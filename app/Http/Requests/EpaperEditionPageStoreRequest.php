<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EpaperEditionPageStoreRequest extends FormRequest
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
            'page_number' => ['required', 'integer', 'min:1'],
            'image' => ['required', 'image', 'max:15360'],
            'image_width_px' => ['nullable', 'integer', 'min:1'],
            'image_height_px' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
