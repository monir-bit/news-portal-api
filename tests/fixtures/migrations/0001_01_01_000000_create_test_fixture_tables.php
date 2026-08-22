<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This app has no base-schema migrations of its own (it reads from the
 * database news-portal-admin owns and migrates in production). These
 * minimal fixture tables let isolated tests exercise the handful of models
 * this app's test suite actually touches, without depending on a sibling
 * app's migration files by path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('linked_news')) {
            Schema::create('linked_news', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('main_news_id');
                $table->bigInteger('linked_news_id');
                $table->integer('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('web_stories')) {
            Schema::create('web_stories', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('news_id')->nullable();
                $table->string('hash_key');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('web_stories');
        Schema::dropIfExists('linked_news');
        Schema::dropIfExists('divisions');
    }
};
