<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaGalleryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
        ];
    }
}
