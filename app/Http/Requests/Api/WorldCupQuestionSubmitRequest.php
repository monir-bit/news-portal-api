<?php

namespace App\Http\Requests\Api;

use App\Models\Participant;
use App\Support\QuizParticipantPhone;
use Illuminate\Foundation\Http\FormRequest;

class WorldCupQuestionSubmitRequest extends FormRequest
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
        $phone = (string) $this->input('phone', '');
        $knownParticipant = $phone !== '' && Participant::query()->where('phone', $phone)->exists();

        return [
            'name' => $knownParticipant
                ? ['nullable', 'string', 'max:50']
                : ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'question_option_id' => ['required', 'integer', 'exists:world_cup_question_options,id'],
        ];
    }
}
