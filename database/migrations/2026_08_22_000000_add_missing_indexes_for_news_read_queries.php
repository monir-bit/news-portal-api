<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL does not auto-create an index on a foreign key column (unlike MySQL),
 * so a plain `->constrained()`/`->foreign()` column with no explicit `->index()`
 * has no index to serve direct lookups on it. These two columns are queried
 * directly on every request that hits their respective endpoints, and had no
 * index (nor a composite/unique constraint covering them as a leading column)
 * in the source schema:
 *
 * - `linked_news.main_news_id`: `LinkedNewsQuery` filters on it for every
 *   `/news-details/{slug}` request (the "related articles" rail).
 * - `web_stories.news_id`: `WebStoryController::sportsWebHistory` filters
 *   `News` via `whereHas('webStory', ...)`, which correlates on this column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('linked_news')) {
            Schema::table('linked_news', function (Blueprint $table) {
                $table->index('main_news_id');
            });
        }

        if (Schema::hasTable('web_stories')) {
            Schema::table('web_stories', function (Blueprint $table) {
                $table->index('news_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('linked_news', function (Blueprint $table) {
            $table->dropIndex(['main_news_id']);
        });

        Schema::table('web_stories', function (Blueprint $table) {
            $table->dropIndex(['news_id']);
        });
    }
};
