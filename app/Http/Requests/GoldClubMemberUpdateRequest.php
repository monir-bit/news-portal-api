<?php

namespace App\Http\Requests;

use App\Support\ClubMember\ClubMemberImageValidation;
use Illuminate\Validation\Rule;

class GoldClubMemberUpdateRequest extends GoldClubMemberStoreRequest
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
            'profession' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'hobby' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:5000'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('gold_club_members', 'phone')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('gold_club_members', 'email')->ignore($id)],
        ];
    }
}
