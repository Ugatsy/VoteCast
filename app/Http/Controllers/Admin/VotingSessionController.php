<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Enrollment;
use App\Models\Participation;
use App\Models\Position;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;

class VotingSessionController extends Controller
{
    public function index()
    {
        $sessions = VotingSession::with('creator', 'positions')
            ->latest()
            ->paginate(10);

        return view('admin.sessions.index', compact('sessions'));
    }

    public function create()
    {
        $courses  = Enrollment::current()->select('course')->distinct()->orderBy('course')->pluck('course');
        $sections = Enrollment::current()->select('section')->distinct()->orderBy('section')->pluck('section');
        $students = User::students()->active()->orderBy('full_name')->get();

        return view('admin.sessions.create', compact('courses', 'sections', 'students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|in:department,course,section,manual',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
        ]);

        $session = VotingSession::create([
            'title'                 => $request->title,
            'description'           => $request->description,
            'category'              => $request->category,
            'target_course'         => $request->target_course,
            'target_department'     => $request->target_department,
            'target_section'        => $request->target_section,
            'status'                => 'scheduled',
            'start_date'            => $request->start_date,
            'end_date'              => $request->end_date,
            'allow_vote_changes'    => $request->boolean('allow_vote_changes'),
            'requires_release_code' => $request->boolean('requires_release_code'),
            'created_by'            => auth()->id(),
        ]);

        return redirect()->route('admin.sessions.candidates', $session)
            ->with('success', 'Election created! Now add positions and candidates.');
    }

    public function show(VotingSession $votingSession)
    {
        $votingSession->load([
            'positions.candidates' => function($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            },
            'positions.candidates.student',
            'creator'
        ]);

        $totalVoters = $votingSession->total_voters;
        $totalVoted  = $votingSession->total_votes_cast;

        return view('admin.sessions.show', compact('votingSession', 'totalVoters', 'totalVoted'));
    }

    public function updateStatus(Request $request, VotingSession $votingSession)
    {
        $request->validate([
            'status' => 'required|in:scheduled,active,paused,completed,cancelled',
        ]);

        $votingSession->update(['status' => $request->status]);

        return back()->with('success', 'Election status updated to "' . ucfirst($request->status) . '".');
    }

    public function candidates(VotingSession $votingSession)
    {
        $votingSession->load('positions.candidates.student');

        // Filter students based on session category
        $studentsQuery = User::students()->active()->orderBy('full_name');

        if ($votingSession->category === 'course' && $votingSession->target_course) {
            $studentsQuery->where('department', $votingSession->target_course);
        } elseif ($votingSession->category === 'section' && $votingSession->target_section) {
            $studentsQuery->where('section', $votingSession->target_section);
        } elseif ($votingSession->category === 'department' && $votingSession->target_department) {
            $studentsQuery->where('department', $votingSession->target_department);
        }

        $students = $studentsQuery->get();

        return view('admin.sessions.candidates', compact('votingSession', 'students'));
    }

    public function addPosition(Request $request, VotingSession $votingSession)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_winners' => 'integer|min:1|max:10',
        ]);

        $order = $votingSession->positions()->count();

        $votingSession->positions()->create([
            'title'         => $request->title,
            'description'   => $request->description,
            'max_winners'   => $request->max_winners ?? 1,
            'display_order' => $order,
        ]);

        return back()->with('success', 'Position "' . $request->title . '" added.');
    }

    public function addCandidate(Request $request, Position $position)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'manifesto'  => 'nullable|string|max:1000',
            'photo'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($position->candidates()->where('student_id', $request->student_id)->exists()) {
            return back()->withErrors(['student_id' => 'This student is already a candidate for this position.']);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('candidates', 'public');
        }

        Candidate::create([
            'position_id' => $position->id,
            'student_id'  => $request->student_id,
            'manifesto'   => $request->manifesto,
            'photo'       => $photoPath,
            'is_approved' => true,
        ]);

        return back()->with('success', 'Candidate added successfully.');
    }

    public function removeCandidate(Candidate $candidate)
    {
        $candidate->delete();
        return back()->with('success', 'Candidate removed.');
    }

    public function deletePosition(Position $position)
    {
        $position->delete();
        return back()->with('success', 'Position deleted.');
    }

    public function results(VotingSession $votingSession)
    {
        $votingSession->load(['positions.candidates' => function($query) {
            $query->withCount('votes')->orderBy('votes_count', 'desc');
        }, 'positions.candidates.student']);

        $results = $votingSession->positions->map(function ($position) {
            $totalVotes = $position->candidates->sum('votes_count');

            $candidates = $position->candidates->map(function ($candidate) use ($totalVotes) {
                $voteCount  = $candidate->votes_count;
                $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100, 2) : 0;

                return [
                    'candidate'  => $candidate,
                    'vote_count' => $voteCount,
                    'percentage' => $percentage,
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

        return view('admin.sessions.results', compact(
            'votingSession', 'results', 'totalVoters', 'totalVoted', 'turnout'
        ));
    }

    public function getVoteStats(VotingSession $votingSession)
    {
        if (!in_array($votingSession->status, ['active', 'completed'])) {
            return response()->json(['error' => 'Session not available'], 403);
        }

        $votingSession->load([
            'positions.candidates' => function($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            }
        ]);

        $totalVoted  = $votingSession->total_votes_cast;
        $totalVoters = $votingSession->total_voters;

        $candidates     = [];
        $progressBars   = [];
        $positionTotals = [];

        foreach ($votingSession->positions as $position) {
            $positionTotalVotes            = $position->candidates->sum('votes_count');
            $positionTotals[$position->id] = $positionTotalVotes;

            foreach ($position->candidates as $candidate) {
                $candidates[$candidate->id] = $candidate->votes_count;
                $percentage = $positionTotalVotes > 0
                    ? ($candidate->votes_count / $positionTotalVotes * 100)
                    : 0;
                $progressBars[$candidate->id] = round($percentage, 1);
            }
        }

        return response()->json([
            'total_voted'     => $totalVoted,
            'total_voters'    => $totalVoters,
            'candidates'      => $candidates,
            'progress_bars'   => $progressBars,
            'position_totals' => $positionTotals,
            'last_update'     => now()->toIso8601String()
        ]);
    }
}
