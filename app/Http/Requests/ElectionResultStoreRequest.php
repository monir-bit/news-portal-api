<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElectionResultStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'election_seat_id' => [
                'required',
                'exists:election_seats,id',
                Rule::unique('election_results', 'election_seat_id'),
            ],
            'election_party_id' => ['required', 'exists:election_parties,id'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'votes_received' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'election_seat_id.unique' => 'এই আসনের জন্য ইতিমধ্যে ফলাফল রয়েছে। সম্পাদনা করতে ইন্ডেক্স পেজ থেকে সম্পাদনা করুন।',
        ];
    }
}
