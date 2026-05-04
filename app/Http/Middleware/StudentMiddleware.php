<?php
namespace App\Http\Middleware;

use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || auth()->user()->role !== 'student') {
            return redirect()->route('student.landing')
                ->with('error', 'Please enter your Student ID to continue.');
        }

        if (!auth()->user()->is_active) {
            auth()->logout();
            return redirect()->route('student.landing')
                ->with('error', 'Your account has been deactivated. Contact admin.');
        }

        // Check if student is in the active semester
        $activeSemester = Semester::getCurrent();

        if (!$activeSemester) {
            auth()->logout();
            return redirect()->route('student.landing')
                ->with('error', 'No active semester is currently set. Please contact your administrator.');
        }

        if (auth()->user()->semester !== $activeSemester->name ||
            auth()->user()->academic_year !== $activeSemester->academic_year) {
            auth()->logout();
            return redirect()->route('student.landing')
                ->with('error', 'You are not enrolled in the current active semester. Please contact your administrator.');
        }

        return $next($request);
    }
}
