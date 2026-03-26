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

        // Debug: Log user info
        Log::info('Student dashboard accessed', [
            'user_id' => $user->id,
            'student_id' => $user->student_id,
            'department' => $user->department,
            'year_level' => $user->year_level,
            'section' => $user->section
        ]);

        // Get all active sessions (status = 'active')
        $allActiveSessions = VotingSession::where('status', 'active')->get();

        // Debug: Log active sessions found
        Log::info('Active sessions found', [
            'count' => $allActiveSessions->count(),
            'sessions' => $allActiveSessions->map(function($s) {
                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'category' => $s->category,
                    'target_course' => $s->target_course,
                    'target_department' => $s->target_department,
                    'status' => $s->status
                ];
            })
        ]);

        // Filter to only sessions this student is eligible for
        $eligibleSessions = collect();

        foreach ($allActiveSessions as $session) {
            $isEligible = $this->checkEligibility($session, $user);

            Log::info('Eligibility check', [
                'session_id' => $session->id,
                'session_title' => $session->title,
                'user_department' => $user->department,
                'user_student_id' => $user->student_id,
                'is_eligible' => $isEligible
            ]);

            if ($isEligible) {
                $eligibleSessions->push($session);
            }
        }

        // Sessions the student has already voted in
        $votedSessionIds = Vote::where('voter_id', $user->id)
            ->distinct('voting_session_id')
            ->pluck('voting_session_id')
            ->toArray();

        Log::info('Voted sessions', [
            'voted_session_ids' => $votedSessionIds
        ]);

        // Split into pending vs already voted
        $pendingSessions = $eligibleSessions->filter(function($session) use ($votedSessionIds) {
            return !in_array($session->id, $votedSessionIds);
        })->values();

        $votedSessions = VotingSession::whereIn('id', $votedSessionIds)
            ->where('status', 'active') // Only show active voted sessions
            ->latest()
            ->get();

        Log::info('Final session counts', [
            'eligible_count' => $eligibleSessions->count(),
            'pending_count' => $pendingSessions->count(),
            'voted_count' => $votedSessions->count()
        ]);

        return view('student.dashboard', compact('user', 'pendingSessions', 'votedSessions'));
    }

    /**
     * Check if a student is eligible for a voting session
     */
    private function checkEligibility($session, $user)
    {
        // Check if session is active
        if ($session->status !== 'active') {
            return false;
        }

        // Check based on category
        switch ($session->category) {
            case 'department':
                // All students are eligible for department-wide elections
                return true;

            case 'course':
                // Check if student's department matches target course
                return $session->target_course === $user->department;

            case 'manual':
                // Check if student is in the manual voters list
                return $session->manualVoters()
                    ->where('user_id', $user->id)
                    ->exists();

            default:
                return false;
        }
    }
}
