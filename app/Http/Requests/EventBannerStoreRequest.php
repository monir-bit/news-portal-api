<?php

namespace App\Http\Requests;

use App\Applications\Enums\EventBannerName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventBannerStoreRequest extends FormRequest
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
            'banner_name' => ['required', Rule::enum(EventBannerName::class), Rule::unique('event_banners', 'banner_name')],
            'mobile_image' => ['required', 'image', 'max:4096'],
            'desktop_image' => ['required', 'image', 'max:4096'],
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
