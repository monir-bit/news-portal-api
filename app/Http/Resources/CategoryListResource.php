<?php

namespace App\Http\Resources;

use App\Support\CategoryPathService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListResource extends JsonResource
{
    /**
     * `slug` = this category's own slug.
     * `path` = full slash-joined segment chain from root down to this category
     *          (parent/child/leaf), matching the news `url` / CategoryPathService.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'path' => app(CategoryPathService::class)->build($this->resource),
        ];
    }
}
