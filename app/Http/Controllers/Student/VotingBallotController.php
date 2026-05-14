<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Participation;
use App\Models\ReleaseCode;
use App\Models\Vote;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VotingBallotController extends Controller
{
    public function show(VotingSession $votingSession)
    {
        $user = auth()->user();

        // Block access for cancelled sessions
        if ($votingSession->status === 'cancelled') {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election has been cancelled and is no longer available.');
        }

        // Block access for paused sessions
        if ($votingSession->status === 'paused') {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election is currently paused. Please check back later.');
        }

        // Check if election has started (for scheduled sessions)
        if ($votingSession->status === 'scheduled' && now()->lt($votingSession->start_date)) {
            $waitMinutes = now()->diffInMinutes($votingSession->start_date);
            $waitHours   = floor($waitMinutes / 60);
            $remainMins  = $waitMinutes % 60;

            $timeMessage = '';
            if ($waitHours > 0) {
                $timeMessage = "{$waitHours} hour(s)";
                if ($remainMins > 0) $timeMessage .= " and {$remainMins} minute(s)";
            } else {
                $timeMessage = "{$waitMinutes} minute(s)";
            }

            return redirect()->route('student.dashboard')
                ->with('error', "This election starts in {$timeMessage} on " .
                    $votingSession->start_date->format('M d, Y \a\t h:i A'));
        }

        // Check if election has ended
        if ($votingSession->status === 'completed' || now()->gt($votingSession->end_date)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election has already ended.');
        }

        // ── FIX: Use direct eligibility check that doesn't depend on canVote()'s
        //   semester gate so the ballot page itself stays accessible even when
        //   canVote() would block (e.g. semester not yet configured).
        //   canVote() is still used for the hard eligibility guard during submit().
        if (!$this->isEligible($votingSession, $user)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not eligible to vote in this election.');
        }

        $alreadyVoted = $user->hasVotedInSession($votingSession->id);

        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You have already voted in this election and vote changes are not allowed.');
        }

        $votingSession->load('positions.candidates.student');

        $showCodeModal = $votingSession->requires_release_code
            && !session("release_code_validated_{$votingSession->id}");

        return view('student.ballot', compact('votingSession', 'alreadyVoted', 'showCodeModal'));
    }

    public function validateReleaseCode(Request $request, VotingSession $votingSession)
    {
        $request->validate([
            'release_code' => 'required|string|max:50',
        ]);

        if (!$votingSession->requires_release_code) {
            return response()->json(['success' => true, 'redirect' => route('student.ballot', $votingSession)]);
        }

        if ($votingSession->validateReleaseCode($request->release_code)) {
            session(["release_code_validated_{$votingSession->id}" => true]);
            return response()->json(['success' => true, 'redirect' => route('student.ballot', $votingSession)]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired release code. Please check and try again.',
        ], 422);
    }

    public function validateQRCodeRedirect(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No QR code detected. Please try again.');
        }

        $releaseCode = ReleaseCode::where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$releaseCode) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Invalid or expired QR code.');
        }

        $votingSession = $releaseCode->votingSession;

        if ($votingSession->status === 'cancelled') {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election has been cancelled. Voting is not available.');
        }

        if ($votingSession->status === 'paused') {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election is currently paused. Please check back later.');
        }

        if (now()->lt($votingSession->start_date)) {
            $waitMinutes = now()->diffInMinutes($votingSession->start_date);
            $waitHours   = floor($waitMinutes / 60);
            $remainMins  = $waitMinutes % 60;

            $timeMessage = $waitHours > 0
                ? "{$waitHours} hour(s)" . ($remainMins > 0 ? " and {$remainMins} minute(s)" : '')
                : "{$waitMinutes} minute(s)";

            return redirect()->route('student.dashboard')
                ->with('error', "This election has not started yet. Please wait {$timeMessage} until " .
                    $votingSession->start_date->format('M d, Y h:i A'));
        }

        if (now()->gt($votingSession->end_date)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election has already ended. You can no longer vote.');
        }

        if (!$this->isEligible($votingSession, auth()->user())) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not eligible to vote in this election.');
        }

        if (auth()->user()->hasVotedInSession($votingSession->id) && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You have already voted in this election.');
        }

        session(["release_code_validated_{$votingSession->id}" => true]);

        return redirect()->route('student.ballot', $votingSession);
    }

    public function submit(Request $request, VotingSession $votingSession)
    {
        // ── FIX 1: eligibility guard that won't throw when Semester is missing ──
        if (!$this->isEligible($votingSession, auth()->user())) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not eligible to vote in this election.');
        }

        $alreadyVoted = auth()->user()->hasVotedInSession($votingSession->id);

        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You have already voted in this election and vote changes are not allowed.');
        }

        if ($votingSession->requires_release_code) {
            if (!session("release_code_validated_{$votingSession->id}")) {
                return redirect()->route('student.ballot', $votingSession)
                    ->with('error', 'Release code validation required.');
            }
        }

        // ── FIX 2: validate each candidate ID exists AND belongs to this session ──
        // The original rule 'exists:candidates,id' is correct; keep it but wrap
        // the whole thing so a bad payload doesn't swallow the real error.
        $request->validate([
            'votes'       => 'nullable|array',
            'votes.*'     => 'nullable|array',
            'votes.*.*'   => 'nullable|integer|exists:candidates,id',
        ]);

        $votes = $request->input('votes', []);

        DB::beginTransaction();

        try {
            if ($alreadyVoted && $votingSession->allow_vote_changes) {
                Vote::where('voting_session_id', $votingSession->id)
                    ->where('voter_id', auth()->id())
                    ->delete();

                Participation::where('voting_session_id', $votingSession->id)
                    ->where('user_id', auth()->id())
                    ->delete();
            }

            $hasVotes  = false;
            $receiptId = uniqid('VOTE_', true);

            foreach ($votes as $positionId => $candidateIds) {
                // Skip empty / null entries (skipped positions arrive as [] or null)
                if (empty($candidateIds)) {
                    continue;
                }

                // Confirm position belongs to this session (security check)
                $position = $votingSession->positions()->find($positionId);
                if (!$position) {
                    continue;
                }

                // Enforce max_winners cap
                $candidateIds = array_values(array_filter($candidateIds));
                $candidateIds = array_slice($candidateIds, 0, $position->max_winners);

                foreach ($candidateIds as $candidateId) {
                    // Confirm candidate belongs to this position (security check)
                    if (!$position->candidates()->where('id', $candidateId)->exists()) {
                        continue;
                    }

                    Vote::create([
                        'voting_session_id' => $votingSession->id,
                        'position_id'       => $positionId,
                        'candidate_id'      => $candidateId,
                        'voter_id'          => auth()->id(),
                        'receipt_id'        => $receiptId,
                        'ip_address'        => $request->ip(),
                        'user_agent'        => $request->userAgent(),
                    ]);
                    $hasVotes = true;
                }
            }

            Participation::create([
                'voting_session_id' => $votingSession->id,
                'user_id'           => auth()->id(),
                'receipt_id'        => $receiptId,
                'has_votes'         => $hasVotes,
                'voted_at'          => now(),
            ]);

            DB::commit();

            $votingSession->clearVoterCache();

            session()->forget("release_code_validated_{$votingSession->id}");

            return redirect()->route('student.confirmation')
                ->with([
                    'receipt_id'        => $receiptId,
                    'voting_session_id' => $votingSession->id,
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // ── FIX 3: log the real exception message so you can debug it ──
            \Log::error('Vote submission failed', [
                'session_id' => $votingSession->id,
                'voter_id'   => auth()->id(),
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to submit your vote. Please try again. (Error: ' . $e->getMessage() . ')');
        }
    }

    public function confirmation(Request $request)
    {
        $receiptId      = session('receipt_id');
        $votingSessionId = session('voting_session_id');

        if (!$receiptId || !$votingSessionId) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No vote confirmation found.');
        }

        $votingSession = VotingSession::findOrFail($votingSessionId);

        // ── FIX 4: fetch ALL votes for this receipt, eager-load relations ──
        $votes = Vote::where('receipt_id', $receiptId)
            ->where('voting_session_id', $votingSessionId)   // scoped to session for safety
            ->with(['candidate.student', 'position'])
            ->get();

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId'));
    }

    public function getReceipt(Request $request, $sessionId)
    {
        try {
            $user    = auth()->user();
            $session = VotingSession::findOrFail($sessionId);

            if (!$this->isEligible($session, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this receipt.',
                ], 403);
            }

            $participation = Participation::where('voting_session_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No receipt found for this election.',
                ], 404);
            }

            // ── FIX 5: fetch by voter_id (consistent with showReceiptPage) ──
            $votes = Vote::where('voting_session_id', $sessionId)
                ->where('voter_id', $user->id)
                ->with(['candidate.student', 'position'])
                ->get();

            return response()->json([
                'success'       => true,
                'receipt_id'    => $participation->receipt_id,
                'voted_at'      => $participation->voted_at
                    ? $participation->voted_at->format('F d, Y \a\t H:i')
                    : null,
                'has_votes'     => $participation->has_votes,
                'session_title' => $session->title,
                'votes'         => $votes->map(function ($vote) {
                    return [
                        'position_title'   => $vote->position->title ?? 'Unknown',
                        'candidate_name'   => $vote->candidate->student->full_name ?? 'Unknown',
                        'candidate_section' => $vote->candidate->student->section ?? 'N/A',
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            \Log::error('getReceipt error', [
                'session_id' => $sessionId,
                'user_id'    => auth()->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showReceiptPage($sessionId)
    {
        $participation = Participation::where('voting_session_id', $sessionId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$participation) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No receipt found for this election.');
        }

        // Fetch all votes for this voter in this session (not just by receipt_id)
        $votes = Vote::where('voting_session_id', $sessionId)
            ->where('voter_id', auth()->id())
            ->with(['candidate.student', 'position'])
            ->orderBy('position_id')          // consistent display order
            ->get();

        $votingSession = VotingSession::findOrFail($sessionId);
        $receiptId     = $participation->receipt_id;

        return view('student.receipt', compact('votingSession', 'votes', 'receiptId'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FIX: Extracted eligibility helper that does NOT throw when the Semester
    //      model/table is missing or getCurrent() returns null.
    //      Uses the same logic as VotingSession::canVote() but wraps it safely.
    // ──────────────────────────────────────────────────────────────────────────
    private function isEligible(VotingSession $session, $user): bool
    {
        if (!$user) {
            return false;
        }

        try {
            return $session->canVote($user);
        } catch (\Exception $e) {
            // canVote() threw (e.g. Semester table missing / getCurrent() error).
            // Fall back to a basic category-only check so the ballot still works.
            \Log::warning('canVote() threw — falling back to category-only check', [
                'session_id' => $session->id,
                'user_id'    => $user->id,
                'error'      => $e->getMessage(),
            ]);

            if ($session->category === 'manual') {
                return $session->manualVoters()->where('user_id', $user->id)->exists();
            }

            if ($session->category === 'course' && $session->target_course) {
                return $user->department === $session->target_course;
            }

            if ($session->category === 'section' && $session->target_section) {
                return $user->section === $session->target_section;
            }

            if ($session->category === 'department' && $session->target_department) {
                return $user->department === $session->target_department;
            }

            return false;
        }
    }
}
