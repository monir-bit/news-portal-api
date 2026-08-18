<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Metadata-only updates (title, print issue ref, status), aligned with the edit form meta POST.
 *
 * Publication, publication_date, and revision are not validated here — they define edition identity
 * and uniqueness at create/revise time.
 */
class EpaperEditionUpdateRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:500'],
            'print_issue_ref' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:64'],
        ];
    }
}
