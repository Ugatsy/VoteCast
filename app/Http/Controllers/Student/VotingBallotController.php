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
            'session_id'               => $votingSession->id,
            'session_title'            => $votingSession->title,
            'session_status'           => $votingSession->status,
            'session_start_date'       => $votingSession->start_date,
            'session_end_date'         => $votingSession->end_date,
            'session_category'         => $votingSession->category,
            'session_target_course'    => $votingSession->target_course,
            'session_target_department'=> $votingSession->target_department,
            'user_id'                  => $user->id,
            'user_department'          => $user->department,
            'user_student_id'          => $user->student_id,
            'current_time'             => now()
        ]);

        if (!$votingSession->isActive()) {
            Log::warning('Session not active', [
                'session_id'   => $votingSession->id,
                'status'       => $votingSession->status,
                'start_date'   => $votingSession->start_date,
                'end_date'     => $votingSession->end_date,
                'current_time' => now()
            ]);

            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        $isEligible = $this->checkEligibility($votingSession, $user);

        Log::info('Eligibility check result', [
            'is_eligible' => $isEligible,
            'session_id'  => $votingSession->id,
            'user_id'     => $user->id
        ]);

        if (!$isEligible) {
            Log::warning('User not eligible', [
                'session_id'               => $votingSession->id,
                'user_id'                  => $user->id,
                'user_department'          => $user->department,
                'session_category'         => $votingSession->category,
                'session_target_course'    => $votingSession->target_course,
                'session_target_department'=> $votingSession->target_department
            ]);

            abort(403, 'You are not eligible to vote in this election.');
        }

        $alreadyVoted = Vote::where('voter_id', $user->id)
            ->where('voting_session_id', $votingSession->id)
            ->exists();

        // Check if user has already voted and changes aren't allowed
        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You have already cast your vote in this election.');
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

        if (!$this->checkEligibility($votingSession, $user) || !$votingSession->isActive()) {
            Log::warning('Vote submission blocked - eligibility failed', [
                'session_id' => $votingSession->id,
                'user_id'    => $user->id
            ]);
            abort(403, 'Voting not permitted.');
        }

        // Check if user has already voted and changes aren't allowed
        if (!$votingSession->allow_vote_changes) {
            $alreadyVoted = Vote::where('voter_id', $user->id)
                ->where('voting_session_id', $votingSession->id)
                ->exists();

            if ($alreadyVoted) {
                Log::warning('Duplicate vote attempt blocked', [
                    'session_id' => $votingSession->id,
                    'user_id'    => $user->id
                ]);
                return redirect()->route('student.dashboard')
                    ->with('error', 'You have already voted in this election and vote changes are not allowed.');
            }
        }

        // Validate release code if required
        if ($votingSession->requires_release_code) {
            $code = $votingSession->releaseCodes()
                ->where('code', $request->release_code)
                ->first();

            if (!$code || !$code->isValid()) {
                return back()->withErrors(['release_code' => 'Invalid or expired release code.']);
            }
        }

        // Build validation rules — handle both single (radio) and multi (checkbox) winners
        $positions = $votingSession->positions;

        $validationRules = [];
        foreach ($positions as $p) {
            if ($p->max_winners > 1) {
                $validationRules["votes.{$p->id}"]   = 'required|array|min:1|max:' . $p->max_winners;
                $validationRules["votes.{$p->id}.*"] = 'exists:candidates,id';
            } else {
                $validationRules["votes.{$p->id}"] = 'required|exists:candidates,id';
            }
        }

        $request->validate($validationRules);

        $votes = $request->input('votes', []);

        // Generate receipt ID
        $receiptId = strtoupper(Str::random(8)) . '-' . time();

        // Use database transaction to ensure all-or-nothing
        DB::beginTransaction();

        try {
            // Remove previous votes if vote changes are allowed
            if ($votingSession->allow_vote_changes) {
                Vote::where('voter_id', $user->id)
                    ->where('voting_session_id', $votingSession->id)
                    ->delete();
            }

            // Save votes — support both single and multiple candidates per position
            foreach ($positions as $position) {
                // Skip if no votes for this position
                if (!isset($votes[$position->id])) {
                    continue;
                }

                $candidateIds = is_array($votes[$position->id])
                    ? $votes[$position->id]
                    : [$votes[$position->id]];

                foreach ($candidateIds as $i => $candidateId) {
                    // Check if this voter already voted for this position
                    $existingVote = Vote::where('voter_id', $user->id)
                        ->where('position_id', $position->id)
                        ->where('voting_session_id', $votingSession->id)
                        ->exists();

                    if ($existingVote && !$votingSession->allow_vote_changes) {
                        // This shouldn't happen due to earlier check, but just in case
                        throw new \Exception('You have already voted for this position.');
                    }

                    Vote::create([
                        'voting_session_id' => $votingSession->id,
                        'position_id'       => $position->id,
                        'candidate_id'      => $candidateId,
                        'voter_id'          => $user->id,
                        'receipt_id'        => $receiptId . '-P' . $position->id . '-C' . $i,
                        'ip_address'        => $request->ip(),
                        'user_agent'        => $request->userAgent(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Votes submitted successfully', [
                'session_id'  => $votingSession->id,
                'user_id'     => $user->id,
                'receipt_id'  => $receiptId,
                'votes_count' => count($votes)
            ]);

            return redirect()->route('student.confirmation', [
                'session' => $votingSession->id,
                'receipt' => $receiptId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Vote submission failed', [
                'session_id' => $votingSession->id,
                'user_id'    => $user->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Failed to submit vote. Please try again.']);
        }
    }

    public function confirmation(Request $request)
    {
        $votingSession = VotingSession::with('positions')->findOrFail($request->session);

        $votes = Vote::where('voter_id', auth()->id())
            ->where('voting_session_id', $request->session)
            ->with('candidate.student', 'position')
            ->get();

        $receiptId = $request->receipt;

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId'));
    }
}
