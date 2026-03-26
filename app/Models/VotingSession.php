<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        return $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    public function isEligible(User $user): bool
    {
        return match ($this->category) {
            'course'     => $this->target_course === $user->department,
            'department' => $this->target_department === $user->department,
            'manual'     => $this->manualVoters()->where('user_id', $user->id)->exists(),
            default      => false,
        };
    }
}
