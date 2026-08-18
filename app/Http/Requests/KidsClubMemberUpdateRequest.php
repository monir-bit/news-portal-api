<?php

namespace App\Http\Requests;

use App\Support\ClubMember\ClubMemberImageValidation;
use Illuminate\Validation\Rule;

class KidsClubMemberUpdateRequest extends KidsClubMemberStoreRequest
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
            'school_or_madrasa' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'hobby' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:5000'],
            'guardian_phone' => ['required', 'string', 'max:20', Rule::unique('kids_club_members', 'guardian_phone')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('kids_club_members', 'email')->ignore($id)],
        ];
    }
}
