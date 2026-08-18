<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class WorldCupTeamUpdateRequest extends WorldCupTeamStoreRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_normalised' => ['nullable', 'string', 'max:255'],
            'continent' => ['required', 'string', Rule::in(self::CONTINENTS)],
            'flag_icon' => ['required', 'string', 'max:50'],
            'flag_unicode' => ['nullable', 'string', 'max:255'],
            'fifa_code' => [
                'required',
                'string',
                'size:3',
                'alpha',
                Rule::unique('world_cup_teams', 'fifa_code')->ignore($teamId),
            ],
            'group' => ['required', 'string', 'size:1', Rule::in(self::GROUPS)],
            'confed' => ['required', 'string', 'max:20', Rule::in(self::CONFEDS)],
        ];
    }
}
