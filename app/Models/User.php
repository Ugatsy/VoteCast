<?php
namespace App\Models;

use App\Services\CloudinaryService;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'student_id', 'email', 'password', 'full_name',
        'photo', 'photo_public_id', 'department', 'year_level', 'section',
        'role', 'is_active', 'last_login_at',
        'manifesto', 'platform',
        'is_candidate', 'candidate_applied_at', 'candidate_status', 'admin_remarks'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'          => 'boolean',
        'last_login_at'      => 'datetime',
        'is_candidate'       => 'boolean',
        'candidate_applied_at' => 'datetime',
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

    public function scopeCandidates($query)
    {
        return $query->where('is_candidate', true);
    }

    public function scopeApprovedCandidates($query)
    {
        return $query->where('is_candidate', true)->where('candidate_status', 'approved');
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
     * Get user's profile photo URL.
     *
     * Builds a proper Cloudinary URL from photo_public_id when available,
     * so transforms are in the URL path (Cloudinary ignores query params).
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->photo_public_id) {
            return $this->buildCloudinaryUrl($this->photo_public_id, 200);
        }
        if ($this->photo) {
            return $this->photo;
        }
        return "https://ui-avatars.com/api/?name=" . urlencode($this->initials) . "&background=1a56db&color=fff&size=200";
    }

    /**
     * Build a Cloudinary delivery URL with face-crop transforms in the path.
     * Format: /image/upload/{transforms}/{public_id}
     *
     * Using photo_public_id directly (not the stored photo URL) avoids the
     * version segment (/vXXXXXX/) in the stored URL that causes path-injection
     * to silently fail.
     */
    public function buildCloudinaryUrl(string $publicId, int $size): string
    {
        $cloudName = config('cloudinary.cloud_name');
        $transform = "c_fill,g_face,h_{$size},q_auto,f_auto,w_{$size}";
        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$transform}/{$publicId}";
    }

    /**
     * Update user's profile photo with Cloudinary
     */
    public function updatePhoto(UploadedFile $photo)
    {
        $cloudinary = app(CloudinaryService::class);

        // Delete old photo if exists
        if ($this->photo_public_id) {
            $cloudinary->deletePhoto($this->photo_public_id);
        }

        // Upload new photo
        $result = $cloudinary->uploadPhoto($photo, 'student_photos', 'student_' . $this->id);

        if ($result['success']) {
            $this->update([
                'photo' => $result['url'],
                'photo_public_id' => $result['public_id'],
            ]);
        }

        return $result;
    }

    /**
     * Delete user's profile photo
     */
    public function deletePhoto()
    {
        if ($this->photo_public_id) {
            $cloudinary = app(CloudinaryService::class);
            $cloudinary->deletePhoto($this->photo_public_id);
        }

        $this->update([
            'photo' => null,
            'photo_public_id' => null,
        ]);
    }

    /**
     * Check if user is a candidate for a specific position
     */
    public function isCandidateFor($positionId = null)
    {
        $query = $this->candidates()->where('is_approved', true);

        if ($positionId) {
            $query->where('position_id', $positionId);
        }

        return $query->exists();
    }

    /**
     * Get candidate status badge HTML
     */
    public function getCandidateStatusBadgeAttribute(): string
    {
        return match($this->candidate_status) {
            'approved' => '<span class="badge bg-success">Approved Candidate</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            default => '<span class="badge bg-warning text-dark">Pending Approval</span>',
        };
    }

    /**
     * Get full candidate name with position if applicable
     */
    public function getCandidateDisplayNameAttribute(): string
    {
        if ($this->is_candidate && $this->candidate_status === 'approved') {
            $positions = $this->candidates()->where('is_approved', true)->with('position')->get();
            if ($positions->isNotEmpty()) {
                $titles = $positions->pluck('position.title')->implode(', ');
                return $this->full_name . ' (Candidate for ' . $titles . ')';
            }
        }
        return $this->full_name;
    }
}
