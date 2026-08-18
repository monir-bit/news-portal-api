<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorldCupMatchStoreRequest extends FormRequest
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
            'team_a' => ['required', 'integer', 'exists:world_cup_teams,id', 'different:team_b'],
            'team_b' => ['required', 'integer', 'exists:world_cup_teams,id', 'different:team_a'],
            'team_a_score' => ['nullable', 'integer', 'min:0', 'max:255'],
            'team_b_score' => ['nullable', 'integer', 'min:0', 'max:255'],
            'team_a_penalty_score' => ['nullable', 'integer', 'min:0', 'max:255'],
            'team_b_penalty_score' => ['nullable', 'integer', 'min:0', 'max:255'],
            'match_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'season' => ['nullable', 'string', 'size:4'],
            'stage' => ['nullable', 'string', 'max:50'],
            'group_name' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['scheduled', 'live', 'finished', 'postponed'])],
            'news_id' => ['nullable', 'integer', 'exists:news,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $emptyToNull = static fn (mixed $value): mixed => $value === '' ? null : $value;

        $this->merge([
            'team_a' => $this->input('team_a') === '' || $this->input('team_a') === null
                ? null
                : (int) $this->input('team_a'),
            'team_b' => $this->input('team_b') === '' || $this->input('team_b') === null
                ? null
                : (int) $this->input('team_b'),
            'team_a_score' => $this->input('team_a_score') === '' || $this->input('team_a_score') === null
                ? 0
                : (int) $this->input('team_a_score'),
            'team_b_score' => $this->input('team_b_score') === '' || $this->input('team_b_score') === null
                ? 0
                : (int) $this->input('team_b_score'),
            'team_a_penalty_score' => $emptyToNull($this->input('team_a_penalty_score')),
            'team_b_penalty_score' => $emptyToNull($this->input('team_b_penalty_score')),
            'venue' => $emptyToNull($this->input('venue')),
            'title' => $emptyToNull($this->input('title')),
            'stage' => $emptyToNull($this->input('stage')),
            'group_name' => $emptyToNull($this->input('group_name')),
            'season' => $emptyToNull($this->input('season')) ?? '2026',
            'news_id' => $this->input('news_id') === '' || $this->input('news_id') === null
                ? null
                : (int) $this->input('news_id'),
        ]);
    }
}
