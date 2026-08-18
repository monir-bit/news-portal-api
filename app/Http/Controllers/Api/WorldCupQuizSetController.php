<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WorldCupQuizAnswerStoreRequest;
use App\Http\Requests\Api\WorldCupQuizStartRequest;
use App\Http\Resources\Api\WorldCupQuizSetListItemResource;
use App\Http\Resources\Api\WorldCupQuizSetResource;
use App\Models\Participant;
use App\Models\WorldCupQuiz;
use App\Models\WorldCupQuizAnswer;
use App\Models\WorldCupQuizOption;
use App\Models\WorldCupQuizParticipation;
use App\Models\WorldCupQuizSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorldCupQuizSetController extends Controller
{
    /**
     * Active quiz sets that have at least one active question.
     *
     * @return array<int, array<string, mixed>>
     */
    public function index()
    {
        $sets = WorldCupQuizSet::query()
            ->activeNow()
            ->whereHas('activeQuizzes')
            ->withCount('activeQuizzes as quizzes_count')
            ->orderByDesc('id')
            ->get();

        return WorldCupQuizSetListItemResource::collection($sets)->resolve();
    }

    public function show(string $slug)
    {
        $set = WorldCupQuizSet::query()
            ->activeNow()
            ->where('slug', $slug)
            ->with(['activeQuizzes.options'])
            ->firstOrFail();

        if ($set->activeQuizzes->isEmpty()) {
            abort(404);
        }

        return WorldCupQuizSetResource::make($set)->resolve();
    }

    public function progress(Request $request, string $slug)
    {
        $set = WorldCupQuizSet::query()
            ->activeNow()
            ->where('slug', $slug)
            ->firstOrFail();

        $phone = (string) $request->query('phone', '');
        if ($phone === '') {
            return response()->json(['message' => 'Phone is required.'], 422);
        }

        $participant = Participant::query()->where('phone', $phone)->first();
        if (! $participant) {
            return response()->json([
                'has_participation' => false,
                'completed' => false,
                'answered_quiz_ids' => [],
            ]);
        }

        $participation = WorldCupQuizParticipation::query()
            ->where('world_cup_quiz_set_id', $set->id)
            ->where('participant_id', $participant->id)
            ->first();

        if (! $participation) {
            return response()->json([
                'has_participation' => false,
                'completed' => false,
                'answered_quiz_ids' => [],
            ]);
        }

        $answeredIds = $participation->answers()->pluck('world_cup_quiz_id')->all();

        return response()->json([
            'has_participation' => true,
            'completed' => $participation->isCompleted(),
            'score' => $participation->score,
            'total_questions' => $participation->total_questions,
            'answered_quiz_ids' => $answeredIds,
        ]);
    }

    public function start(WorldCupQuizStartRequest $request, string $slug)
    {
        $set = WorldCupQuizSet::query()
            ->activeNow()
            ->where('slug', $slug)
            ->with(['activeQuizzes'])
            ->firstOrFail();

        $quizzes = $set->activeQuizzes;
        if ($quizzes->isEmpty()) {
            abort(404);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($set, $quizzes, $data, $request) {
            $participant = Participant::query()->where('phone', $data['phone'])->first();

            if ($participant) {
                $participant->update([
                    'name' => trim($data['name']),
                    'date_of_birth' => $data['date_of_birth'] ?? $participant->date_of_birth,
                ]);
            } else {
                $participant = Participant::create([
                    'name' => trim($data['name']),
                    'phone' => $data['phone'],
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                ]);
            }

            $existing = WorldCupQuizParticipation::query()
                ->where('world_cup_quiz_set_id', $set->id)
                ->where('participant_id', $participant->id)
                ->first();

            if ($existing?->isCompleted()) {
                return response()->json([
                    'message' => 'You have already completed this quiz set.',
                    'already_completed' => true,
                    'score' => $existing->score,
                    'total_questions' => $existing->total_questions,
                ], 422);
            }

            $participation = $existing ?? WorldCupQuizParticipation::create([
                'world_cup_quiz_set_id' => $set->id,
                'participant_id' => $participant->id,
                'total_questions' => $quizzes->count(),
                'started_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $answeredIds = $participation->answers()->pluck('world_cup_quiz_id')->all();

            return response()->json([
                'message' => 'Quiz started.',
                'participation_id' => $participation->id,
                'total_questions' => $participation->total_questions,
                'answered_quiz_ids' => $answeredIds,
                'completed' => $participation->isCompleted(),
                'score' => $participation->score,
            ], 201);
        });
    }

    public function submitAnswer(WorldCupQuizAnswerStoreRequest $request, string $slug)
    {
        $set = WorldCupQuizSet::query()
            ->activeNow()
            ->where('slug', $slug)
            ->with(['activeQuizzes'])
            ->firstOrFail();

        $data = $request->validated();
        $timedOut = (bool) ($data['timed_out'] ?? false);

        $participant = Participant::query()
            ->where('phone', $data['phone'])
            ->firstOrFail();

        $participation = WorldCupQuizParticipation::query()
            ->where('world_cup_quiz_set_id', $set->id)
            ->where('participant_id', $participant->id)
            ->firstOrFail();

        if ($participation->isCompleted()) {
            return response()->json([
                'message' => 'Quiz already completed.',
                'already_completed' => true,
                'score' => $participation->score,
                'total_questions' => $participation->total_questions,
            ], 422);
        }

        $quiz = WorldCupQuiz::query()
            ->where('world_cup_quiz_set_id', $set->id)
            ->where('is_active', true)
            ->whereKey($data['quiz_id'])
            ->firstOrFail();

        $alreadyAnswered = WorldCupQuizAnswer::query()
            ->where('world_cup_quiz_participation_id', $participation->id)
            ->where('world_cup_quiz_id', $quiz->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'message' => 'This question was already answered.',
                'already_answered' => true,
            ], 422);
        }

        $option = null;
        $isCorrect = false;

        if (! $timedOut && ! empty($data['question_option_id'])) {
            $option = WorldCupQuizOption::query()
                ->where('world_cup_quiz_id', $quiz->id)
                ->whereKey($data['question_option_id'])
                ->firstOrFail();
            $isCorrect = (bool) $option->is_correct;
        }

        return DB::transaction(function () use ($participation, $quiz, $option, $isCorrect, $timedOut, $set) {
            WorldCupQuizAnswer::create([
                'world_cup_quiz_participation_id' => $participation->id,
                'world_cup_quiz_id' => $quiz->id,
                'world_cup_quiz_option_id' => $option?->id,
                'is_correct' => $isCorrect,
                'timed_out' => $timedOut,
                'answered_at' => now(),
            ]);

            if ($isCorrect) {
                $participation->increment('score');
            }

            $participation->refresh();
            $answeredCount = $participation->answers()->count();
            $totalQuestions = $participation->total_questions;
            $completed = $answeredCount >= $totalQuestions;

            if ($completed && ! $participation->isCompleted()) {
                $participation->update(['completed_at' => now()]);
            }

            $answeredIds = $participation->answers()->pluck('world_cup_quiz_id')->all();
            $nextQuiz = $set->activeQuizzes
                ->first(fn (WorldCupQuiz $q) => ! in_array($q->id, $answeredIds, true));

            return response()->json([
                'message' => 'Answer recorded.',
                'is_correct' => $isCorrect,
                'timed_out' => $timedOut,
                'score' => $participation->score,
                'completed' => $completed,
                'answered_quiz_ids' => $answeredIds,
                'next_quiz_id' => $nextQuiz?->id,
            ], 201);
        });
    }
}
