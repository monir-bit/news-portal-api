<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventBannerUpdateRequest extends FormRequest
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
            'mobile_image' => ['nullable', 'image', 'max:4096'],
            'desktop_image' => ['nullable', 'image', 'max:4096'],
            'mobile_image_path' => ['nullable', 'string', 'max:1000'],
            'desktop_image_path' => ['nullable', 'string', 'max:1000'],
            'link' => ['nullable', 'string', 'max:1000', 'url'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'link' => $this->emptyToNull($this->input('link')),
            'start_date' => $this->emptyToNull($this->input('start_date')),
            'end_date' => $this->emptyToNull($this->input('end_date')),
            'is_active' => filter_var($this->input('is_active', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    private function emptyToNull(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
