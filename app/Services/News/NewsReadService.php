<?php

namespace App\Services\News;

use App\Models\News;
use App\Models\NewsRead;
use App\Support\PortalDateHelper;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewsReadService
{
    public function read(News $news, $visitor_id): void
    {

        if (! $visitor_id) {
            return;
        }

        try {
            $readDate = PortalDateHelper::todayDateString();
            NewsRead::query()->insertOrIgnore([
                [
                    'news_id' => $news->id,
                    'category_id' => $news->category_id,
                    'read_date' => $readDate,
                    'visitor_id' => $visitor_id,
                    'read_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('News read tracking failed', [
                'news_id' => $news->id,
                'visitor_id' => $visitor_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
