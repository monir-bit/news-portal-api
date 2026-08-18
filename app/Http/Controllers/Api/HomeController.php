<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\UtilsHelper;
use App\Applications\Queries\Api\LatestNewsQuery;
use App\Applications\Queries\Api\LayoutSectionWiseNewsQuery;
use App\Applications\Queries\Api\MostReadNewsQuery;
use App\Applications\Queries\Api\RamadanScheduleQuery;
use App\Applications\Queries\Api\SpecialSegmentNewsQuery;
use App\Enums\LayoutSectionEnum;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{

    public function __construct(protected LayoutSectionWiseNewsQuery $layoutSectionWiseNewsQuery)
    {}

    public function homeInitial(
        MostReadNewsQuery $mostReadNewsQuery,
        LatestNewsQuery $latestNewsQuery,
        SpecialSegmentNewsQuery $specialSegmentNewsQuery,
        RamadanScheduleQuery $ramadanScheduleQuery,
    ) {
//        return Cache::remember('api:home_initial:v1', 60, function () {
            $editorsPickNews = UtilsHelper::IsEnglishVersion() ? LayoutSectionEnum::EditorsPick->value : LayoutSectionEnum::FeatureBox->value;
            return [
                'trending_video_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::TrendingVideoNews->value, 4),
                'lead_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::LeadNews->value, 5),
                'world_cup_lead' => $this->layoutSectionWiseNewsQuery->handleLivePin(LayoutSectionEnum::WorldCupLead->value, 5),
                'pin_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::PinNews->value, 4),
                'sub_lead_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::SubLeadNews->value, 12),
                'editors_pick' => $this->layoutSectionWiseNewsQuery->handle($editorsPickNews, 1),
                'latest_news' => $latestNewsQuery->handle(),
                'most_read_news' => $mostReadNewsQuery->handle(),
                'special_segment_news' => $specialSegmentNewsQuery->handle(),
//                'ramadan_schedules' => $ramadanScheduleQuery->handle(),
                'opinion' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::Opinion->value, 1),
                'advice' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::Advice->value, 1),
                'fact_check' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::FactCheck->value, 1),
                'analysis' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::Analysis->value, 1),
            ];
//        });
    }
}
