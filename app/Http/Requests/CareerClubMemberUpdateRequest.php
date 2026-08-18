<?php

namespace App\Http\Requests;

use App\Support\ClubMember\ClubMemberImageValidation;
use Illuminate\Validation\Rule;

class CareerClubMemberUpdateRequest extends CareerClubMemberStoreRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'image' => ClubMemberImageValidation::rules(),
            'gender' => ['nullable', 'in:male,female,others'],
            'educational_qualification' => ['nullable', 'string', 'max:5000'],
            'preferred_profession' => ['nullable', 'string', 'max:255'],
            'work_experience' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:5000'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('career_club_members', 'phone')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('career_club_members', 'email')->ignore($id)],
        ];
    }
}
