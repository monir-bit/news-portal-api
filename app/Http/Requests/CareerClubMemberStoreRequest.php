<?php

namespace App\Http\Requests;

use App\Support\ClubMember\ClubMemberImageValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CareerClubMemberStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'image' => ClubMemberImageValidation::rules(),
            'gender' => ['nullable', 'in:male,female,others'],
            'educational_qualification' => ['nullable', 'string', 'max:5000'],
            'preferred_profession' => ['nullable', 'string', 'max:255'],
            'work_experience' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:5000'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('career_club_members', 'phone')],
            'email' => ['required', 'email', 'max:255', Rule::unique('career_club_members', 'email')],
        ];
    }

    protected function prepareForValidation(): void
    {
        ClubMemberImageValidation::stripInvalidImageUpload($this);

        $emptyToNull = static fn (mixed $value): mixed => $value === '' ? null : $value;

        $this->merge([
            'age' => $emptyToNull($this->input('age')),
            'gender' => $emptyToNull($this->input('gender')),
            'educational_qualification' => $emptyToNull($this->input('educational_qualification')),
            'preferred_profession' => $emptyToNull($this->input('preferred_profession')),
            'work_experience' => $emptyToNull($this->input('work_experience')),
        ]);
    }
}
