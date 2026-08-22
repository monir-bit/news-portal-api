<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WorldCupQuestionSubmitRequest;
use App\Http\Resources\Api\WorldCupQuestionResource;
use App\Models\Participant;
use App\Models\WorldCupQuestion;
use App\Models\WorldCupQuestionOption;
use App\Models\WorldCupQuestionParticipation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorldCupQuestionController extends Controller
{
    /**
     * All questions (visible). Submission eligibility is per-question.
     *
     * @return array<int, array<string, mixed>>
     */
    public function index(Request $request): array
    {
        $phone = (string) $request->query('phone', '');
        $answeredIds = $this->answeredQuestionIds($phone);

        $questions = WorldCupQuestion::query()
            ->with('options')
            ->ordered()
            ->get();

        return $questions
            ->map(fn (WorldCupQuestion $question) => (
                new WorldCupQuestionResource($question, $answeredIds)
            )->resolve())
            ->values()
            ->all();
    }

    public function progress(Request $request): JsonResponse
    {
        $phone = (string) $request->query('phone', '');
        if ($phone === '') {
            return response()->json(['message' => 'Phone is required.'], 422);
        }

        $participant = Participant::query()->where('phone', $phone)->first();
        if (! $participant) {
            return response()->json([
                'answered_question_ids' => [],
            ]);
        }

        return response()->json([
            'answered_question_ids' => WorldCupQuestionParticipation::query()
                ->where('participant_id', $participant->id)
                ->pluck('world_cup_question_id')
                ->all(),
        ]);
    }

    public function submit(WorldCupQuestionSubmitRequest $request, int $id): JsonResponse
    {
        $question = WorldCupQuestion::query()
            ->with('options')
            ->findOrFail($id);

        if (! $question->isSubmittableNow()) {
            return response()->json([
                'message' => 'This question is not open for submission.',
                'not_submittable' => true,
            ], 422);
        }

        $data = $request->validated();

        $option = WorldCupQuestionOption::query()
            ->where('world_cup_question_id', $question->id)
            ->whereKey($data['question_option_id'])
            ->firstOrFail();

        return DB::transaction(function () use ($request, $question, $option, $data) {
            $participant = Participant::query()->where('phone', $data['phone'])->first();

            if ($participant) {
                if (! empty($data['name'])) {
                    $participant->update(['name' => trim((string) $data['name'])]);
                }
            } else {
                $participant = Participant::create([
                    'name' => trim((string) $data['name']),
                    'phone' => $data['phone'],
                ]);
            }

            $existing = WorldCupQuestionParticipation::query()
                ->where('world_cup_question_id', $question->id)
                ->where('participant_id', $participant->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'You have already answered this question.',
                    'already_answered' => true,
                    'is_correct' => $existing->is_correct,
                ], 422);
            }

            $isCorrect = (bool) $option->is_correct;

            WorldCupQuestionParticipation::create([
                'world_cup_question_id' => $question->id,
                'participant_id' => $participant->id,
                'world_cup_question_option_id' => $option->id,
                'is_correct' => $isCorrect,
                'submitted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Answer recorded.',
                'is_correct' => $isCorrect,
                'question_id' => $question->id,
            ], 201);
        });
    }

    /**
     * @return array<int, int>
     */
    private function answeredQuestionIds(string $phone): array
    {
        if ($phone === '') {
            return [];
        }

        $participant = Participant::query()->where('phone', $phone)->first();
        if (! $participant) {
            return [];
        }

        return WorldCupQuestionParticipation::query()
            ->where('participant_id', $participant->id)
            ->pluck('world_cup_question_id')
            ->all();
    }
}
