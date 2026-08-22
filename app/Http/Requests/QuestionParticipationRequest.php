<?php

namespace App\Http\Requests;

use App\Support\QuizParticipantPhone;
use Illuminate\Foundation\Http\FormRequest;

class QuestionParticipationRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ];
    }
}
