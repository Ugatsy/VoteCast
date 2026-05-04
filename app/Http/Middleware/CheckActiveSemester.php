<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSemester
{
    public function handle(Request $request, Closure $next): Response
    {
        $activeSemester = Semester::getCurrent();

        if (!$activeSemester) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No active semester set'], 403);
            }

            if (auth()->check() && auth()->user()->isAdmin()) {
                return redirect()->route('admin.enrollment.index')
                    ->with('error', 'Please set an active semester before proceeding.');
            }

            abort(403, 'No active semester is currently set.');
        }

        return $next($request);
    }
}
