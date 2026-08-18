<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCategoryPageLayouts;
use Illuminate\Foundation\Http\FormRequest;

class NewsStoreRequest extends FormRequest
{
    use ValidatesCategoryPageLayouts;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('date')) {
            $this->merge([
                'date' => now()->format('Y-m-d'),
            ]);
        }

        $this->prepareCategoryPageLayoutsForValidation();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'shoulder' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'ticker' => ['nullable', 'string'],
            'proofreader' => ['nullable', 'integer'],
            'image' => ['nullable', 'max:1024', 'image'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'representative' => ['nullable', 'string', 'max:255'],
            'is_show_reporter' => ['nullable', 'string', 'in:name,designation'],
            'timeline_id' => ['nullable', 'exists:timelines,id'],
            'published' => ['boolean'],
            'latest' => ['boolean'],
            'news_marquee' => ['boolean'],
            'live_news' => ['boolean'],
            'is_thread' => ['boolean'],
            'is_visible_shoulder' => ['boolean'],
            'is_visible_ticker' => ['boolean'],

            'details' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'category_id' => ['required', 'exists:categories,id'],
            'date' => ['required', 'date'],

            'section_layout_id' => ['nullable'],
            'section_layout_news_position' => ['nullable', 'integer'],
            'is_pinned' => ['boolean'],

            'category_layout_id' => ['nullable'],
            'category_layout_news_position' => ['nullable', 'integer'],
            'category_layout_is_pinned' => ['boolean'],

            ...$this->categoryPageLayoutRules(),

            'video_link' => ['nullable', 'string'],
            'video_source' => ['nullable', 'string'],
            'video_iframe' => ['nullable', 'string'],
            'is_video_in_thumbnail' => ['boolean'],

            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],

            'special_tag_id' => ['nullable', 'integer', 'exists:special_tags,id'],

            'linked_news_ids' => ['nullable', 'array'],
            'linked_news_ids.*' => ['integer', 'distinct', 'exists:news,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($this->boolean('live_news') && $this->boolean('is_thread')) {
                $validator->errors()->add('is_thread', 'লাইভ নিউজ ও থ্রেড একসাথে চালু করা যাবে না।');
            }

            $this->validateCategoryPageLayouts($validator);
        });
    }
}
