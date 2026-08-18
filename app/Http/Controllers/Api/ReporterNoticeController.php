<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\UtilsHelper;
use App\Http\Controllers\Controller;
use App\Models\ReporterNotice;
use App\Models\ReporterNoticeOpinion;
use App\Models\ReporterNoticeReadCount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporterNoticeController extends Controller
{
    /**
     * List notices for the authenticated reporter.
     * Returns notices that are: is_active, published (or no published_at), and either is_for_all or assigned to this reporter.
     */
    public function index(Request $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $notices = ReporterNotice::with('media')
            ->where('is_active', true)
            ->where(function ($q) use ($reporter) {
                $q->where('is_for_all', true)
                    ->orWhereHas('assignments', fn ($a) => $a->where('reporter_id', $reporter->id));
            })
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        $items = $notices->getCollection()->map(function ($n) use ($reporter) {
            $readCount = ReporterNoticeReadCount::where('reporter_notice_id', $n->id)
                ->where('reporter_id', $reporter->id)
                ->value('read_count') ?? 0;

            return [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'from' => $n->from,
                'published_at' => $n->published_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
                'media' => $n->media->map(fn ($m) => [
                    'id' => $m->id,
                    'media_type' => $m->media_type,
                    'media_url' => $m->media_url ? UtilsHelper::GetMediaUrl($m->media_url) : null,
                ])->all(),
                'read_count' => $readCount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $items,
                'current_page' => $notices->currentPage(),
                'last_page' => $notices->lastPage(),
                'per_page' => $notices->perPage(),
                'total' => $notices->total(),
            ],
        ]);
    }

    /**
     * Show single notice and increment read count.
     */
    public function show(int $id): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $notice = ReporterNotice::with('media')
            ->where('is_active', true)
            ->where(function ($q) use ($reporter) {
                $q->where('is_for_all', true)
                    ->orWhereHas('assignments', fn ($a) => $a->where('reporter_id', $reporter->id));
            })
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->find($id);

        if (!$notice) {
            return response()->json(['success' => false, 'message' => 'Notice not found.'], 404);
        }

        $rc = ReporterNoticeReadCount::firstOrCreate(
            [
                'reporter_notice_id' => $notice->id,
                'reporter_id' => $reporter->id,
            ],
            ['read_count' => 0]
        );
        $rc->increment('read_count');
        $readCount = $rc->fresh()->read_count;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $notice->id,
                'title' => $notice->title,
                'content' => $notice->content,
                'from' => $notice->from,
                'published_at' => $notice->published_at?->toIso8601String(),
                'created_at' => $notice->created_at?->toIso8601String(),
                'media' => $notice->media->map(fn ($m) => [
                    'id' => $m->id,
                    'media_type' => $m->media_type,
                    'media_url' => $m->media_url ? UtilsHelper::GetMediaUrl($m->media_url) : null,
                ])->all(),
                'read_count' => $readCount,
            ],
        ]);
    }

    /**
     * Submit opinion (motamot) for a notice.
     */
    public function storeOpinion(Request $request, int $id): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $notice = ReporterNotice::where('is_active', true)
            ->where(function ($q) use ($reporter) {
                $q->where('is_for_all', true)
                    ->orWhereHas('assignments', fn ($a) => $a->where('reporter_id', $reporter->id));
            })
            ->find($id);

        if (!$notice) {
            return response()->json(['success' => false, 'message' => 'Notice not found.'], 404);
        }

        ReporterNoticeOpinion::create([
            'reporter_notice_id' => $notice->id,
            'reporter_id' => $reporter->id,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'মতামত সফলভাবে জমা দেওয়া হয়েছে।',
        ]);
    }
}
