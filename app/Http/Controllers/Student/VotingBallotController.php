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
            'session_start_date' => $votingSession->start_date,
            'session_end_date' => $votingSession->end_date,
            'session_category' => $votingSession->category,
            'user_id' => $user->id,
            'user_department' => $user->department,
            'current_time' => now()
        ]);

        if (!$votingSession->isActive()) {
            Log::warning('Session not active', [
                'session_id' => $votingSession->id,
                'status' => $votingSession->status,
                'start_date' => $votingSession->start_date,
                'end_date' => $votingSession->end_date
            ]);

            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        if (!$votingSession->isEligible($user)) {
            Log::warning('User not eligible', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'user_department' => $user->department,
                'session_category' => $votingSession->category,
                'session_target_course' => $votingSession->target_course
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

    public function submit(Request $request, VotingSession $votingSession)
    {
        $user = auth()->user();

        // Start transaction
        DB::beginTransaction();

        try {
            // Log submission attempt
            Log::info('Starting vote submission', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'allow_vote_changes' => $votingSession->allow_vote_changes,
                'session_status' => $votingSession->status
            ]);

            // Validate eligibility and active status
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

            Log::info('Existing votes check', [
                'has_existing_votes' => $hasExistingVotes,
                'existing_count' => $existingVotes->count(),
                'existing_votes' => $existingVotes->map(function($vote) {
                    return [
                        'position_id' => $vote->position_id,
                        'candidate_id' => $vote->candidate_id
                    ];
                })->toArray()
            ]);

            // Block duplicate votes if changes not allowed
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

            // Build validation rules
            $validationRules = [];
            foreach ($positions as $position) {
                if ($position->max_winners > 1) {
                    $validationRules["votes.{$position->id}"] = 'required|array|min:1|max:' . $position->max_winners;
                    $validationRules["votes.{$position->id}.*"] = 'exists:candidates,id';
                } else {
                    $validationRules["votes.{$position->id}"] = 'required|exists:candidates,id';
                }
            }

            // Validate request
            $validator = validator($request->all(), $validationRules);
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $submittedVotes = $request->input('votes', []);

            // Verify all positions have votes
            $submittedPositionIds = array_keys($submittedVotes);
            $missingPositions = $positions->pluck('id')->diff($submittedPositionIds);

            if ($missingPositions->isNotEmpty()) {
                throw new \Exception('Please vote for all positions before submitting.');
            }

            // Generate receipt ID
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

            // Save new votes
            $votesSaved = 0;
            $voteRecords = [];

            foreach ($positions as $position) {
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
                    // Verify candidate belongs to position
                    $candidate = $position->candidates()->find($candidateId);
                    if (!$candidate) {
                        throw new \Exception("Invalid candidate selected for position: {$position->title}");
                    }

                    // Create vote record
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

            if ($votesSaved === 0) {
                throw new \Exception('No votes were saved. Please try again.');
            }

            // Commit transaction
            DB::commit();

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
            // Rollback on error
            DB::rollBack();

            Log::error('Vote submission failed', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return user-friendly error message
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

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId'));
    }
}
