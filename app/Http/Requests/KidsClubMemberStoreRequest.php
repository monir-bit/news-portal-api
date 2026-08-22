<?php

namespace App\Http\Requests;

use App\Support\ClubMember\ClubMemberImageValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KidsClubMemberStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'image' => ClubMemberImageValidation::rules(),
            'gender' => ['nullable', 'in:male,female,others'],
            'school_or_madrasa' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'hobby' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:5000'],
            'guardian_phone' => ['required', 'string', 'max:20', Rule::unique('kids_club_members', 'guardian_phone')],
            'email' => ['required', 'email', 'max:255', Rule::unique('kids_club_members', 'email')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'এই ইমেইল ইতিমধ্যে নিবন্ধিত।',
            'guardian_phone.unique' => 'এই অভিভাবকের ফোন নম্বর ইতিমধ্যে নিবন্ধিত।',
            'image.image' => 'ছবি অবশ্যই একটি বৈধ ইমেজ ফাইল হতে হবে।',
            'image.mimes' => 'ছবির ধরন হতে হবে: jpeg, jpg, png, gif, webp বা bmp।',
            'image.max' => 'ছবির সাইজ সর্বোচ্চ ৫ MB হতে হবে।',
        ];
    }

    protected function prepareForValidation(): void
    {
        ClubMemberImageValidation::stripInvalidImageUpload($this);

        $emptyToNull = static fn (mixed $value): mixed => $value === '' ? null : $value;

        $this->merge([
            'age' => $emptyToNull($this->input('age')),
            'gender' => $emptyToNull($this->input('gender')),
            'school_or_madrasa' => $emptyToNull($this->input('school_or_madrasa')),
            'blood_group' => $emptyToNull($this->input('blood_group')),
            'hobby' => $emptyToNull($this->input('hobby')),
        ]);
    }
}
