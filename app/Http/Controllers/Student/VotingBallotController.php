<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VotingBallotController extends Controller
{
    public function show(VotingSession $votingSession)
    {
        $user = auth()->user();

        // Eligibility check
        if (!$votingSession->isEligible($user)) {
            abort(403, 'You are not eligible to vote in this election.');
        }

        // Active check
        if (!$votingSession->isActive()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election is not currently active.');
        }

        // Already voted check
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

    public function submit(Request $request, VotingSession $votingSession)
    {
        $user = auth()->user();

        // Re-validate everything server-side
        if (!$votingSession->isEligible($user) || !$votingSession->isActive()) {
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
        $positions       = $votingSession->positions;
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
                'position_id'       => $position->id,
                'candidate_id'      => $votes[$position->id],
                'voter_id'          => $user->id,
                'receipt_id'        => $receiptId . '-P' . $position->id,
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
            ]);
        }

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
