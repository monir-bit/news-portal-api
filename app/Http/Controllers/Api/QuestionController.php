<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuestionAnswerStoreRequest;
use App\Http\Requests\Api\QuestionParticipationRequest;
use App\Http\Resources\Api\QuestionResource;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function getQuestion(string $categorySlug)
    {
        $category = Category::query()
            ->where('slug', $categorySlug)
            ->whereHas('questions', fn ($q) => $q->activeNow())
            ->with([
                'questions' => fn ($q) => $q->activeNow()->with('options'),
            ])
            ->firstOrFail();

        $question = $category->questions->first();
        if (! $question) {
            abort(404);
        }

        return QuestionResource::make($question)->resolve();
    }

    public function participation(QuestionParticipationRequest $request, string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        $question = Question::query()
            ->where('category_id', $category->id)
            ->activeNow()
            ->where('id', $request->validated('question_id'))
            ->first();

        if (! $question) {
            return response()->json(['already_answered' => false], 404);
        }

        $participant = Participant::query()
            ->where('phone', $request->validated('phone'))
            ->first();

        if (! $participant) {
            return response()->json(['already_answered' => false]);
        }

        $answered = QuestionAnswer::query()
            ->where('question_id', $question->id)
            ->where('participant_id', $participant->id)
            ->exists();

        return response()->json(['already_answered' => $answered]);
    }

    public function submitAnswer(QuestionAnswerStoreRequest $request, string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        $question = Question::query()
            ->where('category_id', $category->id)
            ->activeNow()
            ->where('id', $request->validated('question_id'))
            ->firstOrFail();

        $option = QuestionOption::query()
            ->where('question_id', $question->id)
            ->where('id', $request->validated('question_option_id'))
            ->firstOrFail();

        $data = $request->validated();

        return DB::transaction(function () use ($data, $question, $option) {
            // Match by mobile only: new row only when this phone is unknown; never overwrite name/email here.
            $participant = Participant::firstOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => trim((string) $data['name']),
                    'email' => ! empty($data['email']) ? trim((string) $data['email']) : null,
                ]
            );

            $exists = QuestionAnswer::query()
                ->where('question_id', $question->id)
                ->where('participant_id', $participant->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Already answered.',
                    'already_answered' => true,
                ], 422);
            }

            QuestionAnswer::create([
                'question_id' => $question->id,
                'participant_id' => $participant->id,
                'question_option_id' => $option->id,
                'is_correct' => (bool) $option->is_correct,
                'answered_at' => now(),
            ]);

            return response()->json([
                'message' => 'Submitted.',
                'already_answered' => false,
            ], 201);
        });
    }
}
