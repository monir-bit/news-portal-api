<?php

namespace App\Http\Resources\Api;

use App\Models\EpaperEditionPage;
use App\Repositories\MediaHelperRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EpaperEditionPage */
class EpaperReaderPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = app(MediaHelperRepositoryInterface::class);

        return [
            'id' => (string) $this->id,
            'page_number' => $this->page_number,
            'image' => $media->url($this->image_path),
            'annotations' => EpaperReaderRegionResource::collection($this->regions),
        ];
    }
}
