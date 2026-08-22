<?php

namespace App\Http\Requests;

use App\Models\Participant;
use App\Support\QuizParticipantPhone;
use Illuminate\Foundation\Http\FormRequest;

class QuestionAnswerStoreRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'question_option_id' => ['required', 'integer', 'exists:question_options,id'],
            'name' => $knownParticipant
                ? ['nullable', 'string', 'max:50']
                : ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required for new participants.',
            'phone.required' => 'Mobile number is required.',
        ];
    }
}
