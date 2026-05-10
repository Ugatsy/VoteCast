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

        // ✅ NEW: Check if election has started (for scheduled sessions)
        if ($votingSession->status === 'scheduled' && now()->lt($votingSession->start_date)) {
            $waitMinutes = now()->diffInMinutes($votingSession->start_date);
            $waitHours = floor($waitMinutes / 60);
            $remainMins = $waitMinutes % 60;

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

        // ✅ NEW: Check if election has ended
        if ($votingSession->status === 'completed' || now()->gt($votingSession->end_date)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election has already ended.');
        }

        if (!$votingSession->canVote($user)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not eligible to vote in this election.');
        }

        $alreadyVoted = $user->hasVotedInSession($votingSession->id);

        if ($alreadyVoted && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You have already voted in this election and vote changes are not allowed.');
        }

        $votingSession->load('positions.candidates.student');

        $showCodeModal = $votingSession->requires_release_code && !session("release_code_validated_{$votingSession->id}");

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

        return response()->json(['success' => false, 'message' => 'Invalid or expired release code. Please check and try again.'], 422);
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
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$releaseCode) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Invalid or expired QR code.');
        }

        $votingSession = $releaseCode->votingSession;

        // ✅ NEW: Check if election has started
        if (now()->lt($votingSession->start_date)) {
            $waitMinutes = now()->diffInMinutes($votingSession->start_date);
            $waitHours = floor($waitMinutes / 60);
            $remainMins = $waitMinutes % 60;

            $timeMessage = '';
            if ($waitHours > 0) {
                $timeMessage = "{$waitHours} hour(s)";
                if ($remainMins > 0) $timeMessage .= " and {$remainMins} minute(s)";
            } else {
                $timeMessage = "{$waitMinutes} minute(s)";
            }

            return redirect()->route('student.dashboard')
                ->with('error', "This election has not started yet. Please wait {$timeMessage} until " .
                    $votingSession->start_date->format('M d, Y h:i A'));
        }

        // ✅ NEW: Check if election has ended
        if (now()->gt($votingSession->end_date)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This election has already ended. You can no longer vote.');
        }

        // Check eligibility
        if (!$votingSession->canVote(auth()->user())) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You are not eligible to vote in this election.');
        }

        // Check if already voted
        if (auth()->user()->hasVotedInSession($votingSession->id) && !$votingSession->allow_vote_changes) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You have already voted in this election.');
        }

        session(["release_code_validated_{$votingSession->id}" => true]);

        return redirect()->route('student.ballot', $votingSession);
    }

    public function submit(Request $request, VotingSession $votingSession)
    {
        if (!$votingSession->canVote(auth()->user())) {
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

        $request->validate([
            'votes' => 'nullable|array',
            'votes.*' => 'nullable|array',
            'votes.*.*' => 'exists:candidates,id',
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

            $hasVotes = false;
            $receiptId = uniqid('VOTE_', true);

            foreach ($votes as $positionId => $candidateIds) {
                if (empty($candidateIds)) continue;

                $position = $votingSession->positions()->find($positionId);
                if (!$position) continue;

                $candidateIds = array_slice($candidateIds, 0, $position->max_winners);

                foreach ($candidateIds as $candidateId) {
                    Vote::create([
                        'voting_session_id' => $votingSession->id,
                        'position_id' => $positionId,
                        'candidate_id' => $candidateId,
                        'voter_id' => auth()->id(),
                        'receipt_id' => $receiptId,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    $hasVotes = true;
                }
            }

            Participation::create([
                'voting_session_id' => $votingSession->id,
                'user_id' => auth()->id(),
                'receipt_id' => $receiptId,
                'has_votes' => $hasVotes,
                'voted_at' => now(),
            ]);

            DB::commit();

            session()->forget("release_code_validated_{$votingSession->id}");

            return redirect()->route('student.confirmation')
                ->with([
                    'receipt_id' => $receiptId,
                    'voting_session_id' => $votingSession->id,
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Vote submission failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to submit your vote. Please try again.');
        }
    }

    public function confirmation(Request $request)
    {
        $receiptId = session('receipt_id');
        $votingSessionId = session('voting_session_id');

        if (!$receiptId || !$votingSessionId) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No vote confirmation found.');
        }

        $votingSession = VotingSession::findOrFail($votingSessionId);
        $votes = Vote::where('receipt_id', $receiptId)
            ->with(['candidate.student', 'position'])
            ->get();

        return view('student.confirmation', compact('votingSession', 'votes', 'receiptId'));
    }

    public function getReceipt(Request $request, $sessionId)
    {
        $participation = Participation::where('voting_session_id', $sessionId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$participation) {
            return response()->json(['error' => 'No receipt found'], 404);
        }

        $votes = Vote::where('receipt_id', $participation->receipt_id)
            ->with(['candidate.student', 'position'])
            ->get();

        $votingSession = VotingSession::find($sessionId);

        return response()->json([
            'success' => true,
            'receipt_id' => $participation->receipt_id,
            'voted_at' => $participation->voted_at,
            'has_votes' => $participation->has_votes,
            'session_title' => $votingSession ? $votingSession->title : 'Election',
            'votes' => $votes->map(function ($vote) {
                return [
                    'position' => $vote->position->title,
                    'candidate' => $vote->candidate->student->full_name,
                    'candidate_section' => $vote->candidate->student->section ?? 'N/A',
                ];
            }),
        ]);
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

        $votes = Vote::where('receipt_id', $participation->receipt_id)
            ->with(['candidate.student', 'position'])
            ->get();

        $votingSession = VotingSession::findOrFail($sessionId);
        $receiptId = $participation->receipt_id;

        return view('student.receipt', compact('votingSession', 'votes', 'receiptId'));
    }
}
