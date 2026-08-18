<?php

namespace App\Http\Requests\Api;

use App\Models\Participant;
use App\Support\QuizParticipantPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpaperQuizAnswerStoreRequest extends FormRequest
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
        $questionId = (int) $this->input('question_id', 0);
        $phone = (string) $this->input('phone', '');
        $knownParticipant = $phone !== '' && Participant::query()->where('phone', $phone)->exists();

        return [
            'question_id' => ['required', 'integer', 'exists:epaper_questions,id'],
            'question_option_id' => [
                'required',
                'integer',
                Rule::exists('epaper_question_options', 'id')->where(
                    fn ($q) => $q->where('epaper_question_id', $questionId),
                ),
            ],
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
