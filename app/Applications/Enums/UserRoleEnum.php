<?php

namespace App\Applications\Enums;

enum UserRoleEnum: string
{
    case ADMIN = 'admin';
    case SUB_EDITOR = 'sub-editor';
    case PRINT = 'print';
    case SPORTS = 'sports';
    case FEATURE = 'feature';
    case FEATURE_SENIOR = 'feature-senior';
    case OPINION = 'opinion';
    case SHIFT_INCHARGE = 'shift-incharge';
    case PHOTO = 'photo';
    case VIDEO = 'video';
    case BUSINESS = 'business';
    case EDITING_ASSISTANT = 'editing-assistant';
    case CHATTAGRAM = 'chattagram';
    case EPAPER = 'epaper';
    case PRINT_SENIOR = 'print-senior';
    case CAMPAIGN = 'campaign';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::SUB_EDITOR => 'Sub-editor',
            self::PRINT => 'Print',
            self::SPORTS => 'Sports',
            self::FEATURE => 'Feature',
            self::OPINION => 'Opinion',
            self::SHIFT_INCHARGE => 'Shift-in-charge',
            self::PHOTO => 'Photo',
            self::VIDEO => 'Video',
            self::BUSINESS => 'Business',
            self::EDITING_ASSISTANT => 'Editing Assistant',
            self::CHATTAGRAM => 'Chattagram',
            self::EPAPER => 'ePaper',
            self::PRINT_SENIOR => 'Print Senior',
            self::CAMPAIGN => 'Campaign',
            self::FEATURE_SENIOR => 'Feature Senior',
        };
    }
}
