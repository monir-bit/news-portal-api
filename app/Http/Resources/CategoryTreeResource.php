<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->transformCategory($this->resource);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCategory(Category $category, string $parentPath = ''): array
    {
        $currentPath = $parentPath.'/'.$category->slug;

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'path' => $currentPath,
            'children' => collect($category->childrenRecursive)->map(
                fn (Category $child) => $this->transformCategory($child, $currentPath)
            ),
        ];
    }
}
