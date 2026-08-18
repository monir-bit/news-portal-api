<?php

namespace App\Http\Requests\Api;

use App\Support\QuizParticipantPhone;
use Illuminate\Foundation\Http\FormRequest;

class WorldCupQuizAnswerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && is_string($this->input('phone'))) {
            $this->merge([
                'phone' => QuizParticipantPhone::normalize($this->input('phone')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'quiz_id' => ['required', 'integer'],
            'question_option_id' => ['nullable', 'integer'],
            'timed_out' => ['boolean'],
        ];
    }
}
