<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
            'session_status' => $votingSession->status,
            'user_id' => $user->id,
            'user_department' => $user->department,
            'current_time' => now()
        ]);

        if (!$votingSession->isActive()) {
            Log::warning('Session not active', [
                'session_id' => $votingSession->id,
                'status' => $votingSession->status
            ]);

            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        if (!$votingSession->isEligible($user)) {
            Log::warning('User not eligible', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'user_department' => $user->department,
                'session_category' => $votingSession->category
            ]);

            abort(403, 'You are not eligible to vote in this election.');
        }

        // Check for existing votes
        $existingVotes = Vote::where('voter_id', $user->id)
            ->where('voting_session_id', $votingSession->id)
            ->get();

        $alreadyVoted = $existingVotes->isNotEmpty();

        // If user has already voted and changes aren't allowed, redirect
        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            Log::info('User already voted and changes not allowed', [
                'user_id' => $user->id,
                'session_id' => $votingSession->id,
                'vote_count' => $existingVotes->count()
            ]);
            return redirect()->route('student.dashboard')
                ->with('info', 'You have already cast your vote in this election.');
        }

        // If user has partial votes and changes are allowed, clean them up
        if ($alreadyVoted && $votingSession->allow_vote_changes) {
            DB::transaction(function() use ($user, $votingSession) {
                Vote::where('voter_id', $user->id)
                    ->where('voting_session_id', $votingSession->id)
                    ->delete();
                Log::info('Cleaned up existing votes before showing ballot', [
                    'user_id' => $user->id,
                    'session_id' => $votingSession->id
                ]);
            });
            $alreadyVoted = false;
        }

        $votingSession->load('positions.candidates.student');

        return view('student.ballot', compact('votingSession', 'alreadyVoted'));
    }

    private function checkEligibility($session, $user)
    {
        if (!$session->isActive()) {
            return false;
        }

        switch ($session->category) {
            case 'department':
                if (empty($session->target_department)) {
                    return true;
                }
                return $session->target_department === $user->department;

            case 'course':
                return $session->target_course === $user->department;

            case 'manual':
                return $session->manualVoters()
                    ->where('user_id', $user->id)
                    ->exists();

            default:
                return false;
        }
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

            // Log the submitted data for debugging
            Log::info('Submitted votes data', [
                'votes' => $request->input('votes', [])
            ]);

            if (!$votingSession->isActive()) {
                throw new \Exception('This election is no longer active.');
            }

            if (!$votingSession->isEligible($user)) {
                throw new \Exception('You are not eligible to vote in this election.');
            }

            // Check for existing votes with row lock
            $existingVotes = Vote::where('voter_id', $user->id)
                ->where('voting_session_id', $votingSession->id)
                ->lockForUpdate()
                ->get();

            $hasExistingVotes = $existingVotes->isNotEmpty();

            if (!$votingSession->allow_vote_changes && $hasExistingVotes) {
                throw new \Exception('You have already voted in this election and vote changes are not allowed.');
            }

            // Validate release code if required
            if ($votingSession->requires_release_code) {
                $code = $votingSession->releaseCodes()
                    ->where('code', $request->release_code)
                    ->active()
                    ->first();

                if (!$code) {
                    throw new \Exception('Invalid or expired release code.');
                }
            }

            // Get positions
            $positions = $votingSession->positions;

            if ($positions->isEmpty()) {
                throw new \Exception('This election has no positions configured.');
            }

            // Get submitted votes
            $submittedVotes = $request->input('votes', []);

            // Validate each position's votes (if any)
            foreach ($positions as $position) {
                if (isset($submittedVotes[$position->id])) {
                    $candidateIds = is_array($submittedVotes[$position->id])
                        ? $submittedVotes[$position->id]
                        : [$submittedVotes[$position->id]];

                    // Check max winners constraint
                    if (count($candidateIds) > $position->max_winners) {
                        throw new \Exception("You cannot select more than {$position->max_winners} candidates for {$position->title}");
                    }

                    // Validate each candidate exists and belongs to this position
                    foreach ($candidateIds as $candidateId) {
                        $candidate = $position->candidates()->find($candidateId);
                        if (!$candidate) {
                            throw new \Exception("Invalid candidate selected for position: {$position->title}");
                        }
                    }
                }
            }

            $receiptId = strtoupper(Str::random(8)) . '-' . time();

            // Remove existing votes if changes allowed
            if ($votingSession->allow_vote_changes && $hasExistingVotes) {
                $deletedCount = Vote::where('voter_id', $user->id)
                    ->where('voting_session_id', $votingSession->id)
                    ->delete();

                Log::info('Removed existing votes', [
                    'user_id' => $user->id,
                    'session_id' => $votingSession->id,
                    'deleted_count' => $deletedCount
                ]);
            }

            // Save new votes (only for positions with selections)
            $votesSaved = 0;
            $voteRecords = [];

            foreach ($positions as $position) {
                // Skip if no votes for this position (abstain)
                if (!isset($submittedVotes[$position->id])) {
                    Log::info('Skipping position (abstain)', [
                        'position_id' => $position->id,
                        'position_title' => $position->title
                    ]);
                    continue;
                }

                $candidateIds = is_array($submittedVotes[$position->id])
                    ? $submittedVotes[$position->id]
                    : [$submittedVotes[$position->id]];

                Log::info('Saving votes for position', [
                    'position_id' => $position->id,
                    'position_title' => $position->title,
                    'candidate_count' => count($candidateIds),
                    'candidate_ids' => $candidateIds
                ]);

                foreach ($candidateIds as $index => $candidateId) {
                    $vote = Vote::create([
                        'voting_session_id' => $votingSession->id,
                        'position_id' => $position->id,
                        'candidate_id' => $candidateId,
                        'voter_id' => $user->id,
                        'receipt_id' => $receiptId . '-P' . $position->id . '-C' . $index,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    $voteRecords[] = $vote;
                    $votesSaved++;
                }
            }

            Log::info('Votes saved summary', [
                'total_votes_saved' => $votesSaved,
                'positions_with_votes' => count(array_filter($submittedVotes)),
                'total_positions' => $positions->count()
            ]);

            DB::commit();

            // If no votes were saved but the user intentionally skipped everything, still redirect to confirmation
            if ($votesSaved === 0) {
                Log::info('User submitted blank ballot (abstained from all positions)', [
                    'session_id' => $votingSession->id,
                    'user_id' => $user->id,
                    'receipt_id' => $receiptId
                ]);

                // Create a special receipt for blank ballots
                // You might want to store a record of the blank ballot
                return redirect()->route('student.confirmation', [
                    'session' => $votingSession->id,
                    'receipt' => $receiptId,
                    'blank' => true
                ]);
            }

            Log::info('Votes submitted successfully', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'receipt_id' => $receiptId,
                'votes_saved' => $votesSaved
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
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
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
        $isBlank = $request->has('blank') || $votes->isEmpty();

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId', 'isBlank'));
    }

    /**
     * Get receipt data for a voted session (API endpoint)
     */
    public function getReceipt($sessionId)
    {
        try {
            $user = auth()->user();

            Log::info('Receipt request', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'user_name' => $user->full_name
            ]);

            $votes = Vote::where('voter_id', $user->id)
                ->where('voting_session_id', $sessionId)
                ->with(['candidate.student', 'position', 'votingSession'])
                ->get();

            $votingSession = VotingSession::find($sessionId);

            if (!$votingSession) {
                return response()->json(['error' => 'Election not found'], 404);
            }

            $receiptId = $votes->isNotEmpty() ? $votes->first()->receipt_id : null;

            return response()->json([
                'receipt_id' => $receiptId,
                'voted_at' => $votes->isNotEmpty() && $votes->first()->created_at ? $votes->first()->created_at->toISOString() : now()->toISOString(),
                'session_title' => $votingSession->title,
                'is_blank' => $votes->isEmpty(),
                'votes' => $votes->map(function($vote) {
                    return [
                        'position' => $vote->position ? $vote->position->title : 'Unknown Position',
                        'candidate' => $vote->candidate && $vote->candidate->student ? $vote->candidate->student->full_name : 'Unknown Candidate',
                        'candidate_section' => ($vote->candidate && $vote->candidate->student && $vote->candidate->student->section) ? $vote->candidate->student->section : 'N/A',
                        'candidate_id' => $vote->candidate_id
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching receipt', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show receipt as a full page
     */
    public function showReceiptPage($sessionId)
    {
        $user = auth()->user();

        $votes = Vote::where('voter_id', $user->id)
            ->where('voting_session_id', $sessionId)
            ->with(['candidate.student', 'position', 'votingSession'])
            ->get();

        $votingSession = VotingSession::findOrFail($sessionId);
        $receiptId = $votes->isNotEmpty() ? $votes->first()->receipt_id : null;
        $isBlank = $votes->isEmpty();

        return view('student.receipt', compact('votes', 'votingSession', 'receiptId', 'isBlank'));
    }
}
