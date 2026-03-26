<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class VotingBallotController extends Controller
{
    public function show(VotingSession $votingSession)
    {
        $user = auth()->user();

        // Debug logging
        Log::info('Ballot access attempt', [
            'session_id' => $votingSession->id,
            'session_title' => $votingSession->title,
            'session_status' => $votingSession->status,
            'session_start_date' => $votingSession->start_date,
            'session_end_date' => $votingSession->end_date,
            'session_category' => $votingSession->category,
            'session_target_course' => $votingSession->target_course,
            'session_target_department' => $votingSession->target_department,
            'user_id' => $user->id,
            'user_department' => $user->department,
            'user_student_id' => $user->student_id,
            'current_time' => now()
        ]);

        // Check if session is active
        if (!$votingSession->isActive()) {
            Log::warning('Session not active', [
                'session_id' => $votingSession->id,
                'status' => $votingSession->status,
                'start_date' => $votingSession->start_date,
                'end_date' => $votingSession->end_date,
                'current_time' => now()
            ]);

            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        // Check eligibility
        $isEligible = $this->checkEligibility($votingSession, $user);

        Log::info('Eligibility check result', [
            'is_eligible' => $isEligible,
            'session_id' => $votingSession->id,
            'user_id' => $user->id
        ]);

        if (!$isEligible) {
            Log::warning('User not eligible', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id,
                'user_department' => $user->department,
                'session_category' => $votingSession->category,
                'session_target_course' => $votingSession->target_course,
                'session_target_department' => $votingSession->target_department
            ]);

            abort(403, 'You are not eligible to vote in this election.');
        }

        // Check if already voted
        $alreadyVoted = Vote::where('voter_id', $user->id)
            ->where('voting_session_id', $votingSession->id)
            ->exists();

        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You have already cast your vote in this election.');
        }

        $votingSession->load('positions.candidates.student');

        return view('student.ballot', compact('votingSession', 'alreadyVoted'));
    }

    private function checkEligibility($session, $user)
    {
        // Check if session is active
        if (!$session->isActive()) {
            return false;
        }

        // Check based on category
        switch ($session->category) {
            case 'department':
                // For department-wide elections, check if target_department matches or is null
                if (empty($session->target_department)) {
                    return true; // All departments
                }
                return $session->target_department === $user->department;

            case 'course':
                // For course-specific elections
                return $session->target_course === $user->department;

            case 'manual':
                // For manual voter lists
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

        // Re-validate everything server-side
        if (!$this->checkEligibility($votingSession, $user) || !$votingSession->isActive()) {
            Log::warning('Vote submission blocked - eligibility failed', [
                'session_id' => $votingSession->id,
                'user_id' => $user->id
            ]);
            abort(403, 'Voting not permitted.');
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

        // Build validation rules — every position must be answered
        $positions = $votingSession->positions;
        $validationRules = $positions->mapWithKeys(fn($p) => [
            "votes.{$p->id}" => 'required|exists:candidates,id',
        ])->toArray();

        $request->validate($validationRules);

        $votes = $request->input('votes', []);

        // Generate a shared receipt ID for this ballot submission
        $receiptId = strtoupper(Str::random(8)) . '-' . time();

        // Remove previous votes if vote changes are allowed
        if ($votingSession->allow_vote_changes) {
            Vote::where('voter_id', $user->id)
                ->where('voting_session_id', $votingSession->id)
                ->delete();
        }

        // Persist each vote
        foreach ($positions as $position) {
            Vote::create([
                'voting_session_id' => $votingSession->id,
                'position_id' => $position->id,
                'candidate_id' => $votes[$position->id],
                'voter_id' => $user->id,
                'receipt_id' => $receiptId . '-P' . $position->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Log::info('Votes submitted successfully', [
            'session_id' => $votingSession->id,
            'user_id' => $user->id,
            'receipt_id' => $receiptId,
            'votes_count' => count($votes)
        ]);

        return redirect()->route('student.confirmation', [
            'session' => $votingSession->id,
            'receipt' => $receiptId,
        ]);
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
