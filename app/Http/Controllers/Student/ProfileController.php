<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\VotingSession;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get available positions for candidacy
        $activeSessions = VotingSession::where('status', 'active')
            ->with(['positions' => function($query) {
                $query->orderBy('display_order');
            }])
            ->get();

        // Get user's existing candidacies (using candidates() method)
        $myCandidacies = $user->candidates()
            ->with(['position.votingSession'])
            ->get()
            ->keyBy('position_id');

        return view('student.profile', compact('user', 'activeSessions', 'myCandidacies'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'section' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return redirect()->route('profile.index')
            ->with('success', 'Profile updated successfully.');
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = auth()->user();
        $result = $user->updatePhoto($request->file('photo'));

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'photo_url' => $user->photo,
                'message' => 'Photo updated successfully!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload photo: ' . ($result['error'] ?? 'Unknown error')
        ], 500);
    }

    public function removePhoto(Request $request)
    {
        $user = auth()->user();
        $user->deletePhoto();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'default_avatar' => $user->profile_photo_url,
                'message' => 'Photo removed successfully.'
            ]);
        }

        return redirect()->route('profile.index')
            ->with('success', 'Photo removed successfully.');
    }

    public function updateManifesto(Request $request)
    {
        $validated = $request->validate([
            'manifesto' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:10000',
        ]);

        $user = auth()->user();
        $user->update($validated);

        return redirect()->route('profile.index')
            ->with('success', 'Your manifesto and platform have been updated.');
    }

    public function applyForCandidacy(Request $request)
    {
        $validated = $request->validate([
            'position_id' => 'required|exists:positions,id',
            'manifesto' => 'nullable|string|max:5000',
        ]);

        $user = auth()->user();
        $position = Position::with('votingSession')->findOrFail($validated['position_id']);

        // Check if election is active
        if ($position->votingSession->status !== 'active') {
            return back()->with('error', 'Cannot apply for candidacy in an inactive election.');
        }

        // Check if already applied for this position (using candidates() method)
        if ($user->candidates()->where('position_id', $position->id)->exists()) {
            return back()->with('error', 'You have already applied for this position.');
        }

        DB::beginTransaction();

        try {
            $candidate = $user->candidates()->create([
                'position_id' => $position->id,
                'manifesto' => $validated['manifesto'] ?? $user->manifesto,
                'is_approved' => false,
                'display_order' => 0,
            ]);

            // Update user's candidate status if not already
            if (!$user->is_candidate) {
                $user->update([
                    'is_candidate' => true,
                    'candidate_applied_at' => now(),
                    'candidate_status' => 'pending',
                ]);
            }

            DB::commit();

            Log::info('Student applied for candidacy', [
                'user_id' => $user->id,
                'position_id' => $position->id,
                'candidate_id' => $candidate->id,
            ]);

            return redirect()->route('profile.index')
                ->with('success', 'Your candidacy application has been submitted for review.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Candidacy application failed', [
                'user_id' => $user->id,
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    public function withdrawCandidacy($candidateId)
    {
        $user = auth()->user();
        $candidate = $user->candidates()->findOrFail($candidateId);

        $candidate->delete();

        // If user has no more pending candidacies, update status
        if ($user->candidates()->where('is_approved', false)->count() === 0) {
            $user->update([
                'is_candidate' => false,
                'candidate_status' => 'pending',
                'candidate_applied_at' => null,
            ]);
        }

        return redirect()->route('profile.index')
            ->with('success', 'Your candidacy application has been withdrawn.');
    }
}
