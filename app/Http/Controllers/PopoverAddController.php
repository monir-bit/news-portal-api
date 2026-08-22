<?php

namespace App\Http\Controllers;

use App\Models\PopoverAdd;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PopoverAddController extends Controller
{
    /**
     * Active pop-over ad for the news portal front site (within schedule).
     */
    public function active(): JsonResponse
    {
        $now = now();

        $popoverAdd = PopoverAdd::query()
            ->where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->orderByDesc('id')
            ->first();

        if ($popoverAdd === null) {
            return response()->json(['data' => null]);
        }

        $start = $popoverAdd->start_time instanceof Carbon
            ? $popoverAdd->start_time
            : Carbon::parse((string) $popoverAdd->start_time);
        $end = $popoverAdd->end_time instanceof Carbon
            ? $popoverAdd->end_time
            : Carbon::parse((string) $popoverAdd->end_time);

        return response()->json([
            'data' => [
                'id' => $popoverAdd->id,
                'title' => $popoverAdd->title,
                'image' => $popoverAdd->image,
                'link' => $popoverAdd->link,
                'start_time' => $start->toIso8601String(),
                'end_time' => $end->toIso8601String(),
                'delay' => (int) $popoverAdd->delay,
                'duration' => (int) $popoverAdd->duration,
                'is_active' => (bool) $popoverAdd->is_active,
                'width' => $popoverAdd->width !== null ? (int) $popoverAdd->width : null,
                'height' => $popoverAdd->height !== null ? (int) $popoverAdd->height : null,
            ],
        ]);
    }
}
