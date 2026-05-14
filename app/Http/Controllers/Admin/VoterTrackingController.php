<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\VotingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VoterTrackingController extends Controller
{
    /**
     * Get voters with real-time status (optimized with caching).
     */
    public function getVoters(VotingSession $votingSession, Request $request)
    {
        $cacheKey = $this->generateCacheKey($votingSession->id, $request);

        $data = Cache::remember($cacheKey, 3, function () use ($votingSession, $request) {
            $voters = $this->getOptimizedVoters($votingSession);

            // Apply filters (status/search)
            $voters = $this->applyFilters($voters, $request);
            if ($request->filled('search')) {
                $voters = $this->applySearch($voters, (string) $request->search);
            }

            $voters = array_values($voters);

            $perPage = (int) $request->get('per_page', 50);
            $perPage = $perPage > 0 ? $perPage : 50;

            $currentPage = (int) $request->get('page', 1);
            $currentPage = $currentPage > 0 ? $currentPage : 1;

            $total = count($voters);
            $offset = ($currentPage - 1) * $perPage;

            $paginatedVoters = array_slice($voters, $offset, $perPage);

            return [
                'voters' => array_values($paginatedVoters),
                'total' => $total,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'statistics' => $this->getOptimizedStatistics($votingSession),
                'last_update' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Export voters to CSV with current filters.
     */
    public function exportVoters(VotingSession $votingSession, Request $request)
    {
        $voters = $this->getOptimizedVoters($votingSession);
        $voters = $this->applyFilters($voters, $request);

        if ($request->filled('search')) {
            $voters = $this->applySearch($voters, (string) $request->search);
        }

        $voters = array_values($voters);

        $filename = "voters-{$votingSession->id}-" . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'private',
            'Pragma' => 'private',
        ];

        $callback = function () use ($voters) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Student ID',
                'Full Name',
                'Course',
                'Year Level',
                'Section',
                'Status',
                'Voted At',
            ]);

            foreach ($voters as $voter) {
                $statusText = $this->getStatusText((string) ($voter['status'] ?? ''));

                fputcsv($file, [
                    $voter['student_id'] ?? '',
                    $voter['full_name'] ?? '',
                    $voter['department'] ?? '',
                    $voter['year_level'] ?? '',
                    $voter['section'] ?? '',
                    $statusText,
                    !empty($voter['voted_at'])
                        ? date('Y-m-d H:i:s', strtotime((string) $voter['voted_at']))
                        : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Optimized voters data using a single LEFT JOIN query.
     */
    private function getOptimizedVoters(VotingSession $session): array
    {
        // For completed/cancelled sessions, use the session's stored semester
        // For active/scheduled sessions, use current active semester
        $targetSemester = null;

        if (in_array($session->status, ['completed', 'cancelled', 'paused']) && $session->semester && $session->academic_year) {
            // Use the session's original semester for completed/past sessions
            $targetSemester = (object) [
                'name' => $session->semester,
                'academic_year' => $session->academic_year
            ];
        } else {
            $targetSemester = Semester::getCurrent();
        }

        $query = DB::table('users')
            ->select([
                'users.id',
                'users.student_id',
                'users.full_name',
                'users.department',
                'users.year_level',
                'users.section',
                'participations.has_votes',
                'participations.voted_at',
                DB::raw("CASE
                    WHEN participations.id IS NULL THEN 'not_voted'
                    WHEN participations.has_votes = true THEN 'voted'
                    ELSE 'abstained'
                END as status"),
            ])
            ->leftJoin('participations', function ($join) use ($session) {
                $join->on('users.id', '=', 'participations.user_id')
                    ->where('participations.voting_session_id', '=', $session->id);
            })
            ->where('users.role', 'student')
            ->where('users.is_active', true);

        // Use the appropriate semester filter
        if ($targetSemester) {
            $query->where('users.semester', $targetSemester->name)
                ->where('users.academic_year', $targetSemester->academic_year);
        }

        switch ($session->category) {
            case 'course':
                if ($session->target_course) {
                    $query->where('users.department', $session->target_course);
                }
                break;

            case 'section':
                if ($session->target_section) {
                    $query->where('users.section', $session->target_section);
                }
                break;

            case 'department':
                if ($session->target_department) {
                    $query->where('users.department', $session->target_department);
                }
                break;

            case 'manual':
                $manualVoterIds = $session->manualVoters()->pluck('user_id')->toArray();
                if (!empty($manualVoterIds)) {
                    $query->whereIn('users.id', $manualVoterIds);
                } else {
                    return [];
                }
                break;
        }

        return $query
            ->orderBy('users.full_name')
            ->get()
            ->map(static fn($row) => (array) $row)
            ->toArray();
    }

    /**
     * Optimized statistics for the session.
     */
    private function getOptimizedStatistics(VotingSession $session): array
    {
        // Use session's stored total_voters or calculate dynamically
        $eligibleCount = $session->total_voters;

        // If eligibleCount is 0 but session has voters, calculate properly
        if ($eligibleCount === 0 && in_array($session->status, ['completed', 'cancelled'])) {
            // Fallback: count manually using the same logic as getOptimizedVoters
            $eligibleCount = count($this->getOptimizedVoters($session));
        }

        $stats = DB::table('participations')
            ->where('voting_session_id', $session->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN has_votes = true THEN 1 ELSE 0 END) as voted,
                SUM(CASE WHEN has_votes = false THEN 1 ELSE 0 END) as abstained
            ')
            ->first();

        $votedCount = (int) ($stats->voted ?? 0);
        $abstainedCount = (int) ($stats->abstained ?? 0);
        $notVotedCount = max(0, $eligibleCount - $votedCount - $abstainedCount);

        return [
            'eligible' => $eligibleCount,
            'voted' => $votedCount,
            'abstained' => $abstainedCount,
            'not_voted' => $notVotedCount,
            'turnout_percentage' => $eligibleCount > 0
                ? round(($votedCount / $eligibleCount) * 100, 2)
                : 0,
        ];
    }

    private function applyFilters(array $voters, Request $request): array
    {
        if ($request->filled('status') && $request->status !== 'all') {
            return array_filter($voters, function ($voter) use ($request) {
                return (string) ($voter['status'] ?? '') === (string) $request->status;
            });
        }

        return $voters;
    }

    private function applySearch(array $voters, string $search): array
    {
        $search = strtolower(trim($search));
        if ($search === '') return $voters;

        return array_filter($voters, function ($voter) use ($search) {
            $fullName = strtolower((string) ($voter['full_name'] ?? ''));
            $studentId = strtolower((string) ($voter['student_id'] ?? ''));

            return strpos($fullName, $search) !== false || strpos($studentId, $search) !== false;
        });
    }

    private function getStatusText(string $status): string
    {
        return match ($status) {
            'voted' => 'Voted',
            'abstained' => 'Abstained (No Votes)',
            'not_voted' => 'Not Voted',
            default => ucfirst($status),
        };
    }

    private function generateCacheKey(int $sessionId, Request $request): string
    {
        return implode('_', [
            'voters',
            $sessionId,
            (string) $request->get('page', 1),
            (string) $request->get('per_page', 50),
            (string) $request->get('status', 'all'),
            md5((string) $request->get('search', '')),
        ]);
    }

    /**
     * Clear voter cache for a given session.
     */
    public static function clearCache(int $sessionId): void
    {
        Cache::flush(); // fallback if tags are not configured

        Cache::forget("session_total_voters_{$sessionId}");
        Cache::forget("session_stats_{$sessionId}");
    }
}
