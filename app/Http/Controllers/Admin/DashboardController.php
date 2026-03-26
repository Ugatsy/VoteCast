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
        $stats = [
            'total_students'  => User::students()->count(),
            'total_sessions'  => VotingSession::count(),
            'active_sessions' => VotingSession::active()->count(),
            'total_votes'     => Vote::count(),
            'enrollments'     => Enrollment::current()->count(),
        ];

        $recentSessions = VotingSession::with('creator')
            ->latest()
            ->take(5)
            ->get();

        $activeSessions = VotingSession::active()
            ->with('positions')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentSessions', 'activeSessions'));
    }
}
