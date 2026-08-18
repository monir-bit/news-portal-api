<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\UtilsHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReporterPrintNewsStoreRequest;
use App\Models\ReporterNews;
use App\Models\ReporterPrintNews;
use App\Models\ReporterPrintNewsMedia;
use App\Repositories\MediaHelperRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporterPrintNewsController extends Controller
{
    public function __construct(
        private MediaHelperRepositoryInterface $mediaHelper
    ) {}

    /**
     * Upload single media (image) for reporter print news.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = UtilsHelper::MonthYearWisePath();
        $uploadedPath = $this->mediaHelper->upload($request->file('image'), $path);

        return response()->json([
            'success' => true,
            'data' => [
                'media_path' => $uploadedPath,
                'media_url' => $this->mediaHelper->url($uploadedPath),
            ],
        ]);
    }

    /**
     * List reporter's submitted print news.
     * Query params: page, per_page (default 10).
     */
    public function index(Request $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $perPage = min(max((int) $request->query('per_page', 5), 1), 50);
        $page = max((int) $request->query('page', 1), 1);

        $paginator = $reporter->reporterPrintNews()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'reporter_news_id' => $n->reporter_news_id,
            'created_at' => $n->created_at?->toIso8601String(),
            'last_update_at' => $n->updated_at?->toIso8601String(),
        ]);

        $paginator->setCollection($items->values());

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Store new print news.
     * Geo location is not stored on print rows; online submissions attach NewsLocation via ReporterNewsController::store when the reporter has_location is true.
     */
    public function store(ReporterPrintNewsStoreRequest $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $data = [
            'reporter_id' => $reporter->id,
            'title' => $request->title,
            'content' => $request->content,
        ];
        if ($request->filled('reporter_news_id')) {
            $owns = ReporterNews::where('id', $request->reporter_news_id)
                ->where('reporter_id', $reporter->id)
                ->exists();
            if ($owns) {
                $data['reporter_news_id'] = $request->reporter_news_id;
            }
        }
        $printNews = ReporterPrintNews::create($data);

        $mediaPaths = $request->input('media_paths', []);
        if (is_array($mediaPaths) && !empty($mediaPaths)) {
            foreach ($mediaPaths as $path) {
                if (is_string($path) && $path !== '') {
                    ReporterPrintNewsMedia::create([
                        'reporter_id' => $reporter->id,
                        'reporter_print_news_id' => $printNews->id,
                        'media_type' => 'image',
                        'media_url' => $path,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Print news submitted successfully.',
            'data' => [
                'id' => $printNews->id,
                'title' => $printNews->title,
            ],
        ]);
    }
}
