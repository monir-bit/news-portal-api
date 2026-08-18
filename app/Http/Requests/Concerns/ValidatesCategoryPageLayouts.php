<?php

namespace App\Http\Requests\Concerns;

trait ValidatesCategoryPageLayouts
{
    /**
     * @return array<string, mixed>
     */
    protected function categoryPageLayoutRules(): array
    {
        return [
            'category_page_layout_id' => ['nullable', 'integer', 'exists:category_page_layouts,id'],
            'category_page_layout_news_position' => ['nullable', 'integer'],
            'category_page_layout_is_pinned' => ['boolean'],

            'category_page_layouts' => ['nullable', 'array'],
            'category_page_layouts.*.category_page_layout_id' => ['nullable', 'integer', 'exists:category_page_layouts,id'],
            'category_page_layouts.*.category_page_layout_news_position' => ['nullable', 'integer'],
            'category_page_layouts.*.category_page_layout_is_pinned' => ['boolean'],
        ];
    }

    protected function prepareCategoryPageLayoutsForValidation(): void
    {
        $layouts = $this->input('category_page_layouts');

        if (is_array($layouts)) {
            $normalized = collect($layouts)
                ->filter(fn ($row) => is_array($row) && ! empty($row['category_page_layout_id']))
                ->values()
                ->all();

            $this->merge(['category_page_layouts' => $normalized]);

            return;
        }

        if ($this->filled('category_page_layout_id')) {
            $this->merge([
                'category_page_layouts' => [[
                    'category_page_layout_id' => (int) $this->input('category_page_layout_id'),
                    'category_page_layout_news_position' => $this->input('category_page_layout_news_position'),
                    'category_page_layout_is_pinned' => $this->boolean('category_page_layout_is_pinned'),
                ]],
            ]);
        }
    }

    protected function validateCategoryPageLayouts($validator): void
    {
        $layouts = $this->input('category_page_layouts', []);
        if (! is_array($layouts)) {
            return;
        }

        $layoutIds = [];
        foreach ($layouts as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $layoutId = (int) ($row['category_page_layout_id'] ?? 0);
            if ($layoutId <= 0) {
                continue;
            }

            if (isset($layoutIds[$layoutId])) {
                $validator->errors()->add(
                    "category_page_layouts.$index.category_page_layout_id",
                    'একই পেজ লেআউট একাধিকবার নির্বাচন করা যাবে না।'
                );
            }

            $layoutIds[$layoutId] = true;
        }
    }
}
