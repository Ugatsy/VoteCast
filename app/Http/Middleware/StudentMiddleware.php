<?php
namespace App\Http\Middleware;

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

        return $next($request);
    }
}
