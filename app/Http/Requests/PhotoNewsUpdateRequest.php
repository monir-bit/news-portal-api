<?php

namespace App\Http\Requests;

use App\Applications\Queries\GetPhotoCategoryIdsQuery;
use App\Http\Requests\Concerns\ValidatesCategoryPageLayouts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhotoNewsUpdateRequest extends FormRequest
{
    use ValidatesCategoryPageLayouts;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCategoryPageLayoutsForValidation();
    }

    public function rules(): array
    {
        $photoCategoryIds = app(GetPhotoCategoryIdsQuery::class)->handle();

        return [
            'shoulder' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'ticker' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', Rule::in($photoCategoryIds)],
            'proofreader' => ['nullable', 'integer'],
            'image' => ['nullable', 'max:1024', 'image'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'representative' => ['nullable', 'string', 'max:255'],

            'timeline_id' => ['nullable', 'exists:timelines,id'],
            'published' => ['boolean'],
            'latest' => ['boolean'],
            'news_marquee' => ['boolean'],
            'live_news' => ['boolean'],
            'is_visible_shoulder' => ['boolean'],
            'is_visible_ticker' => ['boolean'],

            'details' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],

            'section_layout_id' => ['nullable'],
            'section_layout_news_position' => ['nullable', 'integer'],
            'is_pinned' => ['boolean'],

            'category_layout_id' => ['nullable'],
            'category_layout_news_position' => ['nullable', 'integer'],
            'category_layout_is_pinned' => ['boolean'],

            ...$this->categoryPageLayoutRules(),

            'gallery' => ['required', 'array', 'min:1'],
            'gallery.*.image_path' => ['nullable', 'string', 'max:1000'],
            'gallery.*.caption' => ['nullable', 'string', 'max:1000'],
            'gallery.*.image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $gallery = $this->input('gallery', []);
            foreach (array_keys($gallery) as $i) {
                $hasFile = $this->hasFile("gallery.$i.image");
                $path = trim((string) ($gallery[$i]['image_path'] ?? ''));
                if (! $hasFile && $path === '') {
                    $validator->errors()->add("gallery.$i.image", 'প্রতিটি সারিতে একটি ছবি রাখতে হবে (আগের অথবা নতুন আপলোড)।');
                }
            }

            $this->validateCategoryPageLayouts($validator);
        });
    }
}
