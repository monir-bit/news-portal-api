<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorldCupTeamStoreRequest extends FormRequest
{
    public const CONTINENTS = [
        'Africa',
        'Asia',
        'Europe',
        'North America',
        'Oceania',
        'South America',
    ];

    public const CONFEDS = [
        'AFC',
        'CAF',
        'CONCACAF',
        'CONMEBOL',
        'OFC',
        'UEFA',
    ];

    public const GROUPS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
    ];

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
            'name_normalised' => ['nullable', 'string', 'max:255'],
            'continent' => ['required', 'string', Rule::in(self::CONTINENTS)],
            'flag_icon' => ['required', 'string', 'max:50'],
            'flag_unicode' => ['nullable', 'string', 'max:255'],
            'fifa_code' => [
                'required',
                'string',
                'size:3',
                'alpha',
                Rule::unique('world_cup_teams', 'fifa_code'),
            ],
            'group' => ['required', 'string', 'size:1', Rule::in(self::GROUPS)],
            'confed' => ['required', 'string', 'max:20', Rule::in(self::CONFEDS)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $emptyToNull = static fn (mixed $value): mixed => $value === '' ? null : $value;

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'name_normalised' => $emptyToNull(trim((string) $this->input('name_normalised', ''))),
            'flag_icon' => trim((string) $this->input('flag_icon')),
            'flag_unicode' => $emptyToNull(trim((string) $this->input('flag_unicode', ''))),
            'fifa_code' => strtoupper(trim((string) $this->input('fifa_code'))),
            'group' => strtoupper(trim((string) $this->input('group'))),
            'confed' => strtoupper(trim((string) $this->input('confed'))),
        ]);
    }
}
