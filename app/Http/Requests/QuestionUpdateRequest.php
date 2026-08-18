<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $questionId = $this->route('id');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('questions')->where(
                    fn ($q) => $q->where('category_id', $this->category_id)
                )->ignore($questionId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => [
                'nullable',
                'integer',
                Rule::exists('question_options', 'id')->where(
                    fn ($q) => $q->where('question_id', $questionId)
                ),
            ],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
        ];
    }
}
