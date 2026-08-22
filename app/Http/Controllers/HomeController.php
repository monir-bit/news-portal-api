<?php

namespace App\Http\Controllers;

use App\Enums\LayoutSectionEnum;
use App\Services\News\LatestNewsQuery;
use App\Services\News\LayoutSectionWiseNewsQuery;
use App\Services\News\MostReadNewsQuery;
use App\Services\News\SpecialSegmentNewsQuery;

class HomeController extends Controller
{
    public function __construct(
        protected LayoutSectionWiseNewsQuery $layoutSectionWiseNewsQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function homeInitial(
        MostReadNewsQuery $mostReadNewsQuery,
        LatestNewsQuery $latestNewsQuery,
        SpecialSegmentNewsQuery $specialSegmentNewsQuery,
    ): array {
        $editorsPickSection = app()->getLocale() === 'en'
            ? LayoutSectionEnum::EditorsPick->value
            : LayoutSectionEnum::FeatureBox->value;

        return [
            'trending_video_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::TrendingVideoNews->value, 4),
            'lead_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::LeadNews->value, 5),
            'world_cup_lead' => $this->layoutSectionWiseNewsQuery->handleLivePin(LayoutSectionEnum::WorldCupLead->value, 5),
            'pin_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::PinNews->value, 4),
            'sub_lead_news' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::SubLeadNews->value, 12),
            'editors_pick' => $this->layoutSectionWiseNewsQuery->handle($editorsPickSection, 1),
            'latest_news' => $latestNewsQuery->handle(),
            'most_read_news' => $mostReadNewsQuery->handle(),
            'special_segment_news' => $specialSegmentNewsQuery->handle(),
            'opinion' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::Opinion->value, 1),
            'advice' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::Advice->value, 1),
            'fact_check' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::FactCheck->value, 1),
            'analysis' => $this->layoutSectionWiseNewsQuery->handle(LayoutSectionEnum::Analysis->value, 1),
        ];
    }
}
