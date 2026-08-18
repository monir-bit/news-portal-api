<?php

namespace App\Http\Requests;

use App\Models\EpaperEditionPage;
use App\Models\EpaperRegion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EpaperRegionLinkPairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'head_region_id' => ['required', 'integer', 'min:1'],
            'tail_region_id' => ['required', 'integer', 'min:1', 'different:head_region_id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $editionId = (int) $this->route('id');
            $headId = (int) $this->input('head_region_id');
            $tailId = (int) $this->input('tail_region_id');

            $pageIds = EpaperEditionPage::query()
                ->where('epaper_edition_id', $editionId)
                ->pluck('id')
                ->all();

            if ($pageIds === []) {
                $validator->errors()->add('head_region_id', 'This edition has no pages yet.');

                return;
            }

            $validRegion = fn (int $rid) => EpaperRegion::query()
                ->whereKey($rid)
                ->whereIn('epaper_edition_page_id', $pageIds)
                ->exists();

            if (! $validRegion($headId)) {
                $validator->errors()->add('head_region_id', 'Head region is not part of this edition.');
            }
            if (! $validRegion($tailId)) {
                $validator->errors()->add('tail_region_id', 'Tail region is not part of this edition.');
            }
        });
    }
}
