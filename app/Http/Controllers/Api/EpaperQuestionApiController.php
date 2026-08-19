<?php

namespace App\Http\Controllers\Api;

use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EpaperQuizAnswerStoreRequest;
use App\Http\Requests\Api\EpaperQuizParticipationRequest;
use App\Http\Resources\Api\EpaperQuizQuestionApiResource;
use App\Models\EpaperQuestion;
use App\Models\EpaperQuestionAnswer;
use App\Models\EpaperQuestionOption;
use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class EpaperQuestionApiController extends Controller
{
    private const GRID_PAGES = 16;

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function parsePublishDay(?string $raw): Carbon
    {
        if ($raw === null || trim($raw) === '') {
            return Carbon::now(config('app.timezone'))->startOfDay();
        }

        try {
            return Carbon::parse($raw, config('app.timezone'))->startOfDay();
        } catch (Throwable) {
            abort(422, 'Invalid publish_date');
        }
    }

    /**
     * 16 slots — latest active question per page number matching publish date (all categories).
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function grid(Request $request): JsonResponse
    {
        $publishDay = $this->parsePublishDay($request->query('publish_date'));
        $dateString = $publishDay->format('Y-m-d');

        $payload = Cache::remember(
            CacheKey::epaperQuizGrid($dateString),
            now()->addMinutes(5),
            function () use ($publishDay, $dateString): array {
                $slots = [];
                for ($page = 1; $page <= self::GRID_PAGES; $page++) {
                    $pageLabel = (string) $page;
                    /** @var EpaperQuestion|null $latest */
                    $latest = EpaperQuestion::query()
                        ->where('is_active', true)
                        ->whereRaw('trim(page_number) = ?', [$pageLabel])
                        ->whereDate('publish_date', $publishDay->toDateString())
                        ->orderByDesc('id')
                        ->first();

                    $slots[] = [
                        'page_number' => $page,
                        'question' => $latest
                            ? [
                                'id' => $latest->id,
                                'title' => $latest->title,
                                'publish_date' => $latest->publish_date,
                            ]
                            : null,
                    ];
                }

                return [
                    'publish_date' => $dateString,
                    'slots' => $slots,
                ];
            }
        );

        return response()->json($payload);
    }

    /**
     * All active questions on a numbered page slot for the publish date (all categories).
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function pageQuestions(Request $request, string $page): JsonResponse
    {
        $pageNum = filter_var($page, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => self::GRID_PAGES],
        ]);
        abort_if($pageNum === false, 404);

        $publishDay = $this->parsePublishDay($request->query('publish_date'));
        $pageLabel = (string) $pageNum;
        $dateString = $publishDay->format('Y-m-d');

        $payload = Cache::remember(
            CacheKey::epaperQuizPage($pageNum, $dateString),
            now()->addMinutes(5),
            function () use ($publishDay, $pageNum, $pageLabel, $dateString): array {
                $questions = EpaperQuestion::query()
                    ->where('is_active', true)
                    ->whereRaw('trim(page_number) = ?', [$pageLabel])
                    ->whereDate('publish_date', $publishDay->toDateString())
                    ->with([
                        'options' => fn ($q) => $q->select('id', 'epaper_question_id', 'option_text')
                            ->orderBy('id'),
                    ])
                    ->orderByDesc('id')
                    ->get();

                return [
                    'publish_date' => $dateString,
                    'page_number' => $pageNum,
                    'data' => EpaperQuizQuestionApiResource::collection($questions)->resolve(),
                ];
            }
        );

        return response()->json($payload);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function show(int $question): JsonResponse
    {
        $payload = Cache::remember(
            CacheKey::epaperQuizQuestion($question),
            now()->addMinutes(5),
            function () use ($question): array {
                $questionModel = EpaperQuestion::query()
                    ->whereKey($question)
                    ->where('is_active', true)
                    ->with([
                        'options' => fn ($q) => $q->select('id', 'epaper_question_id', 'option_text')
                            ->orderBy('id'),
                    ])
                    ->firstOrFail();

                return EpaperQuizQuestionApiResource::make($questionModel)->resolve();
            }
        );

        return response()->json($payload);
    }

    /** Check whether participant has already answered the given e-paper question. */
    public function participation(EpaperQuizParticipationRequest $request): JsonResponse
    {
        $question = EpaperQuestion::query()
            ->where('is_active', true)
            ->whereKey($request->validated('question_id'))
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

        $answered = EpaperQuestionAnswer::query()
            ->where('epaper_question_id', $question->id)
            ->where('participant_id', $participant->id)
            ->exists();

        return response()->json(['already_answered' => $answered]);
    }

    /** Store one participant answer — same uniqueness rules as site quiz API. */
    public function submitAnswer(EpaperQuizAnswerStoreRequest $request): JsonResponse
    {
        $question = EpaperQuestion::query()
            ->where('is_active', true)
            ->whereKey($request->validated('question_id'))
            ->firstOrFail();

        $option = EpaperQuestionOption::query()
            ->where('epaper_question_id', $question->id)
            ->whereKey($request->validated('question_option_id'))
            ->firstOrFail();

        $payload = $request->validated();

        return DB::transaction(function () use ($payload, $question, $option) {
            $participant = Participant::firstOrCreate(
                ['phone' => $payload['phone']],
                [
                    'name' => trim((string) $payload['name']),
                    'email' => ! empty($payload['email']) ? trim((string) $payload['email']) : null,
                ]
            );

            $exists = EpaperQuestionAnswer::query()
                ->where('epaper_question_id', $question->id)
                ->where('participant_id', $participant->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Already answered.',
                    'already_answered' => true,
                ], 422);
            }

            EpaperQuestionAnswer::create([
                'epaper_question_id' => $question->id,
                'participant_id' => $participant->id,
                'epaper_question_option_id' => $option->id,
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
