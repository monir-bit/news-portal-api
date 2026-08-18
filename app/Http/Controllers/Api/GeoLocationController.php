<?php

namespace App\Http\Controllers\Api;

use App\Applications\Cache\CacheKey;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Support\Facades\Cache;

class GeoLocationController extends Controller
{
    public function divisions()
    {
        return Cache::rememberForever(CacheKey::divisions(), function () {
            return Division::orderBy('name')->get(['name', 'slug']);
        });
    }

    public function districts(string $divisionSlug)
    {
        return Cache::rememberForever(CacheKey::districts($divisionSlug), function () use ($divisionSlug) {
            $division = Division::where('slug', $divisionSlug)->firstOrFail();
            $districts = District::where('division_id', $division->id)
                ->orderBy('name')
                ->get(['name', 'slug']);

            return [
                'division' => ['name' => $division->name, 'slug' => $division->slug],
                'items' => $districts,
            ];
        });
    }

    public function upazilas(string $districtSlug)
    {
        return Cache::rememberForever(CacheKey::upazilas($districtSlug), function () use ($districtSlug) {
            $district = District::with('division:id,name,slug')->where('slug', $districtSlug)->firstOrFail();
            $upazilas = Upazila::where('district_id', $district->id)
                ->orderBy('name')
                ->get(['name', 'slug']);

            return [
                'division' => ['name' => $district->division->name, 'slug' => $district->division->slug],
                'district' => ['name' => $district->name, 'slug' => $district->slug],
                'items' => $upazilas,
            ];
        });
    }
}
