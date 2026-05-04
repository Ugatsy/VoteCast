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
        if ($this->category === 'manual') {
            return $this->manualVoters()->count();
        }

        $query = User::students()->active();

        // Filter by active semester
        $activeSemester = Semester::getCurrent();
        if ($activeSemester) {
            $query->where('semester', $activeSemester->name)
                  ->where('academic_year', $activeSemester->academic_year);
        }

        if ($this->category === 'course' && $this->target_course) {
            $query->where('department', $this->target_course);
        } elseif ($this->category === 'section' && $this->target_section) {
            $query->where('section', $this->target_section);
        } elseif ($this->category === 'department' && $this->target_department) {
            $query->where('department', $this->target_department);
        }

        return $query->count();
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
}
