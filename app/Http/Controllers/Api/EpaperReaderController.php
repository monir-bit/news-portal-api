<?php

namespace App\Http\Controllers\Api;

use App\Applications\Cache\CacheKey;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EpaperPublicationRowResource;
use App\Http\Resources\Api\EpaperReaderShowResource;
use App\Models\EpaperEdition;
use App\Models\EpaperEditionPage;
use App\Models\EpaperPublication;
use App\Models\EpaperRegion;
use App\Support\EpaperCropDownloadImage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EpaperReaderController extends Controller
{
    public function publications(): JsonResponse
    {
        $payload = Cache::remember(
            CacheKey::epaperPublications(),
            now()->addMinutes(10),
            function (): array {
                $rows = EpaperPublication::query()
                    ->whereHas('editions', fn ($q) => $q->published())
                    ->orderBy('created_at')
                    ->get(['id', 'name', 'slug']);

                return [
                    'publications' => EpaperPublicationRowResource::collection($rows)->resolve(),
                ];
            }
        );

        return response()->json($payload);
    }

    public function show(Request $request, string $slug, string $date): JsonResponse
    {
        $revisionInput = $request->query('revision');
        $revisionKey = ($revisionInput !== null && $revisionInput !== '')
            ? (string) (int) $revisionInput
            : 'latest';

        $payload = Cache::remember(
            CacheKey::epaperReaderShow($slug, $date, $revisionKey),
            now()->addMinutes(10),
            function () use ($request, $slug, $date): array {
                $edition = $this->resolveReaderEdition($request, $slug, $date);
                $edition->load([
                    'publication',
                    'pages.regions.news.details',
                    'pages.regions.news.category.parentRecursive',
                    'pages.regions.news.liveNews',
                ]);

                $publication = $edition->publication;
                $resolvedDate = $edition->publication_date
                    ? Carbon::parse($edition->publication_date)->toDateString()
                    : $date;
                $baseQuery = EpaperEdition::query()
                    ->published()
                    ->where('epaper_publication_id', $publication->id)
                    ->whereDate('publication_date', $resolvedDate);

                $availableRevisions = (clone $baseQuery)
                    ->orderBy('revision')
                    ->get(['id', 'revision']);

                $revisionsPayload = $availableRevisions
                    ->map(fn (EpaperEdition $e) => [
                        'edition_id' => $e->id,
                        'revision' => $e->revision,
                    ])
                    ->values()
                    ->all();

                return array_merge(
                    EpaperReaderShowResource::make($edition)->resolve(),
                    ['available_revisions' => $revisionsPayload]
                );
            }
        );

        return response()->json($payload);
    }

    /**
     * Download one crop, or head+tail merged vertically (single JPEG).
     */
    public function downloadCrops(Request $request, string $slug, string $date)
    {
        $edition = $this->resolveReaderEdition($request, $slug, $date);
        $disk = config('filesystems.default');
        $headInput = $request->query('head_region_id');
        $tailInput = $request->query('tail_region_id');
        $singleInput = $request->query('region_id');

        if ($headInput !== null && $headInput !== '' && $tailInput !== null && $tailInput !== '') {
            $headRegion = $this->regionOnEditionOrAbort((int) $headInput, $edition->id);
            $tailRegion = $this->regionOnEditionOrAbort((int) $tailInput, $edition->id);

            if (! $headRegion->crop_image_path || ! $tailRegion->crop_image_path) {
                abort(404);
            }

            if (! $this->regionsAreLinkedPair($headRegion, $tailRegion)) {
                abort(404);
            }

            $ordered = collect([$headRegion, $tailRegion])
                ->sortBy(fn (EpaperRegion $r) => $r->role === EpaperRegion::ROLE_HEAD ? 0 : 1)
                ->values();
            $head = $ordered[0];
            $tail = $ordered[1];

            if ($head->role !== EpaperRegion::ROLE_HEAD || $tail->role !== EpaperRegion::ROLE_TAIL) {
                abort(404);
            }

            $binary = (new EpaperCropDownloadImage())->mergeVerticalFromStorage(
                $head->crop_image_path,
                $tail->crop_image_path,
                $disk
            );
            $filename = sprintf('epaper-%s-%s-head-tail.jpg', $slug, $date);

            return $this->jpegDownloadResponse($binary, $filename);
        }

        if ($singleInput !== null && $singleInput !== '') {
            $region = $this->regionOnEditionOrAbort((int) $singleInput, $edition->id);
            if (! $region->crop_image_path) {
                abort(404);
            }
            $binary = (new EpaperCropDownloadImage())->fromStoragePath(
                $region->crop_image_path,
                $disk
            );
            $filename = sprintf('epaper-%s-%s-clip-%s.jpg', $slug, $date, $region->id);

            return $this->jpegDownloadResponse($binary, $filename);
        }

        abort(404);
    }

    /**
     * Download full edition page scan (JPEG with center watermark only).
     */
    public function downloadPage(Request $request, string $slug, string $date)
    {
        $edition = $this->resolveReaderEdition($request, $slug, $date);
        $disk = config('filesystems.default');
        $pageInput = $request->query('page');

        if ($pageInput === null || $pageInput === '') {
            abort(404);
        }

        $pageNumber = (int) $pageInput;
        if ($pageNumber < 1) {
            abort(404);
        }

        $page = EpaperEditionPage::query()
            ->where('epaper_edition_id', $edition->id)
            ->where('page_number', $pageNumber)
            ->firstOrFail();

        if (! $page->image_path) {
            abort(404);
        }

        $binary = (new EpaperCropDownloadImage())->fromStoragePath($page->image_path, $disk, false);
        $filename = sprintf('epaper-%s-%s-page-%d.jpg', $slug, $date, $pageNumber);

        return $this->jpegDownloadResponse($binary, $filename);
    }

    private function resolveReaderEdition(Request $request, string $slug, string $date): EpaperEdition
    {
        $publication = EpaperPublication::where('slug', $slug)->firstOrFail();

        $revisionInput = $request->query('revision');

        $edition = $this->findReaderEditionOnDate($publication->id, $date, $revisionInput);
        if ($edition !== null) {
            return $edition;
        }

        $fallbackDate = EpaperEdition::query()
            ->published()
            ->where('epaper_publication_id', $publication->id)
            ->whereDate('publication_date', '<=', $date)
            ->orderByDesc('publication_date')
            ->value('publication_date');

        if ($fallbackDate === null) {
            abort(404);
        }

        $resolvedDate = Carbon::parse($fallbackDate)->toDateString();
        $edition = $this->findReaderEditionOnDate($publication->id, $resolvedDate, $revisionInput);

        return $edition ?? abort(404);
    }

    private function findReaderEditionOnDate(
        int $publicationId,
        string $date,
        mixed $revisionInput
    ): ?EpaperEdition {
        $baseQuery = EpaperEdition::query()
            ->published()
            ->where('epaper_publication_id', $publicationId)
            ->whereDate('publication_date', $date);

        if ($revisionInput !== null && $revisionInput !== '') {
            return (clone $baseQuery)
                ->where('revision', (int) $revisionInput)
                ->first();
        }

        return (clone $baseQuery)->orderByDesc('revision')->first();
    }

    private function regionOnEditionOrAbort(int $regionId, int $editionId): EpaperRegion
    {
        return EpaperRegion::query()
            ->where('id', $regionId)
            ->whereHas('page', fn ($q) => $q->where('epaper_edition_id', $editionId))
            ->firstOrFail();
    }

    private function regionsAreLinkedPair(EpaperRegion $a, EpaperRegion $b): bool
    {
        return ((int) ($a->linked_region_id ?? 0) === (int) $b->id)
            || ((int) ($b->linked_region_id ?? 0) === (int) $a->id);
    }

    private function jpegDownloadResponse(string $binary, string $filename)
    {
        return response($binary, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
