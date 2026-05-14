<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingSession extends Model
{
    protected $table = 'voting_sessions';

    protected $fillable = [
        'title',
        'description',
        'category',
        'target_department',
        'target_course',
        'target_section',
        'status',
        'start_date',
        'end_date',
        'allow_vote_changes',
        'requires_release_code',
        'created_by',
        'semester',
        'academic_year',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'allow_vote_changes' => 'boolean',
        'requires_release_code' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }

    public function manualVoters(): HasMany
    {
        return $this->hasMany(ManualVoter::class);
    }

    public function releaseCodes(): HasMany
    {
        return $this->hasMany(ReleaseCode::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('start_date', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    public function getTotalVotersAttribute(): int
    {
        $cacheKey = "session_total_voters_{$this->id}";

        return \Cache::remember($cacheKey, 300, function () {
            if ($this->category === 'manual') {
                return $this->manualVoters()->count();
            }

            $query = User::students()->active();

            // For completed/cancelled sessions, use the stored semester
            if (in_array($this->status, ['completed', 'cancelled']) && $this->semester && $this->academic_year) {
                $query->where('semester', $this->semester)
                    ->where('academic_year', $this->academic_year);
            } else {
                $activeSemester = Semester::getCurrent();
                if ($activeSemester) {
                    $query->where('semester', $activeSemester->name)
                        ->where('academic_year', $activeSemester->academic_year);
                }
            }

            if ($this->category === 'course' && $this->target_course) {
                $query->where('department', $this->target_course);
            } elseif ($this->category === 'section' && $this->target_section) {
                $query->where('section', $this->target_section);
            } elseif ($this->category === 'department' && $this->target_department) {
                $query->where('department', $this->target_department);
            }

            return $query->count();
        });
    }

    public function getTotalVotesCastAttribute(): int
    {
        return $this->participations()->where('has_votes', true)->count();
    }

    public function getTurnoutPercentageAttribute(): float
    {
        $totalVoters = $this->total_voters;
        if ($totalVoters === 0) return 0;

        return round(($this->total_votes_cast / $totalVoters) * 100, 2);
    }

    // ─── Helper Methods ────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active'
            && now()->between($this->start_date, $this->end_date);
    }

    public function isOngoing(): bool
    {
        return $this->status === 'active' && now()->between($this->start_date, $this->end_date);
    }

    public function canVote(User $user): bool
    {
        // First check if user is in active semester
        $activeSemester = Semester::getCurrent();

        if (!$activeSemester) {
            return false;
        }

        if ($user->semester !== $activeSemester->name ||
            $user->academic_year !== $activeSemester->academic_year) {
            return false;
        }

        // Check if user is eligible based on session category
        if ($this->category === 'manual') {
            return $this->manualVoters()->where('user_id', $user->id)->exists();
        }

        if ($this->category === 'course' && $this->target_course) {
            return $user->department === $this->target_course;
        }

        if ($this->category === 'section' && $this->target_section) {
            return $user->section === $this->target_section;
        }

        if ($this->category === 'department' && $this->target_department) {
            return $user->department === $this->target_department;
        }

        return false;
    }

    public function validateReleaseCode(?string $code): bool
    {
        if (!$this->requires_release_code) {
            return true;
        }

        if (empty($code)) {
            return false;
        }

        return ReleaseCode::verifyCode($this->id, $code);
    }

    /**
     * Clear cached voter totals/stats for this session.
     */
    public function clearVoterCache(): void
    {
        \Cache::forget("session_total_voters_{$this->id}");
        \Cache::forget("session_stats_{$this->id}");

        // Clear controller cache keys as well
        \App\Http\Controllers\Admin\VoterTrackingController::clearCache((int) $this->id);
    }

    /**
     * Get eligible voters for this session with their voting status.
     */
    public function getEligibleVotersWithStatus(): array
    {
        $query = User::students()->active();

        // For completed/cancelled sessions, use the stored semester
        if (in_array($this->status, ['completed', 'cancelled']) && $this->semester && $this->academic_year) {
            $query->where('semester', $this->semester)
                ->where('academic_year', $this->academic_year);
        } else {
            $activeSemester = Semester::getCurrent();
            if ($activeSemester) {
                $query->where('semester', $activeSemester->name)
                    ->where('academic_year', $activeSemester->academic_year);
            }
        }

        // Apply category filters
        if ($this->category === 'course' && $this->target_course) {
            $query->where('department', $this->target_course);
        } elseif ($this->category === 'section' && $this->target_section) {
            $query->where('section', $this->target_section);
        } elseif ($this->category === 'department' && $this->target_department) {
            $query->where('department', $this->target_department);
        } elseif ($this->category === 'manual') {
            $manualVoterIds = $this->manualVoters()->pluck('user_id');
            $query->whereIn('id', $manualVoterIds);
        }

        $eligibleVoters = $query->get();

        // Get users who have voted (via participations)
        $votedUserIds = $this->participations()
            ->where('has_votes', true)
            ->pluck('user_id')
            ->toArray();

        // Get users who have abstained (participated but no votes)
        $abstainedUserIds = $this->participations()
            ->where('has_votes', false)
            ->pluck('user_id')
            ->toArray();

        $votersWithStatus = [];
        foreach ($eligibleVoters as $voter) {
            $status = 'not_voted';

            if (in_array($voter->id, $votedUserIds, true)) {
                $status = 'voted';
            } elseif (in_array($voter->id, $abstainedUserIds, true)) {
                $status = 'abstained';
            }

            $votedAt = $this->participations()
                ->where('user_id', $voter->id)
                ->where('has_votes', true)
                ->value('voted_at');

            $votersWithStatus[] = [
                'id' => $voter->id,
                'student_id' => $voter->student_id,
                'full_name' => $voter->full_name,
                'department' => $voter->department,
                'year_level' => $voter->year_level,
                'section' => $voter->section,
                'status' => $status,
                'voted_at' => $votedAt,
            ];
        }

        return $votersWithStatus;
    }

    /**
     * Get vote statistics summary.
     */
    public function getVoteStatisticsAttribute(): array
    {
        $eligibleCount = $this->total_voters;
        $votedCount = $this->total_votes_cast;

        $abstainedCount = $this->participations()
            ->where('has_votes', false)
            ->count();

        $notVotedCount = $eligibleCount - $votedCount - $abstainedCount;

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
}
