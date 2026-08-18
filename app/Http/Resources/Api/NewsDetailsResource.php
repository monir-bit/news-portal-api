<?php

namespace App\Http\Resources\Api;

use App\Applications\Helpers\SeoHelper;
use App\Http\Resources\NewsImageResource;
use App\Services\CategoryPathService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsDetailsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug_key,
            'url' => $this->whenLoaded('category', function ($category) {
                return $this->newsUrl($category, $this->slug_key);
            }),
            'title' => $this->title,
            'ticker' => $this->ticker,
            'image' => $this->image,
            'image_caption' => $this->image_caption,
            'representative' => $this->representative,
            'is_show_reporter' => $this->is_show_reporter?->value ?? $this->is_show_reporter,
            'reporter' => $this->when($this->relationLoaded('reporterNews') && $this->reporterNews->isNotEmpty(), function () {
                $reporter = $this->reporterNews->first()->reporter;
                if (! $reporter) {
                    return null;
                }
                $locationParts = [];
                if ($this->relationLoaded('newsLocations') && $this->newsLocations->isNotEmpty()) {
                    $loc = $this->newsLocations->first();
                    if ($loc->relationLoaded('division') && $loc->division) {
                        $locationParts[] = $loc->division->name;
                    }
                    if ($loc->relationLoaded('district') && $loc->district) {
                        $locationParts[] = $loc->district->name;
                    }
                    if ($loc->relationLoaded('upazila') && $loc->upazila) {
                        $locationParts[] = $loc->upazila->name;
                    }
                }

                return [
                    'name' => $reporter->name,
                    'alternate_designation' => $reporter->alternate_designation,
                    'designation' => $reporter->designation,
                    'location' => implode(', ', $locationParts),
                ];
            }),
            'shoulder' => $this->shoulder,
            'sort_description' => $this->sort_description,
            'live_news' => $this->live_news,
            'is_thread' => $this->is_thread,
            'live_news_main_content' => $this->whenLoaded('liveNews', function ($liveNews) {
                return [
                    'title' => $liveNews->title,
                    'content' => $liveNews->content,
                    'stopped_at' => $liveNews->stopped_at,
                    'is_active' => $liveNews->is_active,
                ];
            }),
            'is_visible_shoulder' => $this->is_visible_shoulder,
            'is_visible_ticker' => $this->is_visible_ticker,
            'date' => ($this->date ?? $this->created_at)?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'details' => $this->whenLoaded('details', function () {
                return [
                    'description' => $this->details->details,
                    'keyword' => $this->details->keyword,
                    'video_link' => $this->details->video_link,
                    'video_source' => $this->details->video_source,
                    'video_iframe' => $this->details->video_iframe,
                    'is_video_in_thumbnail' => $this->details->is_video_in_thumbnail,
                    'google_drive_link' => $this->details->google_drive_link,
                    'audio_link' => $this->details->audio_link,
                ];
            }),

            'authors' => $this->whenLoaded('authors'),

            'category' => $this->whenLoaded('category', function ($category) {
                return CategoryListResource::make($category)->resolve();
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return TagListResource::collection($this->tags)->resolve();
            }),
            'news_images' => $this->whenLoaded('newsImages', function () {
                return NewsImageResource::collection($this->newsImages)->resolve();
            }),
            'seo_meta' => SeoHelper::Make(
                title: $this->newsSeo?->title ?? $this->title,
                image: $this->newsSeo?->og_image ?? $this->image,
                description: $this->newsSeo?->description ?? $this->sort_description,
                keywords: $this->newsSeo?->keywords ?? $this->tags->pluck('name')->toArray(),
            ),
        ];
    }

    public function newsUrl($category, $slug): string
    {
        $path = app(CategoryPathService::class)->build($category);

        return '/'.$path.'/'.$slug;
    }
}
