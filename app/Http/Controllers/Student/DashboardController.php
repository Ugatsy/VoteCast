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
            'user_id' => $user->id,
            'student_id' => $user->student_id,
            'department' => $user->department,
            'year_level' => $user->year_level,
            'section' => $user->section
        ]);

        // Get all active sessions (status = 'active')
        $allActiveSessions = VotingSession::where('status', 'active')->get();

        // Filter to only sessions this student is eligible for
        $eligibleSessions = collect();

        foreach ($allActiveSessions as $session) {
            $isEligible = $this->checkEligibility($session, $user);

            if ($isEligible) {
                $eligibleSessions->push($session);
            }
        }

        // Sessions the student has already voted in
        $votedSessionIds = Vote::where('voter_id', $user->id)
            ->distinct('voting_session_id')
            ->pluck('voting_session_id')
            ->toArray();

        // Split into pending vs already voted
        $pendingSessions = $eligibleSessions->filter(function($session) use ($votedSessionIds) {
            return !in_array($session->id, $votedSessionIds);
        })->values();

        $votedSessions = VotingSession::whereIn('id', $votedSessionIds)
            ->where('status', 'active')
            ->with(['positions.candidates' => function($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            }, 'positions.candidates.student'])
            ->latest()
            ->get();

        // Get completed sessions for results
        $completedSessions = VotingSession::where('status', 'completed')
            ->whereIn('id', $votedSessionIds)
            ->with(['positions.candidates' => function($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            }, 'positions.candidates.student'])
            ->latest()
            ->take(3)
            ->get();

        return view('student.dashboard', compact('user', 'pendingSessions', 'votedSessions', 'completedSessions'));
    }

    /**
     * Get live results for a session (API endpoint)
     */
    public function getLiveResults($sessionId)
    {
        try {
            $session = VotingSession::with([
                'positions.candidates' => function($query) {
                    $query->withCount('votes')->orderBy('votes_count', 'desc');
                },
                'positions.candidates.student'
            ])->findOrFail($sessionId);

            // Calculate total voters
            $totalVoters = 0;
            if ($session->category === 'course') {
                $totalVoters = \App\Models\User::students()
                    ->where('department', $session->target_course)
                    ->count();
            } elseif ($session->category === 'manual') {
                $totalVoters = $session->manualVoters()->count();
            } else {
                $totalVoters = \App\Models\User::students()->count();
            }

            $totalVoted = $session->votes()->distinct('voter_id')->count('voter_id');
            $turnout = $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 1) : 0;

            $results = [];
            foreach ($session->positions as $position) {
                $totalVotes = $position->candidates->sum('votes_count');
                $candidates = [];

                foreach ($position->candidates as $candidate) {
                    $percentage = $totalVotes > 0 ? round(($candidate->votes_count / $totalVotes) * 100, 1) : 0;
                    $candidates[] = [
                        'id' => $candidate->id,
                        'name' => $candidate->student->full_name,
                        'section' => $candidate->student->section,
                        'photo' => $candidate->photo_url,
                        'vote_count' => $candidate->votes_count,
                        'percentage' => $percentage,
                        'is_winner' => $candidate->votes_count > 0 &&
                            $candidate->votes_count == $position->candidates->max('votes_count')
                    ];
                }

                $results[] = [
                    'id' => $position->id,
                    'title' => $position->title,
                    'max_winners' => $position->max_winners,
                    'total_votes' => $totalVotes,
                    'candidates' => $candidates
                ];
            }

            return response()->json([
                'success' => true,
                'session_title' => $session->title,
                'status' => $session->status,
                'total_voters' => $totalVoters,
                'total_voted' => $totalVoted,
                'turnout' => $turnout,
                'results' => $results,
                'last_update' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching live results', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load results'
            ], 500);
        }
    }

    /**
     * Check if a student is eligible for a voting session
     */
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

            case 'manual':
                return $session->manualVoters()
                    ->where('user_id', $user->id)
                    ->exists();

            default:
                return false;
        }
    }
}
