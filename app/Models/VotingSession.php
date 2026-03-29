<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class VotingSession extends Model
{
    protected $table = 'voting_sessions';
    protected $fillable = [
        'title', 'description', 'category',
        'target_department', 'target_course', 'target_section',
        'status', 'start_date', 'end_date',
        'allow_vote_changes', 'requires_release_code', 'created_by',
    ];

    protected $casts = [
        'start_date'            => 'datetime',
        'end_date'              => 'datetime',
        'allow_vote_changes'    => 'boolean',
        'requires_release_code' => 'boolean',
    ];

    public function positions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Position::class)->orderBy('display_order');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function participations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Participation::class);
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    public function isEligible(User $user): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        switch ($this->category) {
            case 'course':
                return $this->target_course === $user->department;
            case 'section':
                return $this->target_section === $user->section;
            case 'department':
                if (empty($this->target_department)) {
                    return true;
                }
                return $this->target_department === $user->department;
            case 'manual':
                return $this->manualVoters()->where('user_id', $user->id)->exists();
            default:
                return false;
        }
    }

    public function hasUserVoted($userId): bool
    {
        return $this->participations()->where('user_id', $userId)->exists();
    }

    public function getTotalVotersAttribute(): int
    {
        switch ($this->category) {
            case 'course':
                return User::students()->where('department', $this->target_course)->count();
            case 'section':
                return User::students()->where('section', $this->target_section)->count();
            case 'manual':
                return $this->manualVoters()->count();
            default:
                return User::students()->count();
        }
    }

    public function getTotalVotesCastAttribute(): int
    {
        return $this->participations()->count();
    }

    public function getTurnoutPercentageAttribute(): float
    {
        $totalVoters = $this->total_voters;
        if ($totalVoters === 0) return 0;
        return round(($this->total_votes_cast / $totalVoters) * 100, 2);
    }

    public function getStatisticsAttribute(): array
    {
        return $this->positions->map(function ($position) {
            return $position->statistics;
        })->toArray();
    }

    public function getRecentVotesAttribute()
    {
        return $this->votes()
            ->with(['voter', 'candidate', 'position'])
            ->latest('created_at')
            ->take(10)
            ->get();
    }
}
