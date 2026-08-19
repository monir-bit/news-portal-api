# `LayoutSectionWiseNewsQuery` — Problems & Optimization Plan

Files reviewed:
- `app/Applications/Queries/Api/LayoutSectionWiseNewsQuery.php`
- `app/Http/Resources/Api/NewsListResource.php`
- `app/Models/LayoutSection.php`, `app/Models/LayoutSectionNews.php`, `app/Models/News.php`, `app/Models/Category.php`, `app/Models/LiveNews.php`
- DB schema (`layout_section_news`, `layout_sections`, `news`, `categories`) via `database-schema`

---

## 1. Cache key collides between `handle()` and `handleLivePin()` (correctness bug)

```php
Cache::remember(CacheKey::homeSectionWiseNews($section_slug), ...)
```

`CacheKey::homeSectionWiseNews()` (vendor package) only takes `$sectionName`:

```php
public static function homeSectionWiseNews($sectionName): string
{
    return self::base() . ':home-section-wise-news:'.$sectionName;
}
```

Both methods write to **the exact same key** for the same `$section_slug`. If both `handle()` and `handleLivePin()` are ever used for the same section (e.g. two different endpoints/widgets pointing at the same layout section), whichever call runs first "wins" for 5 minutes and the other reads back the wrong ordering (plain `position` vs `live_news DESC, position ASC`).

**Also**, `$limit` is not part of the key. `handle('top-news', 5)` caches 5 items; a subsequent `handle('top-news', 20)` within the 5-minute TTL will get the stale 5-item result instead of 20.

**Fix:** include a discriminator + limit in the key, e.g.:

```php
Cache::remember(
    CacheKey::homeSectionWiseNews($section_slug) . ':live-pin:' . ($limit ?? 'all'),
    ...
);
```
or extend the shared package's `homeSectionWiseNews()` to accept a `$variant`/`$limit` argument so the key is generated consistently everywhere it's used.

---

## 2. Cached payload stores hydrated Eloquent models, not plain arrays

```php
->map(function ($item) {
    return [
        'position' => $item->position,
        'news' => NewsListResource::make($item->news), // <-- a JsonResource wrapping a Model
    ];
});
```

`Cache::remember` serializes whatever the closure returns. Since `NewsListResource::make()` is **not** resolved (`->resolve()` / `->toArray()`), the cached value contains the `JsonResource` object which in turn wraps the full `News` model plus every loaded relation (`category`, `category.parentRecursive` chain, `liveNews`). That means:

- Every cache write serializes full Eloquent models (all columns, including unused text columns like `ticker`, `sort_description`, `old_hash_key`, etc.) instead of the ~12 fields actually needed by the resource.
- Every cache read has to unserialize full model graphs (`Model::__wakeup`, casts re-applied, etc.) — much heavier than decoding a plain array.
- It's fragile: if a relation is renamed/removed or a model's `$casts` change, previously cached (serialized) entries can throw on unserialize.

**Fix:** resolve the resource to a plain array before caching:

```php
'news' => NewsListResource::make($item->news)->resolve(),
```

or `->response()->getData(true)` if you need the wrapping envelope. Plain arrays are cheap to serialize/deserialize and immune to model-shape drift.

---

## 3. Over-fetching — no column selection anywhere in the chain

None of `LayoutSectionNews`, `News`, `Category` (including the recursive `parentRecursive` chain) restrict columns. Every query pulls `SELECT *`, including large/unused `text` columns on `news` (`shoulder`, `ticker`, `sort_description`) and columns `NewsListResource` never reads (`proofreader`, `old_hash_key`, `is_working`, `working_by`, `created_by`, `updated_by`, `read_count`, ...).

**Fix:** constrain both the top-level query and the eager loads to the columns actually used by `NewsListResource` / `CategoryListResource`, always keeping the FK/PK needed for relation matching:

```php
$news_list = LayoutSectionNews::query()
    ->select(['id', 'layout_section_id', 'news_id', 'position'])
    ->with([
        'news' => fn ($q) => $q->select([
            'id', 'category_id', 'slug_key', 'title', 'ticker', 'image',
            'image_caption', 'shoulder', 'sort_description', 'live_news',
            'is_thread', 'is_visible_shoulder', 'is_visible_ticker',
            'date', 'created_at', 'representative',
        ]),
        'news.category:id,name,slug,parent_id',
        'news.category.parentRecursive:id,name,slug,parent_id',
        'news.liveNews:id,news_id,is_active',
    ])
    ...
```

Also apply `LayoutSection::where('slug', $section_slug)->select('id')->first()` — only `id` is used afterward.

---

## 4. `whereHas('news', ...)` runs a correlated `EXISTS` subquery per row

```php
->whereHas('news', function ($q) {
    $q->whereNull('deleted_at');
    $q->where('published', true);
})
```

Per the project's advanced-query rules, `whereHas()` compiles to a correlated `EXISTS` subquery that re-executes against `news` for every `layout_section_news` row being scanned. `handleLivePin()` already shows the better pattern for this exact case — an explicit `join`. Applying the same join to `handle()` avoids the correlated subquery and lets Postgres use the existing `layout_section_news_news_id_index` / `news_pkey` for a single hash/merge join instead:

```php
$news_list = LayoutSectionNews::query()
    ->select('layout_section_news.*')
    ->join('news', 'news.id', '=', 'layout_section_news.news_id')
    ->with(['news.category.parentRecursive'])
    ->where('layout_section_id', $layout_section->id)
    ->whereNull('news.deleted_at')
    ->where('news.published', true)
    ->when($limit, fn ($q) => $q->limit($limit))
    ->orderBy('layout_section_news.position', 'ASC')
    ->get()
    ...
```

Note: `news.published` has an index (`news_published_index`) but there is **no index covering `deleted_at`**. Since every query on this table filters `whereNull('deleted_at')` in addition to `published = true`, consider a composite/partial index, e.g. (Postgres):

```sql
create index news_published_not_deleted_idx on news (published) where deleted_at is null;
```

This directly speeds up both `handle()` and `handleLivePin()`, plus any other query filtering "live" news.

---

## 5. `parentRecursive` walks the category tree one query per depth level

```php
public function parentRecursive()
{
    return $this->parent()->with('parentRecursive');
}
```

This is not a classic per-row N+1 (Eloquent batches all parents at a given depth into one query), but it **is** a sequential chain of round-trips equal to the category tree depth (root → child → grandchild, etc.), because each level's query depends on the previous level's result before it can run. For a 3-level category tree that's 3 extra sequential DB round-trips per request, on top of the main query — and this happens **on every cache miss**, since it lives inside the `Cache::remember` closure.

This is hard to fully eliminate without changing how categories are modeled (e.g. storing a materialized path or `ancestry` closure table), which is out of scope here. Given results are already cached for 5 minutes, the main mitigation is:
- Make sure `categories` columns are trimmed (`id,name,slug,parent_id` — see §3) so each round trip is as cheap as possible.
- Since `categories_parent_id_index` already exists, no further indexing work is needed here.

---

## 6. Relationship-level hidden eager load (`news()` always pulls `liveNews`)

```php
// LayoutSectionNews model
public function news() {
    return $this->belongsTo(News::class, 'news_id', 'id')->with('liveNews');
}
```

Baking `->with('liveNews')` into the relationship definition means **every** use of `LayoutSectionNews::news` (including places unrelated to this query, and even lazy-loading `$layoutSectionNews->news`) always triggers an extra `liveNews` query, whether the caller needs it or not. It also makes the eager-loading behavior of `LayoutSectionWiseNewsQuery` implicit — a reader has to open the model to know `liveNews` gets loaded.

**Fix:** remove `->with('liveNews')` from the relation definition and eager load it explicitly where it's actually consumed:

```php
// LayoutSectionNews model
public function news()
{
    return $this->belongsTo(News::class, 'news_id', 'id');
}
```

```php
// Query
->with(['news.category.parentRecursive', 'news.liveNews'])
```

`NewsListResource` only reads `liveNews` via `whenLoaded`, so this is a safe, purely-explicit change (see `NewsListResource::toArray()` line 31 — `whenLoaded('liveNews', ...)`).

---

## 7. No stale-while-revalidate for a hot homepage endpoint

`Cache::remember(..., now()->addMinutes(5), ...)` means the first request after every 5-minute expiry pays the full query cost (including the category-depth round trips from §5) synchronously. For a homepage section this is a high-traffic key. Per the project's caching guidelines, consider `Cache::flexible()`:

```php
Cache::flexible($cacheKey, [300, 900], function () { ... });
```
Fresh for 5 minutes, served stale (up to 15 minutes) while a background refresh happens — removes the periodic slow request entirely.

---

## Priority Summary

| # | Issue | Type | Impact |
|---|-------|------|--------|
| 1 | Cache key collision between `handle()`/`handleLivePin()` and missing `$limit` in key | Correctness bug | High — wrong data served |
| 2 | Caching resolved `JsonResource`/Eloquent models instead of plain arrays | Cache bloat / fragility | High — cache size & (de)serialize cost |
| 4 | `whereHas` correlated subquery instead of `join` (already proven pattern in `handleLivePin`) | Query performance | Medium |
| 3 | No column selection (`SELECT *` through the whole relation chain) | Over-fetching | Medium |
| 6 | `liveNews` eager load hard-coded into `news()` relation | Hidden N+1 / coupling | Low-Medium |
| 5 | `parentRecursive` sequential per-depth queries | Query performance | Low (mitigated by caching) |
| 7 | No stale-while-revalidate on a hot cache key | Latency spike at TTL expiry | Low-Medium |

**Suggested fix order:** #1 (correctness) → #2 → #4 → #3 → #6 → #7 → #5.

None of these require a schema migration except the optional partial index in §4, which is additive and safe to apply separately.
