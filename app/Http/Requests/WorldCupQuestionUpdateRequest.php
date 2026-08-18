<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorldCupQuestionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['start_date_time', 'end_date_time'] as $field) {
            $v = $this->input($field);
            $clean[$field] = ($v === '' || $v === null) ? null : $v;
        }
        $this->merge($clean);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $options = $this->input('options', []);
            $correctCount = collect($options)->filter(function ($row) {
                return filter_var($row['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN);
            })->count();
            if ($correctCount !== 1) {
                $validator->errors()->add('options', 'Exactly one option must be marked as the correct answer.');
            }

            if ($this->filled('start_date_time') && $this->filled('end_date_time')) {
                if (strtotime((string) $this->end_date_time) < strtotime((string) $this->start_date_time)) {
                    $validator->errors()->add('end_date_time', 'End date/time must be after or equal to start date/time.');
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
            'question' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:1000'],
            'duration_seconds' => ['required', 'integer', 'min:5', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'start_date_time' => ['nullable', 'date'],
            'end_date_time' => ['nullable', 'date'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
        ];
    }
}
