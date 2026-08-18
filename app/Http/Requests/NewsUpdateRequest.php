<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCategoryPageLayouts;
use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;

class NewsUpdateRequest extends FormRequest
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
            $id = $this->route('id');
            if ($id !== null) {
                $news = News::query()->find((int) $id);
                if ($news?->date !== null) {
                    $this->merge([
                        'date' => $news->date->format('Y-m-d'),
                    ]);
                } else {
                    $this->merge([
                        'date' => now()->format('Y-m-d'),
                    ]);
                }
            } else {
                $this->merge([
                    'date' => now()->format('Y-m-d'),
                ]);
            }
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
            'category_id' => ['required', 'exists:categories,id'],
            'proofreader' => ['nullable', 'integer'],
            'image' => ['nullable', 'max:1024', 'image'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'representative' => ['nullable', 'string', 'max:255'],
            'is_show_reporter' => ['nullable', 'string', 'in:name,designation,none'],
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
            'date' => ['required', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $id = $this->route('id');
            if ($id !== null) {
                $mainId = (int) $id;
                $ids = array_map('intval', $this->input('linked_news_ids', []));
                if (in_array($mainId, $ids, true)) {
                    $validator->errors()->add('linked_news_ids', 'একই নিউজ নিজের সাথে লিঙ্ক করা যাবে না।');
                }
            }
            if ($this->boolean('live_news') && $this->boolean('is_thread')) {
                $validator->errors()->add('is_thread', 'লাইভ নিউজ ও থ্রেড একসাথে চালু করা যাবে না।');
            }

            $this->validateCategoryPageLayouts($validator);
        });
    }
}
