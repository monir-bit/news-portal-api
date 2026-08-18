<?php

namespace App\Http\Resources\Api;

use App\Services\CategoryPathService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * `slug` = এই ক্যাটাগরির নিজের slug।
     * `path` = রুট থেকে এই ক্যাটাগরি পর্যন্ত পুরো সেগমেন্ট (parent/child/leaf) —
     *          নিউজ `url` ও CategoryPathService এর সাথে মিল রেখে।
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
