<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VotingSession;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // All currently active sessions
        $allActiveSessions = VotingSession::active()->get();

        // Filter to only sessions this student is eligible for
        $eligibleSessions = $allActiveSessions->filter(fn($s) => $s->isEligible($user));

        // Sessions the student has already voted in
        $votedSessionIds = Vote::where('voter_id', $user->id)
            ->distinct('voting_session_id')
            ->pluck('voting_session_id');

        // Split into pending vs already voted
        $pendingSessions = $eligibleSessions->whereNotIn('id', $votedSessionIds->toArray())->values();
        $votedSessions   = VotingSession::whereIn('id', $votedSessionIds)->latest()->get();

        return view('student.dashboard', compact('user', 'pendingSessions', 'votedSessions'));
    }
}
