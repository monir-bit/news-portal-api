<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElectionResultUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'election_seat_id' => ['required', 'exists:election_seats,id'],
            'election_party_id' => ['required', 'exists:election_parties,id'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'votes_received' => ['required', 'integer', 'min:0'],
        ];
    }
}
