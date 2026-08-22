<?php

use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // This app has no base-schema migrations of its own - it reads from the
    // same database news-portal-admin owns and migrates. Layer a small set
    // of fixture-only table definitions on top of RefreshDatabase's sqlite
    // db so isolated tests can exercise the handful of models this app's
    // test suite actually touches.
    $this->artisan('migrate', ['--path' => 'tests/fixtures/migrations', '--realpath' => false])->run();
});

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

    $stillStale = $this->getJson('/api/divisions')->assertOk()->json();
    expect($stillStale)->toHaveCount(1);

    app(SharedCache::class)->flushTags([CacheTags::geo()]);

    $fresh = $this->getJson('/api/divisions')->assertOk()->json();
    expect($fresh)->toHaveCount(2);
});

it('shares the same physical Redis-tag cache keyspace conventions as the sibling old API', function () {
    // Cross-app sharing depends on both apps building identical keys/tags
    // for the same logical entity - assert that here rather than relying on
    // manual inspection drifting out of sync between the two apps.
    expect(CacheKey::divisions())->toBe('news:v1:geo-location:divisions');
    expect(CacheTags::geo())->toBe('news:tag:geo');
});
