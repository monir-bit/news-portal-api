<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->transformCategory($this->resource);
    }

    private function transformCategory($category, $parentPath = '')
    {
        $currentPath = $parentPath . '/' . $category->slug;

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'path' => $currentPath,

            'children' => collect($category->childrenRecursive)->map(function ($child) use ($currentPath) {
                return $this->transformCategory($child, $currentPath);
            }),
        ];
    }
}
