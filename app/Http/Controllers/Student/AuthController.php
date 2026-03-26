<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLanding()
    {
        if (auth()->check() && auth()->user()->role === 'student') {
            return redirect()->route('student.dashboard');
        }

        return view('student.landing');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string|max:50',
        ]);

        $studentId = trim($request->student_id);

        // Check if enrolled in the currently active semester
        $enrollment = Enrollment::current()
            ->where('student_code', $studentId)
            ->first();

        if (!$enrollment) {
            return back()->withErrors([
                'student_id' => 'Student ID not found in the current enrollment list. Please contact your administrator.',
            ])->withInput();
        }

        // Get or create the student's user account
        $user = User::updateOrCreate(
            ['student_id' => $studentId],
            [
                'email'      => strtolower(str_replace([' ', '/'], '_', $studentId)) . '@student.votecast.edu',
                'password'   => Hash::make($studentId),
                'full_name'  => $enrollment->full_name,
                'department' => $enrollment->course,
                'year_level' => $enrollment->year_level,
                'section'    => $enrollment->section,
                'role'       => 'student',
                'is_active'  => true,
            ]
        );

        if (!$user->is_active) {
            return back()->withErrors([
                'student_id' => 'Your account has been deactivated. Please contact your administrator.',
            ]);
        }

        Auth::login($user);
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.landing');
    }
}
