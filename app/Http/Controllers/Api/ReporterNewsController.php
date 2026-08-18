<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\UtilsHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReporterNewsStoreRequest;
use App\Models\District;
use App\Models\Division;
use App\Models\News;
use App\Models\NewsLocation;
use App\Models\ReporterNews;
use App\Models\ReporterNewsMedia;
use App\Models\ReporterNewsUpdate;
use App\Models\ReporterNewsUpdateMedia;
use App\Models\Upazila;
use App\Repositories\MediaHelperRepositoryInterface;
use App\Services\CategoryPathService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporterNewsController extends Controller
{
    public function __construct(
        private MediaHelperRepositoryInterface $mediaHelper,
        private CategoryPathService $categoryPathService
    ) {}

    /**
     * Upload single media (image) for reporter news. Returns path for storage.
     * Client sends media_paths[] when submitting news.
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
     * Get submit options: categories + expanded location options.
     */
    public function submitOptions(): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reporter->load(['categories', 'locations.division', 'locations.district', 'locations.upazila']);

        $hasLocation = (bool) $reporter->has_location;
        $locationOptions = $hasLocation ? $this->expandReporterLocationOptions($reporter) : [];

        return response()->json([
            'success' => true,
            'data' => [
                'location_options' => $locationOptions,
                'has_category' => $reporter->categories->isNotEmpty(),
                'has_location' => $hasLocation,
                'reporter_type' => $reporter->reporter_type ?? 'reporter',
            ],
        ]);
    }

    /**
     * List reporter's submitted news with filters and stats.
     * Query params: status (published|unpublished|all), from_date, to_date (Y-m-d), page, per_page (default 10).
     */
    public function index(Request $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $status = $request->query('status', 'all');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);
        $page = max((int) $request->query('page', 1), 1);
        $specialOnly = $request->boolean('is_special');

        $baseQuery = ReporterNews::where('reporter_id', $reporter->id)
            ->whereHas('news')
            ->with(['news.category.parent', 'news.details:id,news_id,details', 'updates'])
            ->withCount('newsEdits')
            ->orderByDesc('reporter_news.created_at');

        $newsQuery = clone $baseQuery;
        $newsQuery->join('news', 'reporter_news.news_id', '=', 'news.id');

        if ($specialOnly) {
            $newsQuery->where('reporter_news.is_special', true);
        }

        if ($status === 'published') {
            $newsQuery->where('news.published', true);
        } elseif ($status === 'unpublished') {
            $newsQuery->where('news.published', false);
        }

        if ($fromDate) {
            $newsQuery->whereDate('news.date', '>=', $fromDate);
        }
        if ($toDate) {
            $newsQuery->whereDate('news.date', '<=', $toDate);
        }

        $paginator = $newsQuery->select('reporter_news.*')->paginate($perPage, ['*'], 'page', $page);

        $mapped = $paginator->getCollection()
            ->filter(fn ($rn) => $rn->news !== null)
            ->map(function ($rn) {
                $n = $rn->news;
                $lastUpdateAt = $rn->updates->max('updated_at');
                $url = null;
                if ($n->category && $n->slug_key) {
                    $path = $this->categoryPathService->build($n->category);
                    $url = '/' . $path . '/' . $n->slug_key;
                }
                return [
                    'id' => $n->id,
                    'reporter_news_id' => $rn->id,
                    'is_special' => (bool) $rn->is_special,
                    'title' => $n->title,
                    'slug_key' => $n->slug_key,
                    'url' => $url,
                    'published' => $n->published,
                    'date' => $n->date?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                    'last_update_at' => $lastUpdateAt?->toIso8601String(),
                    'updates_count' => $rn->updates->count(),
                    'edits_count' => (int) ($rn->news_edits_count ?? 0),
                    'received_updated' => $rn->received_updated ?? 'pending',
                    'received_edit' => $rn->received_edit ?? 'pending',
                    'category' => $n->category ? ['id' => $n->category->id, 'name' => $n->category->name] : null,
                ];
            });

        $paginator->setCollection($mapped->values());

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $yearStart = $now->copy()->startOfYear();

        $allNews = ReporterNews::where('reporter_id', $reporter->id)
            ->whereHas('news')
            ->with('news:id,published,date')
            ->get();

        $publishedNews = $allNews->filter(fn ($rn) => $rn->news && $rn->news->published);
        $unpublishedNews = $allNews->filter(fn ($rn) => $rn->news && !$rn->news->published);

        $stats = [
            'total_published' => $publishedNews->count(),
            'total_unpublished' => $unpublishedNews->count(),
            'today' => $publishedNews->filter(fn ($rn) => $rn->news->date && Carbon::parse($rn->news->date)->gte($todayStart))->count(),
            'this_week' => $publishedNews->filter(fn ($rn) => $rn->news->date && Carbon::parse($rn->news->date)->gte($weekStart))->count(),
            'this_month' => $publishedNews->filter(fn ($rn) => $rn->news->date && Carbon::parse($rn->news->date)->gte($monthStart))->count(),
            'this_year' => $publishedNews->filter(fn ($rn) => $rn->news->date && Carbon::parse($rn->news->date)->gte($yearStart))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * List updates for a reporter news.
     */
    public function indexUpdates(int $reporterNewsId): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reporterNews = ReporterNews::with(['news.details'])
            ->where('id', $reporterNewsId)
            ->where('reporter_id', $reporter->id)
            ->firstOrFail();

        $news = $reporterNews->news;
        $originalContent = $news->details?->details ?? $news->sort_description ?? '';

        $updates = $reporterNews->updates()
            ->with('media')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'content' => $u->content,
                'created_at' => $u->created_at?->toIso8601String(),
                'media' => $u->media->map(fn ($m) => [
                    'id' => $m->id,
                    'media_type' => $m->media_type,
                    'media_url' => $m->media_url,
                    'image_url' => $m->image_url,
                ])->all(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'original' => [
                    'title' => $news->title,
                    'content' => $originalContent,
                ],
                'updates' => $updates,
            ],
        ]);
    }

    /**
     * Store update for a reporter news.
     */
    public function storeUpdate(Request $request, int $reporterNewsId): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reporterNews = ReporterNews::where('id', $reporterNewsId)
            ->where('reporter_id', $reporter->id)
            ->firstOrFail();

        $request->validate([
            'content' => ['required', 'string'],
            'media_paths' => ['nullable', 'array'],
            'media_paths.*' => ['string'],
        ]);

        $content = $request->input('content');
        $mediaPaths = $request->input('media_paths', []);

        $update = ReporterNewsUpdate::create([
            'reporter_news_id' => $reporterNews->id,
            'content' => $content,
        ]);

        foreach ($mediaPaths as $path) {
            if (is_string($path) && $path !== '') {
                ReporterNewsUpdateMedia::create([
                    'reporter_news_update_id' => $update->id,
                    'media_type' => 'image',
                    'media_url' => $path,
                ]);
            }
        }

        $reporterNews->update([
            'received_updated' => 'pending',
            'received_edit' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Update submitted successfully.',
            'data' => [
                'id' => $update->id,
                'content' => $update->content,
                'created_at' => $update->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Submit news (unpublished).
     */
    public function store(ReporterNewsStoreRequest $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();
        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $needsNewsLocation = (bool) $reporter->has_location;

        if ($needsNewsLocation) {
            $locationOptions = $this->expandReporterLocationOptions($reporter);
            $isValidLocation = collect($locationOptions)->contains(function ($opt) use ($request) {
                return (int) $opt['division_id'] === (int) $request->division_id
                    && (int) $opt['district_id'] === (int) $request->district_id
                    && (int) $opt['upazila_id'] === (int) $request->upazila_id;
            });

            if (!$isValidLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected location is not within your allowed coverage areas.',
                ], 422);
            }
        }

        $reporter->load('categories');
        $categoryId = $reporter->categories->first()?->id;
        if (!$categoryId) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has no category assigned. Please contact admin.',
            ], 422);
        }

        // Reporter submits plain text with line breaks → convert to HTML for DB
        $detailsHtml = UtilsHelper::plainTextToNewsHtml($request->details);
        $newsBody = UtilsHelper::SplitCkEditorContent($detailsHtml);

        DB::beginTransaction();
        try {
            $news = News::withoutEvents(function () use ($request, $newsBody, $categoryId) {
                return News::create([
                    'title' => $request->title,
                    'slug_key' => UtilsHelper::generateUniqueNewsSlugKey(),
                    'sort_description' => $newsBody['short'],
                    'category_id' => $categoryId,
                    'published' => false,
                    'date' => now(),
                    'created_by' => null,
                    'latest' => true,
                    'news_marquee' => true,
                    'live_news' => false,
                    'is_visible_shoulder' => false,
                    'is_visible_ticker' => false,
                ]);
            });

            $news->details()->create(['details' => $newsBody['rest']]);

            $reporterNews = ReporterNews::create([
                'reporter_id' => $reporter->id,
                'news_id' => $news->id,
                'original_content' => $request->details,
                'is_special' => $request->boolean('is_special'),
            ]);

            $news->newsLocations()->delete();
            if ($needsNewsLocation) {
                NewsLocation::create([
                    'news_id' => $news->id,
                    'division_id' => $request->division_id,
                    'district_id' => $request->district_id,
                    'upazila_id' => $request->upazila_id,
                ]);
            }

            $mediaPaths = $request->input('media_paths', []);
            if (is_array($mediaPaths) && !empty($mediaPaths)) {
                foreach ($mediaPaths as $path) {
                    if (is_string($path) && $path !== '') {
                        ReporterNewsMedia::create([
                            'reporter_id' => $reporter->id,
                            'news_id' => $news->id,
                            'media_type' => 'image',
                            'media_url' => $path,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News submitted successfully.',
                'data' => [
                    'id' => $news->id,
                    'reporter_news_id' => $reporterNews->id,
                    'title' => $news->title,
                    'slug_key' => $news->slug_key,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit news: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expand reporter locations to full (division, district, upazila) options.
     *
     * @return array<array{division_id: int, district_id: int, upazila_id: int, division_name: string, district_name: string, upazila_name: string}>
     */
    private function expandReporterLocationOptions($reporter): array
    {
        $options = [];
        $locations = $reporter->locations;

        foreach ($locations as $loc) {
            $divisionId = (int) $loc->division_id;
            $districtId = $loc->district_id ? (int) $loc->district_id : null;
            $upazilaId = $loc->upazila_id ? (int) $loc->upazila_id : null;

            $division = Division::find($divisionId);
            if (!$division) {
                continue;
            }

            if ($districtId && $upazilaId) {
                $district = District::find($districtId);
                $upazila = Upazila::find($upazilaId);
                if ($district && $upazila && $district->division_id === $divisionId && $upazila->district_id === $districtId) {
                    $options[] = [
                        'division_id' => $divisionId,
                        'district_id' => $districtId,
                        'upazila_id' => $upazilaId,
                        'division_name' => $division->name,
                        'district_name' => $district->name,
                        'upazila_name' => $upazila->name,
                    ];
                }
            } elseif ($districtId) {
                $district = District::find($districtId);
                if ($district && $district->division_id === $divisionId) {
                    $upazilas = Upazila::where('district_id', $districtId)->orderBy('name')->get();
                    foreach ($upazilas as $upazila) {
                        $options[] = [
                            'division_id' => $divisionId,
                            'district_id' => $districtId,
                            'upazila_id' => (int) $upazila->id,
                            'division_name' => $division->name,
                            'district_name' => $district->name,
                            'upazila_name' => $upazila->name,
                        ];
                    }
                }
            } else {
                $districts = District::where('division_id', $divisionId)->orderBy('name')->get();
                foreach ($districts as $district) {
                    $upazilas = Upazila::where('district_id', $district->id)->orderBy('name')->get();
                    foreach ($upazilas as $upazila) {
                        $options[] = [
                            'division_id' => $divisionId,
                            'district_id' => (int) $district->id,
                            'upazila_id' => (int) $upazila->id,
                            'division_name' => $division->name,
                            'district_name' => $district->name,
                            'upazila_name' => $upazila->name,
                        ];
                    }
                }
            }
        }

        return $options;
    }
}
