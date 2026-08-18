<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldCupQuestionResource extends JsonResource
{
    /**
     * @param  array<int, int>|null  $answeredQuestionIds
     */
    public function __construct($resource, protected ?array $answeredQuestionIds = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $answeredIds = $this->answeredQuestionIds ?? [];

        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'image_url' => $this->image,
            'duration_seconds' => (int) $this->duration_seconds,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'start_date_time' => $this->start_date_time?->toIso8601String(),
            'end_date_time' => $this->end_date_time?->toIso8601String(),
            'submission_status' => $this->submissionStatus(),
            'is_submittable' => $this->isSubmittableNow(),
            'has_answered' => in_array($this->id, $answeredIds, true),
            'options' => WorldCupQuestionOptionResource::collection(
                $this->whenLoaded('options')
            ),
        ];
    }
}
