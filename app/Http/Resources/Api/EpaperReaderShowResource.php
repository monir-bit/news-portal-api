<?php

namespace App\Http\Resources\Api;

use App\Models\EpaperEdition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EpaperEdition */
class EpaperReaderShowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EpaperEdition $edition */
        $edition = $this->resource;

        return [
            'publication' => [
                'slug' => $edition->publication->slug,
                'name' => $edition->publication->name,
            ],
            'edition' => [
                'id' => $edition->id,
                'publication_date' => $edition->publication_date?->format('Y-m-d'),
                'title' => $edition->title,
                'revision' => $edition->revision,
            ],
            'pages' => EpaperReaderPageResource::collection(
                $edition->pages->sortBy('page_number')->values()
            ),
        ];
    }
}
