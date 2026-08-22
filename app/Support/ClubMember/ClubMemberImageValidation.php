<?php

namespace App\Support\ClubMember;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared image validation for the club member registration forms (Gold/Kids/Career).
 *
 * ASSUMPTION: the old app (`GoldClubMemberApiStoreRequest`, `KidsClubMemberApiStoreRequest`,
 * `CareerClubMemberApiStoreRequest`) references `App\Support\ClubMember\ClubMemberImageValidation`,
 * but no such class exists anywhere in the old repository (not in `app/`, not in `vendor/`).
 * This is a from-scratch reimplementation. The rule set below was reverse-engineered from the
 * validation messages defined alongside each call site (`image.image`, `image.mimes` listing
 * "jpeg, jpg, png, gif, webp, bmp", and `image.max` stating "5 MB"), so the max size and mime
 * list are not guesses — only the exact rule object composition is new.
 */
class ClubMemberImageValidation
{
    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp,bmp', 'max:5120'];
    }

    /**
     * Multipart forms submit `image` as an empty string when no file is chosen.
     * Strip that before validation so the `nullable`/`image` rules don't reject it.
     */
    public static function stripInvalidImageUpload(FormRequest $request): void
    {
        if ($request->has('image') && ! $request->hasFile('image')) {
            $request->request->remove('image');
        }
    }
}
