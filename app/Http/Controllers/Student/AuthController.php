<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Semester;
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

        $currentAcademicYear = date('Y') . '-' . (date('Y') + 1);

        $currentSemester = 'Current Semester';
        try {
            $semesterModel = Semester::where('is_active', true)->first();
            if ($semesterModel) {
                $currentSemester = $semesterModel->name;
                $currentAcademicYear = $semesterModel->academic_year ?? $currentAcademicYear;
            } else {
                $currentSemester = '1st Semester ' . $currentAcademicYear;
            }
        } catch (\Exception $e) {
            \Log::warning('Could not fetch active semester: ' . $e->getMessage());
        }

        return view('student.landing', compact('currentSemester', 'currentAcademicYear'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string|max:50',
        ]);

        $studentId = trim($request->student_id);
        
        // Get the active semester from the database
        $activeSemester = Semester::where('is_active', true)->first();
        
        if (!$activeSemester) {
            return back()->withErrors([
                'student_id' => 'No active semester is currently set. Please contact your administrator.',
            ])->withInput();
        }
        
        // Use the active semester from database for authorization
        $enrollment = Enrollment::where('semester', $activeSemester->name)
            ->where('academic_year', $activeSemester->academic_year)
            ->where('student_code', $studentId)
            ->first();
        
        if (!$enrollment) {
            return back()->withErrors([
                'student_id' => 'Student ID not found in the current enrollment list for ' . 
                               $activeSemester->name . ' ' . ($activeSemester->academic_year ?? 'current academic year') . 
                               '. Please contact your administrator.',
            ])->withInput();
        }
        
        // Check if enrollment is active for this semester
        if (!$enrollment->is_active) {
            return back()->withErrors([
                'student_id' => 'Your enrollment is not active for the current semester.',
            ])->withInput();
        }

        // Get or create the student's user account.
        // Always sync semester/academic_year so the user record reflects the
        // active semester they are enrolled in — this is the key gate for
        // semester-scoped voting authorization.
        $user = User::updateOrCreate(
            ['student_id' => $studentId],
            [
                'email'         => strtolower(str_replace([' ', '/'], '_', $studentId)) . '@student.votecast.edu',
                'password'      => Hash::make($studentId),
                'full_name'     => $enrollment->full_name,
                'department'    => $enrollment->course,
                'year_level'    => $enrollment->year_level,
                'section'       => $enrollment->section,
                'role'          => 'student',
                'is_active'     => true,
                'semester'      => $enrollment->semester,       // ← sync from enrollment
                'academic_year' => $enrollment->academic_year,  // ← sync from enrollment
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