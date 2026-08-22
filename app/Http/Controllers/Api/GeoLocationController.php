<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class GeoLocationController extends Controller
{
    public function divisions()
    {
        return app(SharedCache::class)->rememberLong(CacheKey::divisions(), [CacheTags::geo()], function () {
            return Division::orderBy('name')->get(['name', 'slug']);
        }, 86400);
    }

    public function districts(string $divisionSlug)
    {
        return app(SharedCache::class)->rememberLong(CacheKey::districts($divisionSlug), [CacheTags::geo()], function () use ($divisionSlug) {
            $division = Division::where('slug', $divisionSlug)->firstOrFail();
            $districts = District::where('division_id', $division->id)
                ->orderBy('name')
                ->get(['name', 'slug']);

            return [
                'division' => ['name' => $division->name, 'slug' => $division->slug],
                'items' => $districts,
            ];
        }, 86400);
    }

    public function upazilas(string $districtSlug)
    {
        return app(SharedCache::class)->rememberLong(CacheKey::upazilas($districtSlug), [CacheTags::geo()], function () use ($districtSlug) {
            $district = District::with('division:id,name,slug')->where('slug', $districtSlug)->firstOrFail();
            $upazilas = Upazila::where('district_id', $district->id)
                ->orderBy('name')
                ->get(['name', 'slug']);

            return [
                'division' => ['name' => $district->division->name, 'slug' => $district->division->slug],
                'district' => ['name' => $district->name, 'slug' => $district->slug],
                'items' => $upazilas,
            ];
        }, 86400);
    }
}
