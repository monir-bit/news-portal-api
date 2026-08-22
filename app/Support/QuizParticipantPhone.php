<?php

namespace App\Support;

/**
 * Normalizes participant phone numbers submitted to quiz/question forms.
 *
 * ASSUMPTION: the old app (`app/Http/Requests/Api/QuestionAnswerStoreRequest.php` and
 * `QuestionParticipationRequest.php`) references `App\Support\QuizParticipantPhone::normalize()`,
 * but no such class exists anywhere in the old repository (not in `app/`, not in `vendor/`).
 * This is a from-scratch reimplementation covering the same call site. It normalizes Bangladeshi
 * mobile numbers to a canonical `880XXXXXXXXXX` (country-code, no leading `+` or `0`) form so the
 * same participant matches consistently regardless of how they typed their number, matching the
 * `Participant::firstOrCreate(['phone' => ...])` lookup-by-phone behavior in the old controller.
 */
class QuizParticipantPhone
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '880'.substr($digits, 1);
        }

        return $digits;
    }
}
