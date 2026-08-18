<?php

namespace App\Http\Controllers\Api;

use App\Enums\PollPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PollVoteStoreRequest;
use App\Http\Resources\Api\PollResource;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * Active polls (for listing).
     */
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 30), 1), 100);

        $polls = Poll::query()
            ->with('options')
            ->withSum('options', 'votes_count')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return PollResource::collection($polls);
    }

    /**
     * Single poll with options (must be active for public API).
     */
    public function show(Request $request, int $id)
    {
        $poll = Poll::query()
            ->activeNow()
            ->with('options')
            ->withSum('options', 'votes_count')
            ->whereKey($id)
            ->firstOrFail();

        return PollResource::make($poll)->resolve();
    }

    /**
     * First active poll for a site page (PollPage enum value: home, sports, …).
     */
    public function firstByPage(Request $request, string $page)
    {
        $pageEnum = PollPage::tryFrom($page);
        if (! $pageEnum) {
            abort(404);
        }

        $poll = Poll::query()
            ->activeNow()
            ->where('page', $pageEnum)
            ->with('options')
            ->withSum('options', 'votes_count')
            ->orderBy('id')
            ->first();

        if (! $poll) {
            abort(404);
        }

        return PollResource::make($poll)->resolve();
    }

    /**
     * Cast a vote (one ballot per poll per IP).
     */
    public function vote(PollVoteStoreRequest $request, int $id)
    {
        $poll = Poll::query()
            ->activeNow()
            ->whereKey($id)
            ->firstOrFail();

        $optionId = (int) $request->validated('poll_option_id');

        $option = PollOption::query()
            ->where('poll_id', $poll->id)
            ->whereKey($optionId)
            ->firstOrFail();

        $ip = $request->ip();
        if ($ip === null) {
            return response()->json(['message' => 'Unable to determine client address.'], 422);
        }

        $already = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('ip_address', $ip)
            ->exists();

        if ($already) {
            return response()->json([
                'message' => 'You have already voted in this poll.',
                'already_voted' => true,
            ], 422);
        }

        DB::transaction(function () use ($poll, $option, $ip, $request): void {
            PollVote::query()->create([
                'poll_id' => $poll->id,
                'poll_option_id' => $option->id,
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
            ]);
            $option->increment('votes_count');
        });

        $poll->load(['options']);
        $poll->loadSum('options', 'votes_count');

        return response()->json([
            'message' => 'Vote recorded.',
            'poll' => (new PollResource($poll))->toArray($request),
        ], 201);
    }
}
