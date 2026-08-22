<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Database\Eloquent\Collection;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class GeoLocationController extends Controller
{
    public function divisions(SharedCache $sharedCache): Collection
    {
        return $sharedCache->rememberLong(
            CacheKey::divisions(),
            [CacheTags::geo()],
            fn () => Division::orderBy('name')->get(['name', 'slug']),
            86400,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function districts(string $divisionSlug, SharedCache $sharedCache): array
    {
        return $sharedCache->rememberLong(
            CacheKey::districts($divisionSlug),
            [CacheTags::geo()],
            function () use ($divisionSlug) {
                $division = Division::where('slug', $divisionSlug)->firstOrFail();
                $districts = District::where('division_id', $division->id)
                    ->orderBy('name')
                    ->get(['name', 'slug']);

                return [
                    'division' => ['name' => $division->name, 'slug' => $division->slug],
                    'items' => $districts,
                ];
            },
            86400,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function upazilas(string $districtSlug, SharedCache $sharedCache): array
    {
        return $sharedCache->rememberLong(
            CacheKey::upazilas($districtSlug),
            [CacheTags::geo()],
            function () use ($districtSlug) {
                $district = District::with('division:id,name,slug')->where('slug', $districtSlug)->firstOrFail();
                $upazilas = Upazila::where('district_id', $district->id)
                    ->orderBy('name')
                    ->get(['name', 'slug']);

                return [
                    'division' => ['name' => $district->division->name, 'slug' => $district->division->slug],
                    'district' => ['name' => $district->name, 'slug' => $district->slug],
                    'items' => $upazilas,
                ];
            },
            86400,
        );
    }
}
