<?php

namespace App\Http\Requests;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EpaperPublicationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => UtilsHelper::SlugMaker($this->string('slug')->value()),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('epaper_publications', 'slug')->whereNull('deleted_at')],
        ];
    }
}
