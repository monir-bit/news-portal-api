<?php

namespace App\Services\Api;

use App\Applications\Helpers\PortalDateHelper;
use App\Models\News;
use App\Models\NewsRead;
use Illuminate\Support\Facades\Log;

class NewsReadService
{
    public function read(News $news)
    {
        try {
            $visitorId = request()->header('X-Visitor-ID');

            if (!$visitorId) {
                return;
            }

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
                ]
            ]);

           /* Log::info('News read tracked', [
                'news_id' => $news->id,
                'visitor_id' => $visitorId,
                'read_date' => $readDate,
            ]);*/

        } catch (\Exception $exception) {
            Log::error('News read tracking failed', [
                'news_id' => $news->id,
                'visitor_id' => $visitorId ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
