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
}
