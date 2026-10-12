<?php

namespace App\Services\News;

use App\Models\LayoutSectionPin;

class PinLayoutSectionQuery
{
    public function __construct(protected LayoutSectionWiseNewsQuery $layoutSectionWiseNewsQuery)
    {}

    public function get(){
        return LayoutSectionPin::with('layoutSection')->orderBy('position', 'asc')->limit(4)->get()->map(function ($pin) {
            return [
                'name' => $pin->name,
                'image_url' => $pin->icon_image_url,
                'data' => $this->layoutSectionWiseNewsQuery->handle($pin->layoutSection?->slug ?? null, 1)
            ];
        });
    }
}
