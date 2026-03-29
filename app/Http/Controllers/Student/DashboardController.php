<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Participation;
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
        $allCompletedSessions = VotingSession::where('status', 'completed')->get();

        $eligibleSessions = collect();
        foreach ($allActiveSessions as $session) {
            if ($this->checkEligibility($session, $user)) {
                $eligibleSessions->push($session);
            }
        }

        $eligibleCompletedSessions = collect();
        foreach ($allCompletedSessions as $session) {
            if ($this->checkEligibilityForCompleted($session, $user)) {
                $eligibleCompletedSessions->push($session);
            }
        }

        $participatedSessionIds = Participation::where('user_id', $user->id)
            ->pluck('voting_session_id')
            ->toArray();

        $votedSessionIds = Vote::where('voter_id', $user->id)
            ->distinct('voting_session_id')
            ->pluck('voting_session_id')
            ->toArray();

        $allDoneIds = array_unique(array_merge($participatedSessionIds, $votedSessionIds));

        $pendingSessions = $eligibleSessions->filter(function ($session) use ($allDoneIds) {
            return !in_array($session->id, $allDoneIds);
        })->values();

        $votedActiveSessions = VotingSession::whereIn('id', $allDoneIds)
            ->where('status', 'active')
            ->with('positions')
            ->latest()
            ->get();

        $completedVotedSessions = VotingSession::whereIn('id', $allDoneIds)
            ->where('status', 'completed')
            ->with('positions')
            ->latest()
            ->get();

        $missedSessions = $eligibleCompletedSessions->filter(function ($session) use ($allDoneIds) {
            return !in_array($session->id, $allDoneIds);
        })->values();

        return view('student.dashboard', compact(
            'user',
            'pendingSessions',
            'votedActiveSessions',
            'completedVotedSessions',
            'missedSessions'
        ));
    }

    public function getLiveResults($sessionId)
    {
        try {
            $user = auth()->user();

            $session = VotingSession::with([
                'positions.candidates' => function($query) {
                    $query->withCount('votes')->orderBy('votes_count', 'desc');
                },
                'positions.candidates.student'
            ])->findOrFail($sessionId);

            $isEligible = false;

            if ($session->status === 'active') {
                $isEligible = $this->checkEligibility($session, $user);
            } elseif ($session->status === 'completed') {
                $isEligible = $this->checkEligibilityForCompleted($session, $user);
            }

            if (!$isEligible) {
                return response()->json([
                    'success' => false,
                    'error'   => 'You are not eligible to view results for this election.'
                ], 403);
            }

            if (!in_array($session->status, ['active', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Results not available yet.'
                ], 403);
            }

            $totalVoters = $session->total_voters;
            $totalVoted  = $session->total_votes_cast;
            $turnout     = $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 1) : 0;

            $results = $session->positions->map(function($position) {
                $totalVotes = $position->candidates->sum('votes_count');
                $sortedCandidates = $position->candidates->sortByDesc('votes_count')->values();
                $maxWinners = $position->max_winners;
                $winners = [];

                if ($sortedCandidates->count() > 0) {
                    $topCandidates = $sortedCandidates->take($maxWinners);
                    $winnerVoteCounts = $topCandidates->pluck('votes_count')->toArray();

                    if ($sortedCandidates->count() > $maxWinners) {
                        $lastWinnerVoteCount = $winnerVoteCounts[$maxWinners - 1] ?? 0;
                        $nextCandidateVoteCount = $sortedCandidates[$maxWinners]->votes_count ?? 0;

                        if ($lastWinnerVoteCount == $nextCandidateVoteCount) {
                            $winners = $sortedCandidates->filter(function($candidate) use ($lastWinnerVoteCount) {
                                return $candidate->votes_count == $lastWinnerVoteCount;
                            })->pluck('id')->toArray();
                        } else {
                            $winners = $topCandidates->pluck('id')->toArray();
                        }
                    } else {
                        $winners = $topCandidates->pluck('id')->toArray();
                    }
                }

                $candidates = $position->candidates->map(function($candidate) use ($totalVotes, $winners) {
                    $voteCount  = $candidate->votes_count;
                    $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100, 1) : 0;
                    $isWinner   = in_array($candidate->id, $winners);

                    return [
                        'id'         => $candidate->id,
                        'name'       => $candidate->student->full_name ?? 'Unknown',
                        'section'    => $candidate->student->section ?? 'N/A',
                        'photo'      => $candidate->photo_url,
                        'vote_count' => $voteCount,
                        'percentage' => $percentage,
                        'is_winner'  => $isWinner,
                    ];
                })->sortByDesc('vote_count')->values();

                return [
                    'id'          => $position->id,
                    'title'       => $position->title,
                    'max_winners' => $position->max_winners,
                    'total_votes' => $totalVotes,
                    'candidates'  => $candidates,
                    'winners'     => $winners,
                ];
            });

            return response()->json([
                'success'       => true,
                'session_title' => $session->title,
                'status'        => $session->status,
                'total_voters'  => $totalVoters,
                'total_voted'   => $totalVoted,
                'turnout'       => $turnout,
                'results'       => $results,
                'last_update'   => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('getLiveResults error', [
                'session_id' => $sessionId,
                'user_id'    => auth()->id(),
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load results: ' . $e->getMessage()
            ], 500);
        }
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
                return $session->manualVoters()->where('user_id', $user->id)->exists();
            default:
                return false;
        }
    }

    private function checkEligibilityForCompleted($session, $user)
    {
        switch ($session->category) {
            case 'department':
                return true;
            case 'course':
                return $session->target_course === $user->department;
            case 'section':
                return $session->target_section === $user->section;
            case 'manual':
                return $session->manualVoters()->where('user_id', $user->id)->exists();
            default:
                return false;
        }
    }
}
