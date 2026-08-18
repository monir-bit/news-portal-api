<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EpaperRegionCropUploadRequest extends FormRequest
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
            'image' => ['required', 'image', 'max:8192'],
        ];
    }
}
