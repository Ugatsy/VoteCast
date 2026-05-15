<?php
// app/Http/Controllers/Admin/ReportsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VotingSession;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        $sessions = VotingSession::with('positions')
            ->whereIn('status', ['completed', 'active'])
            ->latest()
            ->paginate(10);

        return view('admin.reports.index', compact('sessions'));
    }

    public function show(VotingSession $votingSession)
    {
        $votingSession->load([
            'positions.candidates' => function ($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            },
            'positions.candidates.student',
        ]);

        $results = $votingSession->positions->map(function ($position) {
            $totalVotes = $position->candidates->sum('votes_count');

            $candidates = $position->candidates->map(function ($candidate) use ($totalVotes) {
                return [
                    'candidate'  => $candidate,
                    'vote_count' => $candidate->votes_count,
                    'percentage' => $totalVotes > 0 ? round(($candidate->votes_count / $totalVotes) * 100, 2) : 0,
                ];
            })->sortByDesc('vote_count')->values();

            return [
                'position'    => $position,
                'total_votes' => $totalVotes,
                'candidates'  => $candidates,
            ];
        });

        $totalVoters = $votingSession->total_voters;
        $totalVoted  = $votingSession->total_votes_cast;
        $turnout     = $votingSession->turnout_percentage;

        return view('admin.reports.show', compact(
            'votingSession', 'results', 'totalVoters', 'totalVoted', 'turnout'
        ));
    }
}
