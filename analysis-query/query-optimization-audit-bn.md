# নিউজ পোর্টাল API — Query Optimization অডিট ও ফিক্স গাইড (বাংলা)

> **রিভিউয়ার ভূমিকা:** Senior QA + Laravel / PostgreSQL Developer
> **স্ট্যাক:** Laravel 12 · PHP 8.2 · PostgreSQL (`pgsql`) · Laravel Scout (Meilisearch)
> **রিভিউ করা মূল ফাইল:** `app/Applications/Queries/Api/*`, `app/Http/Controllers/Api/NewsController.php`, `HomeController.php`, `app/Services/Api/CategoryNewsPageService.php`, `NewsReadService.php`, `app/Observers/NewsObserver.php`, `app/Http/Resources/Api/*`, মডেল ও `.env`।

---

## ০. এক নজরে (Executive Summary)

এই প্রজেক্টের কোয়ারি কোড মোটামুটিভাবে ভালো প্যাটার্নে লেখা (query class আলাদা, resource আলাদা, cache ব্যবহার আছে)। কিন্তু কয়েকটি জায়গায় এমন সমস্যা আছে যেগুলো **ট্রাফিক বাড়লে সরাসরি ডাটাবেস ধসিয়ে দিতে পারে** অথবা **ভুল ডেটা রিটার্ন করতে পারে (correctness bug)**।

সবচেয়ে বড় দুইটা কাঠামোগত সমস্যা প্রথমেই বলি, কারণ এগুলো অন্য অনেক সমস্যাকে বহুগুণে খারাপ করে দেয়:

1. **`.env` তে `CACHE_STORE=database`** — অর্থাৎ প্রতিটি `Cache::remember(...)` এর মান PostgreSQL এর `cache` টেবিলে সিরিয়ালাইজ হয়ে লেখা হচ্ছে। Redis কনফিগার করা থাকলেও ব্যবহার হচ্ছে না। ফলে "cache" আসলে DB-এর উপর আরও লোড বাড়াচ্ছে।
2. **অনেক cache closure হাইড্রেটেড Eloquent মডেল/`JsonResource` সিরিয়ালাইজ করে রাখছে** (`->resolve()` ছাড়া)। ফলে ছোট JSON-এর বদলে পুরো মডেল-গ্রাফ DB cache টেবিলে জমা হচ্ছে।

এই দুইটা একসাথে মিলে হোমপেজের cache-কে "দ্রুত" না বানিয়ে বরং **বড় সিরিয়ালাইজড ব্লব DB-তে লেখা/পড়ার** একটা ভারী কাজে পরিণত করেছে।

### প্রায়োরিটি টেবিল

| # | সমস্যা | ধরন | প্রায়োরিটি |
|---|--------|-----|-----------|
| C1 | DB cache store + `->resolve()` ছাড়া Resource/মডেল ক্যাশ | Performance / Fragility | 🔴 Critical |
| C2 | `homeSectionWiseNews()` cache key ৩টি ভিন্ন কোয়ারিতে শেয়ার + `limit` key-তে নেই | Correctness bug | 🔴 Critical |
| C3 | `newsByCategoryHome`: সব `layout_section_news` id PHP-তে টেনে `whereNotIn` | Scalability | 🔴 Critical |
| C4 | Hot filter/sort কলামে ইনডেক্স নেই (`news`, `news_reads`, pivot) | Indexing | 🔴 Critical |
| M1 | `whereHas('news', ...)` → correlated EXISTS subquery | Query perf | 🟠 Medium |
| M2 | `parentRecursive` — প্রতি ডেপথে আলাদা রাউন্ড-ট্রিপ | Query perf | 🟠 Medium |
| M3 | সব জায়গায় `SELECT *` (column selection নেই) | Over-fetching | 🟠 Medium |
| M4 | `newsByCategorySports` — ক্যাশহীন, অনেক sequential কোয়ারি | Query perf | 🟠 Medium |
| M5 | Most-read: aggregation-এ correlated `whereHas` + `orderByRaw(COUNT)` | Query perf | 🟠 Medium |
| M6 | `SpecialTagPinNewsQuery` — `published`/limit ছাড়া unbounded relation load | Query perf | 🟠 Medium |
| M7 | `newsDetails` `rememberForever` + DB cache → cache টেবিল অসীম বৃদ্ধি | Cache bloat | 🟠 Medium |
| L1 | `LayoutSectionNews::news()` এ hidden `->with('liveNews')` | Coupling / hidden load | 🟢 Low |
| L2 | `HeaderNewsQuery` — ৩টি আলাদা tag কোয়ারি | Micro-opt | 🟢 Low |
| L3 | `RecursiveCategoryQuery` — `childrenRecursive` এর ভেতরে redundant `parentRecursive` | Micro-opt | 🟢 Low |
| L4 | `MostReadNewsQuery` এ প্রতি cache-miss এ `Log::info` | Noise | 🟢 Low |
| L5 | `NewsReadService::read()` কমেন্ট আউট → most-read ডেটা populate হয় না | Correctness (observation) | 🟢 Low |

---

# 🔴 CRITICAL — এখনই ঠিক করা দরকার

---

## C1. DB cache store + `->resolve()` ছাড়া Resource/মডেল ক্যাশ করা

**ফাইল:** `LayoutSectionWiseNewsQuery`, `ThankNewsQuery`, `MarqueNewsQuery`, `LatestNewsQuery`, `MostReadNewsQuery`, `MostReadNewsByCategoryQuery`, `HeaderNewsQuery` — এবং `.env`

**সমস্যা:**

`.env`:
```env
CACHE_STORE=database   # Redis কনফিগার করা, কিন্তু ব্যবহার হচ্ছে না
```

কোয়ারি (উদাহরণ — `LayoutSectionWiseNewsQuery::handle`):
```php
return Cache::remember(CacheKey::homeSectionWiseNews($section_slug), now()->addMinutes(5), function () {
    // ...
    ->get()->map(function ($item) {
        return [
            'position' => $item->position,
            'news' => NewsListResource::make($item->news), // ⬅ resolve() করা হয়নি
        ];
    });
});
```

`Cache::remember` closure যা রিটার্ন করে সেটাই সিরিয়ালাইজ হয়। এখানে `NewsListResource::make()` রিটার্ন হচ্ছে — এটা একটা `JsonResource` অবজেক্ট, যার ভেতরে **সম্পূর্ণ `News` Eloquent মডেল + সব eager-loaded রিলেশন** (`category`, `category.parentRecursive` চেইন, `liveNews`) বসে আছে। অর্থাৎ ক্যাশে যা জমা হচ্ছে তা হলো:

- ~১২টা ফিল্ডের ছোট array নয়, বরং **পুরো মডেল-গ্রাফ** (`ticker`, `sort_description`, `old_hash_key`, `proofreader`, `read_count` সহ সব কলাম)।
- যেহেতু `CACHE_STORE=database`, এই বড় ব্লবটা **PostgreSQL `cache` টেবিলে** লেখা ও প্রতিবার পড়া হচ্ছে। অর্থাৎ "cache hit"-এও DB রাউন্ড-ট্রিপ + বড় ব্লব unserialize (`Model::__wakeup`, cast রি-অ্যাপ্লাই) হচ্ছে।
- **ভঙ্গুর (fragile):** কোনো রিলেশন rename/remove হলে বা `$casts` বদলালে আগের সিরিয়ালাইজড ক্যাশ unserialize-এ ক্র্যাশ করবে।

**কেন Critical:** হোমপেজ (`/home`) একসাথে ১০+ বার `LayoutSectionWiseNewsQuery` + `LatestNewsQuery` + `MostReadNewsQuery` ডাকে। প্রতিটাই DB cache টেবিলে বড় ব্লব লেখে/পড়ে — এটা সবচেয়ে হট পাথ।

**ফিক্স (২ ধাপ):**

1. ক্যাশ করার আগে resource-কে plain array-তে রিজলভ করুন:
```php
->get()->map(fn ($item) => [
    'position' => $item->position,
    'news' => NewsListResource::make($item->news)->resolve(), // ⬅ plain array
]);
```
Collection-এর ক্ষেত্রে:
```php
return NewsListResource::collection($news)->resolve(); // MarqueNews, LatestNews, MostRead...
```
> লক্ষ্য করুন: `CategoryLatestNewsQuery` ও `cachedNewsByCategoryHome` ইতিমধ্যে `->resolve()` করছে — সেটাই সঠিক প্যাটার্ন, সব cache-করা কোয়ারিতে একই নিয়ম আনুন।

2. cache store Redis-এ সরান (Redis তো কনফিগারই করা আছে):
```env
CACHE_STORE=redis
```
plain array + Redis = ছোট payload, দ্রুত (de)serialize, DB-র উপর কোনো চাপ নেই।

---

## C2. একই cache key ৩টি ভিন্ন কোয়ারিতে শেয়ার + `limit` key-তে নেই (Correctness Bug)

**ফাইল:** `LayoutSectionWiseNewsQuery::handle()`, `::handleLivePin()`, `ThankNewsQuery::handle()`

**সমস্যা:** তিনটি আলাদা কোয়ারি, তিনটাই একই cache key ব্যবহার করছে:
```php
Cache::remember(CacheKey::homeSectionWiseNews($section_slug), ...)  // handle()
Cache::remember(CacheKey::homeSectionWiseNews($section_slug), ...)  // handleLivePin()
Cache::remember(CacheKey::homeSectionWiseNews('thanks'), ...)       // ThankNewsQuery
```

`CacheKey::homeSectionWiseNews()` শুধু `$sectionName` নেয়, আর কিছু না। ফলে তিনটা গুরুতর ঝুঁকি:

1. **ভিন্ন কাঠামো (shape) মিশে যাওয়া:** `ThankNewsQuery` রিটার্ন করে `['meta' => ..., 'news' => ...]`, কিন্তু `LayoutSectionWiseNewsQuery::handle()` রিটার্ন করে `[{position, news}, ...]` লিস্ট। slug `'thanks'`-এর জন্য দুইটা কোড পথ চললে যেটা আগে চলবে সেটার আকার ৫ মিনিট ক্যাশ থাকবে — অন্য এন্ডপয়েন্ট ভুল আকারের ডেটা পাবে → **ফ্রন্টএন্ড ক্র্যাশ / ভুল রেন্ডার**।
2. **`handle()` vs `handleLivePin()`:** একই slug-এ একটা `position ASC`, আরেকটা `live_news DESC, position ASC` — যেটা আগে ক্যাশ হবে সেটাই ৫ মিনিট সবাই পাবে, ordering ভুল হবে।
3. **`limit` key-তে নেই:** `handle('lead-news', 5)` আগে চললে ৫টা আইটেম ক্যাশ হয়; এরপর `handle('lead-news', 12)` ডাকলেও TTL-এর মধ্যে সেই পুরনো ৫টাই ফেরত আসবে।

**ফিক্স:** key-তে variant + limit যোগ করুন:
```php
// handle()
$key = CacheKey::homeSectionWiseNews($section_slug) . ':plain:' . ($limit ?? 'all');

// handleLivePin()
$key = CacheKey::homeSectionWiseNews($section_slug) . ':livepin:' . ($limit ?? 'all');

// ThankNewsQuery
$key = CacheKey::homeSectionWiseNews($section_slug) . ':thanks';
```
সবচেয়ে ভালো — shared package-এর `homeSectionWiseNews()`-এ `$variant`/`$limit` প্যারামিটার যোগ করুন, যাতে সব জায়গায় key একই নিয়মে তৈরি হয়।

---

## C3. `newsByCategoryHome` — সব `layout_section_news` id PHP-তে টেনে এনে `whereNotIn`

**ফাইল:** `app/Http/Controllers/Api/NewsController.php` → `cachedNewsByCategoryHome()`

**সমস্যা:**
```php
$layoutSectionNewsIds = LayoutSectionNews::query()
    ->join('layout_sections', ...)
    ->where('layout_sections.is_enable', true)
    ->whereColumn('layout_section_news.position', '<=', 'layout_sections.max_news')
    ->pluck('layout_section_news.news_id')   // ⬅ সব id PHP মেমরিতে
    ->toArray();

$newsQuery = News::query()
    ->whereNotIn('id', $layoutSectionNewsIds) // ⬅ WHERE id NOT IN (হাজার হাজার আইডি)
    ...
```

এখানে দুইটা সমস্যা:
- `whereNotIn` লিস্টটা **সময়ের সাথে অসীম বৃদ্ধি পায়** — যত news layout section-এ যোগ হবে তত বড় হবে। বিশাল `NOT IN (...)` SQL প্ল্যানারের জন্য খারাপ এবং query string দীর্ঘ হয়।
- **Batch endpoint (`newsByCategoryHomeBatch`) এটাকে বহুগুণ খারাপ করে:** ২৪টা slug পর্যন্ত লুপে `cachedNewsByCategoryHome()` ডাকে, আর **প্রতিবার একই `layout_section_news` pluck আবার চালায়** (মেমোাইজ করা নেই)। অর্থাৎ একই ভারী কোয়ারি ২৪ বার।

**ফিক্স:** PHP-তে id না টেনে সাব-কোয়ারি দিয়ে `whereNotExists` / `whereNotIn(subquery)` ব্যবহার করুন — বাদ দেওয়ার লজিক পুরোটা DB-তে থাকবে:
```php
$excludedIds = fn ($q) => $q->from('layout_section_news')
    ->join('layout_sections', 'layout_section_news.layout_section_id', '=', 'layout_sections.id')
    ->where('layout_sections.is_enable', true)
    ->whereColumn('layout_section_news.position', '<=', 'layout_sections.max_news')
    ->select('layout_section_news.news_id');

$newsQuery = News::query()
    ->whereNotIn('id', $excludedIds)
    // ...
```
আর batch পথে `$excludedIds` একবার রিকোয়েস্টে হিসাব করে সব slug-এ পুনঃব্যবহার করুন (বা batch-লেভেলে ছোট ক্যাশ)।

---

## C4. Hot filter/sort কলামে ইনডেক্স নিশ্চিত করা (PostgreSQL)

**পর্যবেক্ষণ:** রিপোর প্রায় প্রতিটি কোয়ারি এই কলামগুলোর উপর ফিল্টার/সর্ট করে: `news.published`, `news.deleted_at (IS NULL)`, `news.category_id`, `news.date`, `news.created_at`; এবং `news_reads.news_id`, `news_reads.category_id`, তারিখ। domain টেবিলের মাইগ্রেশন রিপোতে নেই (ডাটাবেস আলাদাভাবে ম্যানেজড), তাই **প্রথমেই বর্তমান ইনডেক্স যাচাই করুন**:
```sql
SELECT tablename, indexname, indexdef
FROM pg_indexes
WHERE tablename IN ('news','news_reads','layout_section_news',
                    'category_page_layout_news','special_tag_news','news_tag_mappings');
```

**সুপারিশকৃত ইনডেক্স (PostgreSQL partial/composite):**

```sql
-- "live/published, deleted না" ফিল্টার — প্রায় সব কোয়ারিতে ব্যবহৃত হয়
CREATE INDEX CONCURRENTLY news_published_live_idx
    ON news (category_id, date DESC)
    WHERE published = true AND deleted_at IS NULL;

-- ক্যাটাগরি লিস্টিং + latest sort
CREATE INDEX CONCURRENTLY news_cat_created_idx
    ON news (category_id, created_at DESC)
    WHERE deleted_at IS NULL;

-- most-read aggregation
CREATE INDEX CONCURRENTLY news_reads_news_idx     ON news_reads (news_id);
CREATE INDEX CONCURRENTLY news_reads_cat_idx      ON news_reads (category_id);

-- layout/section joins
CREATE INDEX CONCURRENTLY lsn_section_pos_idx
    ON layout_section_news (layout_section_id, position);
```

- `CONCURRENTLY` ব্যবহার করুন যাতে প্রোডাকশনে টেবিল লক না হয়।
- Partial index (`WHERE published = true AND deleted_at IS NULL`) PostgreSQL-এ ছোট ও দ্রুত, কারণ "live" news-ই বেশিরভাগ কোয়ারির টার্গেট।
- ইনডেক্স যোগের আগে/পরে বাস্তব কোয়ারিতে `EXPLAIN (ANALYZE, BUFFERS)` চালিয়ে যাচাই করুন — অনুমান নয়, প্ল্যান দেখে সিদ্ধান্ত নিন।

---

# 🟠 MEDIUM — পরিকল্পনা করে ঠিক করুন

---

## M1. `whereHas('news', ...)` → correlated EXISTS subquery

**ফাইল:** `LayoutSectionWiseNewsQuery::handle()`, `CategoryPageLayoutWiseNewsQuery`, `SpecialSegmentNewsQuery`, `LatestNewsQuery`, `LinkedNewsQuery`, `CategoryNewsPageService::applyGeoFilter()`

**সমস্যা:** `whereHas('news', fn ($q) => $q->where('published', true)->whereNull('deleted_at'))` কম্পাইল হয়ে হয় একটা **correlated `EXISTS (...)` সাব-কোয়ারি**, যা স্ক্যান করা প্রতিটি রো-র জন্য আবার `news` টেবিলে চলে। একই ফাইলে `handleLivePin()` ইতিমধ্যে সঠিক প্যাটার্ন দেখিয়েছে — সরাসরি `join`:

```php
LayoutSectionNews::query()
    ->select('layout_section_news.*')
    ->join('news', 'news.id', '=', 'layout_section_news.news_id')
    ->where('layout_section_id', $layout_section->id)
    ->whereNull('news.deleted_at')
    ->where('news.published', true)
    ->orderBy('layout_section_news.position')
    // ...
```

**বিশেষ কেস — `LatestNewsQuery`:** এখানে একই ফিল্টার **দুইবার** হচ্ছে — একবার `whereHas('news', ...)`, আবার `->join('news', ...)`। `whereHas`-টা বাদ দিয়ে শুধু join + `where('news.published', true)->whereBetween('news.date', ...)` রাখুন (double work দূর হবে)।

**ফিক্স:** যেখানে related টেবিলের কলামে ফিল্টার/সর্ট দরকার সেখানে `whereHas` → `join`-এ বদলান।

---

## M2. `parentRecursive` — ক্যাটাগরি ট্রি প্রতি ডেপথে আলাদা রাউন্ড-ট্রিপ

**ফাইল:** `Category::parentRecursive()`, এবং যেসব এন্ডপয়েন্ট ক্যাশহীন — `relatedNews`, `latestNews`, `searchNews`, `newsByTags`, `newsByAuthor`, `newsByCategorySports`, `LinkedNewsQuery`, `SpecialTagPinNewsQuery`, `CategoryLatestNewsQuery`

**সমস্যা:**
```php
public function parentRecursive() {
    return $this->parent()->with('parentRecursive');
}
```
এটা per-row N+1 নয় (Eloquent একই ডেপথের সব parent একসাথে আনে), কিন্তু **ট্রি ডেপথের সমান sequential রাউন্ড-ট্রিপ**: root → child → grandchild, প্রতি লেভেল আগেরটার ফলাফলের উপর নির্ভরশীল। ৩-লেভেল ট্রিতে প্রতি রিকোয়েস্টে ৩টা অতিরিক্ত DB রাউন্ড-ট্রিপ — আর ক্যাশহীন এন্ডপয়েন্টে এটা প্রতিবার হয়।

**ফিক্স (পছন্দক্রমে):**
- `RecursiveCategoryQuery` ইতিমধ্যে **পুরো ক্যাটাগরি ট্রি ১ দিনের জন্য ক্যাশ** করে। news-এর URL/path বানাতে per-news `parentRecursive` না হেঁটে সেই ক্যাশড ট্রি থেকে ক্যাটাগরি path রিজলভ করুন (একটা in-memory map)।
- দীর্ঘমেয়াদে: `categories`-এ **materialized path** (`path` কলাম, যেমন `sports/cricket`) বা ancestry/closure টেবিল — তাহলে ১টা কলাম পড়েই পুরো path পাওয়া যাবে, কোনো রিকার্সিভ কোয়ারি লাগবে না। PostgreSQL-এ `ltree` টাইপও ব্যবহার করা যায়।

---

## M3. সব জায়গায় `SELECT *` — column selection নেই

**ফাইল:** প্রায় সব কোয়ারি ও eager load

**সমস্যা:** `News`, `Category`, pivot — কোথাও `select()` নেই, ফলে `SELECT *` — যার মধ্যে বড় text কলাম (`shoulder`, `ticker`, `sort_description`) এবং `NewsListResource` কখনো পড়ে না এমন কলামও (`proofreader`, `old_hash_key`, `is_working`, `working_by`, `created_by`, ...) আসছে। C1-এর সাথে মিলে এগুলো DB cache ব্লবকেও বড় করছে।

**ফিক্স:** top-level কোয়ারি ও eager load — দুই জায়গাতেই resource যে কলাম পড়ে সেগুলোয় সীমিত করুন (FK/PK সবসময় রাখুন):
```php
->with([
    'category:id,name,slug,parent_id',
    'category.parentRecursive:id,name,slug,parent_id',
])
->select([
    'id','category_id','slug_key','title','ticker','image','image_caption',
    'shoulder','sort_description','live_news','is_thread',
    'is_visible_shoulder','is_visible_ticker','date','created_at','representative',
]);
```

---

## M4. `newsByCategorySports` — ক্যাশহীন এবং অনেক sequential কোয়ারি

**ফাইল:** `NewsController::newsByCategorySports()`

**সমস্যা:** একটা রিকোয়েস্টে অনেকগুলো আলাদা কোয়ারি চলছে:
- `football` ও `cricket` ক্যাটাগরি — আলাদা দুইটা `->first()`
- `categoryAllChildrenIdsQuery->handle()` **দুইবার** (প্রতিটা রিকার্সিভ children লোড করে)
- cricket + football news — আলাদা কোয়ারি
- অন্য category page (`newsByCategory`) ৫ মিনিট ক্যাশ করা হলেও **এই এন্ডপয়েন্ট মোটেই ক্যাশ করা হয়নি** — প্রতিবার সব কোয়ারি নতুন করে চলে।

**ফিক্স:**
- পুরো পেলোড ৫ মিনিট `Cache::remember` করুন (অন্য category page-এর মতো)।
- football/cricket একসাথে আনুন: `Category::whereIn('slug', ['football','cricket'])->get()`।
- child-id গুলো একবার হিসাব করে পুনঃব্যবহার করুন।

---

## M5. Most-read — aggregation-এ correlated `whereHas` + `orderByRaw(COUNT)`

**ফাইল:** `MostReadNewsQuery`, `MostReadNewsByCategoryQuery`

**সমস্যা:**
```php
NewsRead::whereHas('news', function ($nQ) {
        $nQ->where('published', true)->whereBetween('date', [...]);
    })
    ->select('news_id')->groupBy('news_id')
    ->orderByRaw('COUNT(*) DESC')->limit(15)->pluck('news_id');
```
সম্ভাব্য বিশাল `news_reads` টেবিলে GROUP BY + প্রতিটা রো-র জন্য correlated `EXISTS` (news-এর published/date চেক) — ভারী।

**ফিক্স:**
```php
NewsRead::query()
    ->join('news', 'news.id', '=', 'news_reads.news_id')
    ->where('news.published', true)
    ->whereBetween('news.date', [PortalDateHelper::subDay(), PortalDateHelper::now()])
    ->whereNull('news.deleted_at')
    ->groupBy('news_reads.news_id')
    ->orderByRaw('COUNT(*) DESC')
    ->limit(15)
    ->pluck('news_reads.news_id');
```
`news_reads(news_id)` ও `news_reads(category_id)` ইনডেক্স নিশ্চিত করুন (C4)। খুব বড় হলে ঘণ্টাভিত্তিক pre-aggregated summary টেবিল/মেটেরিয়ালাইজড ভিউ বিবেচনা করুন।

---

## M6. `SpecialTagPinNewsQuery` — `published`/limit ছাড়া unbounded relation load

**ফাইল:** `SpecialTagPinNewsQuery`

**সমস্যা:**
```php
SpecialTag::with('news.category.parentRecursive')
    ->whereIn('slug', ['fact-check','advice','analysis','opinion'])
    ->get();
```
এই ৪টা ট্যাগের সাথে যুক্ত **প্রতিটা news** লোড হচ্ছে — `published` ফিল্টার নেই, `limit` নেই, ক্যাশ নেই। ট্যাগে হাজারো news থাকলে পুরোটা মেমরিতে আসবে (unpublished সহ)।

**ফিক্স:** nested relation-এ `published` ফিল্টার + প্রতি ট্যাগে limit + ৫ মিনিট ক্যাশ:
```php
SpecialTag::query()
    ->whereIn('slug', ['fact-check','advice','analysis','opinion'])
    ->with(['news' => fn ($q) => $q->where('published', true)
        ->latest('date')->limit(5)
        ->with('category.parentRecursive')])
    ->get();
```

---

## M7. `newsDetails` — `rememberForever` + DB cache → cache টেবিল অসীম বৃদ্ধি

**ফাইল:** `NewsController::newsDetails()`

**সমস্যা:**
```php
$news = Cache::rememberForever(CacheKey::newsDetails($slug), fn () => ...);
```
`CACHE_STORE=database` হওয়ায় প্রতিটা ভিজিট করা slug **স্থায়ীভাবে** `cache` টেবিলে একটা রো তৈরি করে। `NewsObserver` update/delete-এ `Cache::forget` করে ঠিকই, কিন্তু কখনো আপডেট না হওয়া পুরনো news জমতে থাকে → টেবিল ফুলে ওঠে।

এছাড়া outer response-এর `latest_news`, `most_read_news`, `linked_news`, `news_timelines` — এগুলো `$news` ক্যাশড হলেও **প্রতি রিকোয়েস্টে নতুন করে চলে** (এগুলো ক্যাশের বাইরে)।

**ফিক্স:**
- cache store Redis-এ নিন (C1) — memory eviction নিজে সামলাবে।
- অথবা DB-তেই রাখলে বড় TTL দিন (`now()->addDay()`), forever নয়।
- `linked_news`/`news_timelines` per-news হওয়ায় চাইলে `$news` এর সাথে একই ক্যাশ ব্লকে আনা যায়।

---

# 🟢 LOW — সুযোগ পেলে ঠিক করুন

---

## L1. `LayoutSectionNews::news()` এ hidden `->with('liveNews')`

**ফাইল:** `app/Models/LayoutSectionNews.php`
```php
public function news() {
    return $this->belongsTo(News::class, 'news_id', 'id')->with('liveNews'); // ⬅ hidden
}
```
রিলেশন ডেফিনিশনে `->with()` বেক করা থাকলে `LayoutSectionNews::news` যেখানেই ব্যবহার হোক (লেজি লোডেও) সবসময় বাড়তি `liveNews` কোয়ারি চলে — দরকার হোক বা না হোক। eager-loading আচরণ লুকিয়ে যায়।

**ফিক্স:** রিলেশন থেকে `->with('liveNews')` সরান, কোয়ারিতে দরকার হলে explicit করুন: `->with(['news.category.parentRecursive', 'news.liveNews'])`। `NewsListResource` শুধু `whenLoaded('liveNews', ...)` দিয়ে পড়ে, তাই নিরাপদ।

## L2. `HeaderNewsQuery` — ৩টি আলাদা tag কোয়ারি

`getTagNews()` তিনবার আলাদা `whereHas('tags')->latest()->first()` চালায় (স্পেশাল-১/২/৩)। ৫ মিনিট ক্যাশড, তাই প্রভাব কম — তবে চাইলে একটা কোয়ারিতে ৩ ট্যাগের top news এনে PHP-তে ভাগ করা যায়।

## L3. `RecursiveCategoryQuery` — redundant `parentRecursive`

`childrenRecursive` রিলেশন ভেতরে `parentRecursive`-ও eager load করে, অথচ এখানে ট্রি টপ-ডাউন হাঁটা হচ্ছে — parent দরকার নেই। ১ দিন ক্যাশড, তাই কম গুরুত্বপূর্ণ; তবে বাদ দিলে ক্যাশ-মিস দ্রুত হবে।

## L4. `MostReadNewsQuery` — প্রতি cache-miss এ `Log::info`

```php
Log::info("Most read news IDs for details: " . $mostReadIds->implode(', '));
```
প্রোডাকশনে অপ্রয়োজনীয় লগ নয়েজ — সরিয়ে দিন বা `debug` লেভেলে নিন।

## L5. `NewsReadService::read()` কমেন্ট আউট (Correctness observation)

`newsDetails()`-এ `// $newsReadService->read($news);` কমেন্ট করা। ফলে detail পেজ থেকে `news_reads` populate হয় না — অর্থাৎ **most-read কোয়ারিগুলো খালি/পুরনো ডেটা** দিতে পারে। পারফরম্যান্স বাগ নয়, কিন্তু ফিচারটা কাজ করছে কি না যাচাই করুন। চালু করলে C4-এর `news_reads` ইনডেক্স এবং write লোড (`insertOrIgnore` per view) মাথায় রাখুন — high-traffic হলে queue/batch এ নেওয়া ভালো।

---

# পরিশিষ্ট A — সাজেস্টেড ফিক্স অর্ডার

1. **C1** (DB cache + resolve) — সবচেয়ে বেশি ROI, হট পাথ।
2. **C2** (cache key collision) — correctness, ডেটা ভুল হওয়া বন্ধ।
3. **C4** (ইনডেক্স) — `EXPLAIN ANALYZE` দিয়ে যাচাই করে যোগ করুন।
4. **C3** (whereNotIn subquery) → **M1** (whereHas→join) → **M5** (most-read join)।
5. **M3** (column select) → **M4/M6/M7** → **M2** (category path caching)।
6. **L1–L5** cleanup।

# পরিশিষ্ট B — যাচাই (QA) চেকলিস্ট

- প্রতিটি ফিক্সের আগে/পরে বাস্তব কোয়ারিতে `EXPLAIN (ANALYZE, BUFFERS)` — প্ল্যান বদল ও টাইমিং তুলনা করুন।
- `.env` তে `SLOW_QUERY_LOG=true` ও `SlowQueryLoggerService` (>100ms) আছে — ফিক্সের পর `storage/logs`-এর query চ্যানেলে slow query কমেছে কি না দেখুন।
- Telescope (`TELESCOPE_ENABLED=true`) দিয়ে প্রতি এন্ডপয়েন্টে কোয়ারি সংখ্যা (N+1) মনিটর করুন — বিশেষত `/home`, `/news-by-category-sports`, `/news-details/{slug}`।
- cache resolve পরিবর্তনের পর পুরনো ক্যাশ `php artisan cache:clear` — নাহলে পুরনো সিরিয়ালাইজড মডেল unserialize-এ সমস্যা করতে পারে।
- API response আকার আগের সাথে বিট-বাই-বিট মেলান (resolve/select পরিবর্তন যেন JSON বদলে না দেয়)।
- fix গুলো Pest feature test দিয়ে কভার করুন (`php artisan test --compact`)।

> **নোট:** domain টেবিলের মাইগ্রেশন এই রিপোতে নেই (DB আলাদাভাবে ম্যানেজড), তাই C4-এর ইনডেক্স সুপারিশ প্রয়োগের আগে অবশ্যই বর্তমান ইনডেক্স (`pg_indexes`) যাচাই করুন — যাতে ডুপ্লিকেট না হয়।
