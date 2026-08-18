<?php

namespace App\Http\Requests;

use App\Models\PageCategoryMap;
use Illuminate\Foundation\Http\FormRequest;

class PageCategoryMapStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $date = $this->input('date');
            $ids = $this->input('category_ids', []);

            $conflictCount = PageCategoryMap::query()
                ->where('date', $date)
                ->whereIn('category_id', $ids)
                ->count();

            if ($conflictCount > 0) {
                $validator->errors()->add(
                    'category_ids',
                    'One or more categories are already mapped for this date.'
                );
            }
        });
    }
}
