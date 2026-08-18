<?php

namespace App\Http\Requests\Api;

use App\Support\QuizParticipantPhone;
use Illuminate\Foundation\Http\FormRequest;

class EpaperQuizParticipationRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:epaper_questions,id'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ];
    }
}
