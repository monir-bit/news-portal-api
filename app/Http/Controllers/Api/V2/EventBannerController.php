<?php

namespace App\Http\Controllers\Api\V2;

use App\Applications\Enums\EventBannerName;
use App\Http\Controllers\Controller;
use App\Models\EventBanner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class EventBannerController extends Controller
{
    /** Active scheduled event banner for a fixed placement (`EventBannerName` value). */
    public function show(string $name): JsonResponse
    {
        $bannerName = EventBannerName::tryFrom($name);
        if ($bannerName === null) {
            return response()->json(['data' => null]);
        }

        $banner = EventBanner::query()
            ->where('banner_name', $bannerName)
            ->where('is_active', true)
            ->first();

        if ($banner === null) {
            return response()->json(['data' => null]);
        }

        $now = now();

        if ($banner->start_date !== null) {
            $start = $banner->start_date instanceof Carbon
                ? $banner->start_date
                : Carbon::parse((string) $banner->start_date);
            if ($now->lt($start)) {
                return response()->json(['data' => null]);
            }
        }

        if ($banner->end_date !== null) {
            $end = $banner->end_date instanceof Carbon
                ? $banner->end_date
                : Carbon::parse((string) $banner->end_date);
            if ($now->gt($end)) {
                return response()->json(['data' => null]);
            }
        }

        if ($banner->mobile_image_path === null && $banner->desktop_image_path === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'banner_name' => $banner->banner_name->value,
                'banner_label' => $banner->banner_name->label(),
                'mobile_image' => $banner->mobile_image,
                'desktop_image' => $banner->desktop_image,
                'link' => $banner->link,
                'start_date' => $this->formatDateTime($banner->start_date),
                'end_date' => $this->formatDateTime($banner->end_date),
                'is_active' => (bool) $banner->is_active,
            ],
        ]);
    }

    private function formatDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);

        return $date->toIso8601String();
    }
}
