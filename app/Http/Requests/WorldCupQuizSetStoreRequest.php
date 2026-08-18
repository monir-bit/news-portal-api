<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorldCupQuizSetStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['start_time', 'end_time'] as $field) {
            $v = $this->input($field);
            $clean[$field] = ($v === '' || $v === null) ? null : $v;
        }
        $this->merge($clean);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('start_time') && $this->filled('end_time')) {
                if (strtotime((string) $this->end_time) < strtotime((string) $this->start_time)) {
                    $validator->errors()->add('end_time', 'End time must be after or equal to start time.');
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('world_cup_quiz_sets', 'slug')],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['boolean'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
        ];
    }
}
