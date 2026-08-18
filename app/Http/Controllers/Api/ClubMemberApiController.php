<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\UtilsHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CareerClubMemberApiStoreRequest;
use App\Http\Requests\Api\GoldClubMemberApiStoreRequest;
use App\Http\Requests\Api\KidsClubMemberApiStoreRequest;
use App\Models\CareerClubMember;
use App\Models\GoldClubMember;
use App\Models\KidsClubMember;
use App\Repositories\MediaHelperRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class ClubMemberApiController extends Controller
{
    public function __construct(
        private MediaHelperRepositoryInterface $mediaHelper,
    ) {}

    public function storeGold(GoldClubMemberApiStoreRequest $request): JsonResponse
    {
        $data = $this->validatedWithoutImage($request->validated());
        $imagePath = $this->uploadImage($request->file('image'));
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        GoldClubMember::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Gold club registration submitted successfully.',
        ], 201);
    }

    public function storeKids(KidsClubMemberApiStoreRequest $request): JsonResponse
    {
        $data = $this->validatedWithoutImage($request->validated());
        $imagePath = $this->uploadImage($request->file('image'));
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        KidsClubMember::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Kids club registration submitted successfully.',
        ], 201);
    }

    public function storeCareer(CareerClubMemberApiStoreRequest $request): JsonResponse
    {
        $data = $this->validatedWithoutImage($request->validated());
        $imagePath = $this->uploadImage($request->file('image'));
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        CareerClubMember::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Career club registration submitted successfully.',
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function validatedWithoutImage(array $validated): array
    {
        unset($validated['image']);

        return $validated;
    }

    private function uploadImage(mixed $file): ?string
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return null;
        }

        return $this->mediaHelper->upload($file, UtilsHelper::MonthYearWisePath());
    }
}
