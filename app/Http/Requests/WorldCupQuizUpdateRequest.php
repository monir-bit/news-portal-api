<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorldCupQuizUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
        ];
    }
}
