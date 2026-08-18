<?php

namespace App\Http\Requests;

use App\Enums\PollPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PollStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['starts_at', 'ends_at'] as $field) {
            $v = $this->input($field);
            $clean[$field] = ($v === '' || $v === null) ? null : $v;
        }
        $this->merge($clean);

        $options = $this->input('options', []);
        if (is_array($options)) {
            foreach ($options as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $iv = $row['initial_votes'] ?? null;
                $options[$i]['initial_votes'] = ($iv === '' || $iv === null)
                    ? 0
                    : (int) $iv;
            }
            $this->merge(['options' => $options]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('starts_at') && $this->filled('ends_at')) {
                if (strtotime((string) $this->ends_at) < strtotime((string) $this->starts_at)) {
                    $validator->errors()->add('ends_at', 'End time must be after or equal to start time.');
                }
            }
        });
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('polls')->where(
                    fn ($q) => $q->where('page', $this->input('page'))
                ),
            ],
            'page' => ['required', Rule::enum(PollPage::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.initial_votes' => ['required', 'integer', 'min:0', 'max:99999999'],
        ];
    }
}
