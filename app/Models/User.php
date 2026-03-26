<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'student_id', 'email', 'password', 'full_name',
        'photo', 'department', 'year_level', 'section',
        'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function enrollment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Enrollment::class, 'student_code', 'student_id');
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    public function candidates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Candidate::class, 'student_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['super_admin', 'election_admin', 'department_admin', 'view_only']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'election_admin', 'department_admin', 'view_only']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function getFullNameAttribute($value): string
    {
        return $value ?? '';
    }

    /**
     * Check if user has voted in a specific session
     */
    public function hasVotedInSession($sessionId): bool
    {
        return $this->votes()->where('voting_session_id', $sessionId)->exists();
    }

    /**
     * Get user's votes for a specific session
     */
    public function getVotesForSession($sessionId)
    {
        return $this->votes()
            ->where('voting_session_id', $sessionId)
            ->with(['candidate', 'position'])
            ->get();
    }

    /**
     * Get user's initials from full name
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->full_name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return $initials ?: '?';
    }

    /**
     * Get user's profile photo URL
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return "https://ui-avatars.com/api/?name=" . urlencode($this->initials) . "&background=1a56db&color=fff&size=40";
    }
}
