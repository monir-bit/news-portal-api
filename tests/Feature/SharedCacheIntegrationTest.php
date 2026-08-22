<?php

use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

uses(RefreshDatabase::class);

it('caches the divisions endpoint response so a second request does not hit the database again', function () {
    Division::create(['name' => 'Dhaka', 'slug' => 'dhaka']);

    DB::enableQueryLog();

    $first = $this->getJson('/api/divisions')->assertOk();
    $queriesAfterFirst = count(DB::getQueryLog());

    $second = $this->getJson('/api/divisions')->assertOk();
    $queriesAfterSecond = count(DB::getQueryLog());

    expect($first->json())->toBe($second->json());
    expect($queriesAfterSecond)->toBe($queriesAfterFirst);

    DB::disableQueryLog();
});

it('serves fresh data after the geo cache tag is invalidated - exactly as an admin-side write would trigger', function () {
    Division::create(['name' => 'Dhaka', 'slug' => 'dhaka']);

    $before = $this->getJson('/api/divisions')->assertOk()->json();
    expect($before)->toHaveCount(1);

    Division::create(['name' => 'Chattogram', 'slug' => 'chattogram']);

    // Without invalidation the second division would stay invisible for the
    // whole TTL. Simulate the invalidation that a real write (in a sibling
    // app) would trigger through the very same shared package.
    $stillStale = $this->getJson('/api/divisions')->assertOk()->json();
    expect($stillStale)->toHaveCount(1);

    app(SharedCache::class)->flushTags([CacheTags::geo()]);

    $fresh = $this->getJson('/api/divisions')->assertOk()->json();
    expect($fresh)->toHaveCount(2);
});

it('keys most-read-by-category by limit so two different limits never collide', function () {
    $keyA = CacheKey::mostReadNewsByCategory(1, 5);
    $keyB = CacheKey::mostReadNewsByCategory(1, 15);

    expect($keyA)->not->toBe($keyB);
});
