<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\PopoverAdd;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PopoverAddController extends Controller
{
    /** Active pop-over ad for the news portal front site (within schedule). */
    public function active(): JsonResponse
    {
        $now = now();

        $p = PopoverAdd::query()
            ->where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->orderByDesc('id')
            ->first();

        if ($p === null) {
            return response()->json(['data' => null]);
        }

        $start = $p->start_time instanceof Carbon ? $p->start_time : Carbon::parse((string) $p->start_time);
        $end = $p->end_time instanceof Carbon ? $p->end_time : Carbon::parse((string) $p->end_time);

        return response()->json([
            'data' => [
                'id' => $p->id,
                'title' => $p->title,
                'image' => $p->image,
                'link' => $p->link,
                'start_time' => $start->toIso8601String(),
                'end_time' => $end->toIso8601String(),
                'delay' => (int) $p->delay,
                'duration' => (int) $p->duration,
                'is_active' => (bool) $p->is_active,
                'width' => $p->width !== null ? (int) $p->width : null,
                'height' => $p->height !== null ? (int) $p->height : null,
            ],
        ]);
    }
}
