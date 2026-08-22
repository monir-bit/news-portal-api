<?php

namespace App\Enums;

enum LayoutSectionEnum: string
{
    case TrendingVideoNews = 'trending-video-news';
    case LeadNews = 'lead-news';
    case WorldCupLead = 'world-cup-lead';
    case PinNews = 'pin-news';
    case SubLeadNews = 'sub-lead-news';
    case FeatureBox = 'feature-box';
    case Opinion = 'opinion';
    case Advice = 'advice';
    case FactCheck = 'fact-check';
    case Analysis = 'analysis';
    case EditorsPick = 'editors-pick';
}
