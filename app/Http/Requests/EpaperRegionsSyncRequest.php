<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpaperRegionsSyncRequest extends FormRequest
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
            'deleted_region_ids' => ['nullable', 'array'],
            'deleted_region_ids.*' => ['integer', 'min:1'],
            'regions' => ['present', 'array'],
            'regions.*.id' => ['nullable', 'integer', 'min:1'],
            'regions.*.temp_id' => ['nullable', 'string', 'max:64'],
            'regions.*.epaper_edition_page_id' => ['required', 'integer', 'min:1'],
            'regions.*.role' => ['required', Rule::in(['head', 'tail'])],
            'regions.*.title' => ['required', 'string', 'max:500'],
            'regions.*.external_url' => ['nullable', 'string', 'max:2000'],
            'regions.*.x_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'regions.*.y_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'regions.*.width_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'regions.*.height_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'regions.*.linked_region_id' => ['nullable', 'integer', 'min:1'],
            'regions.*.linked_temp_id' => ['nullable', 'string', 'max:64'],
            'regions.*.news_id' => ['nullable', 'integer', 'exists:news,id'],
        ];
    }
}
