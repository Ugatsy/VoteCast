<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Enrollment;
use App\Models\Participation;
use App\Models\Position;
use App\Models\ReleaseCode;
use App\Models\Semester;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VotingSessionController extends Controller
{
    public function index(Request $request)
    {
        // ── Real counts from DB (not from the current page) ──────────────────
        $statusCounts = VotingSession::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalCount     = VotingSession::count();
        $activeCount    = $statusCounts->get('active', 0);
        $scheduledCount = $statusCounts->get('scheduled', 0);
        $completedCount = $statusCounts->get('completed', 0);

        // ── Build query with optional search + status filter ─────────────────
        $query = VotingSession::with(['creator', 'positions'])
            ->latest();

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search filter — matches title or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%")
                  ->orWhere('target_course', 'ilike', "%{$search}%")
                  ->orWhere('target_section', 'ilike', "%{$search}%");
            });
        }

        $sessions = $query->paginate(10)->withQueryString();

        return view('admin.sessions.index', compact(
            'sessions',
            'totalCount',
            'activeCount',
            'scheduledCount',
            'completedCount'
        ));
    }

    public function create()
    {
        $activeSemester = Semester::getCurrent();

        if (!$activeSemester) {
            return redirect()->route('admin.enrollment.index')
                ->with('error', 'Please set an active semester before creating an election.');
        }

        $courses = Enrollment::where('semester', $activeSemester->name)
            ->where('academic_year', $activeSemester->academic_year)
            ->where('is_active', true)
            ->select('course')
            ->distinct()
            ->orderBy('course')
            ->pluck('course');

        $sections = Enrollment::where('semester', $activeSemester->name)
            ->where('academic_year', $activeSemester->academic_year)
            ->where('is_active', true)
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section');

        $students = User::students()
            ->active()
            ->where('semester', $activeSemester->name)
            ->where('academic_year', $activeSemester->academic_year)
            ->orderBy('full_name')
            ->get();

        return view('admin.sessions.create', compact('courses', 'sections', 'students', 'activeSemester'));
    }

    public function store(Request $request)
    {
        $requiresCode = $request->boolean('requires_release_code');

        $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'category'               => 'required|in:department,course,section,manual',
            'start_date'             => 'required|date',
            'end_date'               => 'required|date|after:start_date',
            'generated_release_code' => $requiresCode ? 'required|string|min:6|max:50' : 'nullable|string',
        ]);

        $activeSemester = Semester::getCurrent();

        if (!$activeSemester) {
            return back()->withErrors(['error' => 'No active semester set. Please set an active semester first.']);
        }

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
            'requires_release_code' => $requiresCode,
            'created_by'            => auth()->id(),
        ]);

        if ($requiresCode && $request->filled('generated_release_code')) {
            $releaseCode = ReleaseCode::create([
                'voting_session_id' => $session->id,
                'code'              => strtoupper(trim($request->generated_release_code)),
                'description'       => 'Auto-generated during election setup',
                'expires_at'        => $request->end_date,
                'is_active'         => true,
            ]);

            session()->flash('generated_code_ids', [$releaseCode->id]);
            session()->flash('generated_codes_session_id', $session->id);
            session()->flash('show_codes_modal', true);
        }

        return redirect()->route('admin.sessions.candidates', $session)
            ->with('success', 'Election created! Now add positions and candidates.');
    }

    public function show(VotingSession $votingSession)
    {
        $votingSession->load([
            'positions.candidates' => function ($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            },
            'positions.candidates.student',
            'creator',
            'releaseCodes',
        ]);

        $totalVoters = $votingSession->total_voters;
        $totalVoted  = $votingSession->total_votes_cast;

        return view('admin.sessions.show', compact('votingSession', 'totalVoters', 'totalVoted'));
    }

    /**
     * Manual status override — only for paused / cancelled / reinstating.
     * Scheduled → Active → Completed transitions are handled by reschedule()
     * and the Artisan cron.
     */
    public function updateStatus(Request $request, VotingSession $votingSession)
    {
        $request->validate([
            'status' => 'required|in:scheduled,active,paused,completed,cancelled',
        ]);

        $votingSession->update(['status' => $request->status]);

        return back()->with('success', 'Election status updated to "' . ucfirst($request->status) . '".');
    }

    /**
     * Update start/end dates and immediately re-derive the DB status
     * so the database stays consistent without waiting for the cron.
     */
    public function reschedule(Request $request, VotingSession $votingSession)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        // Don't allow rescheduling cancelled/completed elections
        if (in_array($votingSession->status, ['cancelled', 'completed'])) {
            return back()->withErrors([
                'start_date' => 'Cannot reschedule a ' . $votingSession->status . ' election.',
            ]);
        }

        $now   = Carbon::now();
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        // Derive the correct status from the new times.
        // Paused stays paused — admin must resume it explicitly.
        if ($votingSession->status === 'paused') {
            $newStatus = 'paused';
        } elseif ($now->lt($start)) {
            $newStatus = 'scheduled';
        } elseif ($now->between($start, $end)) {
            $newStatus = 'active';
        } else {
            $newStatus = 'completed';
        }

        $votingSession->update([
            'start_date' => $start,
            'end_date'   => $end,
            'status'     => $newStatus,
        ]);

        return back()->with('success', 'Schedule updated. Status automatically set to "' . ucfirst($newStatus) . '".');
    }

    public function candidates(VotingSession $votingSession)
    {
        $votingSession->load('positions.candidates.student');

        $activeSemester = Semester::getCurrent();

        if (!$activeSemester) {
            return back()->withErrors(['error' => 'No active semester set. Please set an active semester first.']);
        }

        $studentsQuery = User::students()
            ->active()
            ->where('semester', $activeSemester->name)
            ->where('academic_year', $activeSemester->academic_year)
            ->orderBy('full_name');

        if ($votingSession->category === 'course' && $votingSession->target_course) {
            $studentsQuery->where('department', $votingSession->target_course);
        } elseif ($votingSession->category === 'section' && $votingSession->target_section) {
            $studentsQuery->where('section', $votingSession->target_section);
        } elseif ($votingSession->category === 'department' && $votingSession->target_department) {
            $studentsQuery->where('department', $votingSession->target_department);
        }

        $students = $studentsQuery->get();

        return view('admin.sessions.candidates', compact('votingSession', 'students', 'activeSemester'));
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

        $activeSemester = Semester::getCurrent();
        $student        = User::find($request->student_id);

        if (!$activeSemester || !$student) {
            return back()->withErrors(['student_id' => 'Invalid student or no active semester.']);
        }

        if ($student->semester !== $activeSemester->name ||
            $student->academic_year !== $activeSemester->academic_year) {
            return back()->withErrors(['student_id' => 'This student is not enrolled in the active semester.']);
        }

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

    /**
     * Permanently delete a voting session and all its related data
     * (positions, candidates, votes, participations cascade via FK).
     * Active sessions cannot be deleted.
     */
    public function destroy(VotingSession $votingSession)
    {
        if ($votingSession->status === 'active') {
            return back()->with('error', 'An active election cannot be deleted. Pause or cancel it first.');
        }

        $title = $votingSession->title;
        $votingSession->delete();

        return redirect()
            ->route('admin.sessions.index')
            ->with('success', '"' . $title . '" has been permanently deleted.');
    }

    public function results(VotingSession $votingSession)
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
            'positions.candidates' => function ($query) {
                $query->withCount('votes')->orderBy('votes_count', 'desc');
            },
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
                $percentage                 = $positionTotalVotes > 0
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
            'last_update'     => now()->toIso8601String(),
        ]);
    }

    /**
     * Get voters list with status (AJAX endpoint)
     */
    public function getVoters(VotingSession $votingSession, Request $request)
    {
        $voters = $votingSession->getEligibleVotersWithStatus();

        // Apply filters
        if ($request->filled('status')) {
            $status = (string) $request->status;
            $voters = array_values(array_filter($voters, function ($voter) use ($status) {
                return ($voter['status'] ?? null) === $status;
            }));
        }

        if ($request->filled('search')) {
            $search = strtolower((string) $request->search);
            $voters = array_values(array_filter($voters, function ($voter) use ($search) {
                $fullName   = strtolower((string) ($voter['full_name'] ?? ''));
                $studentId  = strtolower((string) ($voter['student_id'] ?? ''));
                return str_contains($fullName, $search) || str_contains($studentId, $search);
            }));
        }

        // Paginate (manual array pagination)
        $perPage = 20;
        $currentPage = (int) $request->get('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        $total = count($voters);
        $lastPage = (int) max(1, ceil($total / $perPage));

        if ($currentPage > $lastPage) $currentPage = $lastPage;

        $offset = ($currentPage - 1) * $perPage;
        $paged = array_slice($voters, $offset, $perPage);

        return response()->json([
            'voters' => $paged,
            'total' => $total,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'statistics' => $votingSession->vote_statistics,
        ]);
    }

/**
 * Get voters who voted for a specific candidate (AJAX endpoint)
 */
public function getCandidateVoters(VotingSession $votingSession, Candidate $candidate, Request $request)
{
    try {
        // Security: Ensure candidate belongs to this session
        if ($candidate->position->voting_session_id !== $votingSession->id) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid candidate for this election session.'
            ], 403);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page
        $page = (int) $request->get('page', 1);
        $page = max($page, 1);
        $search = $request->get('search', '');

        // Build the query for votes on this candidate
        $query = Vote::where('candidate_id', $candidate->id)
            ->where('voting_session_id', $votingSession->id)
            ->with(['voter']);

        // Apply search filter if provided
        if (!empty($search)) {
            $searchTerm = '%' . addcslashes($search, '%_') . '%';
            $query->whereHas('voter', function ($q) use ($searchTerm) {
                $q->where('full_name', 'ilike', $searchTerm)
                  ->orWhere('student_id', 'ilike', $searchTerm)
                  ->orWhere('section', 'ilike', $searchTerm);
            });
        }

        $total = $query->count();
        $offset = ($page - 1) * $perPage;

        $votes = $query->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $voters = $votes->map(function ($vote) {
            $voter = $vote->voter;
            return [
                'student_id' => $voter->student_id ?? 'N/A',
                'full_name' => $voter->full_name ?? 'Unknown',
                'section' => $voter->section ?? 'N/A',
                'year_level' => $voter->year_level ?? 'N/A',
                'voted_at' => $vote->created_at ? $vote->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'candidate_name' => $candidate->student->full_name ?? $candidate->full_name,
            'candidate_section' => $candidate->student->section ?? 'N/A',
            'vote_count' => $total,
            'voters' => $voters,
            'total' => $total,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getCandidateVoters: ' . $e->getMessage(), [
            'session_id' => $votingSession->id,
            'candidate_id' => $candidate->id,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Failed to load voters: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get abstained voters for a specific position (AJAX endpoint)
 */
public function getAbstainedVoters(VotingSession $votingSession, Position $position, Request $request)
{
    try {
        // Security: Ensure position belongs to this session
        if ($position->voting_session_id !== $votingSession->id) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid position for this election session.'
            ], 403);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = min($perPage, 100);
        $page = (int) $request->get('page', 1);
        $page = max($page, 1);
        $search = $request->get('search', '');

        // Get abstained users for this position
        $abstainedUsers = $votingSession->getPositionAbstainedVoters($position, $search);

        $total = $abstainedUsers->count();
        $offset = ($page - 1) * $perPage;
        $paginatedUsers = $abstainedUsers->slice($offset, $perPage);

        $voters = $paginatedUsers->map(function ($user) use ($votingSession) {
            // Get the participation record to find when they voted
            $participation = $votingSession->participations()
                ->where('user_id', $user->id)
                ->first();

            return [
                'student_id' => $user->student_id ?? 'N/A',
                'full_name' => $user->full_name ?? 'Unknown',
                'section' => $user->section ?? 'N/A',
                'year_level' => $user->year_level ?? 'N/A',
                'voted_at' => $participation && $participation->voted_at
                    ? $participation->voted_at->format('Y-m-d H:i:s')
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'position_title' => $position->title,
            'abstain_count' => $total,
            'voters' => $voters,
            'total' => $total,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getAbstainedVoters: ' . $e->getMessage(), [
            'session_id' => $votingSession->id,
            'position_id' => $position->id,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Failed to load abstained voters: ' . $e->getMessage()
        ], 500);
    }
}
}
