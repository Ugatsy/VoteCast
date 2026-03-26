<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class VotingSession extends Model
{
    protected $table = 'voting_sessions';
    protected $fillable = [
        'title', 'description', 'category',
        'target_department', 'target_course',
        'status', 'start_date', 'end_date',
        'allow_vote_changes', 'requires_release_code', 'created_by',
    ];

    protected $casts = [
        'start_date'            => 'datetime',
        'end_date'              => 'datetime',
        'allow_vote_changes'    => 'boolean',
        'requires_release_code' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function positions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Position::class)->orderBy('display_order');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function releaseCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReleaseCode::class);
    }

    public function manualVoters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ManualVoter::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        $isActive = $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();

        Log::info('Session active check', [
            'session_id' => $this->id,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'current_time' => now(),
            'is_active' => $isActive
        ]);

        return $isActive;
    }

    public function isEligible(User $user): bool
    {
        // First check if session is active
        if (!$this->isActive()) {
            Log::info('Session not active - ineligible', [
                'session_id' => $this->id,
                'user_id' => $user->id
            ]);
            return false;
        }

        $eligible = false;

        switch ($this->category) {
            case 'course':
                // Check if student's department matches target course
                $eligible = $this->target_course === $user->department;
                Log::info('Course eligibility check', [
                    'session_id' => $this->id,
                    'target_course' => $this->target_course,
                    'user_department' => $user->department,
                    'eligible' => $eligible
                ]);
                break;

            case 'department':
                // Department-wide election: Check if student's department matches target_department
                if (empty($this->target_department)) {
                    $eligible = true; // All departments
                    Log::info('Department eligibility check (all departments)', [
                        'session_id' => $this->id,
                        'eligible' => true
                    ]);
                } else {
                    $eligible = $this->target_department === $user->department;
                    Log::info('Department eligibility check (specific)', [
                        'session_id' => $this->id,
                        'target_department' => $this->target_department,
                        'user_department' => $user->department,
                        'eligible' => $eligible
                    ]);
                }
                break;

            case 'manual':
                // Check if student is in the manual voters list
                $exists = $this->manualVoters()->where('user_id', $user->id)->exists();
                $eligible = $exists;
                Log::info('Manual eligibility check', [
                    'session_id' => $this->id,
                    'user_id' => $user->id,
                    'exists_in_manual_voters' => $exists,
                    'eligible' => $eligible
                ]);
                break;

            default:
                Log::warning('Unknown category', [
                    'session_id' => $this->id,
                    'category' => $this->category
                ]);
                $eligible = false;
                break;
        }

        return $eligible;
    }

    /**
     * Get total number of eligible voters
     */
    public function getTotalVotersAttribute(): int
    {
        switch ($this->category) {
            case 'course':
                return User::students()
                    ->where('department', $this->target_course)
                    ->count();
            case 'manual':
                return $this->manualVoters()->count();
            default:
                return User::students()->count();
        }
    }

    /**
     * Get total number of votes cast
     */
    public function getTotalVotesCastAttribute(): int
    {
        return $this->votes()->distinct('voter_id')->count('voter_id');
    }

    /**
     * Get voter turnout percentage
     */
    public function getTurnoutPercentageAttribute(): float
    {
        $totalVoters = $this->total_voters;
        if ($totalVoters === 0) return 0;

        return round(($this->total_votes_cast / $totalVoters) * 100, 2);
    }

    /**
     * Get vote statistics for all positions
     */
    public function getStatisticsAttribute(): array
    {
        return $this->positions->map(function ($position) {
            return $position->statistics;
        })->toArray();
    }

    /**
     * Get recent votes (last 10)
     */
    public function getRecentVotesAttribute()
    {
        return $this->votes()
            ->with(['voter', 'candidate', 'position'])
            ->latest('created_at')
            ->take(10)
            ->get();
    }
}
