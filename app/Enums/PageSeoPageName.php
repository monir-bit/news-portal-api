<?php

namespace App\Enums;

/**
 * Fixed portal routes / static pages that can have SEO rows (one row per value in DB).
 *
 * home, latest, search, epaper, about us, terms of service, contact us, privacy policy, team
 */
enum PageSeoPageName: string
{
    case HOME = 'home';
    case LATEST = 'latest';
    case SEARCH = 'search';
    case EPAPER = 'epaper';
    case ABOUT_US = 'about_us';
    case TERMS_OF_SERVICE = 'terms_of_service';
    case CONTACT_US = 'contact_us';
    case PRIVACY_POLICY = 'privacy_policy';
    case TEAM = 'team';
    case CAMPAIGN = 'campaign';
    case CLUB = 'club';
    case CLUB_CAREER = 'club-career';
    case CLUB_GOLD = 'club-gold';
    case CLUB_KIDS = 'club-kids';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::HOME => 'Home',
            self::LATEST => 'Latest',
            self::SEARCH => 'Search',
            self::EPAPER => 'E-paper',
            self::ABOUT_US => 'About us',
            self::TERMS_OF_SERVICE => 'Terms of service',
            self::CONTACT_US => 'Contact us',
            self::PRIVACY_POLICY => 'Privacy policy',
            self::TEAM => 'Team',
            self::CAMPAIGN => 'Campaign',
            self::CLUB => 'Club',
            self::CLUB_CAREER => 'Club Career',
            self::CLUB_GOLD => 'Club Gold',
            self::CLUB_KIDS => 'Club Kids',
        };
    }
}
