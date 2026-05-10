<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingSession;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Core stats ────────────────────────────────────────────────────────
        $stats = [
            'total_students'  => User::students()->count(),
            'total_sessions'  => VotingSession::count(),
            'active_sessions' => VotingSession::where('status', 'active')->count(),
            'total_votes'     => Vote::count(),
            'enrollments'     => Enrollment::current()->count(),
        ];

        // ── Status breakdown for donut chart ─────────────────────────────────
        $statusCounts = VotingSession::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // ── Recent sessions with pre-computed turnout ─────────────────────────
        // We load the last 5 sessions, then attach votes_cast via a single
        // aggregate query instead of N+1 computed attribute calls.
        $recentSessions = VotingSession::with('positions')
            ->latest()
            ->take(5)
            ->get();

        // Batch-fetch participation counts (total voted) per session
        $sessionIds = $recentSessions->pluck('id');

        $votesCastMap = \DB::table('participations')
            ->whereIn('voting_session_id', $sessionIds)
            ->where('has_votes', true)
            ->selectRaw('voting_session_id, count(*) as total')
            ->groupBy('voting_session_id')
            ->pluck('total', 'voting_session_id');

        // Attach as a plain property so the view can use it without triggering
        // the expensive total_voters computed attribute for every row.
        // total_voters is hard to batch (it depends on category + semester filter)
        // so we skip it on the dashboard list and show only votes_cast.
        $recentSessions->each(function ($s) use ($votesCastMap) {
            $s->dash_votes_cast = (int) $votesCastMap->get($s->id, 0);
        });

        // ── Active sessions with live turnout ─────────────────────────────────
        $activeSessions = VotingSession::where('status', 'active')
            ->with('positions')
            ->get();

        $activeIds = $activeSessions->pluck('id');

        $activeVotesCastMap = \DB::table('participations')
            ->whereIn('voting_session_id', $activeIds)
            ->where('has_votes', true)
            ->selectRaw('voting_session_id, count(*) as total')
            ->groupBy('voting_session_id')
            ->pluck('total', 'voting_session_id');

        $activeSessions->each(function ($s) use ($activeVotesCastMap) {
            $s->dash_votes_cast  = (int) $activeVotesCastMap->get($s->id, 0);
            // total_voters is still computed per-model here (only for active sessions)
            $s->dash_total_voters = $s->total_voters;
            $s->dash_turnout_pct  = $s->dash_total_voters > 0
                ? round(($s->dash_votes_cast / $s->dash_total_voters) * 100)
                : 0;
        });

        return view('admin.dashboard', compact(
            'stats',
            'statusCounts',
            'recentSessions',
            'activeSessions'
        ));
    }
}