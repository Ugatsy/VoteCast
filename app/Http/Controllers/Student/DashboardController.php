<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        Log::info('Student dashboard accessed', [
            'user_id'    => $user->id,
            'student_id' => $user->student_id,
            'department' => $user->department,
            'year_level' => $user->year_level,
            'section'    => $user->section
        ]);

        $allActiveSessions = VotingSession::where('status', 'active')->get();

        $eligibleSessions = collect();

        foreach ($allActiveSessions as $session) {
            if ($this->checkEligibility($session, $user)) {
                $eligibleSessions->push($session);
            }
        }

        $votedSessionIds = Vote::where('voter_id', $user->id)
            ->distinct('voting_session_id')
            ->pluck('voting_session_id')
            ->toArray();

        $pendingSessions = $eligibleSessions->filter(function($session) use ($votedSessionIds) {
            return !in_array($session->id, $votedSessionIds);
        })->values();

        $votedSessions = VotingSession::whereIn('id', $votedSessionIds)
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('student.dashboard', compact('user', 'pendingSessions', 'votedSessions'));
    }

    private function checkEligibility($session, $user)
    {
        if ($session->status !== 'active') {
            return false;
        }

        switch ($session->category) {
            case 'department':
                return true;

            case 'course':
                return $session->target_course === $user->department;

            case 'section':
                return $session->target_section === $user->section;

            case 'manual':
                return $session->manualVoters()
                    ->where('user_id', $user->id)
                    ->exists();

            default:
                return false;
        }
    }
}
