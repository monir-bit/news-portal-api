<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class NewsTimelineUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_publish' => $this->boolean('is_publish'),
            'sync_live_title' => $this->boolean('sync_live_title'),
            'sync_live_image' => $this->boolean('sync_live_image'),
            'sync_live_details' => $this->boolean('sync_live_details'),
        ]);

        $caption = $this->input('image_caption');
        $this->merge([
            'image_caption' => is_string($caption) && trim($caption) !== '' ? trim($caption) : null,
        ]);

        $rawDate = $this->input('date');
        if (is_string($rawDate) && trim($rawDate) !== '') {
            try {
                $this->merge([
                    'date' => Carbon::parse($rawDate)->format('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                //
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:500',
            'details' => 'required|string',
            'date' => 'required|date',
            'image' => 'nullable|image|max:5120',
            'image_caption' => 'nullable|string|max:500',
            'is_publish' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'sync_live_title' => 'boolean',
            'sync_live_image' => 'boolean',
            'sync_live_details' => 'boolean',
        ];
    }
}

