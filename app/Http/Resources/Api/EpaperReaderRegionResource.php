<?php

namespace App\Http\Resources\Api;

use App\Models\EpaperRegion;
use App\Repositories\MediaHelperRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EpaperRegion */
class EpaperReaderRegionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = app(MediaHelperRepositoryInterface::class);
        $news = $this->news;

        $newsPayload = null;
        // Linked news is included when present — epaper regions are curated by editors,
        // so do not gate on News::$published (title can show while payload was null).
        if ($news !== null) {
            $newsPayload = [
                'id' => $news->id,
                'image_url' => $news->image,
                'details_html' => $news->relationLoaded('details') ? ($news->details?->details ?? null) : null,
            ];
        }

        return [
            'id' => $this->id,
            'epaper_edition_page_id' => $this->epaper_edition_page_id,
            'role' => $this->role,
            'title' => $this->title,
            'url' => $this->external_url ?? '',
            'x' => (float) $this->x_pct,
            'y' => (float) $this->y_pct,
            'width' => (float) $this->width_pct,
            'height' => (float) $this->height_pct,
            'crop_image_url' => $this->crop_image_path ? $media->url($this->crop_image_path) : null,
            'linkedAnnotationId' => $this->linked_region_id !== null ? (string) $this->linked_region_id : null,
            'linked_annotation_id' => $this->linked_region_id !== null ? (string) $this->linked_region_id : null,
            'news_id' => $this->news_id,
            'news_title' => $news?->title ?? '',
            'news' => $newsPayload,
        ];
    }
}
