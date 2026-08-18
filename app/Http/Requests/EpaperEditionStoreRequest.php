<?php

namespace App\Http\Requests;

use App\Models\EpaperEdition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpaperEditionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'epaper_publication_id' => ['required', 'integer', Rule::exists('epaper_publications', 'id')],
            'publication_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $exists = EpaperEdition::query()
                        ->where('epaper_publication_id', (int) $this->input('epaper_publication_id'))
                        ->whereDate('publication_date', $value)
                        ->exists();

                    if ($exists) {
                        $fail(__('An edition already exists for this publication and date.'));
                    }
                },
            ],
            'title' => ['nullable', 'string', 'max:500'],
            'print_issue_ref' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:64'],
        ];
    }
}
