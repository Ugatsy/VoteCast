<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Participation;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VotingBallotController extends Controller
{
    public function show(VotingSession $votingSession)
    {
        $user = auth()->user();

        Log::info('Ballot access attempt', [
            'session_id' => $votingSession->id,
            'session_title' => $votingSession->title,
            'user_id' => $user->id,
        ]);

        if (!$votingSession->isActive()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        if (!$votingSession->isEligible($user)) {
            abort(403, 'You are not eligible to vote in this election.');
        }

        $hasParticipated = Participation::where('voting_session_id', $votingSession->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasParticipated && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You have already cast your vote in this election.');
        }

        $existingVotes = Vote::where('voter_id', $user->id)
            ->where('voting_session_id', $votingSession->id)
            ->get();

        $alreadyVoted = $existingVotes->isNotEmpty();

        if ($alreadyVoted && $votingSession->allow_vote_changes) {
            DB::transaction(function() use ($user, $votingSession) {
                Vote::where('voter_id', $user->id)
                    ->where('voting_session_id', $votingSession->id)
                    ->delete();
                Participation::where('voting_session_id', $votingSession->id)
                    ->where('user_id', $user->id)
                    ->delete();
            });
            $alreadyVoted = false;
        }

        $votingSession->load('positions.candidates.student');

        return view('student.ballot', compact('votingSession', 'alreadyVoted'));
    }

    public function submit(Request $request, VotingSession $votingSession)
    {
        $user = auth()->user();

        DB::beginTransaction();

        try {
            Log::info('Starting vote submission', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'allow_vote_changes' => $votingSession->allow_vote_changes
            ]);

            if (!$votingSession->isActive()) {
                throw new \Exception('This election is no longer active.');
            }

            if (!$votingSession->isEligible($user)) {
                throw new \Exception('You are not eligible to vote in this election.');
            }

            $existingParticipation = Participation::where('voting_session_id', $votingSession->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $hasExistingParticipation = $existingParticipation !== null;

            if (!$votingSession->allow_vote_changes && $hasExistingParticipation) {
                throw new \Exception('You have already voted in this election and vote changes are not allowed.');
            }

            if ($votingSession->requires_release_code) {
                $code = $votingSession->releaseCodes()
                    ->where('code', $request->release_code)
                    ->active()
                    ->first();

                if (!$code) {
                    throw new \Exception('Invalid or expired release code.');
                }
            }

            $positions = $votingSession->positions;

            if ($positions->isEmpty()) {
                throw new \Exception('This election has no positions configured.');
            }

            $submittedVotes = $request->input('votes', []);

            foreach ($positions as $position) {
                if (isset($submittedVotes[$position->id])) {
                    $candidateIds = is_array($submittedVotes[$position->id])
                        ? $submittedVotes[$position->id]
                        : [$submittedVotes[$position->id]];

                    if (count($candidateIds) > $position->max_winners) {
                        throw new \Exception("You cannot select more than {$position->max_winners} candidates for {$position->title}");
                    }

                    foreach ($candidateIds as $candidateId) {
                        $candidate = $position->candidates()->find($candidateId);
                        if (!$candidate) {
                            throw new \Exception("Invalid candidate selected for position: {$position->title}");
                        }
                    }
                }
            }

            $receiptId = strtoupper(Str::random(8)) . '-' . time();

            if ($votingSession->allow_vote_changes && $hasExistingParticipation) {
                Vote::where('voter_id', $user->id)
                    ->where('voting_session_id', $votingSession->id)
                    ->delete();
                $existingParticipation->delete();
            }

            $votesSaved = 0;
            $hasAnyVotes = false;

            foreach ($positions as $position) {
                if (!isset($submittedVotes[$position->id])) {
                    continue;
                }

                $hasAnyVotes = true;
                $candidateIds = is_array($submittedVotes[$position->id])
                    ? $submittedVotes[$position->id]
                    : [$submittedVotes[$position->id]];

                foreach ($candidateIds as $index => $candidateId) {
                    Vote::create([
                        'voting_session_id' => $votingSession->id,
                        'position_id' => $position->id,
                        'candidate_id' => $candidateId,
                        'voter_id' => $user->id,
                        'receipt_id' => $receiptId . '-P' . $position->id . '-C' . $index,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    $votesSaved++;
                }
            }

            Participation::create([
                'voting_session_id' => $votingSession->id,
                'user_id' => $user->id,
                'receipt_id' => $receiptId,
                'has_votes' => $hasAnyVotes,
                'voted_at' => now(),
            ]);

            DB::commit();

            Log::info('Vote submission completed', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'receipt_id' => $receiptId,
                'votes_saved' => $votesSaved,
                'has_votes' => $hasAnyVotes
            ]);

            return redirect()->route('student.confirmation', [
                'session' => $votingSession->id,
                'receipt' => $receiptId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Vote submission failed', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function confirmation(Request $request)
    {
        $votingSession = VotingSession::with('positions')->findOrFail($request->session);

        $votes = Vote::where('voter_id', auth()->id())
            ->where('voting_session_id', $request->session)
            ->with(['candidate.student', 'position'])
            ->get();

        $receiptId = $request->receipt;
        $isBlank = $votes->isEmpty();

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId', 'isBlank'));
    }

    public function getReceipt($sessionId)
    {
        try {
            $user = auth()->user();

            $votes = Vote::where('voter_id', $user->id)
                ->where('voting_session_id', $sessionId)
                ->with(['candidate.student', 'position', 'votingSession'])
                ->get();

            $votingSession = VotingSession::find($sessionId);

            if (!$votingSession) {
                return response()->json(['error' => 'Election not found'], 404);
            }

            $participation = Participation::where('voting_session_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$participation && $votes->isEmpty()) {
                return response()->json([
                    'error' => 'You did not participate in this election.',
                    'has_participated' => false
                ], 404);
            }

            $receiptId = $participation ? $participation->receipt_id : ($votes->isNotEmpty() ? $votes->first()->receipt_id : null);
            $hasVotes = $participation ? $participation->has_votes : $votes->isNotEmpty();
            $votedAt = $participation ? $participation->voted_at : ($votes->isNotEmpty() ? $votes->first()->created_at : null);

            return response()->json([
                'receipt_id' => $receiptId,
                'voted_at' => $votedAt ? $votedAt->toISOString() : now()->toISOString(),
                'session_title' => $votingSession->title,
                'has_votes' => $hasVotes,
                'has_participated' => $participation !== null || $votes->isNotEmpty(),
                'votes' => $votes->map(function($vote) {
                    return [
                        'position' => $vote->position ? $vote->position->title : 'Unknown Position',
                        'candidate' => $vote->candidate && $vote->candidate->student ? $vote->candidate->student->full_name : 'Unknown Candidate',
                        'candidate_section' => ($vote->candidate && $vote->candidate->student && $vote->candidate->student->section) ? $vote->candidate->student->section : 'N/A',
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching receipt', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to load receipt: ' . $e->getMessage()], 500);
        }
    }
}
