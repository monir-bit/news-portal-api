<?php

namespace App\Http\Requests;

use App\Models\PageCategoryMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PageCategoryMapSaveSortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'map_ids' => ['required', 'array', 'min:1'],
            'map_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $date = $this->input('date');
            $ids = $this->input('map_ids', []);

            $expectedIds = PageCategoryMap::query()
                ->whereDate('date', $date)
                ->pluck('id')
                ->map(fn (int|string $id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $received = collect($ids)->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($expectedIds !== $received) {
                $validator->errors()->add(
                    'map_ids',
                    'The sort list does not match this date\'s maps. Refresh the page and try again.'
                );
            }
        });
    }
}
