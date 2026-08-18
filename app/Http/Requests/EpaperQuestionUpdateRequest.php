<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpaperQuestionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $v = $this->input('publish_date');
        $this->merge([
            'publish_date' => ($v === '' || $v === null) ? null : $v,
        ]);
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $questionId = $this->route('id');

        return [
            'epaper_category_id' => ['required', 'integer', 'exists:epaper_categories,id'],
            'page_number' => ['nullable', 'string', 'max:255'],
            'title' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('epaper_questions')->where(
                    fn ($q) => $q->where('epaper_category_id', $this->epaper_category_id),
                )->ignore($questionId),
            ],
            'publish_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => [
                'nullable',
                'integer',
                Rule::exists('epaper_question_options', 'id')->where(
                    fn ($q) => $q->where('epaper_question_id', $questionId),
                ),
            ],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
        ];
    }
}
