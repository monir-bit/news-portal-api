<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PopoverAddUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'link' => ['nullable', 'string', 'max:1000', 'url'],
            'delay' => ['required', 'integer', 'min:0', 'max:3600000'],
            'duration' => ['required', 'integer', 'min:0', 'max:3600000'],
            'is_active' => ['sometimes', 'boolean'],
            'width' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $emptyToNull = static fn (mixed $v): mixed => ($v === '' ? null : $v);

        $this->merge([
            'link' => $emptyToNull($this->input('link')),
            'width' => $emptyToNull($this->input('width')),
            'height' => $emptyToNull($this->input('height')),
            'is_active' => filter_var($this->input('is_active', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
