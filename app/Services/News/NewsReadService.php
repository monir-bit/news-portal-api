<?php

namespace App\Services\News;

use App\Models\News;
use App\Models\NewsRead;
use App\Support\PortalDateHelper;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewsReadService
{
    public function read(News $news): void
    {
        $visitorId = request()->header('X-Visitor-ID');

        if (! $visitorId) {
            return;
        }

        try {
            $readDate = PortalDateHelper::todayDateString();

            NewsRead::query()->insertOrIgnore([
                [
                    'news_id' => $news->id,
                    'category_id' => $news->category_id,
                    'read_date' => $readDate,
                    'visitor_id' => $visitorId,
                    'read_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('News read tracking failed', [
                'news_id' => $news->id,
                'visitor_id' => $visitorId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
